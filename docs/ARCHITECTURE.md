# Architecture — Training Videos System

The system is two repos that work together:

```
                           ┌──────────────────────────────────────────────┐
                           │  CLIENT WORDPRESS SITE                       │
                           │  (per-install, ~20 sites and growing)        │
                           │                                              │
                           │   training-videos plugin (v1.4.5+)           │
                           │   ┌─────────────────────────────────────┐    │
                           │   │ • CPT: training_videos              │    │
                           │   │ • Settings + Onboarding wizard      │    │
                           │   │ • License key field                 │    │
                           │   │ • Daily heartbeat cron              │    │
                           │   │ • plugin-update-checker             │    │
                           │   └──────────────┬──────────────────────┘    │
                           └──────────────────┼──────────────────────────┘
                                              │
                  ┌───────────────────────────┼─────────────────────────────┐
                  │                           │                             │
       1. POST heartbeat (daily)              │              2. POST license/validate (on demand)
       Authorization: none                    │              Authorization: Bearer <license_key>
                  │                           │                             │
                  ▼                           ▼                             ▼
                           ┌──────────────────────────────────────────────┐
                           │  G&M MAINTENANCE PORTAL                      │
                           │  maintenance.grainandmortar.com              │
                           │  (Next.js 15 + Prisma + Vercel + Neon)       │
                           │                                              │
                           │   ┌──────────────────────────────────────┐   │
                           │   │  /api/training-videos/heartbeat      │   │
                           │   │      → upsert TrainingVideosSite     │   │
                           │   │      → append TrainingVideosHeartbeat│   │
                           │   │                                      │   │
                           │   │  /api/training-videos/license/validate│  │
                           │   │      → look up site by site_url      │   │
                           │   │      → check key match + expiry      │   │
                           │   │      → return {valid, tier, expires_at}│ │
                           │   │                                      │   │
                           │   │  /admin/training-videos              │   │
                           │   │      → Eric's registry dashboard     │   │
                           │   │        (issue/revoke license, view   │   │
                           │   │         heartbeat history)           │   │
                           │   └──────────────────────────────────────┘   │
                           └──────────────────────────────────────────────┘

       3. GitHub Releases (separate channel for plugin updates)
                  │
                  ▼
                           ┌──────────────────────────────────────────────┐
                           │  GitHub Releases (public)                    │
                           │  github.com/ericdowns/gm-training-videos     │
                           │                                              │
                           │  Tag v*.*.* → Action builds zip → attaches   │
                           │  to release. Each install polls daily via    │
                           │  plugin-update-checker → "Update available". │
                           └──────────────────────────────────────────────┘
```

## What the plugin does (per install)

