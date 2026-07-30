# MySaline.com — Site Audit & Modernization Map

_Audit date: July 2026. Source: the live MySaline.com (read-only review — the live site was not modified in any way)._

This document records what the current MySaline.com does, and how the new
theme keeps **the same idea** while making it **modern**. It is the reference
for design and feature decisions in this repository.

---

## 1. What MySaline is

A hyperlocal news publication for **Saline County, Arkansas**, online **since
2007**, self-described as the most-visited media site in the county. It mixes
daily news with public-record content, community listings and heavy local
advertising.

## 2. Navigation (current site)

| Menu | Sub-items |
| --- | --- |
| **Home** | Link Tree · About MySaline & its Owner · Advertise with us · Contact Us · Support local news · Subscribe to Newsletter |
| **Search** | — |
| **News Categories** | All Posts · Business News · Benton Chamber (Ribbon Cuttings, Jobs, Deals & Events) · Dining · Mugshots Archive · 911 Calls · Court Filings · Jobs Listings · Obituaries · Sex Offenders · Marriage License Records |
| **Events** | Recurring Events |
| **Elections, Maps, Columnists** | 2026 Elections Info · Maps (Districts, Wards, Zones) · Elected Officials · Columnists |
| **Business Directory** | — |

## 3. Homepage blocks (top → bottom, current site)

1. Site-wide promo banner (e.g. "Vote in Saline County Favorites until July 29th")
2. Additional promo/announcement banners (library week, etc.)
3. Tagline: "News for Saline County since 2007"
4. Featured event ad banners (Chamber Bingo, Third Thursday)
5. "READ THE NEWS" — grid of ~10 recent articles with thumbnails
6. Pagination (1–3 … 900)
7. Repeated voting banner
8. More featured articles
9. Callout boxes: **Advertise · Elected Officials · Yard Sales · Games**
10. More articles (elections, city council, weather)
11. "Advertise with MySaline" promo box
12. Sidebar widgets: auto-loan ad, Events browse+submit, Yard Sales, daily
    crossword, Elected Officials, Business Directory, Lifestyles magazine promo
13. Quick-link menu bar
14. Full site menu repeated

## 4. Footer (current site)

- **Contact:** MySaline.com · PO Box 165 · Benton, AR 72018 · 501-303-4010 · email
- **Social:** Facebook · Instagram · Twitter
- **Copyright:** © 2026
- Full navigation repeated

## 5. Visual style (current site)

- White background, blue links, black text, sans-serif.
- **Very dense and ad-heavy**: many banners, stacked sidebar widgets, and the
  navigation repeated up to three times on a page.
- Prioritizes access to everything over hierarchy or whitespace.

---

## 6. Same idea, modernized — mapping

| Current site element | New theme equivalent | How it's editable |
| --- | --- | --- |
| Promo banner ("Vote in Saline County Favorites") | **Breaking-news bar** (manual message + link, or auto-from-category) | Customize → MySaline Options → Breaking News |
| Saline County Favorites survey (external Google Form) | **On-site ballot** with search, section tabs, progress meter, autosave (see §9) | Favorites menu + Customize → Saline County Favorites |
| "READ THE NEWS" article grid | **Featured hero** (1 lead + side list) then **Latest News** grid | Mark posts "Featured"; Customize → Homepage |
| Callout boxes (Advertise / Elected Officials / Yard Sales / Games) | **Homepage Quick-Link cards** (icon + title + link) | Customize → Homepage Quick Links |
| News Categories menu + section pages | **Configurable homepage sections** by category + standard category archives | Customize → Homepage Sections; Appearance → Menus |
| Event banners + Events listings + "submit your own" | **Community Events** custom post type + Upcoming Events blocks/widget | Events menu in dashboard |
| Obituaries | **Obituaries** custom post type (portrait, dates, service details) | Obituaries menu |
| Business Directory | **Businesses** custom post type + Business Categories taxonomy | Businesses menu |
| Ad banners everywhere | **Advertisements** custom post type with placement **zones** + run dates | Advertisements menu |
| Newsletter subscribe | **Newsletter** signup (Mailchimp/any provider, no code) | Customize → Newsletter |
| Facebook / Instagram / Twitter | **Social links** (+ YouTube, TikTok, LinkedIn, RSS) | Customize → Social Links |
| PO Box / phone / email in footer | **Footer contact block** | Customize → Footer |
| Repeated menus / clutter | **One** sticky primary nav with dropdowns; a compact top bar; a structured 4-column footer | Appearance → Menus |
| Dense, ad-first layout | Clean grid, generous spacing, clear typographic hierarchy, mobile-first responsive | Colors via Customize → Branding |

## 7. Content preserved unchanged

