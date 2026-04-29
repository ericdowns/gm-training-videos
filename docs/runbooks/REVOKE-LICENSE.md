# Runbook — Revoking a License (Contract End)

## When to use

A client's contract has ended (or you need to pause an install for any reason). The plugin **does not break the front-end** — it surfaces an admin nag so the client knows to re-engage if they want to keep using it.

## Steps

### 1. Revoke in the portal

1. Go to https://maintenance.grainandmortar.com/admin/training-videos
2. Find the site → click into the detail page
3. In the License panel:
   - Set **Status** to `Revoked`
   - (Optional) Add a **Note** explaining why ("contract ended 2026-Q3, no renewal")
4. Click **Save**

That's it on the operator side.

## What happens on the client install

The plugin caches license status for 24h via WP transient. So:

- **Within 0–24h after revoking**: client site is unchanged. Cached `active` from before.
- **After cache expiry (≤24h)**: next call to `/license/validate` returns `{valid: false, reason: "revoked"}`. Plugin flips status to `invalid`. Admin sees:
  > **Training Videos:** license key invalid or expired. [Re-activate in Settings.]
- **Front-end keeps working.** The training video pages still render. Clients keep accessing their library. The intent is "nag them to renew", not "punish them with a broken site."

## Forcing immediate effect (rare — only when you can't wait)

If you need the revocation to take effect right now:

**Option A — clear the transient on the client site (requires WP-CLI access)**:
```bash
wp transient delete training_videos_license_status_cache --path=/path/to/wp-install
```

**Option B — change the `expiresAt` to yesterday in the portal** (forces `expired` reason instead of waiting for `revoked` cache), then save.

Either way, the next admin-page load on the client site triggers re-validation.

## Re-activating later

If the client comes back:

1. Portal → site detail → **Status** back to `Active`
2. (Optional) Update **Expires** to a new date
3. Save

The next plugin re-check (within 24h, or immediate via Settings save on the client side) flips back to `active`.

## What NOT to do

- **Don't delete the site row from the registry.** Keep it for audit + so you can re-activate in one click if they renew.
- **Don't change the plugin code or push a "revoked" build.** That defeats the entire point of the license system. The system handles it server-side.
- **Don't unset the license key on the client side.** Then they'd be `unlicensed` (yellow nag) instead of `invalid` (red nag) — different UX, weaker signal.

## Cross-references

- License lifecycle: [`../ARCHITECTURE.md`](../ARCHITECTURE.md#states)
- The `revoked` reason in API responses: [`../ARCHITECTURE.md`](../ARCHITECTURE.md#post-apitraining-videoslicensevalidate)
