# Managing MySaline from the Dashboard

Everything on the site is editable without touching code. This guide walks
through each control. In the dashboard you can always find a shortcut map at
**Appearance → MySaline Setup**.

Most global settings live in the **Customizer**: go to
**Appearance → Customize → MySaline Options**.

---

## Logo & branding

- **Logo:** Customize → **Site Identity** → *Select logo*.
- **Site title & tagline:** Customize → Site Identity.
- **Colors:** Customize → MySaline Options → **Branding** → primary and accent
  colors. The whole theme recolors instantly.
- **Show/hide tagline** next to the title: same Branding panel.

## Navigation menus

Appearance → **Menus**. Assign menus to these locations:

- **Primary Menu** — the main navigation bar (supports dropdowns; add child
  items to build "News Categories", "Elections/Maps/Columnists", etc.).
- **Top Bar Menu** — small links in the dark bar (About, Advertise, Contact…).
- **Footer Menu** — the "Sections" column in the footer.
- **Social Links Menu** — optional; social icons are usually set in the
  Customizer instead (see below).

To recreate the current dropdowns, create top-level items ("News Categories")
and drag category/page items slightly right to nest them underneath.

## Featured stories (homepage hero)

1. Edit any post. In the right sidebar find the **Featured Story** box and tick
   *"Show this story in the homepage featured hero."*
2. Customize → MySaline Options → **Homepage Hero**: choose whether the hero
   uses *Featured* posts, *Most recent*, or *Newest from a category*, and how
   many stories to show (1 large + the rest listed).

If you pick "Featured" but haven't flagged any posts yet, the hero
automatically falls back to your most recent posts so it's never empty.

## Breaking news bar

Customize → MySaline Options → **Breaking News**:

- Enable/disable the red bar.
- **Custom message** you type (with an optional link), **or**
- **From a category** — it auto-shows the latest headlines from a category.
- Set the bar label (default "Breaking").

## Homepage quick-link cards

Customize → MySaline Options → **Homepage Quick Links**. Four callout cards
(icon + title + link) — the modern version of the old Advertise / Elected
Officials / Yard Sales / Games boxes. Leave a card's title blank to hide it.

## Homepage sections

Customize → MySaline Options → **Homepage Sections**. Up to four category-driven
blocks. For each: enable it, choose a **category**, an optional heading, a
**layout** (3-col grid / 2-col grid / compact list / lead + list) and how many
posts to show.

You can also toggle the built-in homepage blocks (Latest News, Upcoming Events,
Recent Obituaries, Business Spotlight) under **Homepage Hero**.

## Advertisements

Go to the **Advertisements** menu → *Add New*.

- Give it a title (internal name).
- Set a **Photo** (the ad image) *or* paste **Ad code** (AdSense / ad-network).
- Add a **click-through URL** for image ads.
- Choose a **placement zone**: Header, Homepage top, Homepage mid, Sidebar,
  In-content, Below content, or Footer.
- Optionally set **start/stop dates** to schedule the ad.

Multiple ads in the same zone rotate at random. Global on/off and the small
"Advertisement" label are under Customize → MySaline Options → Advertisements.

## Community events

The **Events** menu → *Add New*. Fill in start/end dates, time, venue, address,
cost, organizer and a tickets/info link. Events show in the Upcoming Events
homepage block, the Events archive (ordered by date) and a dedicated widget.

## Obituaries

The **Obituaries** menu → *Add New*. Set the person's name as the title, a
portrait as the **Photo**, dates of birth/passing, age, city, and service
details (date/time, location, funeral home + link).

## Business listings (directory)

The **Businesses** menu → *Add New*. Add the logo as the **Photo**, a
description, and details (phone, email, website, address, hours). Assign one or
more **Business Categories**. Tick *"Feature in the homepage Business
Spotlight"* to promote it on the homepage.

## Saline County Favorites (voting ballot)

The annual reader's-choice survey runs **on the site** now — no external form, so
voters keep seeing your ads and branding, and nobody needs a Google account.

### 1. Build the ballot

Fastest way: **Favorites → Import Ballot**. Paste the whole thing at once —
`##` for a section, a plain line for a category, a dash for each finalist:

```
## Food
Restaurant — BBQ
- Whole Hog Cafe
- Wright's Barbecue | https://example.com

## Businesses
Best Plumber
- Ace Plumbing
```

Adding a `| https://…` after a finalist links their name on the ballot.
Re-importing matches existing categories **by name** and updates them, so you
can't create duplicates. Tick *Replace* to overwrite a category's finalist list
rather than add to it.

To edit by hand instead: **Favorites → Add New**, put the category name as the
title (e.g. "Best Plumber"), pick a **Section**, and list finalists one per line.
Drag categories in the list view or set *Order* to control ballot order.
A category with no finalists is skipped automatically.

### 2. Set the window and rules

Customize → MySaline Options → **Saline County Favorites**:

- **Ballot year** — keeps each year's votes separate.
- **Voting opens / closes** — `YYYY-MM-DD HH:MM` (24-hour, site time). Blank
  means no limit. Before it opens and after it closes the ballot shows a notice
  and the options are disabled.
- **Categories needed** (default 20) and **sections that must each have a vote**
  (default 4) — these drive the live progress meter voters see.
- **Prize / rules line**, **intro line**, **thank-you message**.
- **Publish results publicly** — leave off until you announce winners.

### 3. Put the ballot on a page

