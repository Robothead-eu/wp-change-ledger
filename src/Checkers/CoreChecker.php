<?php

namespace WPChangeLedger\Checkers;

use WPChangeLedger\Logger;

class CoreChecker extends AbstractChecker {

    public function run(): void {
        $current  = get_bloginfo('version');
        $snapshot = get_option(Logger::OPTION_SNAPSHOT_CORE, '');

        if ($snapshot === '') {
            $this->logger->log(
                'core_updated', 'cron', 'core',
                "WordPress installed: {$current}",
                $current
            );
        } elseif ($snapshot !== $current) {
            if (!$this->logger->hasRecentHookEntry('core_updated', 'core', $current)) {
                $this->logger->log(
                    'core_updated', 'cron', 'core',
                    "WordPress updated from {$snapshot} to {$current}",
                    $current,
                    $snapshot
                );
            }
        }

        update_option(Logger::OPTION_SNAPSHOT_CORE, $current);
    }
}
