# Runbook — Onboarding a New Client Install

## When to use

You're delivering the Training Videos plugin to a new client site for the first time. This is the most common operation in the system.

## Prereqs

- SSH/SFTP access to the client's WordPress install (or you're sitting in a Local-by-Flywheel site for them)
- Admin login on their wp-admin
- Their primary + secondary brand colors (hex)

## Steps

### 1. Install the plugin (one-time, manual)

Until the site is on **v1.4.4 or later**, plugin updates require a manual `cp -r`. After that, every update is automatic.

```bash
# Pull the latest release zip
curl -L -o /tmp/training-videos.zip \
  https://github.com/ericdowns/gm-training-videos/releases/latest/download/training-videos.zip

# Upload + activate
# Option A: WP admin → Plugins → Add New → Upload Plugin → choose zip → Install → Activate
# Option B: SSH/SFTP — unzip into wp-content/plugins/ and activate via WP admin or `wp plugin activate training-videos`
```

### 2. Run the onboarding wizard

Activation auto-redirects to **Training Videos → Onboarding**. Complete all three steps:

1. **Brand colors** — paste primary + secondary hex. Watch the live swatch preview. The other 5 surfaces auto-derive (page bg, body text, accent hover, borders, etc.).
2. **Fonts** — usually auto-detected from the active theme. If not, paste the heading + body family + Google Fonts URL.
3. **Bulk import (optional)** — paste a list of Loom share URLs (one per line). Each becomes a `training_videos` post with title + description + thumbnail pulled from Loom oEmbed.

Click **Save & Finish**. The client site is now branded and populated.

### 3. Wait for the first heartbeat

The plugin fires a daily WP cron heartbeat to the registry. To force one immediately:

```bash
wp cron event run training_videos_heartbeat --path=/path/to/wp-install
```

(Local-by-Flywheel: see `loom` skill for the `--require=...` socket override.)

### 4. Issue the license in the portal

1. Go to https://maintenance.grainandmortar.com/admin/training-videos
2. Find the new site in the table (sorted by last heartbeat — should be at the top)
3. Click into the row → **Site detail page**
4. In the License panel:
   - Click **Generate** to create a fresh `GM-XXXXXX-XXXXXX-XXXXXX` key (or paste an existing key)
   - Set **Tier** (e.g., `standard`)
   - Set **Expires** (e.g., 1 year out — match the contract end)
   - Set **Status** to `Active`
   - **Linked client** — pick the existing G&M Client from the dropdown so the install attributes to a billable account
   - **Notes** — any reminder for future-you ("contract renewal flag for Q4 2026")
5. Click **Save**

### 5. Paste the key into the client install

Back on the client site, **Training Videos → Settings → License**:

1. Paste the key you generated
2. Click **Save Settings**
3. The plugin re-validates immediately via `update_option_*` hook
4. Status badge flips to **✓ Active**

### 6. Verify

- Visit the front-end `/training-videos/` archive — confirm branding looks right (header bg, accent button, page bg tint, etc.)
- Click a video — confirm the player + sidebar render correctly
- In wp-admin, the **License required** notice should be gone

## Common issues

**"Site not in the dashboard yet"** — heartbeats are daily. Force one with `wp cron event run training_videos_heartbeat`. If it still doesn't appear, check `/api/training-videos/heartbeat` returns 200 (see [DEBUG-HEARTBEAT.md](DEBUG-HEARTBEAT.md)).

**"Status stuck at Server unreachable"** — confirm `TRAINING_VIDEOS_LICENSE_SERVER` (in `wp-config.php` or default) is reachable from the host. Some firewalled environments block outbound HTTPS — you may need to whitelist `maintenance.grainandmortar.com`.

**"Auto-derived palette looks off"** — open Settings → expand **Advanced — override individual surface colors** and tweak the specific surface manually. Saved overrides persist.

## Cross-references

- Wizard internals: [`../ARCHITECTURE.md`](../ARCHITECTURE.md#what-the-plugin-does-per-install)
- License caching + grace logic: [`../ARCHITECTURE.md`](../ARCHITECTURE.md#plugin-caching--grace-logic)
