# Technology Stack — Training Videos Plugin

_Last verified: 2026-04-28 by Eric / Claude_

## 🤖 Agent Quick Brief

> **Read this; skip the rest unless you need a specific detail.**

**What this is.** Standalone WordPress plugin that delivers a branded training-video library backed by Loom. Auto-updates via GitHub Releases. Each install reports daily to the G&M Maintenance Portal.

**Don't trip on these (footguns):**
- **Older client sites still on ≤v1.3.x do NOT auto-update** — they need a one-time manual `cp -r` to ≥v1.4.4 first to pick up plugin-update-checker. Track which sites need this in `docs/SITES.md`.
- **Loom oEmbed thumbnails 403 for workspace-private videos when using the plain CDN URL pattern** (`cdn.loom.com/sessions/thumbnails/{id}-with-play.gif`). Always go through oEmbed (`loom.com/v1/oembed?url=...`).
- **`'custom-fields'` is intentionally NOT in CPT supports.** v1.4.1 removed it because the native panel was leaking internal `_loom_*` post meta as raw editable rows.
- **Auto-fill of `_video_description` from Loom only fires when the field is empty** — manual edits are preserved on subsequent saves. Clear the field to re-fill.
- **WP-CLI on Local-by-Flywheel sites needs the socket override** (`--require=/tmp/wp-db-override.php`). See the `loom` skill runbook.

**Critical paths (where things live):**
- **Plugin entry point:** `training-videos.php` (header version, plugin-update-checker bootstrap, all hooks)
- **License + heartbeat:** `inc/license.php`, `inc/heartbeat.php`
- **Loom integration:** `inc/loom-helpers.php`
- **Brand-fields registry:** `inc/brand.php`
- **Templates:** `templates/` (self-contained — no theme dependency)
- **Plugin styles:** `css/training-videos.css` (token-driven via `:root { --tv-color-* }`)
- **Vendored update-checker lib:** `vendor/plugin-update-checker/`
- **Release workflow:** `.github/workflows/release.yml`
- **Server URL config:** `define( 'TRAINING_VIDEOS_LICENSE_SERVER', '...' )` in `wp-config.php` (defaults to `https://portal.grainandmortar.com/api/training-videos`)

**Get running locally in one command:**
```bash
ln -s ~/Projects/gm-training-videos \
  ~/Local\ Sites/gm-training-videos-dev/app/public/wp-content/plugins/training-videos
```
Time to running: ~2 minutes (with Local-by-Flywheel installed).

**Companion repos / live URLs:**
- **Plugin repo (this):** https://github.com/ericdowns/gm-training-videos
- **Plugin releases:** https://github.com/ericdowns/gm-training-videos/releases
- **Registry server (companion repo):** https://github.com/ericdowns/gm-maintenance — admin tab at https://maintenance.grainandmortar.com/admin/training-videos
- **Project hub:** `~/.claude-royal/project-notes/training-videos/README.md`

**If something is wrong, start here:**
- Site stops phoning home → [`runbooks/DEBUG-HEARTBEAT.md`](runbooks/DEBUG-HEARTBEAT.md)
- Onboarding a new install → [`runbooks/ONBOARD-CLIENT.md`](runbooks/ONBOARD-CLIENT.md)
- Shipping a new version → [`runbooks/RELEASE-VERSION.md`](runbooks/RELEASE-VERSION.md)
- Revoking a license → [`runbooks/REVOKE-LICENSE.md`](runbooks/REVOKE-LICENSE.md)

---

## Full reference

The 20 sections below exist for when you need a specific answer (e.g., "which version of plugin-update-checker is vendored", "what option keys does the license module write"). Don't read top-to-bottom — search for the section you need.

## 1. Identity

| | |
|--|--|
| **Repo** | https://github.com/ericdowns/gm-training-videos |
| **Local path** | `~/Projects/gm-training-videos` |
| **What it is** | WordPress plugin that gives clients a branded training-video library backed by Loom, with onboarding wizard + auto-updates + license-key registration |
| **License** | MIT (see `LICENSE`) |
| **Primary language** | PHP |
| **Audience** | G&M client WordPress installs (per-site deployment) |
| **Project hub** | `~/.claude-royal/project-notes/training-videos/` |

