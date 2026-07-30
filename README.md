# MySaline — Custom WordPress Theme

A production-ready, modern redesign of **MySaline.com** built as a proper
**custom WordPress theme** (classic PHP theme, not a static site and not a
separate React/Next.js app). It keeps the same content model and community
features the site is known for, presented in a clean, fast, responsive,
dashboard-managed package.

> **The live MySaline.com is never touched by this project.** Everything is
> developed here in the repository and tested on a temporary WordPress site,
> then packaged as a standard installable theme ZIP and uploaded when ready.

---

## What's in this repo

```
.
├── mysaline/            ← the WordPress theme (this is what gets installed)
│   ├── style.css                  Theme header + full stylesheet
│   ├── functions.php              Bootstrap; loads inc/ modules
│   ├── front-page.php             Homepage (hero, sections, events, etc.)
│   ├── header.php / footer.php / sidebar.php / comments.php / searchform.php
│   ├── index.php single.php page.php archive.php search.php 404.php
│   ├── single-ms_event.php single-ms_obituary.php single-ms_business.php
│   ├── theme.json                 Block-editor colors, fonts, layout
│   ├── screenshot.png             Theme preview (1200×900)
│   ├── readme.txt                 WordPress-format theme readme
│   ├── inc/                       Setup, enqueue, Customizer, CPTs, meta,
│   │                              widgets, ads, breaking news, homepage helpers
│   ├── template-parts/            Reusable UI partials (cards, hero, etc.)
│   └── assets/                    css/ js/ images/
├── docs/                Audit + installation, deployment, content, dev guides
├── build.sh             Packages mysaline/ into dist/mysaline.zip
├── .editorconfig .gitignore LICENSE
└── README.md
```

## Feature overview

Everything below is editable from the WordPress dashboard — **no code editing**.
See [`docs/CONTENT-MANAGEMENT.md`](docs/CONTENT-MANAGEMENT.md) for the full,
click-by-click guide, or in the dashboard go to **Appearance → MySaline Setup**.

| Control | Where the owner manages it |
| --- | --- |
| **Featured stories** | Post editor → "Featured Story" box → shows in homepage hero |
| **Breaking news** | Customize → MySaline Options → Breaking News |
| **Homepage sections** | Customize → MySaline Options → Homepage Sections |
| **Homepage quick links** | Customize → MySaline Options → Homepage Quick Links |
| **Advertisements** | Advertisements menu (zones, run dates, image or code) |
| **Community events** | Events menu (dates, venue, tickets) |
| **Obituaries** | Obituaries menu (portrait, dates, services) |
| **Business listings** | Businesses menu (+ Business Categories) |
| **Saline County Favorites** | Favorites menu (bulk ballot importer, results + CSV exports); window & prize rules in Customize |
| **Navigation menus** | Appearance → Menus (primary, top bar, footer, social) |
| **Newsletter signup** | Customize → MySaline Options → Newsletter |
| **Social links** | Customize → MySaline Options → Social Links |
| **Logo & branding** | Customize → Site Identity + MySaline Options → Branding |
| **Footer contact** | Customize → MySaline Options → Footer |
| **Widgets** | Appearance → Widgets (2 sidebars, 4 footer columns, 5 custom widgets) |

## Compatibility

Built on the standard WordPress template hierarchy, so **existing posts, pages,
categories, tags, authors, users, featured images, media, archives and URLs all
continue to work unchanged**. Custom post types add new content only and never
alter existing content or permalinks. See [`docs/AUDIT.md`](docs/AUDIT.md) for
the full mapping of the current site to the new theme.

## Quick start (local / temporary dev site)

1. Have a WordPress site (local via [LocalWP](https://localwp.com/),
   [`wp-env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/),
   Docker, or any temporary host).
2. Copy the `mysaline/` folder into `wp-content/themes/`, **or** run
   `./build.sh` and upload `dist/mysaline.zip` via
   **Appearance → Themes → Add New → Upload Theme**.
3. Activate **MySaline**.
4. Follow [`docs/INSTALLATION.md`](docs/INSTALLATION.md) to set the homepage,
   menus, and demo content.

## Build the installable ZIP

```bash
./build.sh            # → dist/mysaline.zip
./build.sh 1.1.0      # also stamps version 1.1.0 into style.css / readme / functions
```

The ZIP contains a single top-level `mysaline/` folder, exactly as WordPress
expects for **Appearance → Themes → Add New → Upload Theme**.

## Documentation

- [`docs/AUDIT.md`](docs/AUDIT.md) — live-site audit + modernization map
- [`docs/INSTALLATION.md`](docs/INSTALLATION.md) — set up a dev site and the theme
- [`docs/CONTENT-MANAGEMENT.md`](docs/CONTENT-MANAGEMENT.md) — manage every feature from the dashboard
- [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) — package and upload to the MySaline server safely
- [`docs/DEVELOPMENT.md`](docs/DEVELOPMENT.md) — code layout, standards, how to extend

## License

GPL-2.0-or-later, the same license as WordPress. See [`LICENSE`](LICENSE).
