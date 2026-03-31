# WP Change Ledger

A lightweight WordPress plugin that tracks changes to plugins, themes, and WordPress core — including changes made **outside WordPress** via FTP, SSH, deployment scripts, or hosting panel restores.

---

## The problem this solves

On a managed or agency-maintained WordPress site, the question *"what changed and when?"* is harder to answer than it looks.

The obvious approach — listening to WordPress hooks — only captures changes that go through WordPress itself. But a large class of real-world changes don't:

- A developer pushes a deploy via Capistrano, Deployer, or a custom pipeline that rsyncs files directly
- A client's hosting panel restore rolls back plugin files without touching the database
- Someone SSHes in and runs `composer update` or manually replaces a theme folder
- WP-CLI is used with flags that skip hooks
- A staging-to-production push replaces files at the server level
- A cPanel File Manager upload overwrites a plugin

In all of these cases, every existing hook-based change tracker produces a clean, empty log — even though the site just changed in a significant way. This is the gap WP Change Ledger is designed to fill.

---

## How it works

WP Change Ledger uses two complementary tracking mechanisms that run in parallel:

### 1. Hook listeners (real-time)

Listeners attach to WordPress core hooks and fire the moment a change happens through WordPress:

| Hook | What it captures |
|---|---|
| `activated_plugin` / `deactivated_plugin` | Plugin status changes with user attribution |
| `delete_plugin` + `deleted_plugin` | Plugin removal, version captured before files are gone |
| `upgrader_process_complete` | Plugin/theme/core updates, distinguishes automatic vs manual |
| `switch_theme` | Theme changes with previous theme version |

Hook-sourced entries include the WordPress user who made the change and whether it was an automatic or manual update.

### 2. Cron checkers (daily background)

Once per day, each checker reads the current state of plugins, themes, and core, then compares it against a stored snapshot. Any discrepancy — a version that changed, a plugin that appeared or disappeared — gets logged.

This is the layer that catches filesystem-level changes. It doesn't know *who* made the change or *how*, but it knows *what* changed and *when* (within the daily window).

### Deduplication

Every cron entry checks whether a hook-sourced entry for the same event type, identifier, and version already exists within the past 25 hours. If it does, the cron skips it — the hook already captured it with better data. If it doesn't, the cron logs it, which is the signal that the change happened outside WordPress.

### Reading the log

The `source` column is the key diagnostic field:

- `hook` — WordPress knew about this change in real time. You know who did it and how.
- `cron` — WordPress did not know about this change when it happened. Something changed at the filesystem or database level outside of WordPress.

A `cron`-sourced entry with no corresponding `hook` entry means: *look outside WordPress first*.

---

## What it tracks

- Plugin installs, updates, activations, deactivations, and deletions
- Theme switches and version updates
- WordPress core updates
- Whether the change was automatic or manual (hook-detected changes only)
- Which user performed the change (hook-detected changes only)
- Whether the change was caught in real time (`hook`) or by the daily background check (`cron`)
- Previous version on all update events

---

## What it does not do

- Does not detect errors or failures
- Does not send alerts or notifications
- Does not monitor uptime or performance
- Does not predict problems
- Does not modify update behaviour in any way
- Does not attempt to fix issues

These are hard constraints, not missing features. The plugin is intentionally read-only and passive.

---

## Installation

GitHub releases only — not available on the WordPress.org plugin directory.

1. Download the latest release zip from the [Releases](../../releases) page
2. Upload and activate via **Plugins → Add New → Upload Plugin**
3. Find the log under **Tools → Change Ledger**

---

## Requirements

- WordPress 6.0+
- PHP 8.0+

---

## FAQ

### Where is the log?

**Tools → Change Ledger** in the WordPress admin. A "Change Log" link also appears in the plugin's row on the Plugins screen.

### What does the Source column mean?