## 2. Runtime + language

| | |
|--|--|
| **Language** | PHP 7.4+ |
| **Version source** | Plugin header `Requires PHP`; `inc/heartbeat.php` reports actual `PHP_VERSION` to the registry |
| **Enforced by** | WordPress runtime — older PHP installs fail activation |

## 3. Application framework

| | Version | Notes |
|--|---------|-------|
| **Framework** | WordPress 5.0+ | Tested on 6.9.4 |
| **Plugin pattern** | Standalone plugin | Not theme-bundled — full plugin folder, activatable, has Settings page |
| **Routing** | WP rewrite rules + custom post type archive (`/training-videos/`) | NoIndex, excluded from search |
| **Block Editor** | Not used | CPT supports `'title', 'thumbnail'` only — meta boxes via classic-editor pattern |

## 4. Data layer

| | |
|--|--|
| **Database** | MySQL via WordPress (whatever the host site uses) |
| **Schema** | `register_post_type('training_videos')` + `wp_options` for plugin config |
| **Post meta keys** | `_loom_video_url`, `_video_description`, `_loom_thumbnail_url`, `_loom_thumbnail_attachment_id`, `_loom_thumbnail_for_url` |
| **Option keys** | `training_videos_brand_*` (palette), `training_videos_brand_primary`/`_secondary` (source colors), `training_videos_license_key`, `training_videos_license_status`, `training_videos_license_last_checked`, `training_videos_license_last_good`, `training_videos_resource_*`, `training_videos_onboarding_completed` |
| **Transients** | `training_video_oembed_{id}` (7d), `training_videos_license_status_cache` (24h) |
| **Backup** | Whatever the host site does (Flywheel daily for client sites; manual for Local-by-Flywheel dev) |

## 5. Frontend

| | Version | Notes |
|--|---------|-------|
| **Plugin CSS** | Hand-written `css/training-videos.css` | Self-contained — works regardless of parent theme |
| **CSS variables** | `:root { --tv-color-* }` token system | Brand-overlay via `inc/brand.php` writes inline `<style>` block on plugin pages |
| **Font Awesome** | 6.5.1 via use.fontawesome.com | Loaded only on plugin pages |
| **Custom fonts** | Per-site via Settings (Google Fonts URL) | Optional — falls back to system stack |
| **Build tool** | None | No bundler — CSS is hand-written, no JS framework |
| **Admin UI assets** | `assets/admin-onboarding.css` + `.js` (vanilla JS, ~200 lines) | Live palette preview on Settings + Onboarding pages |

## 6. Hosting / where it runs

The plugin itself is distributed; it runs wherever WordPress is hosted by each client.

| Environment | Provider | URL | Notes |
|-------------|----------|-----|-------|
| **Local dev (G&M)** | Local-by-Flywheel | `http://gm-training-videos-dev.local` | Plugin installed via symlink → master repo |
| **Plugin source-of-truth** | GitHub | https://github.com/ericdowns/gm-training-videos | Public repo |
| **Plugin distribution** | GitHub Releases | https://github.com/ericdowns/gm-training-videos/releases | Auto-built zip on every `v*.*.*` tag |
| **Client installs** | Flywheel + others (per `docs/SITES.md`) | various | Hand-deployed initially; auto-update once on ≥v1.4.4 |

## 7. Domains + DNS

N/A — plugin doesn't own any domains. Each client install runs on its own domain.

The companion **G&M Maintenance Portal** (registry server) is hosted at `maintenance.grainandmortar.com` — see that repo's TECHNOLOGY-STACK.md.

## 8. CDN + edge

N/A on the plugin side. Heartbeats hit the portal at `maintenance.grainandmortar.com` directly (Vercel edge handles that).

## 9. CI/CD

