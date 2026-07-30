# Streamlining MySaline — Site Tree & Ad Inventory

_Based on a read-only crawl of the live site, July 2026._

Goal: keep **every page that exists today** but reduce the number of decisions a
reader has to make, using the structure big news sites (Fox News, local Sinclair
/ Gannett properties) settled on — one authoritative top nav with grouped
dropdowns, section fronts, and a predictable ad grid.

---

## 1. What's wrong right now

Findings from crawling the live site, including the mobile screenshot the owner
sent:

| Problem | Evidence |
| --- | --- |
| **Navigation appears 3× on one screen** | Top nav, then a "Click to browse:" run-on link wall, then "EXPLORE MYSALINE.COM WITH THIS MENU 👇" and a full menu tree — all above the fold on mobile |
| **Browse links are a wall of text** | ~10 links separated by bullets, wrapping mid-phrase ("Benton / Chamber News"), with tap targets far under the 44px minimum |
| **Menu text goes stale** | Still reads "2024 Elections" in mid-2026 — hardcoded labels rot |
| **~30 top-level destinations** | 11 items under News Categories alone; no grouping by intent |
| **An active section is hidden** | Sports has 60 pages and posts weekly, but appears only in the text wall — not in the real nav |
| **Obituaries aren't per-person** | Bundled into daily digest posts ("Obituaries from Saline County July 29th") across 121+ pages, so searching a name is unreliable |
| **No filtering where it's needed** | Events is one long chronological list; the directory has no category filter |
| **Ads are decorative, not inventory** | Banners hand-placed between content blocks, repeated, with no defined slots — so nothing is countable or sellable by position |
| **Content pushed below the fold** | On mobile, a reader scrolls past two menus and two promos before reaching a story |

The site isn't short on content — it's short on **hierarchy**. Everything is
offered at the same volume, so nothing reads as primary.

---

## 2. The streamlined site tree

**30+ destinations → 7 top-level sections.** Nothing is deleted; everything is
grouped by *why* a reader came.

```
┌─ Utility bar ─────────────────────────────────────────────────────┐
│  Today's date    About · Advertise · Contact · Newsletter · Social │
└───────────────────────────────────────────────────────────────────┘
┌─ Logo ──────────────────────────────────────────── Search ────────┐
└───────────────────────────────────────────────────────────────────┘
┌─ Primary nav (sticky) ────────────────────────────────────────────┐
│ NEWS  RECORDS  OBITUARIES  THINGS TO DO  BUSINESS  GOVERNMENT  ⭐ │
└───────────────────────────────────────────────────────────────────┘
```

### 1. News  *(mega, 3 columns)*
| Places | Topics | Voices |
| --- | --- | --- |
| Saline County | Sports | Columnists |
| Benton | Schools | Opinion |
| Bryant | Dining | Photo galleries |
| Alexander / Haskell | Community | |

*Why:* one home for editorial. **Promotes Sports into the real nav** — it earns
it at weekly cadence.

### 2. Public Records  *(mega, 2 columns)*
| | |
| --- | --- |
| Mugshots Archive | Court Filings |
| 911 Calls | Marriage Licenses |
| Sex Offender Registry | Jobs Listings |

