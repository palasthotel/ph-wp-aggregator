<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 19.10.15
 * Time: 12:42
 */

namespace Aggregator;

class Settings
{

	function __construct(){
		add_action( 'admin_menu', array($this, 'menu_pages') );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	/**
	 * init sections and fields
	 */
	function admin_init(){
		add_settings_section( 'aggregator_file_location', __('File location'), array( $this, 'section_file_location' ), 'aggregator_settings' );
		add_settings_field( 'aggregator_file_location', __('Where to aggregate the files?'), array( $this, 'field_file_location' ), 'aggregator_settings', 'aggregator_file_location');
		register_setting( 'aggregator_settings', 'aggregator_file_location' );
	}

	/**
	 * add menu
	 */
	function menu_pages(){
		add_submenu_page( 'options-general.php', 'Aggregator', 'Aggregator', 'manage_options', "aggregator", array($this, "render"));
	}

	/**
	 * render menu
	 */
	function render(){
		require dirname(__FILE__)."/../partials/settings.php";
	}

	/**
	 * file locations section
	 */
	function section_file_location(){
	}

	/**
	 * file location field
	 */
	function field_file_location(){
		require dirname(__FILE__)."/../partials/settings-file-location.php";
	}
}