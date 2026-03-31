<?php

namespace WPChangeLedger;

use WPChangeLedger\Admin\AdminPage;
use WPChangeLedger\Admin\CronHealth;
use WPChangeLedger\Admin\PluginsListLink;
use WPChangeLedger\Admin\Settings;
use WPChangeLedger\Checkers\CoreChecker;
use WPChangeLedger\Checkers\PluginChecker;
use WPChangeLedger\Checkers\ThemeChecker;
use WPChangeLedger\Listeners\CoreListener;
use WPChangeLedger\Listeners\PluginListener;
use WPChangeLedger\Listeners\ThemeListener;

class Plugin {

    private static string $file;

    private const CRON_HOOKS = [
        'wp_change_ledger_check_plugins',
        'wp_change_ledger_check_themes',
        'wp_change_ledger_check_core',
    ];

    public static function boot(string $file): void {
        static::$file = $file;
        (new static())->init();
    }

    public static function activate(): void {
        static::createTable();

        foreach (static::CRON_HOOKS as $hook) {
            if (!wp_next_scheduled($hook)) {
                wp_schedule_event(time(), 'daily', $hook);
            }
        }
    }

    public static function deactivate(): void {
        foreach (static::CRON_HOOKS as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
        }
    }

    /**
     * Create or upgrade the log table using dbDelta.
     * Safe to call on every activation — dbDelta is idempotent.
     */
    public static function createTable(): void {
        global $wpdb;

        $table   = $wpdb->prefix . 'change_ledger_log';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  type varchar(50) NOT NULL,
  source varchar(10) NOT NULL,
  identifier varchar(255) NOT NULL,
  message text NOT NULL,
  time datetime NOT NULL,
  version varchar(100) DEFAULT NULL,
  previous_version varchar(100) DEFAULT NULL,
  user_id bigint(20) unsigned DEFAULT NULL,
  user_login varchar(60) DEFAULT NULL,
  automatic tinyint(1) DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY idx_time (time),
  KEY idx_identifier_version (identifier(100),version(50))
) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option(Logger::OPTION_DB_VERSION, Logger::DB_VERSION);
    }

    private function init(): void {
        $logger = new Logger();

        // Ensure table exists (guards against manual option deletions or failed activations)
        if (get_option(Logger::OPTION_DB_VERSION) !== Logger::DB_VERSION) {
            static::createTable();
        }

        // Real-time hook-based listeners
        new PluginListener($logger);
        new ThemeListener($logger);
        new CoreListener($logger);

        // Daily cron-based checkers (catches changes that bypassed WP hooks)
        $pluginChecker = new PluginChecker($logger);
        add_action('wp_change_ledger_check_plugins', function () use ($pluginChecker, $logger): void {
            $pluginChecker->run();
            CronHealth::recordRun();
            $logger->prune((int) Settings::get('retention_days'));
        });

        $themeChecker = new ThemeChecker($logger);
        add_action('wp_change_ledger_check_themes', [$themeChecker, 'run']);

        $coreChecker = new CoreChecker($logger);
        add_action('wp_change_ledger_check_core', [$coreChecker, 'run']);

        // Re-register cron events on every admin page load — cheap self-heal
        // in case another plugin accidentally cleared our events
        if (is_admin()) {
            add_action('admin_init', [static::class, 'activate']);
        }

        if (is_admin()) {
            $adminPage = new AdminPage($logger);
            add_action('admin_menu', [$adminPage, 'register']);

            $pluginsListLink = new PluginsListLink();
            add_filter(
                'plugin_action_links_' . plugin_basename(static::$file),
                [$pluginsListLink, 'addLink']
            );
        }
    }
}
