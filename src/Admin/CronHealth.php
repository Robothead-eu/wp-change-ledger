<?php

namespace WPChangeLedger\Admin;

class CronHealth {

    public const OPTION_LAST_RUN = 'wp_change_ledger_last_cron_run';

    private const STALE_THRESHOLD_HOURS = 48;

    private const CRON_HOOKS = [
        'wp_change_ledger_check_plugins',
        'wp_change_ledger_check_themes',
        'wp_change_ledger_check_core',
    ];

    public static function recordRun(): void {
        update_option(self::OPTION_LAST_RUN, current_time('mysql'));
    }

    /**
     * Returns an array of human-readable issue strings, empty if everything is healthy.
     * Shown only on our own admin pages.
     *
     * Rules:
     * - If any cron event is not scheduled → warn always (they need to add it to their crontab).
     * - If events are scheduled, WP cron is enabled, and last run is stale → warn.
     * - If DISABLE_WP_CRON is true and events are scheduled → trust the user, no nag.
     */
    public static function issues(): array {
        $issues = [];

        // Check if our events are registered in WP's cron system
        $notScheduled = [];
        foreach (self::CRON_HOOKS as $hook) {
            if (!wp_next_scheduled($hook)) {
                $notScheduled[] = $hook;
            }
        }

        if (!empty($notScheduled)) {
            $issues[] = 'One or more background check events are not scheduled. '
                . 'Try deactivating and reactivating the plugin. '
                . 'If you run WP-CLI cron, make sure these hooks are registered: '
                . implode(', ', $notScheduled) . '.';
            return $issues; // No point checking last-run if not even scheduled
        }

        // Only nag about last-run timing when WordPress manages its own cron
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            return $issues;
        }

        $lastRun = get_option(self::OPTION_LAST_RUN);

        if ($lastRun && strtotime($lastRun) < (time() - (self::STALE_THRESHOLD_HOURS * HOUR_IN_SECONDS))) {
            $issues[] = 'Background checks last ran ' . human_time_diff(strtotime($lastRun)) . ' ago. '
                . 'WordPress cron may not be firing. Consider using a real cron job: '
                . '<code>*/15 * * * * php ' . ABSPATH . 'wp-cron.php</code> '
                . 'or set <code>DISABLE_WP_CRON</code> and run <code>wp cron event run --due-now</code>.';
        }

        return $issues;
    }
}
