# Deployment — Shipping to the MySaline Server

This describes how to package the theme and install it on the real MySaline
WordPress server **safely**, without disrupting the live site.

> Golden rule: the live site keeps running its current theme until you activate
> MySaline. Installing a theme does **not** change the site; **activating** it
> does. Test on staging first.

## 1. Build the package

```bash
./build.sh 1.0.0     # stamps version 1.0.0 and builds dist/mysaline.zip
```

Output: `dist/mysaline.zip` — a single top-level `mysaline/` folder, ready for
**Appearance → Themes → Add New → Upload Theme**.

## 2. Back up first

Before any change on the server:

- **Database** and **`wp-content`** backup (UpdraftPlus, your host's snapshot,
  or `wp db export` + a `wp-content` archive).
- Note the current active theme name so you can revert instantly.

## 3. Stage it (strongly recommended)

1. Create a **staging copy** of the live site (most hosts offer one click).
2. Upload and **activate** MySaline on staging.
3. Run **Settings → Permalinks → Save** once (refreshes custom-post-type rules).
4. Click through the checklist in section 6 against real content.

## 4. Go live

When staging looks right:

1. Upload `dist/mysaline.zip` on the production site via
   **Appearance → Themes → Add New → Upload Theme** (this only *installs* it —
   the live theme is still active and unaffected).
2. Pick a low-traffic window. Optionally enable a maintenance notice.
3. **Activate** MySaline.
4. Immediately visit **Settings → Permalinks** and click **Save Changes** once.
5. Configure the Customizer options and menus (or import them from staging).
6. If existing images look uncropped, run **Regenerate Thumbnails** so the
   theme's image sizes are generated.

## 5. Rollback (instant)

If anything looks wrong, **Appearance → Themes → Activate** the previous theme.
Because MySaline never modifies posts, pages, categories, media or permalinks,
reverting the theme returns the site to exactly its prior appearance. Content is
untouched.

## 6. Post-deploy checklist

- [ ] Homepage renders (hero, sections, events/obituaries/business blocks).
- [ ] A sample of **existing** post and page URLs still resolve (no 404s).
- [ ] Category / tag / author / date archives work.
- [ ] Search and the 404 page work.
- [ ] Menus assigned (Primary, Top Bar, Footer).
- [ ] Logo, colors, social links, footer contact set.
- [ ] Newsletter form submits to your provider.
- [ ] Ads display in their zones; scheduled ads respect dates.
- [ ] Mobile: menu toggle, search toggle, breaking bar, responsive grids.
- [ ] Featured images display; run Regenerate Thumbnails if needed.

### Before a Favorites voting window opens

Ballots require an email click to count, so outgoing mail is load-bearing:

- [ ] An SMTP plugin is configured (WP Mail SMTP / Post SMTP / host equivalent) —
      default PHP mail often fails to reach Gmail and Yahoo.
- [ ] Submitted a real test ballot end to end: email arrives, link confirms, the
      vote appears under **Favorites → Results**.
- [ ] Checked the confirmation email doesn't land in spam (test Gmail + Yahoo).
- [ ] Voting open/close datetimes and the ballot year are set correctly.
- [ ] Ballot imported and spot-checked; **Publish results publicly** is OFF.

## 6a. Updating later

Ship updates the same way: `./build.sh <new-version>`, upload, and WordPress
replaces the theme in place. Custom content (CPT entries, Customizer settings,
menus, widgets) is stored in the database and survives theme updates. For
ongoing custom code changes, prefer a **child theme** so updates never overwrite
tweaks.

## 7. What this project never does

- It does **not** connect to, read from, or modify the live MySaline.com during
  development. All work happens in this repo and on your own dev/staging site.
- It does **not** change existing content, users, media or URLs.
