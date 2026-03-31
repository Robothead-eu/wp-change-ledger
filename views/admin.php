<style>
.wp-change-ledger-wrap .nav-tab-wrapper { margin-bottom: 20px; }
.wp-change-ledger table { border-collapse: separate; width: 100%; }
.wp-change-ledger th,
.wp-change-ledger td { padding: 10px 14px; text-align: left; vertical-align: top; }
.wp-change-ledger th { background-color: #f5f5f5; }
.wp-change-ledger .source-cron { font-style: italic; color: #888; font-size: 0.85em; }
.wp-change-ledger .install-action    { background-color: #d4edda; }
.wp-change-ledger .update-action     { background-color: #d6eaff; }
.wp-change-ledger .delete-action     { background-color: #ffab9e; }
.wp-change-ledger .deactivate-action { background-color: #fff3cd; }
.wp-change-ledger .pagination { margin-top: 12px; }
.wp-change-ledger .settings-table th { background: none; width: 200px; vertical-align: middle; }
</style>

<div class="wrap wp-change-ledger-wrap">
    <h1>Change Ledger</h1>

    <?php foreach ($issues as $issue): ?>
    <div class="notice notice-warning">
        <p><?php echo wp_kses($issue, ['code' => []]); ?></p>
    </div>
    <?php endforeach; ?>

    <?php if ($saved): ?>
    <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
    <?php endif; ?>

    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url(admin_url('tools.php?page=wp-change-ledger')); ?>"
           class="nav-tab <?php echo $tab === 'log' ? 'nav-tab-active' : ''; ?>">Log</a>
        <a href="<?php echo esc_url(admin_url('tools.php?page=wp-change-ledger&tab=settings')); ?>"
           class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
    </nav>

    <?php if ($tab === 'log'): ?>

    <div class="wp-change-ledger">
    <?php if (empty($log) && $page === 1): ?>
        <p>No changes recorded yet. The plugin tracks changes in real time via WordPress hooks and runs a daily background check to catch anything that bypassed WordPress (e.g. FTP, SSH, deployment scripts).</p>
    <?php else: ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Type</th>
                    <th>Source</th>
                    <th>Message</th>
                    <th>Version</th>
                    <th>Previous</th>
                    <th>User</th>
                    <th>Automatic</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($log as $row):
                $type  = $row->type ?? '';
                $class = match ($type) {
                    'plugin_installed', 'theme_activated'              => 'install-action',
                    'plugin_updated', 'theme_updated', 'core_updated'  => 'update-action',
                    'plugin_deleted'                                   => 'delete-action',
                    'plugin_deactivated'                               => 'deactivate-action',
                    default                                            => '',
                };
            ?>
                <tr class="<?php echo esc_attr($class); ?>">
                    <td><?php echo esc_html($row->time ?? ''); ?></td>
                    <td><?php echo esc_html($type); ?></td>
                    <td>
                        <span class="<?php echo ($row->source ?? '') === 'cron' ? 'source-cron' : ''; ?>">
                            <?php echo esc_html($row->source ?? ''); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($row->message ?? ''); ?></td>
                    <td><?php echo esc_html($row->version ?? '—'); ?></td>
                    <td><?php echo esc_html($row->previous_version ?? '—'); ?></td>
                    <td><?php echo esc_html($row->user_login ?? '—'); ?></td>
                    <td><?php
                        if ($row->automatic === null) {
                            echo '—';
                        } else {
                            echo esc_html($row->automatic ? 'Yes' : 'No');
                        }
                    ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($pages > 1): ?>
        <div class="pagination tablenav">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo esc_html(number_format_i18n($total)); ?> entries</span>
                <?php if ($page > 1): ?>
                    <a class="button" href="<?php echo esc_url(add_query_arg('paged', $page - 1)); ?>">&laquo; Previous</a>
                <?php endif; ?>
                <span>Page <?php echo esc_html($page); ?> of <?php echo esc_html($pages); ?></span>
                <?php if ($page < $pages): ?>
                    <a class="button" href="<?php echo esc_url(add_query_arg('paged', $page + 1)); ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>
    </div>

    <?php else: // settings tab ?>

    <form method="post" action="<?php echo esc_url(admin_url('tools.php?page=wp-change-ledger&tab=settings')); ?>">
        <?php wp_nonce_field('wp_change_ledger_settings'); ?>
        <table class="form-table wp-change-ledger">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="retention_days">Log retention</label>
                    </th>
                    <td>
                        <input type="number" id="retention_days" name="retention_days"
                               value="<?php echo esc_attr($settings['retention_days']); ?>"
                               min="0" step="1" class="small-text" />
                        <p class="description">
                            Days to keep log entries. Set to <code>0</code> to keep forever.
                            Current log: <strong><?php echo esc_html(number_format_i18n($total)); ?></strong> entries.
                            <!-- Log size limit / pruning is intentional v1 scope — see README. -->
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="wp_change_ledger_settings_save"
                   class="button button-primary" value="Save Settings" />
        </p>
    </form>

    <?php endif; ?>
</div>
