# Runbook — Releasing a New Plugin Version

## When to use

You've made changes to the plugin in `~/Projects/gm-training-videos` and want them on every client install. Once a site is on **v1.4.4 or later**, this is fully automated — no `cp -r`, no SFTP, no manual install. Tag a release, every install picks it up via WP cron.

## Steps

### 1. Land your changes on `main`

```bash
cd ~/Projects/gm-training-videos
# ... edit code, test locally on http://gm-training-videos-dev.local ...
git add -A && git commit -m "Description of change"
```

### 2. Bump the version

Edit `training-videos.php` plugin header:

```php
* Version: 1.X.Y
```

Use [Semantic Versioning](https://semver.org/):

| Bump | Reason | Example |
|------|--------|---------|
| **PATCH** | Bug fix, copy tweak, doc update | 1.4.5 → 1.4.6 |
| **MINOR** | New feature, new setting | 1.4.5 → 1.5.0 |
| **MAJOR** | Breaking change (settings rename, removed feature) | 1.4.5 → 2.0.0 |

### 3. Update CHANGELOG.md

Move items from `## [Unreleased]` to a new versioned section. Date it. Brief — one bullet per material change.

```markdown
## [1.X.Y] — 2026-MM-DD

### Added / Changed / Fixed / Removed
- One-line description of each change
```

### 4. Commit + push the version bump

```bash
git add training-videos.php CHANGELOG.md
git commit -m "v1.X.Y — short description"
git push origin main
```

### 5. Tag the release

```bash
git tag v1.X.Y
git push origin v1.X.Y
```

This triggers `.github/workflows/release.yml`. Watch it run:

```bash
gh run list --workflow=release.yml --limit 1
```

The workflow:
1. Verifies the tag matches the plugin header `Version:` exactly. If not, fails — fix the header, re-tag.
2. Builds a clean zip (excludes `.git/`, `tests/`, `create-sample-videos.php`, etc.)
3. Creates a GitHub Release at `https://github.com/ericdowns/gm-training-videos/releases/tag/vX.Y.Z`
4. Attaches `training-videos.zip` to the release

### 6. Confirm the release

```bash
gh release view v1.X.Y --json tagName,assets --jq '{tag: .tagName, asset: .assets[0].name, size: .assets[0].size}'
```

Should return:
```json
{ "tag": "v1.X.Y", "asset": "training-videos.zip", "size": 220000 }
```

### 7. Verify update propagates (optional, for high-stakes releases)

The fleet picks up updates within ~12h via WP cron. To confirm one site picked it up:

```bash
# Force the update check on a specific site
wp cron event run wp_update_plugins --path=/path/to/wp-install
wp plugin status training-videos --path=/path/to/wp-install
# Look for "Update Available: 1.X.Y"
```

For visibility across the fleet, watch the registry dashboard: `https://maintenance.grainandmortar.com/admin/training-videos`. Sites' `pluginVersion` column updates the next time each install fires its daily heartbeat.

## What about sites still on ≤v1.3.x?

Pre-v1.4.4 installs don't have plugin-update-checker. They need a one-time manual update **to v1.4.4 or later** before they self-update from there. Track these in `docs/SITES.md` — until they're brought current, you're managing them by hand.

```bash
# Manual update for a stuck site:
ssh user@host
cd /path/to/wp-content/plugins/
rm -rf training-videos
curl -L -o /tmp/tv.zip https://github.com/ericdowns/gm-training-videos/releases/latest/download/training-videos.zip
unzip /tmp/tv.zip -d .
# Then activate (or it stays activated if you didn't deactivate)
```

After they're on v1.4.4, future updates flow automatically.

## Rollback

If a release shipped a regression and you need to roll back the fleet:

1. **Re-tag a previous version** is NOT a thing — git tags are immutable for the update flow.
2. **Cut a new release with the fix** (preferred). Bump to the next patch and ship the fix.
3. **For a single broken site**, install a previous release zip via WP admin → Plugins → Add New → Upload — pick from https://github.com/ericdowns/gm-training-videos/releases.

## Common issues

**Workflow failed: "Plugin header version does not match tag"** — you bumped one but not the other. Fix the header to match the tag, push, delete the bad tag (`git tag -d vX.Y.Z && git push --delete origin vX.Y.Z`), re-tag.

**Release published but no `.zip` asset** — the workflow `softprops/action-gh-release` step failed. Check the run logs. Usually a `GITHUB_TOKEN` permission issue (workflow needs `contents: write`).

**Site says "no update available" but the new release is out** — WP cron is throttled. Force it: `wp cron event run wp_update_plugins`. Also check `wp option get _site_transient_update_plugins` for plugin-update-checker's last-known state.

## Cross-references

- Workflow definition: `.github/workflows/release.yml`
- plugin-update-checker docs: https://github.com/YahnisElsts/plugin-update-checker
- Tech stack details: [`../TECHNOLOGY-STACK.md`](../TECHNOLOGY-STACK.md#9-cicd)