- `hook` — change was detected in real time by a WordPress hook.
- `cron` — change was detected by the daily background check. This is the expected source for filesystem-level changes (FTP, SSH, deployment pipelines, hosting restores, etc.).

A `cron`-only entry for a change you expected to see as `hook` is a signal worth investigating.

### The log shows a change I didn't expect. What does that mean?

If the entry is `source: cron`, the change happened outside WordPress. Common causes: a deployment pipeline that copies files directly, a hosting backup restore, a manual file edit over FTP/SSH, or a staging push.

If the entry is `source: hook`, the change went through WordPress. Check the `user_login` column and the WordPress admin action log if you have one.

### Does it support Multisite?

Not in the current version. The plugin operates at the individual site level and stores data per-site. Multisite support (network admin view, network-level tracking) is planned for a future release.

### What happens to data when I deactivate the plugin?

All log data and snapshots are preserved. This is intentional — deactivating and reactivating should not destroy your history. Cron events are properly cleared on deactivation and rescheduled on reactivation.

### What happens to data when I delete the plugin?

Data is also preserved on deletion. A data management option (export, clear, selective delete) is planned for a future release. To clear manually: delete the `{prefix}change_ledger_log` table and the `wp_change_ledger_*` options from the database.

### Is there a log size limit?

Configurable under **Tools → Change Ledger → Settings**. Default is 90 days. Set to `0` to keep entries indefinitely. Pruning runs automatically as part of the daily background check.

### I run WP cron from the command line (DISABLE_WP_CRON). Will this work?

Yes. As long as your cron job calls `wp cron event run --due-now` (or hits `wp-cron.php`), the plugin's scheduled events will fire normally. The admin notice about cron health respects `DISABLE_WP_CRON` and will not nag you about timing — it will only alert if the events are not registered in WordPress at all.

### Why daily checks instead of more frequent?

Daily is the right default balance between coverage and performance. The `wp_options` table is not a queue — running snapshot diffs every few minutes on a large plugin list would add meaningful overhead. For most real-world debugging scenarios ("what changed this week?"), daily resolution is sufficient.

If you need sub-daily resolution for a specific investigation, you can trigger the cron events manually: `wp cron event run wp_change_ledger_check_plugins`.

### What is the 24-hour blind spot?

If a plugin is installed and then deleted within a single day before the cron checker runs, the snapshots will match and no change will be recorded. This is an inherent limitation of the snapshot diffing approach. It is documented here so you can account for it — a clean log does not guarantee nothing happened within the last 24 hours.

---

## Changelog

### 2.0.0

Complete rewrite.

- Renamed to WP Change Ledger
- Dedicated database table (`{prefix}change_ledger_log`) instead of serialized option — proper indexing, no memory bloat, paginatable
- Dual tracking: real-time hook listeners + daily cron snapshot checkers
- Hook-sourced events include user attribution and automatic vs manual detection
- Cron deduplication: skips logging if the same event type + identifier + version was already captured by a hook within 25 hours
- `source` field on every entry (`hook` / `cron`)
- `previous_version` field on all update events
- Configurable log retention (default 90 days), pruning runs on daily cron cycle
- Cron health notice on Change Ledger pages only: warns if events are unscheduled; respects `DISABLE_WP_CRON`
- Cron events self-heal on `admin_init` if another plugin accidentally clears them
- PSR-4 class structure under `WPChangeLedger\` namespace
- Proper `register_activation_hook` / `register_deactivation_hook` lifecycle
- `manage_options` capability check enforced on admin page render
- All output escaped with `esc_html()`
- Paginated log table (50 entries per page)
- Tabbed admin UI: Log + Settings
- "Change Log" action link in plugin row on Plugins screen
- Consistent naming throughout (Tools → Change Ledger)

### 1.0.0

Initial release as ChangeTrack-o-Matic.

---

## Author

[Robothead](https://robothead.eu) · [wpchangeledger.com](https://wpchangeledger.com)

## License

GPL-2.0-or-later
