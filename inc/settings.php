<?php

namespace Aggregator;

class Settings {

	const MENU_SLUG = "aggregator";
	const SECTION_SETTINGS = "aggregator_settings";
	const SECTION_STATS = "aggregator_stats";

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

//		add_settings_section(
//			self::SECTION_STATS,
//			__( 'Statistics', Plugin::DOMAIN ),
//			array( $this, 'section_stats' ),
//			self::MENU_SLUG
//		);

		add_settings_section(
			self::MENU_SLUG,
			__( 'Stats', Plugin::DOMAIN ),
			array( $this, 'section_stats' ),
			self::MENU_SLUG
		);

		add_settings_field( 'aggregator_file_location', __( 'Where to aggregate the files?' ), array(
			$this,
			'field_file_location'
		), self::MENU_SLUG, self::MENU_SLUG );
		add_settings_field( 'aggregator_minify', __( 'Minify?', Plugin::DOMAIN ), array(
			$this,
			'field_minify'
		), self::MENU_SLUG, self::MENU_SLUG );

		register_setting( self::MENU_SLUG, Plugin::OPTION_FILE_LOCATION );
		register_setting( self::MENU_SLUG, Plugin::OPTION_MINIFY );



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
	function render( $args ) {

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
	function field_minify( $args ) {

		$setting = get_option( Plugin::OPTION_MINIFY, 0 );
		?>
		<input type="checkbox" <?php echo ( $setting ) ? "checked" : "" ?> name="<?php echo Plugin::OPTION_MINIFY ?>"
		       value="1"/>
		<?php

	}

	/**
	 * file locations section
	 */
	function section_stats( $args ) {
		$paths = $this->plugin->file_handler->paths();
		echo "In {$paths->url} <br>";

		if ( is_dir($paths->dir) && $handle = opendir( $paths->dir ) ) {

			echo "Einträge:<br>";

			$i = 0;
			while ( false !== ( $entry = readdir( $handle ) ) ) {
				if ( preg_match( "/.*\.js/", $entry ) ) {
					$i ++;
					echo "$entry<br>";
				}

			}

			echo "{$i} aggregierte JavaScript Dateien";

			closedir( $handle );
		}
	}
}