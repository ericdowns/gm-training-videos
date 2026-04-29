# Runbook — Debugging When a Site Stops Phoning Home

## When to use

The portal dashboard at https://maintenance.grainandmortar.com/admin/training-videos shows a site's "Last heartbeat" stuck at >36 hours (amber) or >72 hours (red). The site is technically still working — heartbeats are best-effort — but it's a signal something's off.

This runbook walks through the failure chain in dependency order. Stop at the first layer that's broken; usually the answer is at layer 1 or 2.

## Prereqs

- Either: WP-CLI access on the affected site, or admin login
- A way to view PHP error logs (or `WP_DEBUG_LOG = true` is already on)

## Layer 1: Is WP cron firing at all?

Many sites disable internal WP cron and rely on system cron (`DISABLE_WP_CRON = true`). If that system cron stops, every WP scheduled task stops — including ours.

```bash
# Is anything cron-scheduled at all?
wp cron event list --path=/path/to/wp-install

# Is OUR event scheduled?
wp cron event list --path=/path/to/wp-install | grep training_videos_heartbeat
```

Expected: a row for `training_videos_heartbeat` with a "Next run" timestamp in the future.

**If missing**: the plugin was deactivated/reactivated and the schedule didn't re-arm. Force it:

```bash
wp eval 'training_videos_register_heartbeat();' --path=/path/to/wp-install
```

Or just toggle the plugin off and on in wp-admin.

**If present but "Next run" is in the past**: cron isn't running. Check `DISABLE_WP_CRON` in `wp-config.php`. If true, there should be a system crontab calling `wp-cron.php`. Verify:

```bash
crontab -l | grep wp-cron
```

If nothing, that's your culprit. Either (a) re-enable internal cron by removing `DISABLE_WP_CRON`, or (b) restore the system cron entry.

## Layer 2: Manually firing the heartbeat — does the plugin code even work?

```bash
wp cron event run training_videos_heartbeat --path=/path/to/wp-install
```

Expected: silent success.

**If error like "Function doesn't exist"**: plugin is deactivated. Activate it.

**If silent but the site still doesn't appear in the dashboard within a minute**: the plugin code ran but the HTTP request failed. Move to Layer 3.

## Layer 3: Can the site reach the registry server at all?

```bash
wp eval '
$response = wp_remote_post(
  "https://maintenance.grainandmortar.com/api/training-videos/heartbeat",
  [
    "timeout" => 8,
    "headers" => ["Content-Type" => "application/json"],
    "body" => json_encode(["site_url" => home_url(), "plugin_version" => "debug"]),
  ]
);
if ( is_wp_error( $response ) ) {
  echo "WP_ERROR: " . $response->get_error_message() . PHP_EOL;
} else {
  echo "HTTP " . wp_remote_retrieve_response_code( $response ) . PHP_EOL;
  echo wp_remote_retrieve_body( $response ) . PHP_EOL;
}
' --path=/path/to/wp-install
```

**Expected output:**
```
HTTP 200
{"ok":true}
```

**`WP_ERROR: cURL error 28: Connection timed out`** → outbound HTTPS to `maintenance.grainandmortar.com` is blocked. Common on hosts behind aggressive firewalls. Fix: ask host to whitelist the domain, OR redirect via `define('TRAINING_VIDEOS_LICENSE_SERVER', 'https://your-allowlisted-proxy/')`.

**`WP_ERROR: cURL error 6: Could not resolve host`** → DNS issue. Site can't resolve the domain. Usually transient — retry. If persistent, host's DNS resolver is broken.

**`HTTP 502 / 503 / 504`** → portal is having an outage. Check Vercel status. Plugin will retry on next cron tick — no action needed.

**`HTTP 405 Method Not Allowed`** → you sent a GET. The endpoint is POST-only. The above test posts correctly; if you see 405 with a real heartbeat, something is rewriting the request method. Check for plugin/host-level URL rewrites.

## Layer 4: Server logs — did the request arrive but fail to write?

If the previous step returned 200 but the dashboard still shows nothing, the request landed but wasn't recorded. Rare. Check Vercel logs:

```bash
# In ~/Projects/gm-maintenance/frontend
vercel logs --since 1h | grep training-videos
```

Or in the Vercel dashboard: Project → Logs → filter by `/api/training-videos/heartbeat`.

Look for Prisma errors. Most likely cause: schema drift (a column the plugin sends doesn't exist in the DB). Re-run `prisma db push` after merging any schema changes.

## Layer 5: Is the plugin even installed and active?

If a site stops appearing entirely after weeks of working:

```bash
wp plugin list --path=/path/to/wp-install | grep training-videos
```

Possible:
- `inactive` — admin deactivated it. Reactivate or contact them.
- `active` but `Update Available` — they're behind. Run [`RELEASE-VERSION.md`](RELEASE-VERSION.md) update flow.
- Not in the list at all — somebody deleted the plugin folder. Reinstall via Layer 1 of [`ONBOARD-CLIENT.md`](ONBOARD-CLIENT.md).

## Layer 6: Did the dashboard's `is_local` filter hide it?

Self-check: in the portal dashboard, the table shows total / live / billable counts. The "billable" count excludes `is_local: true` rows. If you set up a staging site that uses a `*.local` or `*.test` host, it'll be flagged DEV and may visually be deprioritized — but it should still appear in the main table.

If you genuinely want a `.com` URL flagged as dev, manually edit `isLocal` on the site row in the portal admin (no UI for this yet — direct DB edit or add as a future feature).

## Quick decision tree

```
Last heartbeat stale?
├── Layer 1: cron running?  ────── no  → fix cron
│                                  yes ↓
├── Layer 2: plugin code OK?  ──── no  → reactivate plugin
│                                  yes ↓
├── Layer 3: outbound HTTP works? ─ no → host firewall / DNS
│                                  yes ↓
├── Layer 4: server received it? ─ no → Vercel/Prisma issue
│                                  yes ↓
└── It's there. Refresh dashboard.
```

## Cross-references

- Heartbeat code: `inc/heartbeat.php`
- Wire format: [`../ARCHITECTURE.md`](../ARCHITECTURE.md#post-apitraining-videosheartbeat)
- Failure surfaces table: [`../ARCHITECTURE.md`](../ARCHITECTURE.md#failure-surfaces--what-breaks-if-x-goes-down)
