<?php

namespace WPChangeLedger\Listeners;

use WPChangeLedger\Concerns\DetectsCurrentUser;
use WPChangeLedger\Logger;

class PluginListener {

    use DetectsCurrentUser;

    /** Holds plugin data captured just before deletion, keyed by plugin path. */
    private array $pendingDeletions = [];

    public function __construct(private Logger $logger) {
        add_action('activated_plugin',          [$this, 'onActivated'],        10, 1);
        add_action('deactivated_plugin',        [$this, 'onDeactivated'],      10, 1);
        add_action('delete_plugin',             [$this, 'beforeDeleted'],      10, 1);
        add_action('deleted_plugin',            [$this, 'onDeleted'],          10, 2);
        add_action('upgrader_process_complete', [$this, 'onUpgraderComplete'], 10, 2);
    }

    public function onActivated(string $pluginPath): void {
        $data              = get_plugins()[$pluginPath] ?? null;
        [$userId, $login]  = $this->currentUser();

        $this->logger->log(
            'plugin_activated', 'hook', $pluginPath,
            'Plugin activated: ' . ($data['Name'] ?? $pluginPath),
            $data['Version'] ?? null,
            null, $userId, $login, false
        );
    }

    public function onDeactivated(string $pluginPath): void {
        $data             = get_plugins()[$pluginPath] ?? null;
        [$userId, $login] = $this->currentUser();

        $this->logger->log(
            'plugin_deactivated', 'hook', $pluginPath,
            'Plugin deactivated: ' . ($data['Name'] ?? $pluginPath),
            $data['Version'] ?? null,
            null, $userId, $login, false
        );
    }

    /**
     * Capture plugin data before files are removed so we can log the version
     * even though the files will be gone by the time onDeleted() fires.
     */
    public function beforeDeleted(string $pluginPath): void {
        $data = get_plugins()[$pluginPath] ?? null;
        if ($data !== null) {
            $this->pendingDeletions[$pluginPath] = $data;
        }
    }

    public function onDeleted(string $pluginPath, bool $deleted): void {
        if (!$deleted) {
            return;
        }

        $data             = $this->pendingDeletions[$pluginPath] ?? null;
        [$userId, $login] = $this->currentUser();

        $this->logger->log(
            'plugin_deleted', 'hook', $pluginPath,
            'Plugin deleted: ' . ($data['Name'] ?? $pluginPath),
            $data['Version'] ?? null,
            null, $userId, $login, false
        );

        unset($this->pendingDeletions[$pluginPath]);
    }

    public function onUpgraderComplete(\WP_Upgrader $upgrader, array $hookExtra): void {
        if (($hookExtra['type'] ?? '') !== 'plugin' || ($hookExtra['action'] ?? '') !== 'update') {
            return;
        }

        $automatic        = class_exists('Automatic_Upgrader_Skin') && $upgrader->skin instanceof \Automatic_Upgrader_Skin;
        [$userId, $login] = $automatic ? [null, null] : $this->currentUser();
        $allPlugins       = get_plugins();

        foreach ($hookExtra['plugins'] ?? [] as $pluginPath) {
            $data = $allPlugins[$pluginPath] ?? null;
            $this->logger->log(
                'plugin_updated', 'hook', $pluginPath,
                'Plugin updated: ' . ($data['Name'] ?? $pluginPath),
                $data['Version'] ?? null,
                null, $userId, $login, $automatic
            );
        }
    }
}
