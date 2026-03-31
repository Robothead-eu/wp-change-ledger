<?php
/**
 * Plugin Name: WP Change Ledger
 * Plugin URI:  https://wpchangeledger.com
 * Description: Tracks changes to plugins, themes, and WordPress core — including changes made outside WordPress via FTP, SSH, or deployment scripts.
 * Version:     2.0.0
 * Author:      Robothead
 * Author URI:  https://robothead.eu
 * License:     GPL-2.0-or-later
 * Requires PHP: 8.0
 * Requires at least: 6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

spl_autoload_register(function (string $class): void {
    $prefix = 'WPChangeLedger\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

register_activation_hook(__FILE__, ['WPChangeLedger\\Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['WPChangeLedger\\Plugin', 'deactivate']);

WPChangeLedger\Plugin::boot(__FILE__);
