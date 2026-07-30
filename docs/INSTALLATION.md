# Installation — Temporary Development Site

Set up the theme on a **temporary/local** WordPress site for building and
testing. **Do not install on the live MySaline.com** until you've signed off
(see [`DEPLOYMENT.md`](DEPLOYMENT.md)).

---

## Fastest path: one command

The repository ships a ready-made local WordPress environment. You need
[Docker Desktop](https://www.docker.com/products/docker-desktop/) running and
Node.js installed, then:

```bash
npm install
npm start
```

That starts WordPress, activates the theme, fills it with realistic demo content
and sets clean permalinks. When it finishes:

| | |
| --- | --- |
| **Site** | http://localhost:8888 |
| **Dashboard** | http://localhost:8888/wp-admin (`admin` / `password`) |
| **Setup guide** | Appearance → MySaline Setup |

What the demo content includes: 16 posts across 8 categories (5 flagged as
Featured for the hero), 5 events, 4 obituaries, 6 businesses in 5 categories, an
ad in every one of the 7 zones, the **full 33-category Favorites ballot with 100+
finalists**, three menus with dropdowns, all theme options, and widgets in the
sidebars and footer. Featured images are generated locally, so nothing binary is
stored in the repository.

### Testing the Favorites ballot locally

Voting requires email confirmation, and a local site can't send email — so the
dev environment **captures** it instead. Nothing leaves your machine.

1. Visit the **Saline County Favorites — Vote** page and submit a ballot.
2. Either open `dev/mail.log`, run `npm run mail`, or just look at the admin —
   a notice shows the latest confirmation link as a clickable link.
3. Click it. The votes are counted and your browser is remembered.
4. Check **Favorites → Results** to see the tally and try the CSV exports.

The mail catcher lives in `dev/` and is loaded only by the local environment.
It's never part of the packaged theme, so it cannot reach production.

### Everyday commands

```bash
npm start        # start + activate + seed (safe to re-run)
npm stop         # stop the containers
npm run seed     # re-seed content only
npm run reset    # wipe the database and rebuild from scratch
npm run mail     # show captured emails
npm run cli ...  # any WP-CLI command, e.g. npm run cli plugin list
npm run zip      # build dist/mysaline.zip for upload
npm run lint:php # syntax-check every theme file
```

---

## Manual setup (any other WordPress)

Use this if you'd rather not use Docker, or you're testing on a staging host.

## 1. Get a WordPress site

Any of these works:

- **LocalWP** (easiest, free desktop app) — https://localwp.com/
- **wp-env** (Node): `npx @wordpress/env start`
- **Docker**: the official `wordpress` image
- Any temporary staging host / subdomain

Use WordPress **6.0+** and PHP **7.4+** (PHP 8.1+ recommended).

## 2. Install the theme

**Option A — upload the ZIP (mirrors the real deployment):**

```bash
./build.sh
```

Then in the dashboard: **Appearance → Themes → Add New → Upload Theme**, choose
`dist/mysaline.zip`, install and **Activate**.

**Option B — symlink/copy for active development:**

```bash
ln -s "$(pwd)/mysaline" /path/to/wordpress/wp-content/themes/mysaline
# or: cp -R mysaline /path/to/wordpress/wp-content/themes/
```

Then activate **MySaline** under Appearance → Themes.

## 3. First-run setup (about 5 minutes)

1. **Permalinks:** Settings → Permalinks → choose *Post name* → Save. (Also
   refreshes the custom post type URLs.)
2. **Homepage:** Settings → Reading → *Your homepage displays* → **A static
   page**. Create/select a page named e.g. "Home" for the front page, and a
   page "News"/"Blog" for the posts page. The theme's `front-page.php` renders
   the full magazine homepage automatically.
3. **Menus:** Appearance → Menus → create a menu, add your categories/pages,
   assign it to **Primary Menu**. Optionally build Top Bar and Footer menus.
4. **Branding:** Appearance → Customize → Site Identity (logo) and
   MySaline Options → Branding (colors).
5. **Options:** Work through Customize → MySaline Options (Breaking News,
   Homepage, Quick Links, Homepage Sections, Newsletter, Social Links, Footer).

## 4. Add demo content to test every feature

- A few **posts** with featured images; flag 3–5 as **Featured Story**.
- 2–3 **Events**, **Obituaries**, and **Businesses** (with a Business Category).
- 1–2 **Advertisements** in different zones (add an image + link).
- Set a **Newsletter** action URL and some **Social Links**.

Recommended plugins for a realistic test (not required by the theme):

- **FakerPress** — generate demo posts/terms quickly.
- **Regenerate Thumbnails** — after activation, to build the theme's image
  sizes for existing images.

## 5. Verify

Check the homepage, a category archive, a single post, search, a 404, and each
custom post type archive/single. Confirm the mobile menu, search toggle,
breaking bar and ads all behave. On a real content import, confirm existing
permalinks still resolve.

Next: [`CONTENT-MANAGEMENT.md`](CONTENT-MANAGEMENT.md) for day-to-day editing,
or [`DEPLOYMENT.md`](DEPLOYMENT.md) to ship it.
