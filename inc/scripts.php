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

		add_action('wp_head', array($this, "wp_head"), 1);
		add_action('wp_footer', array($this, "wp_footer"));

		add_action( 'wp_enqueue_scripts', array(
			$this,
			'scripts'
		), 9999 );

	}

	function wp_head(){
		$header = $this->get_header_scripts();
		?>
		<!-- START: Aggregator extra script data from header scripts -->
		<?php
		$this->_render_script_data($header);
		?>
		<!-- END: Aggregator extra script data from header scripts -->
		<?php
	}
	function wp_footer(){
		$footer = $this->get_footer_scripts();
		?>
		<!-- START: Aggregator extra script data from footer scripts -->
		<?php
		$this->_render_script_data($footer);?>
		<!-- END: Aggregator extra script data from header scripts -->
		<?php
	}

	/**
	 * @param $scripts
	 */
	private function _render_script_data($scripts){
		foreach ($scripts as $script){
			if($script->extra_data == null || $script->extra_data == "") continue;
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

			$this->plugin->file_handler->aggregate_and_write($header_filename, $header);

		}


		$footer          = $this->get_footer_scripts();
		$footer_filename = $this->get_js_filename( $footer );

		if ( ! $this->plugin->file_handler->file_exists( $footer_filename ) ) {
			// aggregate new footer js
			$this->plugin->file_handler->aggregate_and_write($footer_filename, $footer);

		}

		/*
		 * dequeue scripts
		 */
		$this->dequeue($header);
		$this->dequeue($footer);

		/*
		 * enqueue aggregated files
		 */
		wp_enqueue_script(
			Plugin::HANDLE_HEADER,
			$this->plugin->file_handler->paths()->url . "/{$header_filename}",
			array(),
			1,
			false
		);
		wp_enqueue_script(
			Plugin::HANDLE_FOOTER,
			$this->plugin->file_handler->paths()->url . "/{$footer_filename}",
			array(),
			1,
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
		foreach ( $scripts as $script ) {
			$ids[]    = "{$script->handle}-{$script->url}-{$script->changed}";
			$post_fix = ( $script->footer ) ? "-footer" : "-header";
		}

		return sha1( implode( '--', $ids ) ) . "{$post_fix}.js";
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
		if($this->_footer_scritps == null) $this->_footer_scritps = $this->_get_scripts( true );
		return $this->_footer_scritps;
	}

	/**
	 * get all header scripts for aggregator
	 * @return array
	 */
	function get_header_scripts() {
		if($this->_header_scripts == null) $this->_header_scripts = $this->_get_scripts( false );
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
			$blog_domain = str_replace(array('https://','http://', '//'),'', $blog_info_url);
			$protocoll_relative = str_replace( array(
				"http://",
				"https://"
			), "//", $blog_info_url );

			/*
			 * resolve dependencies
			 */
			$wp_scripts->all_deps( $wp_scripts->queue );

			foreach ( $wp_scripts->to_do as $js ) {

				if ( $this->is_ignored( $js ) ) {
					continue;
				}

				if ( ! empty( $wp_scripts->registered[ $js ] ) ) {

					$script = $wp_scripts->registered[ $js ];

					if($script->src)

					$obj = (object) array(
						'handle'  => $js,
						'src'     => $script->src,
						'file_path'    => null,
						'url'     => null,
						'footer'  => false,
						'changed' => '',
						'extra_data'    => null,
						'external' => false,
					);

					// TODO: handle src
					// http://... https://... and //...
					// or internal with /...

					preg_match('/(http:|https:)?\/\/(.*)/', $obj->src, $matches);
					if($matches){
						if( strpos($matches[2], $blog_domain) !== false ){
							$obj->file_path = ABSPATH.str_replace($blog_domain,'',$matches[2]);
						}
						$obj->url = $obj->src;
					}

					if(strpos($obj->src,'/') === 0){
						$obj->file_path = rtrim(ABSPATH, '/').$obj->src;
					}



//					if ( strpos( $obj->src, $blog_info_url ) === 0 ) {
//						$obj->url = substr( $obj->src, strlen( $blog_info_url ) + 1 );
//
//					} else if ( strpos( $obj->src, $protocoll_relative ) === 0 ) {
//						$obj->url = substr( $obj->src, strlen( $protocoll_relative ) + 1 );
//					} else {
//						$obj->url = substr( $obj->src, 1 );
//					}
//					if ( strpos( $obj->url, "?" ) ) {
//						$obj->url = substr( $obj->url, 0, strpos( $obj->url, "?" ) );
//					}
//
//					$obj->file_path = ABSPATH.$obj->url;

					if ( is_array( $script->extra ) ) {
						$extra = $script->extra;
						/*
						 * is in footer group
						 */
						if ( isset( $extra["group"] ) && $extra['group'] == 1 ) {
							$obj->footer = true;
						}

						/*
						 * has extra data
						 */

						if ( isset( $extra["data"] ) && is_string( $extra['data'] ) ) {
							$obj->extra_data = $extra["data"];
						}
					}




					/*
					 * check file time
					 */
					if ( file_exists( $obj->url ) ) {
						$obj->changed = filemtime( $obj->url );
					}

					$scripts[$js] = $obj;
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
		// TODO: handle deprecated filter
		return apply_filters( Plugin::FILTER_IGNORED_FILES, array() );
	}

}