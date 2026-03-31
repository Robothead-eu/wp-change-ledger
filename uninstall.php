<?php
/**
 * WP Change Ledger — Uninstall handler.
 *
 * Data retention is intentional: all log and snapshot options are preserved
 * on uninstall so history survives a reinstall. A data deletion option is
 * planned for a future release. See README for details.
 *
 * Options retained in the database:
 *   wp_change_ledger_log
 *   wp_change_ledger_snapshot_plugins
 *   wp_change_ledger_snapshot_theme
 *   wp_change_ledger_snapshot_core
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
