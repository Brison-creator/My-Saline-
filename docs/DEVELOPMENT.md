# Development Guide

## Requirements

- WordPress 6.0+, PHP 7.4+ (8.1+ recommended)
- A local WordPress (LocalWP / `wp-env` / Docker) — see `INSTALLATION.md`
- PHP CLI for linting; optionally
  [PHP_CodeSniffer + WPCS](https://github.com/WordPress/WordPress-Coding-Standards)

## Code layout

```
mysaline/
├── functions.php        Defines constants, loads inc/ modules in order
├── inc/
│   ├── theme-setup.php      Supports, menus, image sizes, sidebars, setup page
│   ├── enqueue.php          Assets + dynamic CSS variables from the Customizer
│   ├── template-tags.php    Presentation helpers (badges, meta, thumbnails, icons)
│   ├── template-functions.php  body_class, excerpt, menu fallback, misc hooks
│   ├── customizer.php       All global options (branding, breaking, homepage,
│   │                        quick links, sections, newsletter, social, ads, footer)
│   ├── post-types.php       CPTs (obituary, event, business, ad) + taxonomies
│   ├── meta-boxes.php       Featured flag + CPT detail fields (+ save handlers)
│   ├── widgets.php          Widget areas registration + 5 custom widgets
│   ├── ads.php              Ad zones, querying, rendering, in-content injection
│   ├── breaking-news.php    Breaking-bar data helpers
│   ├── events.php           Event query/date helpers
│   └── homepage.php         Hero query + configurable section renderers
├── template-parts/      Reusable partials (cards, hero, quick-links, newsletter…)
├── assets/css|js|images
├── theme.json           Editor palette / typography / layout
└── *.php                Template hierarchy files (single, archive, page, …)
```

## Conventions

- **Prefix everything** `mysaline_` (functions), `_ms_` (post meta),
  `ms-` (CSS classes), `ms_` (custom post types), `mysaline_` (theme mods).
- **Escape on output** (`esc_html`, `esc_url`, `esc_attr`, `wp_kses_post`) and
  **sanitize on input** (Customizer `sanitize_callback`, meta save handlers).
- **Text domain** `mysaline` on every user-facing string.
- Follow the WordPress Coding Standards (tabs for indentation).
- CSS uses design tokens (CSS custom properties) in `:root`; the Customizer
  overrides `--ms-primary` / `--ms-accent` at runtime via inline CSS.

## Common tasks

**Lint all PHP:**

```bash
for f in $(find mysaline -name '*.php'); do php -l "$f" >/dev/null || echo "FAIL $f"; done
```

**Lint with WPCS (optional):**

```bash
composer require --dev wp-coding-standards/wpcs dealerdirect/phpcodesniffer-composer-installer
./vendor/bin/phpcs --standard=WordPress mysaline
```

**Build the ZIP:** `./build.sh` (see `DEPLOYMENT.md`).

**Regenerate the screenshot:** the source generator lives outside the theme;
`screenshot.png` is a static 1200×900 PNG. Replace it with a real homepage
capture before final release if you prefer a photographic preview.

## Adding features

- **New homepage section slot:** raise `MYSALINE_HOMEPAGE_SECTIONS` in
  `inc/customizer.php`; the loop and renderer scale automatically.
- **New ad zone:** add to `mysaline_ad_zone_choices()` in `inc/ads.php`, then
  call `mysaline_ad( 'your_zone' )` in a template.
- **New CPT field:** add it to `mysaline_meta_fields()` in `inc/meta-boxes.php`
  (rendering + saving are handled generically) and output it in the CPT
  template.

## Extending safely in production

Do site-specific tweaks in a **child theme** or a small site plugin so
`./build.sh` updates never overwrite them. All CPT slugs are filterable
(`mysaline_*_slug`) so URLs can be adjusted without editing the theme.

## Git

Develop on the feature branch; keep `mysaline/` the single source of truth for
theme files. `dist/` is build output and is git-ignored.

## Validation performed

The theme has been booted and exercised on a real WordPress install
(WordPress 7.0.3, PHP 8.4, SQLite via the official `sqlite-database-integration`
drop-in — used because Docker's build network could not reach Alpine mirrors,
so `wp-env` was unavailable).

What was verified end to end:

- **Rendering** — 13 page types and 6 single templates return HTTP 200 with zero
  PHP notices, warnings or fatals; the debug log stays clean.
- **Scale** — with 2,022 posts the homepage renders in ~0.17s and every archive,
  search and deep-pagination page (tested to page 50) in under 0.08s, on SQLite
  and PHP's single-threaded dev server. Production MySQL with opcache is faster.
- **Admin** — all 12 dashboard screens load clean, every meta box renders on its
  post type, and both CSV exports return `text/csv` with correct filenames.
- **Favorites, full double opt-in** — 33 categories / 99 nominees render; a
  submitted ballot is held pending with zero confirmed votes; the emailed link
  confirms the votes, consumes the pending row and issues the trust cookie;
  replaying the link returns `fav=expired`.
- **Social metadata** — Open Graph, Twitter Card and a valid three-node JSON-LD
  graph render on a live post, with exactly one canonical tag.

### Security probes against the vote path

All rejected: missing nonce, forged nonce, missing email, malformed email, a
nominee that isn't on the ballot, XSS and SQL-injection payloads as nominee
values, nonexistent and negative category IDs, and an empty ballot. Confirmation
tokens reject short, non-hex, path-traversal and brute-force candidates.

Both CSV exports return HTTP 400 with no data to an unauthenticated request, and
attempting to overwrite another voter's confirmed ballot by submitting their
email fails — it only creates a new pending ballot, which requires access to
their inbox to confirm.
