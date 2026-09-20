# Round 1 — The living training library

## 1. The problem, in Eric's words

> "We do Loom videos where Brooke walks around the site and shows the client where to use
> things. The problem is, as soon as something changes, the client wants a new section, or
> something moves, or WordPress upgrades and changes the look and feel. These videos
> essentially become antiquated."

> "There are times Brooke's taking 5-10 hours to create these videos, then for them only to be
> dated 3-6 months later when the content changes."

> "This is a premium offering that we deliver to clients."

At $225/hr across 22 installs, one pass over the base is about $37k. Refreshed twice a year for
five years it is roughly $371k. Nobody refreshes on that cycle, so the cost is actually paid in
clients running on training that stopped being true a quarter after delivery.

## 2. What the surface is for

A WordPress plugin, already on ~22 client sites, that today shows a login-gated grid of Loom
videos. We are turning it into a library of **lessons** that a generator writes from the site's
own field definitions, and regenerates when the site changes.

Its readers are not developers. On Benson Theatre they are three board members with the WordPress
`editor` role: Jim Schneider, Heather Tedesco, Miranda Hindman. They open this when they are
already stuck, usually once every few months.

## 3. The fixed data shape

A lesson is one post. Every lesson has: **title**, **description**, **kind**, and a **freshness
state**. What it carries beyond that depends on the kind.

| Kind | Carries | Note |
|---|---|---|
| `loom` | A Loom embed, poster image, duration | What every existing install has today. Must keep working unchanged. |
| `steps` | An ordered list of steps, each with text and one screenshot | Generated from the site's field definitions |
| `clip` | A silent MP4 (10-40s) + WebVTT captions + an animated GIF fallback | Captions are burned into the GIF, selectable on the MP4 |
| `shot` | One annotated screenshot with a ring drawn on the thing being described | |

**Freshness is a first-class state, not a badge bolted on.** Three values:

- `current` — the fingerprint matches the site
- `stale` — the site changed underneath this lesson; it may now be wrong
- `regenerating` — the generator is redrawing it right now

A lesson also knows **what part of the site it teaches** (a wp-admin screen: "Site Settings >
Contact & Organization", "Events > Add New Event", "Pages > Donate").

## 4. Rulings that stand

1. **Brand comes from the client, not from us.** The plugin derives a full palette from **two**
   hex values the client gives at onboarding, through `inc/brand-derive.php`, producing seven
   semantic roles: `accent`, `accent_alt`, `bg`, `card_bg`, `border`, `heading`, `text`. There is
   a WCAG AA guard on the derivation. **Design in those seven roles only.** Do not introduce a
   colour that is not one of them.
   **Draw every direction twice**, in two unrelated client brands, to prove the system survives
   it. Use Benson Theatre (`#C7B182` brass on near-black ink) and one cool, high-contrast brand of
   your choosing. A direction that only works in one of them has failed.
2. **Known drift to design past:** `css/training-videos.css` still hardcodes G&M literals
   (`--tv-color-brick`, `--tv-color-navy`, `--tv-color-beige`, `--tv-color-orange`,
   `--tv-color-stone-blue`). Those are leftovers. Express everything in the seven roles.
3. **Login-gated, always.** There is no logged-out state to design.
4. **The existing Loom lesson must render unchanged.** ~22 installs have only `loom` posts and no
   kind set. They upgrade into whatever you draw, so `loom` is the default and it cannot regress.
5. **Fonts are detected from the client's theme**, not chosen by us (`inc/font-detect.php`).
   Assume a serif display and a neutral sans are both possible.
6. Type and control metrics come from the repo. Read `css/training-videos.css`,
   `css/training-standalone.css`, `templates/archive-training_videos.php` and
   `templates/single-training_videos.php` rather than inventing a scale.

## 5. What to explore

Two or three directions, each covering **three surfaces**:

**A. The library index.** What the client lands on. The open question is the organising
principle: an ordered course with a "start here", versus a reference index keyed to where you
are in wp-admin, versus something else. Mixed kinds must sit together without looking like a
broken grid: a 40-second clip and a six-step written lesson are peers.

**B. A single lesson.** One shell that holds all four kinds credibly. The hard case is `steps`,
which is text and screenshots rather than a single media object, and which can run to a dozen
steps.

**C. Freshness.** This is the part with no precedent and the reason the product exists.
- What does a `stale` lesson look like to the **client**? It must be honest without being alarming.
  A board member seeing "this may be out of date" should trust the library more, not less.
- What does `regenerating` look like?
- And the operator view: **a G&M-side panel listing every client site and which lessons went
  stale.** This one is G&M-branded, not client-branded, and it is what turns this from a
  deliverable into a retainer line item.

## 6. Board conventions

- Artboard labels: `R1 · <surface> · <width> · <direction><brand>`, e.g.
  `R1 · library index · 1280 · A-benson`.
- Widths **1280 and 375** for every surface and every direction.
- Anything new that is not already in the repo gets flagged **PROPOSAL** with one line of reason.
- Tag the CHANGE LEDGER entries `[DESIGN]` / `[FUNCTIONALITY]` / `[COPY]`.
- Include a **TECHNICAL REQUESTS** block, separate from the ledger, naming the repo file for
  anything that is engineering wearing design clothes.
- **Hold the package until Eric picks a direction.**

## 7. Open questions (explore, do not block)

- Should a stale lesson still be readable, or should the library hide it until it is regenerated?
  Our instinct is readable with a clear mark, because a slightly old instruction beats no
  instruction, but we have not tested that on a client.
- Does the client ever want to know *what* changed, or only that something did?
- Is there a case for the library telling a client "you have never opened this one"?
- Where does search belong, if anywhere, at this library size (10-30 lessons)?
