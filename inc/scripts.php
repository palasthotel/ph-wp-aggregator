<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 18.04.17
 * Time: 17:32
 */

namespace Aggregator;


class Scripts {

	/**
	 * Enqueue constructor.
	 *
	 * @param Plugin $plugin
	 */
	function __construct( Plugin $plugin ) {

		$this->plugin = $plugin;

		$this->_header_scripts = null;
		$this->_footer_scritps = null;

		add_action( 'wp_head', array( $this, "wp_head" ), 1 );
		add_action( 'wp_footer', array( $this, "wp_footer" ) );

		add_action( 'wp_enqueue_scripts', array(
			$this,
			'scripts'
		), 9999 );

	}

	function wp_head() {
		$header = $this->get_header_scripts();
		echo "\n<!-- START: Aggregator extra script data from header scripts -->\n";

		$this->_render_script_data( $header );
		echo "\n<!-- END: Aggregator extra script data from header scripts -->\n";
	}

	function wp_footer() {
		$footer = $this->get_footer_scripts();
		echo "\n<!-- START: Aggregator extra script data from footer scripts -->\n";

		$this->_render_script_data( $footer );
		echo "\n<!-- END: Aggregator extra script data from footer scripts -->\n";
	}

	/**
	 * @param $scripts
	 */
	private function _render_script_data( $scripts ) {
		foreach ( $scripts as $script ) {
			if ( $script->extra_data == null || $script->extra_data == "" ) {
				continue;
			}
			$extra = $script->extra_data;
			?>
			<script type="text/javascript"><?php echo $extra; ?></script>
			<?php
		}
	}

	/**
	 * add action for aggregation
	 */
	function scripts() {

		$header          = $this->get_header_scripts();
		$header_filename = $this->get_js_filename( $header );

		if ( ! $this->plugin->file_handler->file_exists( $header_filename ) ) {
			// aggregate new header js
			$this->plugin->file_handler->aggregate_and_write( $header_filename, $header );

		}


		$footer          = $this->get_footer_scripts();
		$footer_filename = $this->get_js_filename( $footer );

		if ( ! $this->plugin->file_handler->file_exists( $footer_filename ) ) {
			// aggregate new footer js
			$this->plugin->file_handler->aggregate_and_write( $footer_filename, $footer );

		}

		/*
		 * dequeue scripts
		 */
		$this->dequeue( $header );
		$this->dequeue( $footer );

		/*
		 * enqueue aggregated files
		 */
		wp_enqueue_script(
			Plugin::HANDLE_HEADER,
			$this->plugin->file_handler->paths()->url . "/{$header_filename}",
			null,
			filemtime( $this->plugin->file_handler->paths()->dir . "/{$header_filename}" ),
			false
		);
		wp_enqueue_script(
			Plugin::HANDLE_FOOTER,
			$this->plugin->file_handler->paths()->url . "/{$footer_filename}",
			null,
			filemtime( $this->plugin->file_handler->paths()->dir . "/{$footer_filename}" ),
			true
		);
	}

	/**
	 * @param $scripts array of script objects
	 *
	 * @return string
	 */
	function get_js_filename( $scripts ) {
		$ids      = array();
		$post_fix = "";
		$prefix = "";
		foreach ( $scripts as $script ) {
			$ids[]    = "{$script->handle}-{$script->url}-{$script->changed}";
			$post_fix = ( $script->footer ) ? "-footer" : "-header";
			$prefix = ($script->footer)? "f": "h";
		}

		return $prefix.sha1( implode( '--', $ids ) ) . "{$post_fix}.js";
	}

	/**
	 * @param bool $footer
	 *
	 * @return array
	 */
	private function _get_scripts( $footer = true ) {
		return array_filter( $this->get_scripts(), function ( $value, $key ) use ( $footer ) {
			return $value->footer == $footer;
		}, ARRAY_FILTER_USE_BOTH );
	}

	/**
	 * get all footer scripts for aggregator
	 * @return array
	 */
	function get_footer_scripts() {
		if ( $this->_footer_scritps == null ) {
			$this->_footer_scritps = $this->_get_scripts( true );
		}

		return $this->_footer_scritps;
	}

	/**
	 * get all header scripts for aggregator
	 * @return array
	 */
	function get_header_scripts() {
		if ( $this->_header_scripts == null ) {
			$this->_header_scripts = $this->_get_scripts( false );
		}

		return $this->_header_scripts;
	}

