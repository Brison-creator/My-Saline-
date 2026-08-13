#!/usr/bin/env python3
"""
Export the running WordPress site to a static, deployable copy.

Crawls the local install from the homepage outward, following every internal
link it renders, saves each page at its real path, and rewrites URLs so the
result works from a web root — including a custom domain on GitHub Pages.

This is a *browsable copy of the design*, not WordPress: there is no dashboard,
and anything that needs PHP (submitting a ballot, search, comments) is inert.
It exists so the site can be seen and shared at a real address while the actual
WordPress hosting is being set up.

The content is demo content, so every page is emitted noindex and robots.txt
disallows everything — a staging copy full of invented obituaries and business
listings must never end up in a search index under the MySaline name.

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

# Where the export is deployed. Page links are made root-relative so the copy
# works from any web root, but canonical/og:url must stay absolute to mean
# anything, so those are rewritten to this origin.
SITE_ORIGIN = os.environ.get("MYSALINE_ORIGIN", "https://mysaline.net")

# Where the crawl starts. Everything else is discovered by following links.
ROOTS = ["/"]

# Paths that exist only to serve PHP and cannot be represented statically.
SKIP_PREFIXES = (
    "/wp-admin/", "/wp-json/", "/wp-login.php", "/xmlrpc.php",
    "/wp-content/", "/wp-includes/",
)

# Anything that is a file rather than a page gets copied as an asset, not crawled.
ASSET_SUFFIXES = (
    ".css", ".js", ".png", ".jpg", ".jpeg", ".gif", ".svg", ".ico", ".webp",
    ".woff", ".woff2", ".ttf", ".eot", ".pdf", ".zip", ".mp4", ".mp3",
)

MAX_PAGES = 4000


def wp(*args):
    r = subprocess.run(
        ["php", "/tmp/wp-cli.phar", *args, "--allow-root"],
        cwd=WPROOT, capture_output=True, text=True,
    )
    return r.stdout.strip()


def fetch(path):
    """Return (body, status, content_type) for a path on the dev site."""
    url = BASE + path
    try:
        with urllib.request.urlopen(
            urllib.request.Request(url, headers={"User-Agent": "static-export"}), timeout=60
        ) as r:
            return r.read().decode("utf-8", "replace"), r.status, r.headers.get("Content-Type", "")
    except urllib.error.HTTPError as e:
        return e.read().decode("utf-8", "replace"), e.code, e.headers.get("Content-Type", "")
    except Exception as e:                                        # noqa: BLE001
        print(f"  ! {path}: {e}")
        return "", 0, ""


def normalise(href, current="/"):
    """Resolve a link to a crawlable site path, or None if it is not one."""
    href = href.strip()
    if not href or href.startswith(("#", "mailto:", "tel:", "javascript:", "data:")):
        return None

    # Absolute URLs are in scope only if they point back at the dev site.
    if href.startswith(("http://", "https://", "//")):
        parsed = urllib.parse.urlparse(href if "//" != href[:2] else "http:" + href)
        if parsed.netloc not in ("localhost:8080", "127.0.0.1:8080"):
            return None
        href = parsed.path or "/"
    elif not href.startswith("/"):
        href = urllib.parse.urljoin(current, href)

    path = urllib.parse.urlparse(href).path            # drop query and fragment
    if not path.startswith("/"):
        return None
    if path.startswith(SKIP_PREFIXES) or path.endswith(ASSET_SUFFIXES):
        return None
    if not path.endswith("/"):
        path += "/"
    return path


def links_in(html, current):
    """Every internal page path an HTML document points at."""
    found = set()
    for href in re.findall(r'href=["\']([^"\']+)["\']', html):
        p = normalise(href, current)
        if p:
            found.add(p)
    return found


BANNER = """
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

NOINDEX = '<meta name="robots" content="noindex, nofollow, noarchive">\n'


def rewrite(html):
    """Make local URLs root-relative and neutralise everything that needs PHP."""
    for host in ("http://localhost:8080", "http://127.0.0.1:8080",
                 "https://localhost:8080", "https://127.0.0.1:8080"):
        html = html.replace(host + "/", "/")
        html = html.replace(host, "")

    # Endpoints that cannot work without PHP.
    html = re.sub(r'action="[^"]*wp-admin/admin-post\.php"', 'action="#" data-inert="1"', html)
    html = re.sub(r'action="[^"]*wp-login\.php[^"]*"', 'action="#" data-inert="1"', html)

    # WordPress emitters pointing at PHP-only endpoints: REST, oEmbed, RSD, XML-RPC.
    html = re.sub(r'<link[^>]+rel=[\'"](pingback|EditURI|wlwmanifest)[\'"][^>]*>', "", html)
    html = re.sub(r'<link[^>]+(xmlrpc\.php|wp-json|api\.w\.org)[^>]*>', "", html)

    # Feeds are exported as real XML documents; point the links at them.
    html = html.replace('/feed/"', '/feed/index.xml"').replace("/feed/'", "/feed/index.xml'")

    # A DNS hint for the build host is meaningless once deployed.
    html = re.sub(r'<link[^>]+rel=[\'"]dns-prefetch[\'"][^>]*>', "", html)

    # A relative canonical or og:url says nothing; point them at the real origin.
    html = re.sub(
        r'(<link[^>]+rel=["\']canonical["\'][^>]+href=["\'])(/[^"\']*)',
        lambda m: m.group(1) + SITE_ORIGIN + m.group(2), html
    )
    html = re.sub(
        r'(<meta[^>]+property=["\']og:url["\'][^>]+content=["\'])(/[^"\']*)',
        lambda m: m.group(1) + SITE_ORIGIN + m.group(2), html
    )

    # Demo content must never be indexed under the MySaline name. Core's own
    # robots meta is dropped so there is exactly one directive on the page.
    html = re.sub(r'<meta[^>]+name=[\'"]robots[\'"][^>]*>', "", html)
    if "<head>" in html:
        html = html.replace("<head>", "<head>\n" + NOINDEX, 1)

    return html.replace("<body", BANNER + "<body", 1) if "<body" in html else BANNER + html


