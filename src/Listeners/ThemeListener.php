<?php

namespace WPChangeLedger\Listeners;

use WPChangeLedger\Concerns\DetectsCurrentUser;
use WPChangeLedger\Logger;

class ThemeListener {

    use DetectsCurrentUser;

    public function __construct(private Logger $logger) {
        add_action('switch_theme',              [$this, 'onSwitched'],         10, 3);
        add_action('upgrader_process_complete', [$this, 'onUpgraderComplete'], 10, 2);
    }

    public function onSwitched(string $newName, \WP_Theme $newTheme, \WP_Theme $oldTheme): void {
        [$userId, $login] = $this->currentUser();

        $this->logger->log(
            'theme_switched', 'hook', $newTheme->get_stylesheet(),
            "Theme switched from {$oldTheme->get('Name')} to {$newName}",
            $newTheme->get('Version'),
            $oldTheme->get('Version'),
            $userId, $login, false
        );
    }

    public function onUpgraderComplete(\WP_Upgrader $upgrader, array $hookExtra): void {
        if (($hookExtra['type'] ?? '') !== 'theme' || ($hookExtra['action'] ?? '') !== 'update') {
            return;
        }

        $automatic        = class_exists('Automatic_Upgrader_Skin') && $upgrader->skin instanceof \Automatic_Upgrader_Skin;
        [$userId, $login] = $automatic ? [null, null] : $this->currentUser();

        foreach ($hookExtra['themes'] ?? [] as $slug) {
            $theme = wp_get_theme($slug);
            $this->logger->log(
                'theme_updated', 'hook', $slug,
                "Theme updated: {$theme->get('Name')}",
                $theme->get('Version'),
                null, $userId, $login, $automatic
            );
        }
    }
}
