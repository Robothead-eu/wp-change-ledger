<?php

namespace WPChangeLedger\Listeners;

use WPChangeLedger\Logger;

class CoreListener {

    public function __construct(private Logger $logger) {
        add_action('upgrader_process_complete', [$this, 'onUpgraderComplete'], 10, 2);
    }

    public function onUpgraderComplete(\WP_Upgrader $upgrader, array $hookExtra): void {
        if (($hookExtra['type'] ?? '') !== 'core') {
            return;
        }

        $automatic = class_exists('Automatic_Upgrader_Skin') && $upgrader->skin instanceof \Automatic_Upgrader_Skin;
        $version   = get_bloginfo('version');

        $this->logger->log(
            'core_updated', 'hook', 'core',
            "WordPress updated to {$version}",
            $version,
            null, null, null, $automatic
        );
    }
}
