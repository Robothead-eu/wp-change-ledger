<?php

namespace WPChangeLedger\Checkers;

use WPChangeLedger\Logger;

class PluginChecker extends AbstractChecker {

    public function run(): void {
        $activePluginPaths = (array) get_option('active_plugins', []);
        $allPlugins        = get_plugins();
        $snapshot          = get_option(Logger::OPTION_SNAPSHOT_PLUGINS, []);

        $current = [];
        foreach ($allPlugins as $path => $data) {
            $current[$path] = [
                'name'    => $data['Name'],
                'version' => $data['Version'],
                'status'  => in_array($path, $activePluginPaths, true) ? 'active' : 'inactive',
            ];
        }

        if (!empty($snapshot)) {
            foreach ($current as $path => $plugin) {
                if (!array_key_exists($path, $snapshot)) {
                    if (!$this->logger->hasRecentHookEntry('plugin_installed', $path, $plugin['version'])) {
                        $this->logger->log(
                            'plugin_installed', 'cron', $path,
                            "Plugin installed: {$plugin['name']}",
                            $plugin['version']
                        );
                    }
                } else {
                    $prev = $snapshot[$path];
                    if ($prev['version'] !== $plugin['version']) {
                        if (!$this->logger->hasRecentHookEntry('plugin_updated', $path, $plugin['version'])) {
                            $this->logger->log(
                                'plugin_updated', 'cron', $path,
                                "Plugin updated: {$plugin['name']}",
                                $plugin['version'],
                                $prev['version']
                            );
                        }
                    } elseif ($prev['status'] !== $plugin['status']) {
                        $type = $plugin['status'] === 'active' ? 'plugin_activated' : 'plugin_deactivated';
                        if (!$this->logger->hasRecentHookEntry($type, $path, $plugin['version'])) {
                            $this->logger->log(
                                $type, 'cron', $path,
                                "Plugin {$plugin['status']}: {$plugin['name']}",
                                $plugin['version']
                            );
                        }
                    }
                }
            }

            foreach ($snapshot as $path => $plugin) {
                if (!array_key_exists($path, $current)) {
                    if (!$this->logger->hasRecentHookEntry('plugin_deleted', $path, $plugin['version'])) {
                        $this->logger->log(
                            'plugin_deleted', 'cron', $path,
                            "Plugin deleted: {$plugin['name']}",
                            $plugin['version']
                        );
                    }
                }
            }
        }

        update_option(Logger::OPTION_SNAPSHOT_PLUGINS, $current);
    }
}
