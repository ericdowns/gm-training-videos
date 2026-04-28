# CLAUDE.md - Training Videos Plugin

This document provides guidance to Claude Code when working with the Training Videos plugin and explains the Git repository structure for managing it across multiple WordPress sites.

## Plugin Overview

**Name:** Training Videos
**Version:** 1.1.1
**Author:** Eric Downs - Technical Director at Grain & Mortar
**Purpose:** Provide clients with a professional training video library portal using Loom videos

This is a standalone WordPress plugin (not a theme include) that creates a complete training video system with:
- Custom post type for training videos
- Loom video embedding with auto-URL conversion
- Documentation resource card (Google Doc link)
- Admin bar quick access
- Self-contained templates (doesn't rely on theme)

---

## Repository Structure

```
gm-training-videos (standalone repo)    <-- Master copy - make all changes here
       │
       ├── Site A (copy in plugins/)    <-- Full plugin copy
       ├── Site B (copy in plugins/)    <-- Can be different version
       └── Site C (copy in plugins/)    <-- Updated independently
```

### Important Locations

| Location | Purpose |
|----------|---------|
| `/Users/edowns/Projects/gm-training-videos` | **Master repo** - Make all changes here |
| `site/wp-content/plugins/training-videos/` | **Site copy** - Deployed plugin |
| `https://github.com/ericdowns/gm-training-videos` | **Remote** - Where master repo is stored |

### Why Not a Submodule?

Unlike dev-tools (which is an include), this is a full WordPress plugin that:
- Needs to appear in Plugins list
- Can be activated/deactivated
- Has its own update workflow
- May need site-specific settings (stored in wp_options)

Plugins are typically deployed by copying the folder, not submodules.

---

## Golden Rules

1. **Make changes in the master repo** at `/Users/edowns/Projects/gm-training-videos`
2. **Test changes locally** before committing
3. **Bump version** when making changes (see Versioning section)
4. **Deploy to sites** by copying the updated plugin folder
5. **Site-specific settings** (resource URL, etc.) stay in the database - not in code

---

## Versioning

**CLAUDE CODE: You MUST bump the version every time you make changes.**

Uses [Semantic Versioning](https://semver.org/): `MAJOR.MINOR.PATCH`

| Bump Type | When to Use | Example |
|-----------|-------------|---------|
| **PATCH** | Bug fixes, small tweaks, documentation | 1.1.0 → 1.1.1 |
| **MINOR** | New features, new settings, enhancements | 1.1.1 → 1.2.0 |
| **MAJOR** | Breaking changes, major rewrites | 1.2.0 → 2.0.0 |

### How to Bump Version

Edit `training-videos.php` header:
```php
* Version: 1.1.1  // Update this number
```

---

## Local Dev Site

A dedicated Local by Flywheel site exists for iterating on this plugin without touching any client install:

- **URL:** http://gm-training-videos-dev.local
- **Path:** `/Users/edowns/Local Sites/gm-training-videos-dev/`
- **Plugin install:** **symlink** from `~/Projects/gm-training-videos` to the site's `wp-content/plugins/training-videos/`. Edits to the master repo are live in the demo immediately — no `cp -r` step.
- **WP admin:** `admin` / `admin` at `/wp-login.php`
- **Seeded:** 3 sample training videos + resource URL configured for testing the archive layout.

**WP-CLI usage** — Local sites use a non-standard MySQL socket. The new site's `wp-config.php` was patched to use `localhost:/Users/edowns/Library/Application Support/Local/run/J2ZShdexvw/mysql/mysqld.sock` so `wp` from `/opt/homebrew/bin/wp` works against it via `--path`. If you spin up a different demo, look up the run/ID in `~/Library/Application Support/Local/sites.json` and patch `DB_HOST` the same way.

**To verify changes in the browser:** see the chrome-devtools workflow in the user's CLAUDE.md (port 9233). The login template requires `is_user_logged_in()`, so log in first via `/wp-login.php`.

---

## Deployed Sites

Source of truth: [`docs/SITES.md`](docs/SITES.md).

That file lists every client site running this plugin, the installed version, and any per-site notes (rebranded forks, non-Local hosts, etc.). It's hand-maintained from filesystem scans of `~/Local Sites/*/app/public/wp-content/plugins/training-videos/`.

**This is interim.** The license-key + central-dashboard cards on this repo (see Open Cards below) replace this file with a live registry once they land. Until then: when you `cp -r` the plugin to a new site or update an existing one, update `docs/SITES.md` in the same commit.

---

## Common Tasks

### Task 1: Make Changes (New Features, Bug Fixes)

```bash
# 1. Go to the master repo
cd /Users/edowns/Projects/gm-training-videos

# 2. Make your changes

# 3. Bump version in training-videos.php

# 4. Commit
git add .
git commit -m "Description of changes - v1.1.1"
git push origin main
```

### Task 2: Deploy to a Site

```bash
# Option A: Copy files
cp -r /Users/edowns/Projects/gm-training-videos /path/to/site/wp-content/plugins/training-videos

# Option B: For development, use symlink
ln -s /Users/edowns/Projects/gm-training-videos /path/to/site/wp-content/plugins/training-videos
```

### Task 3: Update a Site's Plugin

```bash
# Remove old version and copy new
rm -rf /path/to/site/wp-content/plugins/training-videos
cp -r /Users/edowns/Projects/gm-training-videos /path/to/site/wp-content/plugins/training-videos
```

---

## File Structure

```
training-videos/
├── training-videos.php           # Main plugin file (version, post type, settings)
├── CLAUDE.md                     # This documentation (for AI)
├── README.md                     # Installation/usage docs (for humans)
├── loom-helper.php               # Standalone URL conversion tool
├── templates/
│   ├── training-header.php       # Navy header with nav
│   ├── training-footer.php       # Navy footer with credits
│   ├── archive-training_videos.php  # Thumbnail grid + resource card
│   └── single-training_videos.php   # Video player with sidebar
├── css/
│   └── training-videos.css       # Plugin styles — enqueue currently disabled (see training-videos.php:25). Re-enable when brand-theming card #4 lands.
├── docs/
│   ├── README.md                 # Master index for plugin docs
│   └── SITES.md                  # Deployment registry — every site running this plugin
├── create-sample-videos.php      # Sample video generator
├── check-video-url.php           # URL validation helper
├── check-videos.php              # Debug helper
├── create-videos-now.php         # Bulk creation script
├── flush-rewrite.php             # Permalink flush helper
├── test-create.php               # Test script
├── test-query.php                # Query test script
└── update-videos.php             # Batch update script
```

---

## Features

### 1. Custom Post Type
- **Post Type:** `training_videos`
- **Slug:** `/training-videos/`
- Loom video embedding
- Description field (140 characters)
- Menu ordering for video sequence
- Excluded from search, NoIndex for SEO

### 2. Template System
Self-contained templates using California Forever theme colors:
- Navy header/footer
- Beige/linen backgrounds
- Orange accent colors
- Font Awesome Sharp icons

### 3. Thumbnail Support
Automatically generates thumbnails from Loom URLs:
- `https://cdn.loom.com/sessions/thumbnails/[VIDEO_ID]-with-play.gif`

### 4. Documentation Resource
Plugin settings for Google Doc/external documentation:
- **Settings:** Training Videos → Settings
- **Display:** Navy card with document icon above video grid
- Opens in new tab

### 5. Admin Bar Link
"Need Help?" dropdown in WordPress admin bar:
- Watch Training Videos → Archive page
- Documentation link (if configured)

### 6. Loom URL Auto-Conversion
Automatically converts share URLs to embed URLs on save:
```
https://www.loom.com/share/abc123 → https://www.loom.com/embed/abc123
```

### 7. Access Control
Login required by default (can be disabled in templates)

---

## Data Structure

### Post Meta
```php
get_post_meta($post_id, '_loom_video_url', true);      // Loom embed URL
get_post_meta($post_id, '_video_description', true);   // 140 char description
```

### Plugin Options
```php
get_option('training_videos_resource_title');       // Resource title
get_option('training_videos_resource_url');         // Google Doc URL
get_option('training_videos_resource_description'); // Brief description
```

---

## Color Scheme (California Forever)

| Color | Hex | Usage |
|-------|-----|-------|
| Navy | #112D40 | Headers, primary text, backgrounds |
| Stone Blue | #3A5161 | Secondary text |
| Beige | #FDF9E3 | Light backgrounds |
| Linen | #EAE7D7 | Borders, card backgrounds |
| Orange | #FFBC21 | CTAs, active states |
| Brick | #B15221 | Hover states |
| Green | #42725F | Success badges |

---

## Companion Tools

### `/loom` skill

The Claude Code `/loom` skill ([source: `ericdowns/claude_skills` repo, `loom/` folder](https://github.com/ericdowns/claude_skills/tree/main/loom) — local: `~/.claude/skills/loom/`) is the **bidirectional companion** to this plugin:

- The skill owns the **Loom side**: transcript fetching, folder listings, AI summaries, oEmbed thumbnail lookups, MCP server setup, cookie auth.
- This plugin owns the **WordPress side**: the `training_videos` CPT, post meta (`_loom_video_url`, `_video_description`), templates, admin Settings page.
- The skill's `SKILL.md` has a "Companion: gm-training-videos plugin" section that points back to this repo.

**Workflows the skill provides** (not yet built into the plugin itself):

- **Pull producer-authored `_video_description` from Loom (PRIMARY)** — Brooke writes a description in Loom (Edit Video → Description textarea, the standard `RegularUserVideo.description` field). The plugin reads it via `get_description` and writes it to post meta. No AI, no synthesis, verbatim copy. Tracked as a card on this repo.
- **Bulk-import Loom folder → WordPress posts** — find videos by name prefix or scrape folder display order, generate WP-CLI import script with correct `menu_order`.
- **Pull MP4 download URLs, transcripts, captions** — for content that needs to live outside the plugin (newsletters, support docs, etc.).
- **Transcript-based description synthesis (FALLBACK)** — only when Brooke hasn't written a Loom description and the team needs something now. Reads the transcript, synthesizes a 1-sentence summary. See `~/.claude/skills/loom/examples/` for the actual scripts used on Xomox 2026-04-28 before the producer-driven path was set up.

The skill carries its own setup runbook (cookie-based GraphQL auth, ~30-day refresh) and 60 tools across reads/writes on videos, folders, transcripts, comments, and library mgmt.

**Future plugin work that depends on this skill is tracked as GitHub issues** on this repo (`ericdowns/gm-training-videos`). See the [open issues list](https://github.com/ericdowns/gm-training-videos/issues?q=is%3Aopen+is%3Aissue+label%3Acard) for current candidates — most current cards graduate skill workflows into native plugin features.

---

## Open Cards / Backlog

Plugin work is tracked as GitHub Issue cards on this repo using the `/cards` workflow. Drop new ones with `card on gm-training-videos: …` from any session.

**Major in-flight initiatives:**

| Theme | Card(s) | What it unlocks |
|---|---|---|
| **Brand theming separation** | [#4](https://github.com/ericdowns/gm-training-videos/issues/4) | Drive colors and fonts from a per-site Settings tab so we stop forking templates per client (Xomox is the smoking gun). |
| **License key + phone-home registration** | [#9](https://github.com/ericdowns/gm-training-videos/issues/9) | Each install reports its URL + version + license to a central server. Lets us see who has it activated and revoke when contracts end. |
| **Central deployment dashboard** | [#10](https://github.com/ericdowns/gm-training-videos/issues/10) | Web app that consumes the phone-home pings — shows every site, version, last-seen, license status. Push-update button per site. |
| **GitHub Releases + native WP update flow** | [#11](https://github.com/ericdowns/gm-training-videos/issues/11) | Tag-driven releases with auto-zip; plugin uses `plugin-update-checker` so WP's "Update available" banner just works. |
| **GitHub repo hardening** | [#12](https://github.com/ericdowns/gm-training-videos/issues/12) | LICENSE, CHANGELOG.md, issue/PR templates, disable Wiki + classic Projects, set delete-branch-on-merge. |

**Quick links:**
- [All open cards](https://github.com/ericdowns/gm-training-videos/issues?q=is%3Aopen+is%3Aissue+label%3Acard)
- [Awaiting review (PRs to merge)](https://github.com/ericdowns/gm-training-videos/issues?q=is%3Aopen+is%3Aissue+label%3Acard+label%3Aawaiting-review)

## Changelog

### April 28, 2026 - v1.1.1

**Thumbnail Fix (Workspace-Private Videos)**
- Loom's plain-ID thumbnail URL (`{id}-with-play.gif`) returns HTTP 403 for workspace-private videos, so cards rendered as blank placeholders for any client with private Loom content.
- Replaced with oEmbed-based fetch: `wp_remote_get('https://www.loom.com/v1/oembed?url=...')` returns a hash-suffixed thumbnail URL (`{id}-{hash}.gif`) that's publicly accessible regardless of video privacy.
- Cached via WP transient: 7 days on success, 5 minutes on failure (so a stale 403 doesn't stick).
- No template/markup changes — drop-in replacement of `get_video_thumbnail_url()` in `archive-training_videos.php`.

### December 17, 2025 - v1.1.0

**Documentation Resource Feature**
- Added plugin settings page (Training Videos → Settings)
- Resource card displays at top of archive (Google Doc link)
- Navy background, document icon, opens in new tab

**Admin Bar Integration**
- Added "Need Help?" dropdown to WordPress admin bar
- Links to Training Library and Documentation resource
- Video icon, opens in new tab

**YouTube Support Removed**
- Removed YouTube thumbnail generation
- Removed YouTube conditional embed in single template
- Plugin now Loom-only (cleaner, focused)

**Archive Improvements**
- Changed grid from 3 columns to 4 columns
- Removed "Watched" badge feature and localStorage tracking
- Improved resource card spacing

**Meta Box Cleanup**
- Changed title from "Loom Video URL / Google Doc" to "Loom Video URL"

---

## For Claude Code

### CRITICAL: Deployment Workflow

**Source of Truth:** Always pull from the master Git repo at `/Users/edowns/Projects/gm-training-videos`

**IMPORTANT:** Always confirm with the user before deploying to a site. Never copy files without explicit approval.

### When User Says "Add training videos plugin"

1. **Confirm first:** "I'll copy the Training Videos plugin from the master repo to this site's plugins folder. Proceed?"
2. **Wait for approval**
3. **Then execute:**
   ```bash
   cp -r /Users/edowns/Projects/gm-training-videos [site]/wp-content/plugins/training-videos
   ```
4. **Remind user:** Plugin needs to be activated in WordPress admin

### When User Says "Update training videos"

1. **Check current version** in the site's `training-videos.php` header
2. **Check master repo version** at `/Users/edowns/Projects/gm-training-videos/training-videos.php`
3. **Confirm first:** "Site has v1.1.0, master repo has v1.2.0. I'll replace the plugin with the latest version. Proceed?"
4. **Wait for approval**
5. **Then execute:**
   ```bash
   rm -rf [site]/wp-content/plugins/training-videos
   cp -r /Users/edowns/Projects/gm-training-videos [site]/wp-content/plugins/training-videos
   ```
6. **Note:** Site settings (resource URL, etc.) are stored in database and won't be affected

### When User Says "Fix/change training videos"

1. **Work in master repo:** `/Users/edowns/Projects/gm-training-videos`
2. **Make changes**
3. **Bump version** in `training-videos.php` header
4. **Commit and push** to GitHub
5. **Ask user:** "Changes committed to master repo. Do you want me to deploy to any sites?"

### Quick Reference

| Request | Action |
|---------|--------|
| "Add training videos plugin" | **Confirm** → Copy from master repo to site |
| "Update training videos" | **Confirm** → Replace site copy with master repo |
| "Add feature to training videos" | Work in master repo, bump version, **ask about deploy** |
| "Fix bug in training videos" | Work in master repo, bump version, **ask about deploy** |
| "Configure training videos" | Edit settings in WP admin, not code |

### Always Remember

1. **Always confirm** before copying/replacing plugin files
2. **Always pull from master repo** at `/Users/edowns/Projects/gm-training-videos`
3. **Never edit site copies directly** - changes go in master repo
4. **Bump version** for any code changes
5. **Site settings** (resource URL, etc.) are stored in database - won't be overwritten

---

## Setup Checklist for New Site

1. [ ] Copy plugin folder to `wp-content/plugins/training-videos/`
2. [ ] Activate plugin in WordPress admin
3. [ ] Go to Training Videos → Settings, configure resource URL
4. [ ] Add training videos (or create samples)
5. [ ] Test: Visit `/training-videos/` on frontend
6. [ ] Test: Check "Need Help?" link in admin bar
