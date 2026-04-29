# Claude-driven plugin onboarding

This file is for **Claude Code agents** running inside a client's WordPress install. It is not client-facing documentation. When the user says something like "set up training videos" or "run the training videos onboarding," read this file first, then walk the wizard at `Training Videos → Onboarding` using the client's actual brand and fonts.

The wizard has three steps: brand colors, fonts, optional Loom bulk import. Step 4 (license key) lives on the Settings page.

---

## Where to find brand inputs

You are inside a G&M-built WordPress theme. The brand tokens are almost always defined in the theme's Tailwind config or `theme.json`. Check both before falling back to inspection.

### Brand colors

Order of preference:

1. **`theme/tailwind.config.js`** — look in `theme.extend.colors` (or `theme.colors`). Typical token names: `navy`, `orange`, `cream`, `sand`, `green`. Pick:
   - **Primary** = the darkest brand color that's used for headers/dark surfaces (usually `navy` or whatever the brand's "anchor dark" is).
   - **Secondary** = the brand's accent CTA color (usually `orange`, `green`, or the warm accent).
2. **`theme/style.css` / `theme.json`** — fallback if no Tailwind config. Block themes use `theme.json` `settings.color.palette`.
3. **Theme's masterdoc / brand guide** — last resort. Pull from the project's masterdoc or `~/.claude/people/clients/<slug>.md`.

The wizard auto-derives the other 5 surfaces (page bg, body text, accent hover, borders, card bg) from those two via WCAG-AA HSL math. Do not try to hand-fill the derived swatches — let the wizard compute them.

### Fonts

Order of preference:

1. **`theme/tailwind.config.js`** — `theme.extend.fontFamily.sans` and `.serif`. The first font in each stack is the "real" family name.
2. **`theme/inc/styles-scripts.php`** (or wherever `wp_enqueue_style` lives) — search for `fonts.googleapis.com` URLs. The `family=` query param tells you which Google Fonts the theme loads.
3. **`theme/theme.json`** — block themes list typography under `settings.typography.fontFamilies`.

Fill the wizard:

- **Heading family** = theme's sans (or the heading-specific font if the theme distinguishes).
- **Body family** = theme's serif if the theme uses one for body, otherwise the sans.
- **Font URL** = the Google Fonts URL for whichever family is NOT self-hosted. If both are self-hosted (e.g. licensed Grilli Type fonts), leave the URL blank — the plugin can't load them in its own template context, and the family name will fall through to system fonts. That's acceptable; this is an internal training library, not a marketing surface.

If the theme self-hosts a licensed font (GT Standard, Söhne, Founders Grotesk, etc.) put the family name in the field anyway. The plugin templates will fall through to the system stack and render legibly. Don't try to copy the licensed font files into the plugin — that breaks the license and the plugin won't ship them on update.

### License key

Stored in 1Password. Item name pattern: `G&M Training Videos License — <client name>`. If no item exists, the client doesn't have one yet — leave the field blank. The plugin soft-fails: the front-end Training Library renders normally without a key, only the wp-admin notice persists.

---

## Step-by-step wizard fill

1. Open `Training Videos → Onboarding` in wp-admin.
2. **Brand colors:** paste Primary and Secondary hex values. Watch the live preview re-derive the palette. If the derived `body text` swatch fails contrast against the page background (rare, only on unusual brands), bump Primary darker or page background lighter.
3. **Fonts:** paste Heading family, Body family, and the Google Fonts URL (or leave URL blank for self-hosted fonts). The wizard pre-fills with whatever it auto-detected from the parent theme — if the auto-detection is wrong, overwrite it.
4. **Loom import (optional):** skip unless the client has handed over a list of Loom share URLs. If they have, paste one URL per line. The plugin pulls title/description/thumbnail from Loom's public oEmbed; producer-authored descriptions are honored verbatim.
5. Click **Save & Finish**.
6. Verify: navigate to `/training-videos/` on the front-end and confirm the header bar is the brand's primary color and the empty-state CTA is the brand's secondary color.
7. Set the license key at `Training Videos → Settings → License` if 1Password has one.
8. Set the documentation resource (Google Doc URL + title + short description) at `Training Videos → Settings`. This drives the resource card on the archive page.

If the client's brand colors are unusual (e.g. very pale primary, or two warm colors with low contrast), open `Training Videos → Settings → Advanced` after the wizard and hand-tune the derived surfaces.

---

## Common tweaks after the wizard

| Symptom | Where to fix |
|---|---|
| Header bar feels too dark / too saturated | Settings → Advanced → `Heading + header bg` (override derived value) |
| Page background feels too warm/cool for the brand | Settings → Advanced → `Page background` |
| Body text contrast too low on the page background | Settings → Advanced → `Body text` (bump darker) |
| CTA buttons hard to see | Settings → Advanced → `Accent (CTAs)` (override secondary) |
| Need a different welcome video card | Settings → Welcome Video |
| Need to swap the documentation resource link | Settings → Documentation Resource |

---

## What this file is NOT for

- Plugin development changes — those go in the master repo's `CLAUDE.md` and the relevant `inc/` file.
- Plugin architecture decisions — see `docs/ARCHITECTURE.md`.
- Per-site deployment notes (which client is on which version) — see `docs/SITES.md`.
- Loom-side workflows (transcript pulls, folder scrapes, description sync) — see the `/loom` skill in `~/.claude/skills/loom/`.

This file is **only** the runbook for "an agent is sitting in a client install and needs to populate the onboarding wizard with the client's actual brand."
