# Deployed Sites Registry

> **Interim doc.** This file is hand-maintained from filesystem scans. Once the central registration/dashboard cards land ([#9](https://github.com/ericdowns/gm-training-videos/issues/9), [#10](https://github.com/ericdowns/gm-training-videos/issues/10)), the source of truth moves to the live registry and this file becomes a periodic snapshot.

**Last scan:** 2026-07-24
**Master repo version:** v1.4.9

---

## Update urgency

| Status | Count | Action |
|---|---|---|
| 🔴 v1.0.0 | 16 | Pre-update-checker. **Requires manual `cp -r` / rsync** — these installs have no `plugin-update-checker`, so they will never see WP's "Update available" banner. |
| 🟡 v1.1.0 | 2 | Same — manual deploy required. `xomox` is a rebranded fork; reconcile templates before overwriting. |
| 🟢 v1.4.9 (current) | 4 | 2 symlinks (always current by construction) + FSL local and both **production** installs updated 2026-07-24. |

**Net:** 18 of 22 installs are on ≤1.1.0 and cannot auto-update. Every install that *can* self-update is now on 1.4.9.

### Production installs (live, on Flywheel)

Both were on 1.4.8, both carried the dead sample-videos button, and both were updated to 1.4.9 on 2026-07-24 via `wp plugin update training-videos` over SSH — the native update-checker path pulling the GitHub Release zip, not an rsync.

| Site | Prod URL | SSH target | Before → After | Videos | Backup on server |
|---|---|---|---|---|---|
| Cumulus Quality | https://cumulusquality.com | `team+flywheel-hosting+cumulus-quality@ssh.getflywheel.com` | 1.4.8 → **1.4.9** | 15 | `~/training-videos-1.4.8-backup-20260724.tar.gz` |
| Within Reach | https://withinreach.com | `team+flywheel-hosting+within-reach@ssh.getflywheel.com` | 1.4.8 → **1.4.9** | 12 | `~/training-videos-1.4.8-backup-20260724.tar.gz` |

Verified after: version + active status, zero references to the removed `create_samples` code, post counts and `training_videos_resource_url` intact, hooks load without fatals, and `/` + `/training-videos/` returning 200. Cumulus admin was additionally checked in-browser on the exact `?create_samples=1` URL that produced the original warnings — clean.

**No CDN purge was needed.** 1.4.9 changes admin-side PHP only; no frontend template, CSS, or JS output changed, and wp-admin is never edge-cached.

⚠️ **`cumulusquality.com` sits behind an aggressive bot rule** (the Wordfence anti-bot baseline). A UA-less `curl` gets **422**, which reads like an outage and is not one — always pass a real browser user agent when smoke-testing this site.

---

## Dev / demo

| Slug | Path type | URL | Plugin source | Notes |
|---|---|---|---|---|
| `gm-training-videos-dev` | Local (**symlink**) | http://gm-training-videos-dev.local | **Symlink** to `~/Projects/gm-training-videos` | Dev/test sandbox. Edits to the master repo are live immediately — no `cp -r` needed. WP admin: `admin` / `admin`. Seeded with 5 training videos + resource URL configured. |

---

## Sites

| # | Slug | Path type | Installed version | Production URL | Last deployed | Notes |
|---|---|---|---|---|---|---|
| 1 | abri | Local | 1.0.0 | TBD | TBD | needs update; manual deploy |
| 2 | aurotext | Local | 1.0.0 | TBD | TBD | needs update; manual deploy |
| 3 | avicado-hostinger | Hostinger (non-Local) | 1.0.0 | TBD | TBD | non-standard path: `wp-content/plugins/training-videos` (no `app/public/`) |
| 4 | californiaforever | Local | 1.1.0 | TBD | TBD | needs update; was listed as `east-solano-plan` in the 2026-04-29 scan |
| 5 | chandler-conway | Local | 1.0.0 | TBD | TBD | needs update; manual deploy |
| 6 | **cumulus-quality** | Local (**symlink**) + Flywheel | local 1.4.9 · **prod 1.4.9** | https://cumulusquality.com | 2026-07-24 | ⚠️ **Local install is symlinked to the master repo** — any repo edit is instantly live there. Not a copy; do NOT `rm -rf` this path, and don't rely on a Local→Flywheel push to carry it. Prod is a real copy, updated over SSH. Site is behind a bot rule that 422s UA-less curl. |
| 7 | **family-service-lincoln** | Local | 1.4.9 | TBD | 2026-07-24 (local) | Release-zip install. Surfaced the sample-videos bug on 2026-07-24 and was missing from the previous scan. Updated local to 1.4.9 via the WP banner. Prod status unknown — not yet inspected. |
| 8 | foster-display-group | Local | 1.0.0 | TBD | TBD | needs update; manual deploy |
| 9 | hive-to-table | Local | 1.0.0 | TBD | TBD | needs update; manual deploy |
| 10 | hive-to-table-fischers-honey | Local | 1.0.0 | TBD | TBD | needs update; manual deploy |
| 11 | hive-to-table-jamies-honey | Local | 1.0.0 | TBD | TBD | needs update; manual deploy |
| 12 | hive-to-table-kelleys-honey | Local | 1.0.0 | TBD | TBD | needs update; manual deploy |
| 13 | hive-to-table-zeiglers-honey | Local | 1.0.0 | TBD | TBD | needs update; manual deploy |
| 14 | km-associates | Local | 1.0.0 | TBD | TBD | needs update; manual deploy |
| 15 | kros-strain-brewing | Local | 1.0.0 | TBD | TBD | needs update; manual deploy |
| 16 | o-paorg | Local | 1.0.0 | TBD | TBD | needs update; manual deploy |
| 17 | soaring-wings | Local | 1.0.0 | TBD | TBD | needs update; manual deploy |
| 18 | solano-archive-californiaforever | Local | 1.0.0 | TBD | TBD | archive mirror; needs update |
| 19 | solano-californiaforever | Local | 1.0.0 | TBD | TBD | needs update; was `solanocaliforniaforevercom` in the previous scan |
| 20 | steven-ginn-architects | Local | 1.0.0 | TBD | TBD | needs update; manual deploy |
| 21 | within-reach | Local + Flywheel | local 1.4.8 · **prod 1.4.9** | **https://withinreach.com** | 2026-07-24 | ⚠️ **This site has LAUNCHED.** `sites.json` and earlier revisions of this file both said "pre-launch at withinreach.flywheelsites.com" — prod now answers on `withinreach.com` with 12 training videos live. Prod updated to 1.4.9 over SSH; **local is still on 1.4.8** and lags. License is optional on this install. |
| 22 | xomox | Local | 1.1.0 | TBD | TBD | **rebranded fork** — diverged templates (graydark/gold/pagebg). Reconcile per [card #4](https://github.com/ericdowns/gm-training-videos/issues/4) before overwriting |

**Production URLs and last-deployed dates** are TBD — backfill on demand. Cross-reference `~/.claude/skills/sites-dashboard/sites.json` when filling in any row.

### Slug drift since the 2026-04-29 scan

The Local site names moved under `california forever`. Old row → new row:

| 2026-04-29 | 2026-07-24 |
|---|---|
| `east-solano-plan` | `californiaforever` |
| `solanocaliforniaforevercom` | `solano-californiaforever` |
| `staging-solanocaliforniaforever` | `solano-archive-californiaforever` |

`family-service-lincoln` and `cumulus-quality` are new to the registry — both were installed between scans.

---

## Conventions

- **Slug** matches the Local by Flywheel site name (`~/Local Sites/<slug>/`).
- **Path type** = `Local` for standard `~/Local Sites/<slug>/app/public/wp-content/plugins/training-videos`. Anything else is flagged. **Symlink** means the path points at the master repo — treat it as live, never as a copy.
- **Installed version** = read from `training-videos.php` plugin header on the deployed copy, NOT the master repo.
- When a site is rebranded with diverged templates (like Xomox), flag it. The brand-theming card will reconcile these into a per-site config.
- **The 1.4.4 line matters.** Installs below 1.4.4 have no `plugin-update-checker` and can only be updated by hand. Installs at ≥1.4.4 pick up a tagged GitHub Release through WP's native update flow.

## How to refresh this file

```bash
for f in "$HOME/Local Sites"/*/app/public/wp-content/plugins/training-videos/training-videos.php; do
  [ -f "$f" ] || continue
  slug=$(echo "$f" | sed 's|.*/Local Sites/||; s|/app/public.*||')
  ver=$(grep -m1 -E "^\s*\*\s*Version:" "$f" | awk -F: '{print $2}' | tr -d ' \r')
  d=$(dirname "$f"); link=""; [ -L "$d" ] && link=" (SYMLINK)"
  echo "$slug|$ver$link"
done | sort
```

Then repeat with `*/wp-content/plugins/training-videos/` (no `app/public/`) to catch non-Local installs like `avicado-hostinger`.

## Why this exists (and what replaces it)

This is the gap that surfaces every time something changes in the master repo: there's no quick way to answer "which sites are on which version" without grepping the filesystem. The next-step fix is the **central registration system + dashboard** (cards [#9](https://github.com/ericdowns/gm-training-videos/issues/9) / [#10](https://github.com/ericdowns/gm-training-videos/issues/10)) — once a site phones home on activation, the dashboard is the source of truth and this file becomes redundant. This scan is the argument for it: two installs were missing entirely and three slugs had drifted.

Until then: keep this updated whenever you `cp -r` the plugin to a site.
