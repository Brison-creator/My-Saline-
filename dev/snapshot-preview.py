#!/usr/bin/env python3
"""
Snapshot the *real* running MySaline theme into one self-contained page.

Rather than hand-maintaining a mockup (which drifted from the theme four
separate times), this crawls the live WordPress install, inlines the theme's
own stylesheet and images, and stitches the pages into a single artifact with a
view switcher. What you see is literally what the templates emitted.
"""
import base64
import mimetypes
import os
import re
import urllib.request
from html import escape

BASE = "http://127.0.0.1:8080"
WPROOT = "/tmp/wpsite"

PAGES = [
    ("home",      "/",                                    "Homepage"),
    ("section",   "/category/saline-county/",             "Section front"),
    ("article",   None,                                   "Article"),          # resolved below
    ("records",   "/category/public-records/",            "Public Records"),
    ("events",    "/events/",                             "Events"),
    ("event",     None,                                   "Single event"),
    ("obits",     "/obituaries/",                         "Obituaries"),
    ("obit",      None,                                   "Single obituary"),
    ("directory", "/businesses/",                         "Directory"),
    ("business",  None,                                   "Single business"),
    ("todo",      "/things-to-do/",                       "Things To Do"),
    ("govt",      "/government/",                         "Government"),
    ("ballot",    "/saline-county-favorites-vote/",       "Favorites ballot"),
    ("search",    "/?s=benton",                           "Search"),
    ("e404",      "/a-page-that-does-not-exist/",         "404"),
]

_img_cache = {}


def fetch(url):
    req = urllib.request.Request(url, headers={"User-Agent": "snapshot"})
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return r.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        # A 404 page is exactly the content we want to capture.
        body = e.read().decode("utf-8", "replace")
        if body:
            return body
        print(f"  ! {url}: {e}")
        return ""
    except Exception as e:                                    # noqa: BLE001
        print(f"  ! {url}: {e}")
        return ""


def local_path(url):
    """Map a site URL back to a file on disk, avoiding another HTTP round trip."""
    for host in ("http://localhost:8080", "http://127.0.0.1:8080"):
        if url.startswith(host):
            return os.path.join(WPROOT, url[len(host):].lstrip("/"))
    return None


def data_uri(url):
    if url in _img_cache:
        return _img_cache[url]
    p = local_path(url)
    if not p or not os.path.isfile(p):
        _img_cache[url] = url
        return url
    mime = mimetypes.guess_type(p)[0] or "image/jpeg"
    with open(p, "rb") as f:
        blob = f.read()
    if len(blob) > 400_000:            # skip anything unreasonably large
        _img_cache[url] = url
        return url
    uri = f"data:{mime};base64," + base64.b64encode(blob).decode()
    _img_cache[url] = uri
    return uri


def inline_images(html):
    # srcset would pull many variants; collapse to the single src we inline.
    html = re.sub(r'\s+srcset="[^"]*"', "", html)
    html = re.sub(r'\s+sizes="[^"]*"', "", html)

    def repl(m):
        return f'src="{data_uri(m.group(1))}"'

    return re.sub(r'src="(https?://(?:localhost|127\.0\.0\.1):8080[^"]+)"', repl, html)


def get_body(html):
    m = re.search(r"<body[^>]*>(.*)</body>", html, re.S)
    return m.group(1) if m else ""


THEME_JS = {
    "main.js": "/home/user/My-Saline-/mysaline/assets/js/main.js",
    "favorites.js": "/home/user/My-Saline-/mysaline/assets/js/favorites.js",
}


def strip_noise(body):
    """Drop the admin bar; inline the theme's own JS, drop everything else.

    The theme scripts are what make the ballot's search, tabs, progress meter
    and autosave work, so they are inlined rather than stripped. Any other
    external script (WordPress core, jQuery) is removed, since it would be a
    blocked cross-origin request inside the artifact sandbox.
    """
    body = re.sub(r"<div id=[\"']wpadminbar[\"'].*?</div>\s*(?=<)", "", body, flags=re.S)

    def repl(m):
        tag = m.group(0)
        for name, path in THEME_JS.items():
            if name in tag and os.path.isfile(path):
                with open(path) as f:
                    return "<script>\n" + f.read() + "\n</script>"
        return ""

    return re.sub(r"<script[^>]*\ssrc=[^>]*></script>", repl, body)


