<?php

namespace Aggregator;


class FileHandler {

	/**
	 * FileHandler constructor.
	 *
	 * @param Plugin $plugin
	 */
	function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @param $filename
	 *
	 * @return bool
	 */
	function file_exists($filename){
		return (file_exists($this->paths()->dir."/{$filename}"));
	}

	/**
	 * get paths
	 *
	 * @return object
	 */
	function paths( ) {

		$location = get_option( Plugin::OPTION_FILE_LOCATION, Plugin::OPTION_FILE_LOCATION_UPLOADS );

		if ( $location == Plugin::OPTION_FILE_LOCATION_THEME ) {
			return (object) array(
				'dir'          => rtrim( get_stylesheet_directory(), "/" ) . "/aggregated",
				'url'          => rtrim( get_stylesheet_directory_uri(), "/" ) . "/aggregated",
			);
		}

		$uploads    = wp_upload_dir();
		return (object) array(
			'dir'          => rtrim( $uploads["basedir"], "/" ) . "/aggregated",
			'url'          => rtrim( $uploads["baseurl"], "/" ) . "/aggregated",
		);

	}


	/**
	 * @param $filename
	 * @param $scripts
	 */
	function aggregate_and_write($filename, $scripts){
		$content = "";
		foreach ($scripts as $handle => $script){
			$content.= $this->get_content($script);
		}

		if( get_option(Plugin::OPTION_MINIFY, Plugin::OPTION_MINIFY_ON)){
			require_once $this->plugin->dir."/lib/minifier.php";
			$content = \JShrink\Minifier::minify($content);
		}

		$this->write($filename,$content);
	}

	/**
	 * get script content
	 *
	 * @param $script
	 *
	 * @return boolean|string
	 */
	function get_content( $script ) {
		$js_content      = "";

		if($script->file_path != null && file_exists($script->file_path)){
			$source_file     = fopen(  $script->file_path, 'r' );
			if ( $source_file ) {
				return $this->wrap_content($script, fread( $source_file, filesize( $script->file_path ) ));
			}
		}

		// if could not handle by file path get from url

		$contents = file_get_contents($script->url);
		return $this->wrap_content($script, $contents);

	}

	/**
	 * wrap content with info
	 * @param $script
	 * @param $content
	 *
	 * @return string
	 */
	function wrap_content($script, $content){
		$js_content = "";
		$js_content .= "// AGGREGATOR Handle: ".$script->handle;
		$js_content .= "// AGGREGATOR file: " . $script->url . "\n";

		// extra data comes with wp_head and wp_footer. See Scripts class.

		$js_content .= "// AGGREGATOR Content:\n";
		$js_content .= $this->wrap_in_try_catch( $content ) . "\n";
		/**
		 * remove source maps
		 */
		$js_content = str_replace( "sourceMappingURL", "", $js_content );

		return $js_content;
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
	 * write content to file
	 * @param $filename
	 * @param $content
	 *
	 * @return bool
	 */
	function write($filename, $content){
		$paths = $this->paths();
		if ( ! is_dir( $paths->dir ) ) {
			$success = mkdir( $paths->dir );
			if ( ! $success ) {
				return false;
			}
		}
		if ( ! is_writable( $paths->dir ) ) {
			return false;
		}

		$the_file_path        = rtrim( $paths->dir, "/" ) . "/" . $filename;

		$the_file = fopen( $the_file_path, 'w' );
		fwrite( $the_file, $content );
		fclose( $the_file );

//			chmod($the_file, 0777);

		$this->purge( $paths->url . "/" . $filename );
	}

	/**
	 * purge file url
	 *
	 * @param $file_url
	 */
	function purge( $file_url ) {
		wp_remote_request( $file_url, array( 'method' => 'PURGE' ) );
	}

	/**
	 * get all files in aggregation folder
	 * @return array
	 */
	function get_all_files(){
		$files = array();
		$paths = $this->paths();
		if ( is_dir( $paths->dir ) && $handle = opendir( $paths->dir ) ) {

			while ( false !== ( $entry = readdir( $handle ) ) ) {
				if ( preg_match( "/.*\.js/", $entry ) ) {
					$files[] = $entry;
				}

			}

			closedir( $handle );
		}
		return $files;
	}


}