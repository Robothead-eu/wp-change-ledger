<?php

namespace WPChangeLedger\Admin;

class Settings {

    public const OPTION_KEY = 'wp_change_ledger_settings';

    public const DEFAULTS = [
        'retention_days' => 90,
    ];

    public static function get(string $key): mixed {
        $settings = get_option(self::OPTION_KEY, self::DEFAULTS);
        return $settings[$key] ?? self::DEFAULTS[$key] ?? null;
    }

    public static function all(): array {
        return array_merge(self::DEFAULTS, (array) get_option(self::OPTION_KEY, []));
    }

    /**
     * Handle form submission. Call from admin page on POST.
     * Returns true if settings were saved.
     */
    public static function handleSave(): bool {
        if (
            !isset($_POST['wp_change_ledger_settings_save'])
            || !check_admin_referer('wp_change_ledger_settings')
        ) {
            return false;
        }

        update_option(self::OPTION_KEY, [
            'retention_days' => max(0, (int) ($_POST['retention_days'] ?? self::DEFAULTS['retention_days'])),
        ]);

        return true;
    }
}