1. **WordPress admin setup** — On first activation, redirects to a 3-step Onboarding wizard:
   - Brand colors (primary + secondary; the other 5 surfaces auto-derive via HSL math with WCAG AA guard)
   - Fonts (auto-detected from the active theme's `theme.json` for block themes, or from registered Google Fonts URLs for classic themes)
   - Bulk import (paste a list of Loom share URLs; oEmbed populates title + description + thumbnail per URL)
2. **Per-video edit screen** — Loom URL → auto-fetch title + description + thumbnail. Description is editable; manual edits are preserved on subsequent saves. Featured Image overrides the auto-fetched thumbnail if set.
3. **License + heartbeat** — A daily WP cron POSTs a snapshot to the registry. License key in Settings re-validates against `/license/validate` on save (and on the same daily cadence). Soft-fail: front-end always renders; admin gets nagged if license is missing or invalid.
4. **Auto-updates** — plugin-update-checker polls the GitHub repo. New release tag → "Update available" in the WP plugins list within ~12h.

## What the portal does (operator side)

1. **Receives heartbeats** — every install upserted by `siteUrl`, with a rolling history of pings.
2. **Validates licenses** — bearer-auth lookup. Returns `{valid, tier, expires_at}` or a typed `reason` for failures (`invalid_key`, `expired`, `revoked`).
3. **Surfaces a registry tab** at `/admin/training-videos`:
   - Table view (every install, with status badge, heartbeat freshness, DEV flag for `*.local` hosts)
   - Detail page per install (license management form, heartbeat history, optional client link)
   - One-click license generation (`GM-XXXXXX-XXXXXX-XXXXXX`)

## Wire format

### `POST /api/training-videos/heartbeat`

**Body:**
```json
{
  "site_url":       "https://example.com",
  "plugin_version": "1.4.5",
  "wp_version":     "6.9.4",
  "php_version":    "8.2.13",
  "multisite":      false,
  "active_theme":   "twentytwentyfive",
  "license_key":    "GM-XXXXXX-XXXXXX-XXXXXX",
  "video_count":    11,
  "is_local":       false,
  "sent_at":        1714512000
}
```

**Response:** `{ "ok": true }` (200). Plugin sends fire-and-forget; doesn't read response.

**Validation:**
- `site_url` required → 400 if missing
- Empty body → 400 invalid JSON
- License key absent doesn't block — just stores `null` and lets the install run unlicensed

**DB writes:**
- Upsert `TrainingVideosSite` by `siteUrl` (creates if new, updates plugin/wp/php/theme/videoCount/isLocal/lastHeartbeatAt)
- Append `TrainingVideosHeartbeat` row with the full payload as JSON for history

### `POST /api/training-videos/license/validate`

**Headers:** `Authorization: Bearer <license_key>`
**Body:**
```json
{ "site_url": "https://example.com", "plugin_version": "1.4.5" }
```

**Response (200):**
```json
{ "valid": true, "tier": "standard", "expires_at": "2026-12-31T00:00:00.000Z" }
```
or
```json
{ "valid": false, "reason": "invalid_key" }   // wrong key OR site not registered
{ "valid": false, "reason": "expired" }        // expiresAt in the past
{ "valid": false, "reason": "revoked" }        // status = "revoked"
```

**Errors:**
- Missing bearer → 401 `{ "valid": false, "reason": "missing_bearer" }`
- Missing site_url → 400 `{ "valid": false, "reason": "missing_site_url" }`

The endpoint **does not reveal whether the site exists** — both wrong-key and unknown-site return `invalid_key`. This is intentional.

## Plugin caching + grace logic

The plugin does NOT call `/license/validate` on every page load. Caching:

- **License status cache**: `set_transient( ..., 24 * HOUR_IN_SECONDS )` after a confirmed `valid: true | false` response. So at most one validation call per install per day.
- **Server unreachable**: if the call returns `WP_Error` (network) or 5xx, the cache is NOT set, the stored option flips to `unreachable`, and the **last-good timestamp** is consulted. If the last `valid: true` was within 7 days, the install treats itself as `active` (grace window). Past 7 days, status is `unreachable` and admins see no nag (server outage is our problem, not theirs).
- **Key change**: changing the license key in Settings invalidates the transient and triggers an immediate re-validation via the `update_option_*` hook.

This guarantees:
- A single network outage doesn't cascade to "invalid" across 20+ installs
- A revoked license takes ≤24h to take effect (transient TTL)
- Manual re-validation happens on Settings save

## States

A given install at any moment is in one of:

| Status | Means | UI |
|--------|-------|----|
| `unlicensed` | No `licenseKey` set in `wp_options` | yellow `notice-warning` admin nag |
| `active` | Last validation returned `valid: true` (within 24h transient) OR within 7-day grace after server outage | no nag |
| `invalid` | Last validation returned `valid: false` (any reason) | red `notice-error` admin nag |
| `unreachable` | Last validation got network/5xx, AND grace window expired | no nag (intentional — silent) |

Front-end templates render in **all** states. The plugin never breaks a site over license state.

## Security model

- **Heartbeat is unauthenticated.** Anyone can POST any `site_url` and create a stub registry row. This is acceptable: heartbeats don't grant any privilege; they just populate the dashboard. Spam rows can be filtered/deleted by Eric in the admin tab.
- **License validation is bearer-only.** The server checks the presented key against the stored key for the matching `siteUrl`. There's no side-channel for guessing — `invalid_key` is returned for both "wrong key" and "site not registered" so attackers can't enumerate registered sites.
- **Repo is public.** Source-code visibility is intentional — auto-update flow can't gate a download behind auth without per-install PATs (which we explicitly rejected). The license layer is the gate, not the source code. Older WP plugins (paid + free) routinely ship public source.

## Failure surfaces — what breaks if X goes down

| Failure | Plugin impact | Operator impact |
|---------|---------------|-----------------|
| **Portal heartbeat endpoint 5xx for hours** | None visible — `error_log` entry, next day retries | Heartbeats missing from dashboard until recovered |
| **Portal heartbeat endpoint down for >7 days** | All installs in grace expire → `unreachable` cached → no nag (intentional) | Loss of registry view until back |
| **GitHub releases API down** | plugin-update-checker silently retries on next cron tick — no UI break | Manual deploys via GitHub Releases page still work |
| **Loom oEmbed down** | Existing posts keep their cached descriptions/thumbnails (7-day transient + sideloaded local copies). New post saves get stub data; admin can manually populate | None — operator side doesn't depend on Loom |
| **Plugin file deleted from a site** | Site loses CPT but core WP keeps running | Site stops phoning home → "Last heartbeat: 5 days ago" surfaces in dashboard |

## File map

### Plugin (this repo)

| Path | Owns |
|------|------|
| `training-videos.php` | Plugin bootstrap, CPT registration, meta boxes, Settings page, plugin-update-checker wire-up, activation/deactivation hooks |
| `inc/license.php` | License key storage, validation, transient + grace logic, admin notice |
| `inc/heartbeat.php` | Daily WP cron event, payload builder |
| `inc/loom-helpers.php` | Loom URL parsing, oEmbed fetch + cache, thumbnail sideloading, description backfill |
| `inc/brand.php` | Brand-fields registry, `<style>` overlay rendering |
| `inc/brand-derive.php` | 2-color → 7-surface palette derivation (HSL math + AA contrast guard) |
| `inc/font-detect.php` | theme.json + Google Fonts URL parser |
| `inc/bulk-import.php` | Loom share-URL paste-list importer |
| `inc/onboarding.php` | 3-step wizard, activation hook, admin notice when incomplete |
| `templates/` | Self-contained archive + single + header + footer templates |
| `css/training-videos.css` | Frontend plugin styles (token-driven) |
| `assets/admin-onboarding.{css,js}` | Wizard live preview |
| `vendor/plugin-update-checker/` | YahnisElsts/plugin-update-checker v5.6 (vendored) |
| `.github/workflows/release.yml` | Tag-driven release zip builder |

### Portal (sibling repo)

| Path | Owns |
|------|------|
| `frontend/prisma/schema.prisma` | `TrainingVideosSite` + `TrainingVideosHeartbeat` models |
| `frontend/app/api/training-videos/heartbeat/route.ts` | Heartbeat endpoint |
| `frontend/app/api/training-videos/license/validate/route.ts` | License validation endpoint |
| `frontend/app/admin/training-videos/page.tsx` | Registry list view |
| `frontend/app/admin/training-videos/[siteId]/page.tsx` | Per-site detail + license management |

## Cross-references

- **Tech stack details (this repo)**: [`docs/TECHNOLOGY-STACK.md`](TECHNOLOGY-STACK.md)
- **Operational runbooks**: [`docs/runbooks/`](runbooks/) — onboarding, revoke, release, debug
- **Portal repo integration view**: `~/Projects/gm-maintenance/docs/TRAINING-VIDEOS.md`
- **Portal tech stack**: `~/Projects/gm-maintenance/docs/TECHNOLOGY-STACK.md`
- **Project hub (cross-repo status timeline)**: `~/.claude-royal/project-notes/training-videos/README.md`
