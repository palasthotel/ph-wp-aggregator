<?php

namespace Aggregator;

/**
 * Plugin Name: Aggregator
 * Description: Aggregates js files.
 * Version: 2.0
 * Author: Palasthotel <rezeption@palasthotel.de> (Edward Bock)
 * Author URI: https://palasthotel.de
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Plugin {

	const DOMAIN = "aggregator";

	const OPTION_FILE_LOCATION = "aggregator_file_location";
	const OPTION_FILE_LOCATION_UPLOADS = "uploads";
	const OPTION_FILE_LOCATION_THEME = "theme";

	const OPTION_MINIFY = "aggregator_minify";

	const FILTER_IGNORE_FILE = "aggregator_ignpre";
	/**
	 * @deprecated
	 */
	const FILTER_IGNORED_FILES = "ph_aggregator_ignore";

	/**
	 * js handles
	 */
	const HANDLE_HEADER = "aggregator-header";
	const HANDLE_FOOTER = "aggregator-footer";

	/**
	 * register actions and filters
	 */
	function __construct() {

		/*
		 * base paths
		 */
		$this->dir = plugin_dir_path( __FILE__ );
		$this->url = plugin_dir_url( __FILE__ );

		// TODO:check if localization works

		/*
		 * classes
		 */
		require( dirname( __FILE__ ) . "/inc/settings.php" );
		$this->settings = new Settings($this);

		require( dirname( __FILE__ ) . "/inc/file-handler.php" );
		$this->file_handler = new FileHandler($this);

		require( dirname( __FILE__ ) . "/inc/scripts.php" );
		$this->scripts = new Scripts($this);

		require dirname(__FILE__). "/inc/test.php";
		$this->test = new Test($this);
	}

}

new Plugin();