| Workflow | Trigger | Purpose |
|----------|---------|---------|
| `.github/workflows/release.yml` | Tag matching `v*.*.*` | Verify plugin header version matches tag → build clean zip (excludes `.git/`, `tests/`, dev-only PHP helpers) → attach to GitHub Release |

| | |
|--|--|
| **Permissions** | `contents: write` (to create releases) |
| **Status** | Verified live with v1.4.4 release (2026-04-28) |
| **Secrets** | None required (uses `GITHUB_TOKEN` auto-provided to Actions) |

## 10. Auto-updates

| | |
|--|--|
| **Library** | `YahnisElsts/plugin-update-checker` v5.6 (MIT) — vendored at `vendor/plugin-update-checker/` |
| **Wired in** | `training-videos.php` — calls `Puc\\v5\\PucFactory::buildUpdateChecker()` near plugin load |
| **Update source** | GitHub release-asset zip (`->getVcsApi()->enableReleaseAssets()`) |
| **Polling cadence** | WP cron — same schedule as `wp_update_plugins` (~12h) |
| **Auth** | None (repo is public) |

## 11. Auth

| | |
|--|--|
| **Front-end** | WordPress login required by default — `wp_redirect( wp_login_url(...) )` in archive template (can be disabled per-template) |
| **Admin** | WordPress role: `manage_options` for Settings page + onboarding wizard |
| **API to portal** | License key as bearer token (`Authorization: Bearer <key>`) for `/license/validate`; heartbeats are unauthenticated (server upserts by `site_url`) |

## 12. Email

N/A — plugin doesn't send email. License nag fires as wp-admin notice only.

## 13. Storage

| | |
|--|--|
| **Loom thumbnails** | Sideloaded to WordPress Media Library via `media_sideload_image()` — local URL stored in `_loom_thumbnail_url` |
| **Featured Image override** | Standard WordPress Media Library |
| **No external blob storage** | All assets live in the WP `uploads/` folder per-site |

## 14. Background jobs / scheduled tasks

| Hook | Cadence | Purpose |
|------|---------|---------|
| `training_videos_run_thumbnail_cache` | One-shot, scheduled on `save_post` | Sideload Loom thumbnail to Media Library 60s after save |
| `training_videos_heartbeat` | Daily | POST install snapshot to portal registry |
| `wp_update_plugins` | ~12h (WP core) | Triggers plugin-update-checker GitHub poll |

All run via WordPress cron. Heartbeat unschedules on plugin deactivation.

## 15. Third-party integrations

| Service | Used for | Auth | Failure mode |
|---------|----------|------|--------------|
| **Loom oEmbed** (`loom.com/v1/oembed`) | Video title, description, thumbnail per share URL | None (public endpoint) | Cached failure for 5 min via transient; falls back to placeholder image on archive |
| **GitHub** (`api.github.com`, releases) | Plugin auto-update polling + release asset download | None (public repo) | plugin-update-checker logs error, no UI break; admin can ignore until next poll |
| **G&M Maintenance Portal** (`maintenance.grainandmortar.com/api/training-videos/*`) | Daily heartbeat + license validation | License key as bearer | Soft-fail with 7-day grace; admin notice only on confirmed-invalid |

## 16. Analytics + observability

| | |
|--|--|
| **Frontend analytics** | Whatever the host theme sets up (GA4, etc.) — plugin doesn't add tracking |
| **Plugin observability** | Heartbeat payloads to G&M portal (plugin version, WP/PHP version, video count, theme, multisite flag) |
| **Error logging** | Standard WordPress `error_log()` — heartbeat HTTP failures land in `debug.log` if `WP_DEBUG_LOG` is on |

## 17. Secrets management

| Secret | Where it lives |
|--------|----------------|
| **License key** (per-site) | `wp_options.training_videos_license_key` — set via Settings page on each install |
| **License server URL** | `define( 'TRAINING_VIDEOS_LICENSE_SERVER', '...' )` in `wp-config.php` (defaults to `https://portal.grainandmortar.com/api/training-videos`) |
| **No secrets in code** | Loom oEmbed is public; GitHub repo is public; license keys are per-site user data |

