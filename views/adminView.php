<style>
.changetrackomatic table {
    border-collapse: separate;
    width: 100%;
}
.changetrackomatic th, .changetrackomatic td {
    padding: 15px;
    text-align: left;
}
.changetrackomatic th {
    background-color: #f5f5f5;
}
.install-action {
    background-color: #CEF6D8; /*light green */
}
.update-action {
    background-color: #81BEF7; /*light blue */
}
.delete-action {
    background-color: #ffab9e; /*light blue */
}
</style>
<?php
$log_data = $this->view_log($log);
if (empty($log_data)):
    echo "<div class='wrap changetrackomatic'><h2>Change-Track-o_matic Changes Log</h2><p>Sorry, no data yet. Please wait to collect first data</p></div>";
else: 
?>
<div class='wrap changetrackomatic'><h2>Log-O-Matic Changes Log</h2>
<table>
    <thead>
        <tr>
            <th>Type</th>
            <th>Message</th>
            <th>Time</th>
            <th>Version</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($log_data as $row): 
            $class = '';
            if ($row['type'] === 'plugin_change') {
                $class = 'update-action';
            } elseif ($row['type'] === 'new_plugin') {
                $class = 'install-action';
            } elseif ($row['type'] === 'deleted_plugin') {
                $class = 'delete-action';
            }
        ?>
        <tr class="<?= $class ?>">
            <td><?php echo $row['type']; ?></td>
            <td><?php echo $row['message']; ?></td>
            <td><?php echo $row['time']; ?></td>
            <td><?php echo $row['version']; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>''