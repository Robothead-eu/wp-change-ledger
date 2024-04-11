<?php
class runCheckTheme {
	
	function run_check_theme() {
		$current_theme = wp_get_theme();
		$logged_theme = get_option('changetrackomatic_logged_theme', array());

		if(empty($logged_theme) || $current_theme->name != $logged_theme['name'] || $current_theme->version != $logged_theme['version']) {
			$this->log_theme_change($current_theme, $logged_theme);
		}

		update_option('changetrackomatic_logged_theme', array('name' => $current_theme->name, 'version' => $current_theme->version));
	}

	function log_theme_change($current_theme, $logged_theme) {
		$log = get_option('changetrackomatic_change_log', array());

		if(empty($logged_theme)) {
			$message = "Theme activated: " . $current_theme->name;
		} elseif($current_theme->name != $logged_theme['name']) {
			$message = "Theme switched: from " . $logged_theme['name'] . " to " . $current_theme->name;
		} else {
			$message = "Theme " . $current_theme->name . " version changed from " . $logged_theme['version'] . " to " . $current_theme->version;
		}

		array_push($log, array(
			'type' => 'theme_change', 
			'message' => $message, 
			'time' => current_time('mysql'),
			'version' => $current_theme->version
		));

		update_option('changetrackomatic_change_log', $log);
	}
}