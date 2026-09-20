# Design rounds

One folder per round under `rounds/`, dated. The brief, the before-shots, the board captures and
the returned package live together so the connector, the implementing agent and Eric all point at
one path.

**The truth doctrine, in three bullets:**

- The repo is the product system. A returned board is a proposal, never truth.
- A board becomes truth only when it lands in code in the same commit that records it here.
- Directions Eric did not pick stay on the board as the record. They are not deleted, not built.

| Round | Surface | Outcome |
|---|---|---|
| [2026-09-20 living-library](rounds/2026-09-20-living-library/) | Library index, lesson page, freshness states, operator panel | **R1 board returned, awaiting Eric's pick.** 14 artboards, [board](https://claude.ai/design/p/e961c17c-968e-4651-9d01-84a2b8750edb). A "The Path" (ordered course), B "The Desk" (reference index), C the G&M operator panel on Graphite Signal. Both client directions native WP admin after Eric's mid-round correction (REV 2). Package HELD. |

## R1 — the three PROPOSAL items, which are Eric's to rule on

1. **Accent, and only on three things**: the h1 rule, the active item in the library's own nav, and
   the progress fill. Exactly the reach of a WordPress admin colour scheme. *Its reason: it makes
   the library feel bought rather than bolted on.*
2. **Group header rows inside the list table.** Not a stock `wp-list-table` feature. *Its reason:
   the premise of Direction B is that you arrive knowing the screen and not the words, and a flat
   table loses that.*
3. **One dismissible dashboard notice per regeneration batch, never per lesson.** *Its reason:
   three board members opening WordPress a few times a year will read one notice; five notices
   become wallpaper, and wallpaper is how the one that matters gets missed.*

## Carried into the build regardless of the pick

- **The UI copy is full of em-dashes.** Eric's standing rule kills them everywhere, including
  interface copy. Fix at build time rather than re-litigating on the board.
- The mockups invent a user called "Marianne". Cosmetic.
- The freshness copy is the strongest thing on the board and should survive verbatim: *"This
  screen changed on 12 September. The words are still right, one picture isn't. We're redrawing
  it."* It answers the brief's open question (a stale lesson stays readable) better than the
  question was asked.