## 18. Local dev setup

```bash
# Clone the master repo
cd ~/Projects && git clone https://github.com/ericdowns/gm-training-videos
cd gm-training-videos

# Symlink into a Local-by-Flywheel site
ln -s ~/Projects/gm-training-videos \
  ~/Local\ Sites/gm-training-videos-dev/app/public/wp-content/plugins/training-videos

# Activate in wp-admin → Plugins
```

**Time to running locally:** ~2 minutes assuming Local-by-Flywheel is installed.

**Un-obvious requirements:** none. Public repo, no auth, no submodules.

See [project-notes hub](~/.claude-royal/project-notes/training-videos/README.md) for the dev site's WP admin credentials.

## 19. Deployment / release process

**Routine release** (auto-update flow):

```bash
# In the master repo
vim training-videos.php   # bump Version: header
vim CHANGELOG.md          # add entry
git add . && git commit -m "v1.4.X — description"
git push origin main
git tag v1.4.X && git push origin v1.4.X
# .github/workflows/release.yml builds zip, attaches to Release
# Every install picks it up within ~12h via WP cron / "Update available"
```

**First deployment to a new site** (one-time `cp -r`):

```bash
cp -r ~/Projects/gm-training-videos \
  /path/to/wp-content/plugins/training-videos
# Activate → from then on, auto-updates from GitHub
```

**Rollback** — install the previous release zip from https://github.com/ericdowns/gm-training-videos/releases via Plugins → Add New → Upload.

## 20. Known fragility / footguns

- **Older client sites still on ≤v1.3.x do NOT get auto-updates** — they need a one-time manual `cp -r` to ≥v1.4.4 first to pick up plugin-update-checker. Track which sites need this in `docs/SITES.md`.
- **Loom oEmbed thumbnails 403 for workspace-private videos when using the plain CDN URL pattern** (`cdn.loom.com/sessions/thumbnails/{id}-with-play.gif`). Always go through oEmbed (`loom.com/v1/oembed?url=...`). v1.1.1 onwards uses oEmbed; v1.4.1 stripped the last fragile reference.
- **Plugin's auto-fill of `_video_description` from Loom only fires when the field is empty** — manual edits are preserved on subsequent saves. Clear the field to re-fill.
- **License server URL defaults to portal prod** — for testing against a staging portal, set `define('TRAINING_VIDEOS_LICENSE_SERVER', '...')` in `wp-config.php`.
- **`'custom-fields'` is intentionally NOT in CPT supports.** v1.4.1 removed it because the native Custom Fields panel was leaking internal `_loom_*` post meta as raw editable rows. Don't put it back.
- **WP-CLI on Local-by-Flywheel sites needs the socket override:** `--require=/tmp/wp-db-override.php` with the Local-specific socket path. See the `loom` skill for the runbook.

## Verified state at last sync

- **Plugin header version**: 1.4.5
- **Latest release**: v1.4.4 attached zip ✓ (2026-04-28)
- **Latest commit on main**: `3321c94` (v1.4.5 — License key + daily heartbeat plugin side)
- **Vendored libs**: `vendor/plugin-update-checker/` v5.6
- **Open cards**: #29 (Loom folder enum, blocked), #26 (Flywheel prod for demo, blocked)

## Cross-references

- **Architecture overview**: [`docs/ARCHITECTURE.md`](ARCHITECTURE.md)
- **Per-runbook docs**: [`docs/runbooks/`](runbooks/)
- **Site deployment registry (interim, hand-maintained)**: [`docs/SITES.md`](SITES.md) — replaced by `maintenance.grainandmortar.com/admin/training-videos` once all installs upgrade
- **Companion repo (registry server)**: https://github.com/ericdowns/gm-maintenance — see its `docs/TRAINING-VIDEOS.md` for the integration view
- **Project hub**: `~/.claude-royal/project-notes/training-videos/README.md`
