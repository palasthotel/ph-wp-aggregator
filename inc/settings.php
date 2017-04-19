<?php

namespace Aggregator;

class Settings {

	const MENU_SLUG = "aggregator";

	const OPTION_CC = "aggregator_clear_cache";

	/**
	 * Settings constructor.
	 *
	 * @param Plugin $plugin
	 */
	function __construct( Plugin $plugin ) {

		$this->plugin = $plugin;
		add_action( 'admin_menu', array( $this, 'menu_pages' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	/**
	 * init sections and fields
	 */
	function admin_init() {

		add_settings_section(
			self::MENU_SLUG,
			__( 'Stats', Plugin::DOMAIN ),
			array( $this, 'section_stats' ),
			self::MENU_SLUG
		);

		add_settings_field( Plugin::OPTION_FILE_LOCATION, __( 'Where to aggregate the files?' ), array(
			$this,
			'field_file_location'
		), self::MENU_SLUG, self::MENU_SLUG );
		add_settings_field( Plugin::OPTION_MINIFY, __( 'Minify?', Plugin::DOMAIN ), array(
			$this,
			'field_minify'
		), self::MENU_SLUG, self::MENU_SLUG );
		add_settings_field( self::OPTION_CC, __( 'Clear aggregated files?', Plugin::DOMAIN ), array(
			$this,
			'field_clear_cache'
		), self::MENU_SLUG, self::MENU_SLUG );

		register_setting( self::MENU_SLUG, Plugin::OPTION_FILE_LOCATION );
		register_setting( self::MENU_SLUG, Plugin::OPTION_MINIFY );
		register_setting( self::MENU_SLUG, self::OPTION_CC, array( $this, 'clear_cache' ) );


	}

	/**
	 * add menu
	 */
	function menu_pages() {
		add_submenu_page( 'options-general.php', 'Aggregator', 'Aggregator', 'manage_options', "aggregator", array(
			$this,
			"render"
		) );
	}

	/**
	 * render menu
	 */
	function render() {

		?>
		<div class="wrap">
			<h2><?php _e( "Aggregator", Plugin::DOMAIN ); ?></h2>
			<form method="post" action="options.php">
				<?php

				settings_fields( self::MENU_SLUG );

				do_settings_sections( self::MENU_SLUG );

				?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * file locations section
	 */
	function section_stats() {
		$paths = $this->plugin->file_handler->paths();
		echo "In {$paths->url} <br>";

		$files = $this->plugin->file_handler->get_all_files();

		echo "Einträge:<br>";
		foreach ( $files as $file ) {
			echo "$file<br>";
		}

		$files_count = count( $files );
		echo "{$files_count} aggregierte JavaScript Dateien";

	}


	/**
	 * file location field
	 */
	function field_file_location() {

		$setting = get_option( Plugin::OPTION_FILE_LOCATION, Plugin::OPTION_FILE_LOCATION_UPLOADS );
		?>
		<select name="aggregator_file_location">
			<option
					value="<?php echo Plugin::OPTION_FILE_LOCATION_UPLOADS; ?>"
				<?php echo( $setting == Plugin::OPTION_FILE_LOCATION_UPLOADS ? 'selected' : '' ); ?>
			>Uploads
			</option>

			<option
					value="<?php echo Plugin::OPTION_FILE_LOCATION_THEME; ?>"
				<?php echo( $setting == Plugin::OPTION_FILE_LOCATION_THEME ? 'selected' : '' ); ?>
			>Theme
			</option>

		</select>
		<?php

	}

	/**
	 * minify field
	 */
	function field_minify() {
		$setting = get_option( Plugin::OPTION_MINIFY, '' );
		?>
		<input type="checkbox" <?php echo ( $setting ) ? "checked" : "" ?>
		       name="<?php echo Plugin::OPTION_MINIFY; ?>"
		       value="1"/>
		<?php

	}

	/**
	 * clear cache
	 */
	function field_clear_cache() {
		?>
		<input type="checkbox" name="<?php echo self::OPTION_CC; ?>" value="1"/>
		<?php

	}

	function clear_cache( $data ) {

		if ( "1" == $data ) {
			$files = $this->plugin->file_handler->get_all_files();
			$dir   = $this->plugin->file_handler->paths()->dir;
			foreach ( $files as $file ) {
				unlink( $dir . "/" . $file );
			}
		}

		return '';
	}

}