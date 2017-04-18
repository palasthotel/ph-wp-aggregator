<?php

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
		?>
		<div class="wrap">
			<h2><?php  _e( "Aggregator Settings", Plugin::DOMAIN ); ?></h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'aggregator_settings' );
				do_settings_sections( 'aggregator_settings' );
				?>
				<?php submit_button(); ?>
			</form>
			</div>
		<?php
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

		$setting = get_option( Plugin::SETTING_FILE_LOCATION, Plugin::OPTION_FILE_LOCATION_UPLOADS );
		?>
		<select name="aggregator_file_location">
			<option
				value="<?php echo Plugin::OPTION_FILE_LOCATION_UPLOADS; ?>"
				<?php echo( $setting == Plugin::OPTION_FILE_LOCATION_UPLOADS ? 'selected' : '' ); ?>
			>Uploads</option>

			<option
				value="<?php echo Plugin::OPTION_FILE_LOCATION_THEME; ?>"
				<?php echo( $setting == Plugin::OPTION_FILE_LOCATION_THEME ? 'selected' : '' ); ?>
			>Theme</option>

		</select>
		<?php

	}
}