The theme uses the standard WordPress template hierarchy, so **all existing
posts, pages, categories, tags, authors, media, archives and URLs keep working
exactly as they do now**. The custom post types add *new* content only; they
never touch or rename existing content. Mugshots, 911 calls, court filings,
marriage/sex-offender records, dining, jobs and columnists all remain ordinary
posts/categories and render through the modernized archive and single templates.

## 8. Items that are content, not code (owner adds via dashboard)

These existed on the old site and are recreated simply by adding pages, menu
items or listings — no theme code required:

- About / Advertise / Contact / Support Local News / Link Tree → **Pages** + menu
- Elected Officials, Maps, Columnists, 2026 Elections → **Pages** or categories
- Yard Sales, Games/crossword → **Pages**, or a Quick-Link card pointing to them
- Recurring Events → **Events** entries (or an Event Category)

## 9. Saline County Favorites — the survey, audited

The annual reader's-choice survey is one of the site's biggest traffic drivers,
and the hardest thing on it to actually use.

**How it works today**

- Annual cycle: nominations (mid-June) → nominees verify spelling → finalists
  published → voting (Jul 20 5:00 AM – Jul 29 6:00 PM) → winners ~Aug 7.
- **~155+ categories** in four sections: Food (~25), Businesses (~80+),
  People (~30), Places & Things (~20).
- Voting happens in an **external Google Form**; the site links out to it.
- Finalists are published on a **separate, very long plain-text page**.
- Prize rule: vote in **≥20 categories** and **at least one in each of the four
  sections** to enter a **$100 drawing**.
- Only a voter's most recent pick per category counts; re-voting is allowed.
- Instructions include "It's FREE", "no card or payment required", "Scroll past
  any ad", and "sign into Google to save progress".

**Friction found**

| Problem | Consequence |
| --- | --- |
| 155+ questions in one linear form | Exhausting; most voters abandon partway |
| No progress indicator | Voters can't tell if they've hit the 20-category / 4-section prize threshold |
| Finalists on a different page from the ballot | Constant bouncing between a giant list and the form |
| No search or filter | Finding "Best Plumber" means scrolling past 80+ business categories |
| External Google Form | Leaves the site — loses ad impressions and branding |
| Saving progress needs a Google sign-in | Contradicts "no account needed"; drops anyone not signed in |
| Long lists on mobile | Poor navigation on the majority of traffic |

**How the new ballot fixes each one**

| Fix | Detail |
| --- | --- |
| **Instant search** | Type "plumb" → only Best Plumber shows. Escape clears. |
| **Section tabs** | Vote Food / Businesses / People / Places one section at a time. |
| **Live progress meter** | "7 of 20 needed" + a chip per section that lights up, and an explicit "✓ You qualify for the drawing". |
| **"Hide ones I've done"** | A 155-category ballot shrinks as you work through it. |
| **Finalists on the ballot** | Nominees are the radio options — no separate page. Optional link per finalist. |
| **On-site** | Keeps her ads, branding and traffic; nothing leaves the site. |
| **Autosave, no account** | Picks persist in the browser; a warning fires if you leave with unsubmitted picks. |
| **Skip freely** | Nothing required. Email optional, only for the drawing. |
| **Mobile-first** | Sticky compact progress bar, 44px tap targets, single-column options, scrollable tabs. |
| **Re-voting preserved** | A UNIQUE key per (voter, year, category) reproduces "most recent pick counts". |

**Owner-side**

Hand-entering 155 categories isn't realistic, so **Favorites → Import Ballot**
takes the existing plain-text finalist lists in one paste (`##` section, plain
line = category, `-` = finalist, optional `| URL`). Re-importing matches by name
and updates rather than duplicating. Results screen shows per-category leaders
with **CSV exports** for both full results and qualified drawing entries.

**Vote integrity.** Ballots require **email confirmation** (double opt-in): votes
are held pending and only counted once the voter clicks a single-use, 48-hour link,
so **one confirmed email = one ballot**. Tokens are stored hashed; picks are
re-validated server-side against the real finalist list on submit *and* at
confirmation; once a browser is confirmed, identity comes from a signed cookie
rather than the submitted address, so nobody can overwrite another voter's ballot;
confirmation emails are rate-limited per address and per IP so the form can't be
used to mail-bomb someone.

**Remaining limit:** someone with many working email addresses can still cast one
ballot per address — the practical ceiling short of demanding ID, and far stronger
than the old Google Form. `CONTENT-MANAGEMENT.md` documents this plus the two
sanity checks (results CSV for implausible jumps, drawing CSV for `name+1@`-style
address batches).

**Operational dependency:** confirmation is only as good as the site's outgoing
mail, so an SMTP plugin and a test send before voting opens are required, not
optional — noted in the deployment checklist.

## 10. Deliberately not carried over

- Triple-repeated navigation menus (kept to one primary + optional top/footer).
- Uncontrolled stacked ad banners (replaced by managed ad zones so the owner
  keeps the revenue model but the page stays clean and fast).
