<?php
class AddPluginPage {
	function add_plugin_page() {
		add_submenu_page(
			'tools.php',
			'ChangeTrack-o-Matic',
			'ChangeTrack-o-Matic',
			'manage_options',
			'changetrackomatic-changes',
			array($this, 'create_admin_page')
		);
	}

	function create_admin_page() {
		$log = get_option('changetrackomatic_change_log', array());
		include_once plugin_dir_path(__FILE__) . '../views/adminView.php';
	}
	function view_log($log) {
		$output = [];
		if (!empty($log)) {
			foreach ($log as $entry) {
				if (empty($entry['version'])) {
					$entry['version'] = "no version logged";            	
				}
				$output[] = $entry;
			}
		} 

		return $output;
	}
}