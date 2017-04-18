<?php

namespace Aggregator;


class FileHandler {

	/**
	 * FileHandler constructor.
	 *
	 * @param Plugin $plugin
	 */
	function __construct(Plugin $plugin) {
	}

	/**
	 * @param $files array of files
	 */
	function getFilename($files){
		// TODO: return filename as hash of files
	}

	/**
	 * get file content
	 *
	 * @param $js
	 *
	 * @return mixed|string
	 */
	function get_content( $js ) {
		$js_content      = "";
		$js_relative_url = $js->path;
		$source_file     = fopen( $js_relative_url, 'r' );
		if ( $source_file ) {
			$js_content .= "// AGGREGATOR Aggregated file: " . $js_relative_url . "\n";
			$js_content .= "// AGGREGATOR Extra data:\n";
			$js_content .= $this->wrap_in_try_catch( $js->extra_data );
			$js_content .= "// AGGREGATOR Content:\n";
			$js_content .= $this->wrap_in_try_catch( fread( $source_file, filesize( $js_relative_url ) ) ) . "\n";
			fclose( $source_file );
			/**
			 * remove source maps
			 */
			$js_content = str_replace( "sourceMappingURL", "", $js_content );
		}

		return $js_content;
	}

	/**
	 * get paths
	 *
	 * @param null $place
	 *
	 * @return object
	 */
	function paths( $place = null ) {
		/**
		 * separate files for logged in users and logged out users
		 */
		$logged_in = '';
		if ( is_user_logged_in() ) {
			$logged_in = 'logged-in-';
		}
		$paths = (object) array(
			'dir'          => rtrim( get_stylesheet_directory(), "/" ) . "/aggregated",
			'url'          => rtrim( get_stylesheet_directory_uri(), "/" ) . "/aggregated",
			'file_pattern' => $logged_in . '%place%.js',
			'file'         => '',
		);

		/**
		 * if uploads is selected in options
		 */
		if ( get_option( 'aggregator_file_location', 'uploads' ) == 'uploads' ) {
			$uploads    = wp_upload_dir();
			$style_dir  = get_stylesheet_directory();
			$parts      = explode( "/", $style_dir );
			$template   = end( $parts );
			$paths->dir = rtrim( $uploads["basedir"], "/" ) . "/aggregated_" . $template;
			$paths->url = rtrim( $uploads["baseurl"], "/" ) . "/aggregated_" . $template;
		}

		if ( $place != null ) {
			$paths->file = str_replace( "%place%", $place, $paths->file_pattern );
		}

		return $paths;
	}


	/**
	 * Wrapps a Javascript code block inside a try and catch block.
	 *
	 * @param string $code A JS code block
	 * @return string JS code block, wrapped inside a try catch block.
	 */
	function wrap_in_try_catch( $code ) {
		if ( ! empty( $code ) ) {
			$code = "try {\n" . $code . "\n}\ncatch (err) {\n\tconsole.error(err);\n}\n";
		}

		return $code;
	}

	/**
	 * rewrites the aggregated scripts
	 */
	function rewrite( $js_contents ) {
		foreach ( $js_contents as $place => $content ) {
			$paths = $this->paths( $place );
			if ( ! is_dir( $paths->dir ) ) {
				$success = mkdir( $paths->dir );
				if ( ! $success ) {
					return false;
				}
//				chmod($paths->dir, 0777);
			}
			if ( ! is_writable( $paths->dir ) ) {
				return false;
			}
			$the_file        = rtrim( $paths->dir, "/" ) . "/" . $paths->file;
			$aggregated_file = fopen( $the_file, 'w' );
			fwrite( $aggregated_file, $content );
			fclose( $aggregated_file );

//			chmod($the_file, 0777);

			$this->purge( $paths->url . "/" . $paths->file );
		}

		return true;
	}

	/**
	 * purge file url
	 *
	 * @param $file_url
	 */
	function purge( $file_url ) {
		wp_remote_request( $file_url, array( 'method' => 'PURGE' ) );
	}




}