def save_page(path, html):
    dest = os.path.join(OUT, path.strip("/"), "index.html") if path.strip("/") \
        else os.path.join(OUT, "index.html")
    os.makedirs(os.path.dirname(dest), exist_ok=True)
    with open(dest, "w") as f:
        f.write(rewrite(html))


def save_feed(path, xml):
    """Feeds land as index.xml so the host serves them with an XML content type."""
    for host in ("http://localhost:8080", "http://127.0.0.1:8080"):
        xml = xml.replace(host + "/", "https://mysaline.net/").replace(host, "https://mysaline.net")
    dest = os.path.join(OUT, path.strip("/"), "index.xml")
    os.makedirs(os.path.dirname(dest), exist_ok=True)
    with open(dest, "w") as f:
        f.write(xml)


def crawl():
    """Breadth-first over every internal link until the frontier is empty."""
    seen, feeds, queue = set(), set(), list(ROOTS)
    pages = 0

    while queue and pages < MAX_PAGES:
        path = queue.pop(0)
        if path in seen:
            continue
        seen.add(path)

        # Feeds are fetched separately so their XML is not HTML-rewritten.
        if path.endswith("/feed/"):
            feeds.add(path)
            continue

        html, status, ctype = fetch(path)
        if status != 200 or "html" not in ctype:
            print(f"  {status or '---'}  {path}   (skipped)")
            continue

        save_page(path, html)
        pages += 1
        print(f"  200  {path}")

        for link in links_in(html, path):
            if link not in seen:
                queue.append(link)

    return pages, feeds


def referenced_assets():
    """Every local asset path the exported HTML actually asks for."""
    wanted = set()
    # A real file reference, so it must end in a filename — this deliberately
    # excludes the directory globs in WordPress's speculation-rules JSON.
    pat = re.compile(r'["\'(](/wp-(?:content|includes)/[^"\')?#\s*]*[^"\')?#\s*/]\.[a-zA-Z0-9]{2,5})')
    for root, _dirs, files in os.walk(OUT):
        for fn in files:
            if not fn.endswith((".html", ".xml", ".css")):
                continue
            with open(os.path.join(root, fn), encoding="utf-8", errors="replace") as f:
                body = f.read()
            wanted.update(pat.findall(body))
            # srcset lists several URLs per attribute, comma separated.
            for srcset in re.findall(r'srcset=["\']([^"\']+)["\']', body):
                for candidate in srcset.split(","):
                    url = candidate.strip().split()[0] if candidate.strip() else ""
                    if url.startswith("/wp-"):
                        wanted.add(url)
    return wanted


def copy_assets():
    """Copy only the assets the pages reference.

    A blanket copy of wp-includes is ~43MB for the four files the theme
    actually loads, which makes the deploy needlessly slow and heavy.
    """
    # The theme directory is small and self-referential (CSS can point at its
    # own images), so it comes across whole; everything else is by reference.
    theme_src = os.path.realpath(os.path.join(WPROOT, "wp-content/themes/mysaline"))
    theme_dst = os.path.join(OUT, "wp-content/themes/mysaline")
    if os.path.isdir(theme_src):
        shutil.copytree(theme_src, theme_dst, dirs_exist_ok=True,
                        ignore=shutil.ignore_patterns("*.map", "node_modules", "*.php"))

    copied, missing = 0, []
    for rel in sorted(referenced_assets()):
        if rel.startswith("/wp-content/themes/"):
            continue                                  # already handled above
        src = os.path.join(WPROOT, rel.lstrip("/"))
        if not os.path.isfile(src):
            missing.append(rel)
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
    if missing:
        print(f"  {len(missing)} referenced but absent from the install:")
        for rel in missing[:10]:
            print(f"    {rel}")


def main():
    shutil.rmtree(OUT, ignore_errors=True)
    os.makedirs(OUT, exist_ok=True)

    print(f"crawling {BASE} → {OUT}")
    pages, feeds = crawl()

    print(f"\nfeeds ({len(feeds)})")
    for path in sorted(feeds):
        xml, status, _ = fetch(path)
        if status == 200 and xml.lstrip().startswith("<?xml"):
            save_feed(path, xml)
            print(f"  200  {path}index.xml")
        else:
            print(f"  {status or '---'}  {path}   (skipped)")

    # A 404 document; GitHub Pages serves /404.html automatically.
    html, _status, _ctype = fetch("/this-page-does-not-exist/")
    if html:
        with open(os.path.join(OUT, "404.html"), "w") as f:
            f.write(rewrite(html))

    print("\ncopying assets…")
    copy_assets()

    # Pages must not run the content through Jekyll.
    open(os.path.join(OUT, ".nojekyll"), "w").close()

    # Demo content, under the real brand name: keep every crawler out.
    with open(os.path.join(OUT, "robots.txt"), "w") as f:
        f.write("# Staging preview of the MySaline redesign — demo content only.\n"
                "User-agent: *\nDisallow: /\n")

    total = sum(len(fs) for _r, _d, fs in os.walk(OUT))
    size = subprocess.run(["du", "-sh", OUT], capture_output=True, text=True).stdout.split()[0]
    print(f"\n  {pages} pages, {len(feeds)} feeds, {total} files, {size}")


if __name__ == "__main__":
    main()
