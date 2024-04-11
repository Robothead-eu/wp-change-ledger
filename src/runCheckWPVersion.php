<?php 
class runCheckWPVersion {

	function run_check_wp_version(){
		$current_wp_version = get_bloginfo('version');
		$logged_wp_version = get_option('changetrackomatic_logged_wp_version');

		if($logged_wp_version != $current_wp_version){
			$this->log_wp_version_change($current_wp_version, $logged_wp_version);
		}

		update_option('changetrackomatic_logged_wp_version', $current_wp_version);
	}
	function log_wp_version_change($current_version, $old_version){
		$log = get_option('changetrackomatic_change_log', array());

		if(empty($old_version)){
			$message = "WordPress installed: " . $current_version;
		} else {
			$message = "WordPress updated from $old_version to $current_version";
		}

		array_push($log, array(
			'type' => 'wp_version_change', 
			'message' => $message,
			'time' => current_time('mysql'),
			'version' => $current_version
		));

		update_option('changetrackomatic_change_log', $log);
	}

}