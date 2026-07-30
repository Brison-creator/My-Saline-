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
