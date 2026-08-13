#!/usr/bin/env python3
"""
Export the running WordPress site to a static, deployable copy.

Crawls the local install, saves each page at its real path, and rewrites URLs so
the result works from any web root — including a custom domain on GitHub Pages.

This is a *browsable copy of the design*, not WordPress: there is no dashboard,
and anything that needs PHP (submitting a ballot, search, comments) is inert.
It exists so the site can be seen and shared at a real address while the actual
WordPress hosting is being set up.

Usage:  python3 dev/static-export.py [output_dir]
"""
import os
import re
import shutil
import subprocess
import sys
import urllib.error
import urllib.parse
import urllib.request

BASE = "http://localhost:8080"
WPROOT = "/tmp/wpsite"
OUT = sys.argv[1] if len(sys.argv) > 1 else "/tmp/static-site"

# Pages crawled by path. Singles are resolved from the database below.
SEEDS = [
    "/",
    "/news/",
    "/category/saline-county/",
    "/category/benton/",
    "/category/bryant/",
    "/category/business-news/",
    "/category/public-records/",
    "/category/sports/",
    "/category/dining/",
    "/category/elections/",
    "/events/",
    "/obituaries/",
    "/businesses/",
    "/things-to-do/",
    "/government/",
    "/about-mysaline/",
    "/advertise-with-us/",
    "/contact-us/",
    "/saline-county-favorites-vote/",
]

MAX_SINGLES_PER_TYPE = 12


def wp(*args):
    r = subprocess.run(
        ["php", "/tmp/wp-cli.phar", *args, "--allow-root"],
        cwd=WPROOT, capture_output=True, text=True,
    )
    return r.stdout.strip()


def fetch(path):
    url = BASE + path
    try:
        with urllib.request.urlopen(
            urllib.request.Request(url, headers={"User-Agent": "static-export"}), timeout=60
        ) as r:
            return r.read().decode("utf-8", "replace"), r.status
    except urllib.error.HTTPError as e:
        return e.read().decode("utf-8", "replace"), e.code
    except Exception as e:                                        # noqa: BLE001
        print(f"  ! {path}: {e}")
        return "", 0


def dest_for(path):
    """Map a URL path to a file on disk, always as <path>/index.html."""
    p = urllib.parse.urlparse(path).path.strip("/")
    return os.path.join(OUT, p, "index.html") if p else os.path.join(OUT, "index.html")


def rewrite(html):
    """Make every absolute local URL root-relative, and neutralise PHP-only bits."""
    for host in ("http://localhost:8080", "http://127.0.0.1:8080",
                 "https://localhost:8080", "https://127.0.0.1:8080"):
        html = html.replace(host + "/", "/")
        html = html.replace(host, "")

    # Endpoints that cannot work without PHP.
    html = re.sub(r'action="[^"]*wp-admin/admin-post\.php"', 'action="#" data-inert="1"', html)
    html = re.sub(r'action="[^"]*wp-login\.php[^"]*"', 'action="#" data-inert="1"', html)
    # WordPress emitters that point at PHP files.
    html = re.sub(r'<link[^>]+rel=[\'"](pingback|EditURI|wlwmanifest)[\'"][^>]*>', "", html)
    html = re.sub(r'<link[^>]+xmlrpc\.php[^>]*>', "", html)

    # A short banner making the nature of this copy obvious, plus form guards.
    banner = """
<style>
.stx-banner{position:sticky;top:0;z-index:9999;background:#0b2545;color:#fff;
font:600 13px/1.4 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
padding:.5rem 1rem;text-align:center}
.stx-banner b{color:#f2b705}
@media print{.stx-banner{display:none}}
</style>
<div class="stx-banner"><b>Preview build</b> — a static copy of the new MySaline design.
Dashboard, voting and search need the live WordPress install.</div>
<script>
document.addEventListener('submit',function(e){e.preventDefault();},true);
</script>
"""
    return html.replace("<body", banner + "<body", 1) if "<body" in html else banner + html


def save(path, html):
    dest = dest_for(path)
    os.makedirs(os.path.dirname(dest), exist_ok=True)
    with open(dest, "w") as f:
        f.write(rewrite(html))
    return dest


def referenced_assets():
    """Every local asset path the exported HTML actually asks for."""
    wanted = set()
    pat = re.compile(r'["\'(](/wp-(?:content|includes)/[^"\')?#]+)')
    for root, _dirs, files in os.walk(OUT):
        for fn in files:
            if not fn.endswith(".html"):
                continue
            with open(os.path.join(root, fn), encoding="utf-8", errors="replace") as f:
                wanted.update(pat.findall(f.read()))
    return wanted


def copy_assets():
    """Copy only the assets the pages reference.

    A blanket copy of wp-includes is ~43MB for the four files the theme
    actually loads, which makes the deploy needlessly slow and heavy.
    """
    wanted = referenced_assets()

    # The theme directory is small and self-referential (CSS can point at its
    # own images), so it comes across whole; everything else is by reference.
    theme_src = os.path.realpath(os.path.join(WPROOT, "wp-content/themes/mysaline"))
    theme_dst = os.path.join(OUT, "wp-content/themes/mysaline")
    if os.path.isdir(theme_src):
        shutil.copytree(theme_src, theme_dst, dirs_exist_ok=True,
                        ignore=shutil.ignore_patterns("*.map", "node_modules", "*.php"))

    copied = 0
    for rel in sorted(wanted):
        if rel.startswith("/wp-content/themes/"):
            continue                                  # already handled above
        src = os.path.join(WPROOT, rel.lstrip("/"))
        if not os.path.isfile(src):
            continue
        dst = os.path.join(OUT, rel.lstrip("/"))
        os.makedirs(os.path.dirname(dst), exist_ok=True)
        shutil.copy2(src, dst)
        copied += 1

    # PHP would never be served; drop any that slipped in.
    for root, _dirs, files in os.walk(OUT):
        for fn in files:
            if fn.endswith(".php"):
                os.remove(os.path.join(root, fn))
    print(f"  {copied} referenced assets + the theme directory")


def main():
    shutil.rmtree(OUT, ignore_errors=True)
    os.makedirs(OUT, exist_ok=True)

    paths = list(SEEDS)

    # Real permalinks for a sample of each content type.
    for pt in ("post", "ms_event", "ms_obituary", "ms_business"):
        urls = wp("post", "list", f"--post_type={pt}",
                  f"--posts_per_page={MAX_SINGLES_PER_TYPE}", "--field=url",
                  "--orderby=ID", "--order=ASC").splitlines()
        for u in urls:
            p = u.replace("http://localhost:8080", "").strip()
            if p and p not in paths:
                paths.append(p)

    print(f"exporting {len(paths)} pages → {OUT}")
    ok = 0
    for p in paths:
        html, status = fetch(p)
        if not html:
            continue
        save(p, html)
        ok += 1
        print(f"  {status}  {p}")

    # A 404 document; GitHub Pages serves /404.html automatically.
    html, _ = fetch("/this-page-does-not-exist/")
    if html:
        os.makedirs(OUT, exist_ok=True)
        with open(os.path.join(OUT, "404.html"), "w") as f:
            f.write(rewrite(html))

    print("copying assets…")
    copy_assets()

    # Pages must not run the content through Jekyll.
    open(os.path.join(OUT, ".nojekyll"), "w").close()

    total = sum(len(fs) for _r, _d, fs in os.walk(OUT))
    size = subprocess.run(["du", "-sh", OUT], capture_output=True, text=True).stdout.split()[0]
    print(f"\n  {ok} pages, {total} files, {size}")


if __name__ == "__main__":
    main()
