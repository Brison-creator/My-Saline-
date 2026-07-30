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
├── dev/                 Local-only dev tooling (NEVER shipped in the ZIP)
│   ├── seed-demo.php          Fills the dev site with realistic demo content
│   └── mu-plugins/            Dev mail catcher, so the ballot's email
│                              confirmation can be tested without SMTP
├── docs/                Audit + installation, deployment, content, dev guides
├── build.sh             Packages mysaline/ into dist/mysaline.zip
├── .wp-env.json         One-command local WordPress (Docker)
├── package.json         npm scripts: start / seed / reset / mail / zip
├── .editorconfig .gitignore LICENSE
└── README.md
```

`build.sh` copies from `mysaline/` only, so everything in `dev/` is structurally
incapable of reaching production.

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

## Quick start — see it running in one command

Requires [Docker Desktop](https://www.docker.com/products/docker-desktop/)
running, plus Node.js.

```bash
npm install
npm start
```

Starts WordPress, activates the theme, and fills it with realistic demo content:

| | |
| --- | --- |
| **Site** | http://localhost:8888 |
| **Dashboard** | http://localhost:8888/wp-admin — `admin` / `password` |
| **Where everything is edited** | Appearance → MySaline Setup |

The seed includes 16 posts across 8 categories (5 featured), 5 events, 4
obituaries, 6 businesses, an ad in each of the 7 zones, the **full 33-category
Favorites ballot with 100+ finalists**, three menus with dropdowns, every theme
option and the sidebar/footer widgets.

Because ballots need an emailed confirmation link and a local site can't send
mail, the dev environment captures outgoing email to `dev/mail.log` and surfaces
the confirmation link as an admin notice — so double opt-in is fully testable
offline. That catcher lives in `dev/` and never ships in the theme.

```bash
npm stop         # stop containers
npm run seed     # re-seed content
npm run reset    # wipe and rebuild
npm run mail     # show captured email
npm run zip      # build dist/mysaline.zip for upload
npm run lint:php # syntax-check the theme
```

Prefer LocalWP, MAMP or a staging host? See the manual path in
[`docs/INSTALLATION.md`](docs/INSTALLATION.md).

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
