<?php
/**
* Plugin Name: ChangeTrack-o-Matic
* Description: Logs and checks plugin data changes
* Version: 1.0
* Author: Robothead
* Author URI: https://robothead.eu
*/




if(!class_exists('ChangeTrackoMatic')){
	class ChangeTrackoMatic{

		public $addPluginPage;
		public $runCheckTheme;
		public $runCheckWPVersion;
		public $runCheckPlugins;



		function __construct(){

			require_once('src/runCheckWPVersion.php');
			require_once('src/runCheckPlugins.php');
			require_once('src/runCheckTheme.php');
			require_once('src/admin.php');


			if(! wp_next_scheduled ( 'changetrackomatic_logging_event_plugin' )) {
				wp_schedule_event(time(), 'hourly', 'changetrackomatic_logging_event_plugin' );
			}
			if(! wp_next_scheduled ( 'changetrackomatic_logging_wp_version_event' )) {
				wp_schedule_event(time(), 'hourly', 'changetrackomatic_logging_wp_version_event' );
			}

			if(! wp_next_scheduled ( 'changetrackomatic_logging_theme_event' )) {
				wp_schedule_event(time(), 'hourly', 'changetrackomatic_logging_theme_event' );
			}

			$this->runCheckTheme = new runCheckTheme();
			add_action('changetrackomatic_logging_theme_event', array($this->runCheckTheme, 'run_check_theme'));

			$this->runCheckPlugins = new runCheckPlugins();
			add_action('changetrackomatic_logging_event_plugin', array($this->runCheckPlugins, 'run_check_plugin'));

			$this->runCheckWPVersion = new runCheckWPVersion();
			add_action('changetrackomatic_logging_wp_version_event', array($this->runCheckWPVersion, 'run_check_wp_version'));


			if( is_admin() ) {
				$this->addPluginPage = new AddPluginPage();
				add_action('admin_menu', array($this->addPluginPage, 'add_plugin_page'));
			}
		}

	}

	$changeTrackoMatic = new ChangeTrackoMatic();
}


