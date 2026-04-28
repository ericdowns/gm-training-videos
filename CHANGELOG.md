# Changelog

All notable changes to the Training Videos plugin. Versions follow [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.3.0] — 2026-04-28

### Added
- Brand Theme settings — override the CalForever palette (page bg, headings, text, accent, accent hover, border, card bg) and fonts (heading family, body family, font import URL) per site, no template forks needed — #4
- Repo hardening: LICENSE, CHANGELOG.md, `.github/ISSUE_TEMPLATE/`, `.github/PULL_REQUEST_TEMPLATE.md` — #12
- Loom thumbnails sideloaded to Media Library on save (cron-driven, never blocks the front end). Templates serve local URLs once cached — #3
- Auto-populate `_video_description` from Loom oEmbed `description` field on save (only when post meta is empty — never overwrites manual edits) — #7
- "Loom Data" sidebar meta box on the edit screen with Refresh description + Refresh thumbnail buttons — #6, #8
- Bulk actions on the training_videos list table: "Pull descriptions from Loom" and "Re-cache thumbnails from Loom" — #2

### Changed
- Refactored `inc/loom-helpers.php` around a shared `training_videos_fetch_loom_oembed()` cache so thumbnail + description paths share the same network round-trip
- Repo settings: Wiki disabled, delete-branch-on-merge enabled

### Blocked
- Bulk import from Loom folder URL (#5) — requires authenticated Loom folder API; oEmbed only covers single videos. Will land once card #10 (central dashboard) provides server-side Loom auth.

### Added
- Self-contained CSS that renders correctly on any client theme (no more naked HTML when the parent theme lacks brand tokens)
- Font Awesome 6 enqueue gated to plugin pages
- `inc/loom-helpers.php` — shared `training_videos_get_loom_thumbnail_url()` helper using oEmbed (workspace-private safe)
- Lazy-load Loom iframe with click-to-play poster (no Loom JS until user clicks) — #15
- Mobile drawer + "Video N of M" position label + "All videos" pill — #16
- "Back to all videos" link at the bottom of single pages — #13
- Adaptive archive grid: 1/2/3/4+ video count drives the column layout — #18
- `docs/SITES.md` deployment registry, `docs/README.md` master index

### Changed
- Stack PREV/NEXT pager full-width on mobile, no mid-word truncation — #14
- Header nav labels visible at all viewport widths, brighter Manage button contrast — #17
- Drop `min-h-screen` so pages end naturally above the footer — #13

### Fixed
- Loom thumbnails 403 on workspace-private videos (now uses oEmbed for hash-suffixed URLs) — v1.1.1 carryover, fully wired

## [1.1.1] — 2026-04-28

### Fixed
- Loom thumbnail 403 on workspace-private videos — replaced plain-ID URL with oEmbed-fetched hash-suffixed URL, cached 7 days via WP transient

## [1.1.0] — 2025-12-17

### Added
- Plugin Settings page (Training Videos → Settings) for documentation resource
- Resource card on archive (Google Doc link, navy bg, document icon)
- "Need Help?" admin bar dropdown linking to Training Library + Documentation

### Changed
- Archive grid 3 → 4 columns (later normalized in v1.2.0 to count-adaptive)
- Meta box title "Loom Video URL / Google Doc" → "Loom Video URL"

### Removed
- YouTube thumbnail generation
- YouTube conditional embed in single template
- "Watched" badge feature + localStorage tracking

## [1.0.0] — 2025-12-17

### Added
- Initial release
- `training_videos` custom post type
- Loom URL auto-conversion (share → embed) on save
- Self-contained templates (header/footer/archive/single)
- Login-required access by default
- Admin bar quick-access link