*Why:* the single biggest win. Five separate menu items today, all serving the
same "look something up" intent. Readers hunting records now have one door.
(Jobs sits here because it's also a recurring list people check, not a story.)

### 3. Obituaries
Direct link to the archive, plus *Submit an obituary*.

*Why:* high-traffic and emotionally urgent — it should never be nested. See §5
for the per-person fix.

### 4. Things To Do  *(mega, 2 columns)*
| | |
| --- | --- |
| Events Calendar | Yard Sales |
| Recurring Events | Daily Puzzle |
| Submit an Event | Dining Guide |

*Why:* collapses the "what's happening this weekend" cluster that's currently
scattered between the nav, the text wall and the sidebar.

### 5. Business
Business News · Business Directory · Benton Chamber · Bryant Chamber ·
*Advertise with us* · *List your business*

### 6. Government & Elections
2026 Elections · Elected Officials · District & Ward Maps · Meeting Calendar

*Why:* label the **year dynamically**, so "2024 Elections" can never appear
again.

### 7. ⭐ Saline County Favorites
Vote · Finalists · Past Winners. Lives in the nav only during the season, then
becomes *Past Winners*.

### What gets removed
- The **"Click to browse:"** text wall — fully replaced by the nav and the
  homepage quick-link cards.
- The **"EXPLORE MYSALINE.COM WITH THIS MENU 👇"** duplicate tree — the footer
  covers it.
- Duplicate promo banners repeated within one page.

That's roughly **two full screens of scrolling returned to actual news** on
mobile.

### Building it
In **Appearance → Menus**, create the seven top-level items and drag children
under them. For the three big ones, add the CSS class `mega` (or `mega-3` for
News) via *Screen Options → CSS Classes* — the theme flows those dropdowns into
columns instead of a long tower. `npm run seed` already builds this exact menu on
the dev site, so you can click it before committing to it.

---

## 3. Ad inventory

Today ads are placed by hand wherever there's a gap. The theme turns each
position into a **named zone** you can sell, schedule and count — matching what
the Advertise page already offers ("header, embedded in the list of articles,
embedded in the articles themselves, directory pages").

| Zone | Where it renders | Sold as | Typical creative |
| --- | --- | --- | --- |
| `header` | Top of every page, beside the logo | Leaderboard | 970×90 / 728×90 |
| `homepage_top` | Directly under the featured hero | Homepage takeover | 970×250 |
| `in_feed` | **Between story cards** in the homepage grid and every archive | Native in-feed | 600×500 or card-shaped |
| `homepage_mid` | Between homepage sections | Mid-page banner | 970×250 |
| `sidebar` | Beside articles and archives, sticky | Rail | 300×250, 300×600 |
| `in_content` | Auto-inserted after the 3rd paragraph of an article | In-article | 300×250 responsive |
| `below_content` | Under the article, above related stories | End-of-read | 728×90 |
| `directory` | Between directory listings | Directory sponsor | card-shaped |
| `newsletter` | Beside the newsletter signup | List sponsor | 300×100 |
| `sticky_mobile` | Dismissable bar pinned to the bottom, **mobile only** | Mobile anchor | 320×50 |
| `footer` | Above the footer | Run-of-site | 728×90 |

**Every zone supports:** multiple ads rotating at random, start/stop dates for
scheduling, an image + click URL *or* pasted ad-network code, and a per-advertiser
sponsor name. Global on/off plus the "Advertisement" label are in the Customizer.

### Why this is worth money

- **`in_feed` and `directory` are new** and were added specifically because the
  Advertise page already sells "embedded in the list of articles" and "directory
  pages" — the theme previously had nowhere to put them.
- **Named positions can be priced.** "Homepage leaderboard, one week" is a
  product; "a banner somewhere on the page" isn't.
- **Scheduling ends manual swaps.** Set the dates once; ads appear and expire on
  their own.
- **`sticky_mobile` monetises the majority of traffic** without another banner in
  the article body, and readers can dismiss it.

### Density guidance
Fox-style density without the clutter: **leaderboard + one in-feed per ~6 cards +
sidebar rail + one in-article + mobile anchor.** That's more sellable inventory
than the current layout while *looking* calmer, because each slot has a fixed
home instead of appearing wherever there's room.

---

## 4. Homepage, in priority order

1. Breaking bar — only when something is genuinely breaking
2. Featured hero: one dominant lead + 4 secondary
3. `header` / `homepage_top` leaderboard
4. Quick-link cards (Advertise · Officials · Yard Sales · Events)
5. Latest News grid, in-feed ad after every 3rd card
6. Two or three category sections (Business, Around the County, Dining)
7. Upcoming Events · Recent Obituaries · Business Spotlight
8. Newsletter band with sponsor slot
9. Footer: contact, sections, community, social

The reader hits a headline **immediately** — no menu walls first.

---

## 5. Two content fixes worth scheduling

These are content-shape problems the theme can't fix on its own:

1. **Obituaries should be one post per person.** They're currently bundled into
   daily digests, so name search is unreliable and no obituary has its own
   shareable link — the thing families most want. The theme's Obituary type
   supports per-person entries with a portrait, dates and service details. Going
   forward, publish one per person; the existing digests can stay as archive.

2. **Events and directory need filters, not longer lists.** The theme sorts
   events by date automatically and gives the directory real categories, which
   is most of it. Worth adding a "this weekend" filter once volume justifies it.

Also: event and obituary submissions currently go through **Google Forms**. Same
pattern as the Favorites ballot — worth bringing on-site later so submissions
land straight in the dashboard as drafts. Not urgent; noted so it isn't lost.
