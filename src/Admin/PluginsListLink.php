<?php

namespace WPChangeLedger\Admin;

class PluginsListLink {

    public function addLink(array $links): array {
        if (!current_user_can('manage_options')) {
            return $links;
        }

        $url  = admin_url('tools.php?page=wp-change-ledger');
        $link = sprintf('<a href="%s">%s</a>', esc_url($url), 'Change Log');
        array_unshift($links, $link);

        return $links;
    }
}