def rewrite_links(body, known):
    """Point internal links at the view switcher where we have that page."""
    url_map = {}
    for key, path, _ in known:
        if path:
            url_map[path] = key

    def repl(m):
        href = m.group(1)
        for host in ("http://localhost:8080", "http://127.0.0.1:8080"):
            if href.startswith(host):
                href = href[len(host):] or "/"
        if href in url_map:
            return f'href="#" data-goto="{url_map[href]}"'
        if href.startswith("#"):
            return f'href="{href}"'
        return 'href="#" data-inert="1"'

    return re.sub(r'href="([^"]*)"', repl, body)


def main():
    # Resolve the singles by asking WP-CLI for real permalinks.
    import subprocess

    def one(post_type, name=None):
        cmd = ["php", "/tmp/wp-cli.phar", "post", "list", f"--post_type={post_type}",
               "--posts_per_page=1", "--field=url", "--allow-root", "--orderby=ID", "--order=ASC"]
        if name:
            cmd.append(f"--name={name}")
        out = subprocess.run(cmd, cwd=WPROOT, capture_output=True, text=True).stdout.strip()
        return out.splitlines()[0] if out else None

    resolved = []
    for key, path, label in PAGES:
        if path is None:
            pt, slug = {
                "article":  ("post", "county-fair-returns-this-weekend-with-a-bigger-midway-and-free-parking"),
                "event":    ("ms_event", None),
                "obit":     ("ms_obituary", None),
                "business": ("ms_business", None),
            }[key]
            url = one(pt, slug)
            path = url.replace("http://localhost:8080", "") if url else "/"
        resolved.append((key, path, label))

    css = open("/home/user/My-Saline-/mysaline/style.css").read()
    # Drop the theme header comment; keep the rules.
    css = re.sub(r"^/\*.*?\*/", "", css, count=1, flags=re.S)

    views = []
    for key, path, label in resolved:
        print(f"  capturing {label} … {path}")
        html = fetch(BASE + path)
        if not html:
            continue
        body = strip_noise(get_body(html))
        body = inline_images(body)
        body = rewrite_links(body, resolved)
        views.append((key, label, body))

    tabs = "\n".join(
        f'<button class="snap-tab" role="tab" data-view="{k}" '
        f'aria-selected="{"true" if i == 0 else "false"}">{escape(l)}</button>'
        for i, (k, l, _) in enumerate(views)
    )
    panels = "\n".join(
        f'<div class="snap-view{"" if i == 0 else " snap-hidden"}" data-view="{k}">{b}</div>'
        for i, (k, _, b) in enumerate(views)
    )

    out = f"""<title>MySaline — Live Theme Snapshot</title>
<style>
:root {{ --snap-bg:#eef1f6; --snap-panel:#fff; --snap-ink:#1b2230; --snap-muted:#5d6a7d; --snap-line:#d3dae4; --snap-accent:#0b2545; --snap-flag:#c8102e; }}
@media (prefers-color-scheme: dark) {{ :root:not([data-theme="light"]) {{ --snap-bg:#0d1219; --snap-panel:#151c26; --snap-ink:#e6ecf5; --snap-muted:#94a3b8; --snap-line:#26303e; --snap-accent:#7fa8dd; --snap-flag:#ff5c74; }} }}
:root[data-theme="dark"] {{ --snap-bg:#0d1219; --snap-panel:#151c26; --snap-ink:#e6ecf5; --snap-muted:#94a3b8; --snap-line:#26303e; --snap-accent:#7fa8dd; --snap-flag:#ff5c74; }}
:root[data-theme="light"] {{ --snap-bg:#eef1f6; --snap-panel:#fff; --snap-ink:#1b2230; --snap-muted:#5d6a7d; --snap-line:#d3dae4; --snap-accent:#0b2545; --snap-flag:#c8102e; }}
*,*::before,*::after {{ box-sizing:border-box; }}
body {{ margin:0; background:var(--snap-bg); color:var(--snap-ink); font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; }}
.snap-shell {{ max-width:1340px; margin:0 auto; padding:1.25rem 1rem 3rem; }}
.snap-head h1 {{ font-family:Georgia,serif; font-size:1.4rem; margin:.4rem 0 .3rem; }}
.snap-head p {{ margin:0 0 .9rem; color:var(--snap-muted); font-size:.9rem; max-width:70ch; }}
.snap-flag {{ display:inline-block; background:var(--snap-flag); color:#fff; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; padding:.28rem .6rem; border-radius:3px; }}
.snap-tabs {{ display:flex; gap:.35rem; flex-wrap:wrap; margin-bottom:.9rem; }}
.snap-tab {{ background:var(--snap-panel); color:var(--snap-ink); border:1px solid var(--snap-line); padding:.45rem .85rem; border-radius:999px; font:inherit; font-size:.85rem; font-weight:600; cursor:pointer; }}
.snap-tab:hover {{ border-color:var(--snap-accent); }}
.snap-tab[aria-selected="true"] {{ background:var(--snap-accent); color:var(--snap-bg); border-color:var(--snap-accent); }}
.snap-tab:focus-visible {{ outline:2px solid var(--snap-flag); outline-offset:2px; }}
.snap-frame {{ background:#fff; border:1px solid var(--snap-line); border-radius:10px; overflow:hidden; box-shadow:0 1px 2px rgba(0,0,0,.06),0 16px 44px rgba(11,37,69,.13); }}
.snap-hidden {{ display:none !important; }}
.snap-note {{ font-size:.82rem; color:var(--snap-muted); margin:.8rem 0 0; }}
.snap-view {{ color:#1a1d23; background:#fff; color-scheme:light; }}
.snap-view a[data-inert] {{ cursor:default; }}
/* ---- the theme's own stylesheet, scoped to the snapshot panes ---- */
{css}
</style>

<div class="snap-shell">
  <header class="snap-head">
    <span class="snap-flag">Live snapshot · real theme output</span>
    <h1>MySaline — the theme as WordPress actually renders it</h1>
    <p>Captured from the theme running on WordPress 7.0.3. This is the templates' real HTML with the real stylesheet inlined — not a mockup. Links between pages work; outbound links are inert. Content is seeded sample data.</p>
  </header>
  <div class="snap-tabs" role="tablist" aria-label="Pages">
{tabs}
  </div>
  <div class="snap-frame">
{panels}
  </div>
  <p class="snap-note">The Favorites ballot's search, section tabs, progress meter and autosave run here exactly as they do on the site. Submitting needs PHP, so the button is inert.</p>
</div>

<script>
(function () {{
  var tabs = [].slice.call(document.querySelectorAll('.snap-tab'));
  var views = [].slice.call(document.querySelectorAll('.snap-view'));
  function show(name) {{
    views.forEach(function (v) {{ v.classList.toggle('snap-hidden', v.getAttribute('data-view') !== name); }});
    tabs.forEach(function (t) {{ t.setAttribute('aria-selected', String(t.getAttribute('data-view') === name)); }});
    var f = document.querySelector('.snap-frame');
    if (f) {{ f.scrollIntoView({{ behavior: 'smooth', block: 'start' }}); }}
  }}
  tabs.forEach(function (t) {{ t.addEventListener('click', function () {{ show(t.getAttribute('data-view')); }}); }});
  document.addEventListener('click', function (e) {{
    var a = e.target.closest && e.target.closest('a');
    if (!a) {{ return; }}
    if (a.hasAttribute('data-goto')) {{ e.preventDefault(); show(a.getAttribute('data-goto')); return; }}
    if (a.hasAttribute('data-inert')) {{ e.preventDefault(); }}
  }});
  // Neutralise forms so nothing tries to post.
  document.addEventListener('submit', function (e) {{ e.preventDefault(); }});
}}());
</script>
"""
    dest = "/home/user/My-Saline-/preview/index.html"
    open(dest, "w").write(out)
    print(f"\n  wrote {dest}  ({len(out):,} bytes, {len(views)} views)")


if __name__ == "__main__":
    main()
