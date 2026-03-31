<?php

namespace WPChangeLedger\Checkers;

use WPChangeLedger\Logger;

class ThemeChecker extends AbstractChecker {

    public function run(): void {
        $theme      = wp_get_theme();
        $snapshot   = get_option(Logger::OPTION_SNAPSHOT_THEME, []);
        $identifier = $theme->get_stylesheet();
        $name       = $theme->get('Name');
        $version    = $theme->get('Version');

        if (empty($snapshot)) {
            $this->logger->log(
                'theme_activated', 'cron', $identifier,
                "Theme activated: {$name}",
                $version
            );
        } elseif ($snapshot['identifier'] !== $identifier) {
            if (!$this->logger->hasRecentHookEntry('theme_switched', $identifier, $version)) {
                $this->logger->log(
                    'theme_switched', 'cron', $identifier,
                    "Theme switched from {$snapshot['name']} to {$name}",
                    $version
                );
            }
        } elseif ($snapshot['version'] !== $version) {
            if (!$this->logger->hasRecentHookEntry('theme_updated', $identifier, $version)) {
                $this->logger->log(
                    'theme_updated', 'cron', $identifier,
                    "Theme updated: {$name} from {$snapshot['version']} to {$version}",
                    $version,
                    $snapshot['version']
                );
            }
        }

        update_option(Logger::OPTION_SNAPSHOT_THEME, [
            'identifier' => $identifier,
            'name'       => $name,
            'version'    => $version,
        ]);
    }
}
