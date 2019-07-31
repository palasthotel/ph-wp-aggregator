<?php

namespace Aggregator;

/**
 * @property \Aggregator\Plugin plugin
 */
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

		add_filter('plugin_action_links_' . $plugin->basename, array($this, 'add_action_links'));
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

		add_settings_field( Plugin::OPTION_HEADER_SCRIPT_ATTRIBUTES, __( 'Additional header script attributes', Plugin::DOMAIN ), array(
			$this,
			'field_header_attributes'
		), self::MENU_SLUG, self::MENU_SLUG );
		add_settings_field( Plugin::OPTION_FOOTER_SCRIPT_ATTRIBUTES, __( 'Additional footer script attributes', Plugin::DOMAIN ), array(
			$this,
			'field_footer_attributes'
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
		register_setting( self::MENU_SLUG, Plugin::OPTION_HEADER_SCRIPT_ATTRIBUTES );
		register_setting( self::MENU_SLUG, Plugin::OPTION_FOOTER_SCRIPT_ATTRIBUTES );
		register_setting( self::MENU_SLUG, self::OPTION_CC, array( $this, 'clear_cache' ) );

	}

	/**
	 * add menu
	 */
	function menu_pages() {
		add_submenu_page(
			'options-general.php',
			'Aggregator',
			'Aggregator',
			'manage_options',
			"aggregator",
			array(
				$this,
				"render"
			)
		);
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
		$files = $this->plugin->file_handler->get_all_files();

		?>
		<p>Es gibt <?php echo count( $files ) ?> aggregierte JavaScript Dateien in <code><?php echo $paths->url; ?></code>

		<div>
			<button class="button" type="button" id="aggregator-legend" >Details</button>
			<div id="aggregator-details" style="display: none; border: 1px solid #d3d3d3; background-color: white; padding: 10px 15px;">
				<?php
				foreach ( $files as $file ) {
					echo "<p>$file</p>";
				}
				?>
			</div>
		</div>
		<script>
			document.getElementById('aggregator-legend').addEventListener('click',function(){
				if(document.getElementById('aggregator-details').style.display == "block"){
					document.getElementById('aggregator-details').style.display = "none";
				} else {
					document.getElementById('aggregator-details').style.display = "block";
				}
			});
		</script>

		<?php

	}


	/**
	 * file location field
	 */
	function field_file_location() {

		$setting = get_option( Plugin::OPTION_FILE_LOCATION, Plugin::OPTION_FILE_LOCATION_UPLOADS );
		?>
		<select name="<?php echo Plugin::OPTION_FILE_LOCATION; ?>">
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
	 * header script attributes
	 */
	function field_header_attributes() {

		$value = get_option( Plugin::OPTION_HEADER_SCRIPT_ATTRIBUTES, '' );

		?>
		<code>&lt;script src="...-header.js" &gt;<select style="min-width: 100px;" name="<?php echo Plugin::OPTION_HEADER_SCRIPT_ATTRIBUTES ?>">
				<?php $this->render_script_attribute_options($value); ?>
			</select>&lt;/script&gt;</code>
		<?php

	}

	/**
	 * footer script attributes
	 */
	function field_footer_attributes() {

		$value = get_option( Plugin::OPTION_FOOTER_SCRIPT_ATTRIBUTES, '' );
		?>
		<code>&lt;script src="...-footer.js" <select style="min-width: 100px;" name="<?php echo Plugin::OPTION_FOOTER_SCRIPT_ATTRIBUTES ?>">
				<?php $this->render_script_attribute_options($value); ?>
			</select>&gt;&lt;/script&gt;</code>
		<?php

	}

	private function render_script_attribute_options($value){
		?>
			<option value=""></option>
			<option
					value="defer"
				<?php echo( $value == "defer"? 'selected' : '' ); ?>
			>defer
			</option>
			<option
					value="async"
				<?php echo( $value == "async"? 'selected' : '' ); ?>
			>async
			</option>
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

	/**
	 * action link to settings on plugins list page
	 * @param $links
	 *
	 * @return array
	 */
	public function add_action_links($links){
		return array_merge($links, array(
			'<a href="'.admin_url('options-general.php?page='.self::MENU_SLUG).'">Settings</a>'
		));
	}

}