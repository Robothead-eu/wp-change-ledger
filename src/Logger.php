<?php

namespace WPChangeLedger;

class Logger {

    public const DB_VERSION              = '1';
    public const OPTION_DB_VERSION       = 'wp_change_ledger_db_version';
    public const OPTION_SNAPSHOT_PLUGINS = 'wp_change_ledger_snapshot_plugins';
    public const OPTION_SNAPSHOT_THEME   = 'wp_change_ledger_snapshot_theme';
    public const OPTION_SNAPSHOT_CORE    = 'wp_change_ledger_snapshot_core';

    private function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'change_ledger_log';
    }

    public function log(
        string  $type,
        string  $source,
        string  $identifier,
        string  $message,
        ?string $version         = null,
        ?string $previousVersion = null,
        ?int    $userId          = null,
        ?string $userLogin       = null,
        ?bool   $automatic       = null
    ): void {
        global $wpdb;

        $wpdb->insert(
            $this->table(),
            [
                'type'             => $type,
                'source'           => $source,
                'identifier'       => $identifier,
                'message'          => $message,
                'time'             => current_time('mysql'),
                'version'          => $version ?? 'unknown',
                'previous_version' => $previousVersion,
                'user_id'          => $userId,
                'user_login'       => $userLogin,
                'automatic'        => $automatic === null ? null : (int) $automatic,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d']
        );
    }

    /**
     * Returns log entries in reverse chronological order with optional pagination.
     *
     * @return array<int, object>
     */
    public function getLog(int $perPage = 50, int $page = 1): array {
        global $wpdb;
        $offset = ($page - 1) * $perPage;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table()} ORDER BY id DESC LIMIT %d OFFSET %d",
                $perPage,
                $offset
            )
        );
    }

    public function getLogCount(): int {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table()}");
    }

    /**
     * Check whether a hook-sourced entry already exists for this type + identifier + version
     * within the past 25 hours. Prevents cron from duplicating events already captured live.
     */
    public function hasRecentHookEntry(string $type, string $identifier, string $version): bool {
        global $wpdb;
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table()}
                 WHERE source = 'hook'
                   AND type = %s
                   AND identifier = %s
                   AND version = %s
                   AND time >= %s",
                $type,
                $identifier,
                $version,
                gmdate('Y-m-d H:i:s', time() - (25 * HOUR_IN_SECONDS))
            )
        );
        return (int) $count > 0;
    }

    /**
     * Delete entries older than $days days. 0 = keep forever.
     */
    public function prune(int $days): void {
        if ($days <= 0) {
            return;
        }
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table()} WHERE time < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }
}