	/**
	 * get all scripts for aggregator
	 * @return array
	 */
	function get_scripts() {
		global $wp_scripts;

		if ( ! is_a( $wp_scripts, "WP_Scripts" ) ) {
			return array();
		}

		$scripts = array();
		if ( is_array( $wp_scripts->queue ) ) {

			$blog_info_url      = get_bloginfo( 'url' );
			$blog_domain        = str_replace( array( 'https://', 'http://', '//' ), '', $blog_info_url );

			/*
			 * resolve dependencies
			 */
			$wp_scripts->all_deps( $wp_scripts->queue );

			foreach ( $wp_scripts->to_do as $js ) {

				// TODO: Remove deprecated get_ignores() call in future version.
				if ( $this->is_ignored( $js ) ||
					   in_array( $js, $this->get_ignores() ) ) {
					continue;
				}

				if ( ! empty( $wp_scripts->registered[ $js ] ) ) {

					$script = $wp_scripts->registered[ $js ];

					if ( !$script->src ) continue;

					$obj = (object) array(
						'handle'     => $js,
						'src'        => $script->src,
						'file_path'  => null,
						'url'        => null,
						'footer'     => false,
						'changed'    => '',
						'extra_data' => null,
						'external'   => false,
					);

					$src = $obj->src;
					// parse_url cannot use protocol relatives
					if(strpos($src, "//") === 0){
						$src = "http:$src";
					}
					$parsed = parse_url($src);


					if( !isset($parsed["host"]) && !empty($parsed["path"]) ){
						// no host. just path on our own site
						$guessed_path = ABSPATH . $parsed["path"];
						if(file_exists($guessed_path)){
							$obj->file_path = $guessed_path;
						}
					} else if(!empty($parsed["host"]) && !empty($parsed["path"])){
						// is it own domain? we can try file path
						$guessed_path = ABSPATH . $parsed["path"];
						$port = (isset($parsed["port"]))? $parsed["port"]:"";
						if ( ( $parsed["host"] == $blog_domain || $parsed["host"].":$port" == $blog_domain )
						     && file_exists($guessed_path)) {
							$obj->file_path = $guessed_path;
						} else {

							// fallback load via http
							$ch = curl_init($src);
							curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
							curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body
							curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
							curl_setopt($ch, CURLOPT_TIMEOUT,10);
							curl_exec($ch);
							$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							curl_close($ch);

							if($http_code == 200){
								$obj->url = $src;
							}
						}


					}

					// if cannot resolve source skip it!
					if($obj->url == null && $obj->file_path == null) continue;

					if ( is_array( $script->extra ) ) {
						$extra = $script->extra;
						/*
						 * is in footer group
						 */
						if ( isset( $extra["group"] ) && $extra['group'] == 1 ) {
							$obj->footer = true;
						}

						/*
						 * has extra data like localization
						 */

						if ( isset( $extra["data"] ) && is_string( $extra['data'] ) ) {
							$obj->extra_data = $extra["data"];
						}
					}


					/*
					 * check file time
					 */
					if ( file_exists( $obj->file_path ) ) {
						$obj->changed = filemtime( $obj->file_path );
					}

					$scripts[ $js ] = $obj;
				}
			}

		}

		return $scripts;
	}

	/**
	 * dequeue scripts
	 *
	 * @param $scripts
	 *
	 */
	function dequeue( $scripts ) {
		foreach ( $scripts as $script ) {
			wp_dequeue_script( $script->handle );
		}
		global $wp_scripts;
		if ( ! is_array( $wp_scripts->queue ) ) {
			return;
		}
		$wp_scripts->all_deps( $wp_scripts->queue );
		for ( $i = 0; $i < count( $wp_scripts->to_do ); $i ++ ) {
			$handle = $wp_scripts->to_do[ $i ];
			if ( isset( $scripts[ $handle ] ) ) {
				$wp_scripts->remove( $handle );
				unset( $wp_scripts->registered[ $handle ] );
				array_splice( $wp_scripts->to_do, $i, 1 );
				$i --;
			}
		}
	}

	/**
	 * @param $js_handle
	 *
	 * @return bool
	 */
	function is_ignored( $js_handle ) {
		return apply_filters( Plugin::FILTER_IGNORE_FILE, false, $js_handle );
	}

	/**
	 * get js to ignore while aggregating
	 * @return array
	 */
	function get_ignores() {
		return apply_filters( Plugin::FILTER_IGNORED_FILES, array() );
	}
}
