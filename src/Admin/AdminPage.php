<?php

namespace WPChangeLedger\Admin;

use WPChangeLedger\Logger;

class AdminPage {

    private const PER_PAGE = 50;

    public function __construct(private Logger $logger) {}

    public function register(): void {
        add_submenu_page(
            'tools.php',
            'Change Ledger',
            'Change Ledger',
            'manage_options',
            'wp-change-ledger',
            [$this, 'render']
        );
    }

    public function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to view this page.'));
        }

        $tab = isset($_GET['tab']) && $_GET['tab'] === 'settings' ? 'settings' : 'log';

        $saved = false;
        if ($tab === 'settings') {
            $saved = Settings::handleSave();
        }

        $issues  = CronHealth::issues();
        $page    = max(1, (int) ($_GET['paged'] ?? 1));
        $log     = $this->logger->getLog(self::PER_PAGE, $page);
        $total   = $this->logger->getLogCount();
        $pages   = (int) ceil($total / self::PER_PAGE);
        $settings = Settings::all();

        include __DIR__ . '/../../views/admin.php';
    }
}