Either way works:

- Create the page (e.g. `/scf-2026-vote/`) and set **Page Attributes →
  Template → Saline County Favorites Ballot**, or
- Add the shortcode `[mysaline_favorites_ballot]` anywhere in a page.

For winners, use `[mysaline_favorites_results]` and tick *Publish results
publicly*. Editors can preview results before they're public.

### 4. What voters get

- **Search** — typing "plumb" jumps straight to Best Plumber instead of
  scrolling past 80 business categories.
- **Section tabs** — vote one section at a time.
- **Live progress meter** — "7 of 20 needed" plus a lit-up chip per section, so
  they can see exactly when they qualify for the drawing.
- **"Hide ones I've done"** — shrinks a 155-category ballot as they go.
- **Autosave** — picks are kept on their device; closing the tab loses nothing.
- **Skip anything**; no category is required.
- They can return and change picks until voting closes — only the latest pick in
  each category counts.

### 4a. Email confirmation (on by default)

To keep the vote fair, a ballot only counts once the voter clicks a link emailed
to them:

1. Voter fills in the ballot and enters their email, then submits.
2. The ballot is held as **pending** and a confirmation email goes out.
3. They click the link → the votes are counted, and the page says thanks.
4. Their browser is remembered, so if they come back to change picks it saves
   **instantly** — no second email.

This means **one confirmed email = one ballot**. Pending ballots are never
included in tallies or exports, and unclicked links are deleted automatically
after 48 hours.

The **Favorites → Results** screen shows both numbers: confirmed ballots, and how
many are still awaiting confirmation.

You can edit the email's subject and opening line under Customize → MySaline
Options → Saline County Favorites. The link, vote count and expiry note are added
for you.

**Make sure your site can send email.** Confirmation is only as reliable as
WordPress's outgoing mail. Many hosts deliver poorly to Gmail/Yahoo by default,
which would silently cost you votes. Install an SMTP plugin (WP Mail SMTP,
Post SMTP, or your host's own) and send yourself a test before voting opens.
If sending fails, the voter is told to try again rather than being left guessing.

Confirmation can be switched off in the Customizer, but the results screen will
warn you that repeat voting is then possible.

### 5. Results and the drawing

**Favorites → Results** shows the leader and vote totals per category, plus:

- **Export all results (CSV)** — every category, ranked, with counts.
- **Export drawing entries (CSV)** — the emails of voters who met the threshold,
  ready to pick a winner from.

### A note on vote integrity

With email confirmation on (the default), the protections are:

- **One confirmed email = one ballot.** Voters are identified by a salted hash of
  their confirmed address, so a second submission from the same address updates
  their ballot instead of adding a new one.
- **Unconfirmed ballots never count.** They sit in a separate pending table that
  tallies and exports don't read.
- **Picks are re-validated server-side** against the real finalist list, both on
  submit and again at confirmation time. A pick that isn't a genuine finalist is
  discarded, so the ballot can't be tampered with from the browser.
- **Nobody can overwrite someone else's ballot.** Once a browser is confirmed,
  identity is taken from a signed cookie, never from whatever address is typed
  into the form.
- **Single-use, expiring links.** Tokens are stored hashed (a database leak can't
  be replayed as votes), work once, and die after 48 hours.
- **The form can't be used to spam anyone** — confirmation emails are capped per
  address and per IP address per hour.

**What it does not stop:** someone with many real, working email addresses can
still cast one ballot per address. That's the practical ceiling for any contest
that doesn't demand identity documents, and it's a large step up from the old
Google Form. Two habits close most of the remaining gap:

1. Before announcing winners, export the results CSV and glance at any category
   whose leader jumped suddenly — real local voting is fairly flat.
2. The drawing export lists each entrant's email and category count. Batches of
   near-identical addresses (`name+1@`, `name+2@`) are the usual tell.

## Newsletter signup

Customize → MySaline Options → **Newsletter**:

1. Enable it.
2. Paste your provider's **form action URL** (from your Mailchimp/Constant
   Contact embed code — the address in the form's `action`).
3. Set the email field name (Mailchimp uses `EMAIL`), heading, description and
   button text.

The footer signup appears automatically once an action URL is set. There's also
a **MySaline: Newsletter Signup** widget for sidebars.

## Social links

Customize → MySaline Options → **Social Links**. Paste full URLs for Facebook,
Instagram, X/Twitter, YouTube, TikTok, LinkedIn and/or RSS. Blank ones are
hidden. Icons appear in the top bar and footer, and via the
**MySaline: Social Links** widget.

## Footer

Customize → MySaline Options → **Footer**: about text, copyright line, and the
**contact block** (mailing address, phone, email). The four footer widget
columns are under Appearance → **Widgets** (Footer Column 1–4).

## Widgets

Appearance → **Widgets**. Areas: Main Sidebar, Homepage Sidebar, Footer Columns
1–4. Custom widgets included: Advertisement, Newsletter Signup, Social Links,
Recent Posts (with photos), Upcoming Events.

---

## Advanced: changing a custom post type URL slug

The custom post types use these URL bases: `/obituaries/`, `/events/`,
`/businesses/`. If one ever collides with an existing page, change it with a
tiny snippet in a **child theme** or a site plugin — no core files edited:

```php
add_filter( 'mysaline_business_slug', function () { return 'directory'; } );
```

After changing a slug, visit **Settings → Permalinks** and click *Save* once to
refresh the URL rules.
