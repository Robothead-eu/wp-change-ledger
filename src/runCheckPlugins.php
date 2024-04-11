<?php
class runCheckPlugins {
	function run_check_plugin(){
		$active_plugins = (array) get_option('active_plugins', array());
		$all_plugins = get_plugins();
		$current_active_plugins = array();
		$logged_plugins = get_option('changetrackomatic_logged_plugins', array());

		foreach($active_plugins as $plugin){
			$current_active_plugins[$plugin] = array(
				'name' => $all_plugins[$plugin]['Name'],
				'version' => $all_plugins[$plugin]['Version'],
				'status' => 'active'
			);
		} 

		foreach($all_plugins as $plugin_path => $plugin){
			if(!array_key_exists($plugin_path, $current_active_plugins)){
				$current_active_plugins[$plugin_path] = array(
					'name' => $plugin['Name'],
					'version' => $plugin['Version'],
					'status' => 'inactive'
				);
			}
		}

		if(!empty($logged_plugins)){
			foreach($current_active_plugins as $plugin_path => $plugin){
				if(!array_key_exists($plugin_path, $logged_plugins)){
					$this->log_change('new_plugin', "Plugin installed: " . $plugin['name'], $plugin['version']);
				}
				else{
					if($logged_plugins[$plugin_path]['status'] != $plugin['status']){
						if($plugin['status'] == 'active'){
							$this->log_change('plugin_activated', "Plugin activated: " . $plugin['name'], $plugin['version']);
						} else {
							$this->log_change('plugin_deactivated', "Plugin deactivated: " . $plugin['name'], $plugin['version']);
						}
					} elseif ($logged_plugins[$plugin_path]['version'] != $plugin['version']) {
						$this->log_change('plugin_change', "Plugin " . $plugin['name'] . " version changed", $plugin['version']);
					}
				}
			}             

			foreach($logged_plugins as $plugin_path => $plugin){
				if(!array_key_exists($plugin_path, $current_active_plugins)){
					$this->log_change('deleted_plugin', "Plugin deleted: " . $plugin['name'], $plugin['version']);
				}
			}
		}

		update_option('changetrackomatic_logged_plugins', $current_active_plugins);
	}

	function log_change($type, $message, $version){
		$log = get_option('changetrackomatic_change_log', array());
		if (empty($version)) {
			$version = "no version detected";            	
		}

		array_push($log, array(
			'type' => $type, 
			'message' => $message, 
			'time' => current_time('mysql'),
			'version' => $version
		));

		update_option('changetrackomatic_change_log', $log);
	}

}