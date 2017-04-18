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
		add_action( 'wp_enqueue_scripts', array(
			$this,
			'scripts'
		), 9999 );
	}

	/**
	 * @param $scripts array of script objects
	 *
	 * @return string
	 */
	function get_js_filename($scripts){
		$ids = array();
		foreach ($scripts as $script){
			$ids[] = "{$script->handle}-{$script->url}-{$script->changed}";
		}
		return sha1(implode('--', $ids)).".js";
	}

	/**
	 * add action for aggregation
	 */
	function scripts() {

		// TODO: build hash from file names + filedates
		// TODO: check if hash js file exists
		// TODO: if not aggregate
		// TODO: dequeue all aggregated
		// TODO: enqueue aggregated


		$header = $this->get_header_scripts();
		$header_filename = $this->get_js_filename($header);

		$footer = $this->get_footer_scripts();
		$footer_filename = $this->get_js_filename($footer);



		return;
		/**
		 * update options
		 */
		$js_contents = $this->script( $options );

		/**
		 * write js files
		 */
		if ( $options['rewrite'] ) {
			$success = $this->rewrite( $js_contents );
			/**
			 * save changes to options if no success with writing
			 */
			if ( ! $success ) {
				$options["rewrite"] = false;
			}
		}

		/**
		 * enqueues new scripts
		 */
		$this->enqueue( $options );

		/**
		 * dequeue aggregated scripts
		 */
		$this->dequeue( $options );

		/**
		 * set rewrite false and save options if was rewritten
		 */
		if ( $options["rewrite"] ) {
			$options["rewrite"] = false;
			$this->options( $options );
		}
	}

	private function _get_scripts( $footer = true ) {
		return array_filter( $this->get_scripts(), function ( $value, $key ) use ( $footer ) {
			return $value->footer == $footer;
		}, ARRAY_FILTER_USE_BOTH );
	}

	/**
	 * get all footer scripts for aggragator
	 * @return array
	 */
	function get_footer_scripts() {
		return $this->_get_scripts( true );
	}

	/**
	 * get all header scripts for aggragator
	 * @return array
	 */
	function get_header_scripts() {
		return $this->_get_scripts( false );
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

					$obj = (object) array(
						'handle'  => $js,
						'src'     => $script->src,
						'url'     => $script->src,
						'footer'  => false,
						'changed' => '',
						'data'    => null,
					);


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
							$obj->data = $extra["data"];
						}
					}

					if ( strpos( $obj->src, $blog_info_url ) === 0 ) {
						$obj->url = substr( $obj->src, strlen( $blog_info_url ) + 1 );

					} else if ( strpos( $obj->src, $protocoll_relative ) === 0 ) {
						$obj->url = substr( $obj->src, strlen( $protocoll_relative ) + 1 );
					} else {
						$obj->url = substr( $obj->src, 1 );
					}
					if ( strpos( $obj->url, "?" ) ) {
						$obj->url = substr( $obj->url, 0, strpos( $obj->url, "?" ) );
					}

					/*
					 * check file time
					 */
					if ( file_exists( $obj->url ) ) {
						$obj->changed = filemtime( $obj->url );
					}

					$scripts[] = $obj;
				}
			}

		}

		return $scripts;
	}

	/**
	 * check javascripts
	 *
	 * @param $options
	 *
	 * @return array|void
	 */
	function script( &$options ) {
		global $wp_scripts;
		$scripts     = $options['js'];
		$js_contents = array();
		$js_files    = array();
		$ignores     = $this->get_ignores();

		$blog_info_url      = get_bloginfo( 'url' );
		$protocoll_relative = str_replace( array(
			"http://",
			"https://"
		), "//", $blog_info_url );

		if ( ! is_a( $wp_scripts, "WP_Scripts" ) ) {
			return;
		}
		if ( is_array( $wp_scripts->queue ) ) {
			/**
			 * needs rebuild if
			 * - there is an aggregated script that is not queued anymore
			 * - if there is an aggregated script that is ignored
			 */
			$wp_scripts->all_deps( $wp_scripts->queue );
			foreach ( $scripts as $handle => $value ) {
				if ( ! in_array( $handle, $wp_scripts->to_do )
				     || in_array( $handle, $ignores )
				) {
					$js_place = $value['place'];
					unset( $scripts[ $handle ] );
					$options["rewrite"] = true;
				}
			}
			/**
			 * needs rebuild if
			 * - script is not ignored by filter
			 * - script was not aggregated before
			 * - script has newer file time
			 */
			foreach ( $wp_scripts->to_do as $js ) {
				if ( in_array( $js, $ignores ) ) {
					continue;
				}
				$js_src   = $wp_scripts->registered[ $js ]->src;
				$js_extra = $wp_scripts->registered[ $js ]->extra;
				$js_place = 'header';
				$js_data  = '';
				if ( is_array( $js_extra ) ) {
					/**
					 * is footer script
					 */
					if ( isset( $js_extra["group"] ) && $js_extra['group'] == 1 ) {
						$js_place = 'footer';
					}
					/**
					 * has extra data
					 */
					if ( isset( $js_extra["data"] ) && is_string( $js_extra['data'] ) ) {
						$js_data = $js_extra["data"];
					}
				}

				if (
					( ! ( strpos( $js_src, $blog_info_url ) === false )
					  || ! ( strpos( $js_src, $protocoll_relative ) === false )
					  || substr( $js_src, 0, 1 ) === "/"
					  || substr( $js_src, 0, 1 ) === "." )

					&& ( substr( $js_src, - 3 ) == ".js" )
				) {
					/**
					 * is a locally loaded js file
					 */
					if ( strpos( $js_src, $blog_info_url ) === 0 ) {
						$js_relative_url = substr( $js_src, strlen( $blog_info_url ) + 1 );

					} else if ( strpos( $js_src, $protocoll_relative ) === 0 ) {
						$js_relative_url = substr( $js_src, strlen( $protocoll_relative ) + 1 );
					} else {
						$js_relative_url = substr( $js_src, 1 );
					}
					if ( strpos( $js_relative_url, "?" ) ) {
						$js_relative_url = substr( $js_relative_url, 0, strpos( $js_relative_url, "?" ) );
					}
					/**
					 * does aggregated file exists?
					 */
					$paths = $this->paths( $js_place );
					if ( ! is_file( rtrim( $paths->dir, "/" ) . "/" . $paths->file ) ) {
						$options["rewrite"] = true;
					}
					/**
					 * have a look at modified time
					 */
					$js_time = null;
					if ( file_exists( $js_relative_url ) ) {
						$js_time = filemtime( $js_relative_url );
					}
					if ( $js_time != null ) {

						if ( ! isset( $scripts[ $js ] ) || ! is_array( $scripts[ $js ] ) ) {
							$scripts[ $js ] = array();
						}
						if ( ! isset( $scripts[ $js ]['modified'] )
						     || ! isset( $scripts[ $js ]['place'] )
						     || $js_time != $scripts[ $js ]['modified']
						     || $js_place != $scripts[ $js ]['place']
						) {
							$options['rewrite']         = true;
							$scripts[ $js ]['modified'] = $js_time;
							$scripts[ $js ]['place']    = $js_place;
							$options["rewrite"]         = true;
							if ( $options["modified"] < $js_time ) {
								$options["modified"] = $js_time;
							}
						}
						if ( ! isset( $js_files[ $js_place ] ) || ! is_array( $js_files[ $js_place ] ) ) {
							$js_files[ $js_place ] = array();
						}
						$js_files[ $js_place ][] = (object) array(
							"path"       => $js_relative_url,
							"extra_data" => $js_data
						);
					}
				}
			}
		}
		$options['js'] = $scripts;
		/**
		 * add places to options
		 */
		$options["places"] = array();
		foreach ( $js_files as $place => $js__files ) {
			$options['places'][] = $place;
			if ( $options["rewrite"] ) {
				/**
				 * get contents if needed
				 */
				$js_contents[ $place ] = "";
				foreach ( $js__files as $index => $file ) {
					$js_contents[ $place ] .= $this->get_content( $file );
				}
			}
		}

		return $js_contents;
	}

	/**
	 * enqueue scripts
	 *
	 * @param $options
	 */
	function enqueue( &$options ) {
		foreach ( $options['places'] as $place ) {
			$paths  = $this->paths( $place );
			$footer = false;
			if ( $place == "footer" ) {
				$footer = true;
			}
			$url  = rtrim( $paths->url, "/" ) . "/" . $paths->file;
			$path = rtrim( $paths->dir, "/" ) . "/" . $paths->file;
			wp_enqueue_script( 'ph-aggregated-' . $place, $url, array(), filemtime( $path ), $footer );
		}
	}

	/**
	 * dequeue scripts
	 *
	 * @param $options
	 */
	function dequeue( &$options ) {
		foreach ( $options['js'] as $handle => $script ) {
			wp_dequeue_script( $handle );
		}
		global $wp_scripts;
		if ( ! is_array( $wp_scripts->queue ) ) {
			return;
		}
		$wp_scripts->all_deps( $wp_scripts->queue );
		for ( $i = 0; $i < count( $wp_scripts->to_do ); $i ++ ) {
			$handle = $wp_scripts->to_do[ $i ];
			if ( isset( $options['js'][ $handle ] ) ) {
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