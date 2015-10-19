<?php
/**
 * Plugin Name: Aggregator
 * Description: Aggregates js files.
 * Version: 1.1
 * Author: PALASTHOTEL by Edward Bock
 * Author URI: http://www.palasthotel.de
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Aggregator
{
	/**
	 * register actions and filters
	 */
	function __construct()
	{
		add_action('wp_print_scripts', array($this, 'wp_print_scripts'),9999);
		require(dirname(__FILE__)."/classes/settings.inc");
		new \Aggregator\Settings();
	}

	/**
	 * get and set options
	 */
	function options($options = null)
	{
		if ($options == null) {
			$default = array(
				'modified' => 0,
				'rewrite' => false,
				'js' => array(),
			);
			return get_option('ph-aggregator-js', $default);
		} else {
			return update_option('ph-aggregator-js', $options);
		}
	}
	/**
	 * add action for aggregation
	 */
	function wp_print_scripts() {
		/**
		 * no compression when is admin areas are displayed
		 * or if on login page
		 */
		if(is_admin() || in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php')) ) return;

		/**
		 * get options
		 */
		$options = $this->options();

		/**
		 * update options
		 */
		$js_contents = $this->script($options);

		/**
		 * write js files
		 */
		if( $options['rewrite'] ){
			$success = $this->rewrite($js_contents);
			/**
			 * save changes to options if no success with writing
			 */
			if(!$success){
				$options["rewrite"] = false;
			}
		}

		/**
		 * enqueues new scripts
		 */
		$this->enqueue($options);

		/**
		 * dequeue aggregated scripts
		 */
		$this->dequeue($options);

		/**
		 * set rewrite false and save options if was rewritten
		 */
		if($options["rewrite"]){
			$options["rewrite"] = false;
			$this->options($options);
		}
	}

	/**
	 * get paths
	 * @param null $place
	 * @return object
	 */
	function paths($place = null){
		/**
		 * separate files for logged in users and logged out users
		 */
		$logged_in = '';
		if(is_user_logged_in()){
			$logged_in = 'logged-in-';
		}
		$paths = (object) array(
			'dir' => rtrim(get_stylesheet_directory(), "/")."/aggregated",
			'url' => rtrim(get_stylesheet_directory_uri(), "/")."/aggregated",
			'file_pattern' => $logged_in.'%place%.js',
			'file' => '',
		);

		/**
		 * if uploads is selected in options
		 */
		if(get_option( 'aggregator_file_location', 'theme' ) != 'theme'){
			$uploads = wp_upload_dir();
			$paths->dir = rtrim($uploads["basedir"], "/")."/aggregated";
			$paths->url = rtrim($uploads["baseurl"], "/")."/aggregated";
		}

		if($place != null){
			$paths->file = str_replace("%place%", $place, $paths->file_pattern);
		}
		return $paths;
	}

	/**
	 * check javascripts
	 * @param $options
	 * @return array|void
	 */
	function script(&$options){
		global $wp_scripts;
		$scripts = $options['js'];
		$js_contents = array();
		$js_files = array();
		$ignores = $this->get_ignores();

		$blog_info_url = get_bloginfo('url');
		$protocoll_relative = str_replace(array("http://", "https://"), "//", $blog_info_url);

		if ( !is_a($wp_scripts, "WP_Scripts") ) return;
		if (is_array($wp_scripts->queue)) {
			/**
			 * needs rebuild if
			 * - there is an aggregated script that is not queued anymore
			 * - if there is an aggregated script that is ignored
			 */
			$wp_scripts->all_deps($wp_scripts->queue);
			foreach ($scripts as $handle => $value) {
				if ( !in_array( $handle, $wp_scripts->to_do)
					|| in_array($handle, $ignores)) {
					$js_place=$value['place'];
					unset($scripts[$handle]);
					$options["rewrite"] = true;
				}
			}
			/**
			 * needs rebuild if
			 * - script is not ignored by filter
			 * - script was not aggregated before
			 * - script has newer file time
			 */
			foreach ($wp_scripts->to_do as $js) {
				if(in_array($js, $ignores)) continue;
				$js_src=$wp_scripts->registered[$js]->src;
				$js_extra =$wp_scripts->registered[$js]->extra;
				$js_place = 'header';
				$js_data = '';
				if (is_array($js_extra)) {
					/**
					 * is footer script
					 */
					if( isset($js_extra["group"]) && $js_extra['group']==1 ){
						$js_place='footer';
					}
					/**
					 * has extra data
					 */
					if( isset($js_extra["data"]) && is_string($js_extra['data']) ){
						$js_data=$js_extra["data"];
					}
				}

				if (
					( !(strpos($js_src,$blog_info_url)===false)
						|| !(strpos($js_src,$protocoll_relative) === false)
						|| substr($js_src,0,1)==="/"
						|| substr($js_src,0,1)===".")

					&& (substr($js_src,-3)==".js") ) {
					/**
					 * is a locally loaded js file
					 */
					if (strpos($js_src,$blog_info_url)===0) {
						$js_relative_url=substr($js_src,strlen($blog_info_url)+1);

					}
					else if(strpos($js_src,$protocoll_relative)===0){
						$js_relative_url=substr($js_src,strlen($protocoll_relative)+1);
					}
					else {
						$js_relative_url=substr($js_src,1);
					}
					if (strpos($js_relative_url,"?")){
						$js_relative_url=substr($js_relative_url,0,strpos($js_relative_url,"?"));
					}
					/**
					 * does aggregated file exists?
					 */
					$paths = $this->paths($js_place);
					if(!is_file(rtrim($paths->dir,"/")."/".$paths->file)){
						$options["rewrite"] = true;
					}
					/**
					 * have a look at modified time
					 */
					$js_time=null;
					if(file_exists($js_relative_url)){
						$js_time=filemtime($js_relative_url);
					}
					if ($js_time != null) {

						if(!isset($scripts[$js]) || !is_array($scripts[$js]) ){
							$scripts[$js] = array();
						}
						if ( !isset($scripts[$js]['modified'])
							|| !isset($scripts[$js]['place'])
							|| $js_time != $scripts[$js]['modified']
							|| $js_place != $scripts[$js]['place']
						) {
							$options['rewrite'] = true;
							$scripts[$js]['modified'] = $js_time;
							$scripts[$js]['place'] = $js_place;
							$options["rewrite"] = true;
							if($options["modified"] < $js_time){
								$options["modified"] = $js_time;
							}
						}
						if( !isset($js_files[$js_place]) || !is_array($js_files[$js_place]) ){
							$js_files[$js_place] = array();
						}
						$js_files[$js_place][] = (object)array("path"=>$js_relative_url, "extra_data"=>$js_data);
					}
				}
			}
		}
		$options['js'] = $scripts;
		/**
		 * add places to options
		 */
		$options["places"] = array();
		foreach ($js_files as $place => $js__files) {
			$options['places'][] = $place;
			if($options["rewrite"]){
				/**
				 * get contents if needed
				 */
				$js_contents[$place] = "";
				foreach ($js__files as $index => $file) {
					$js_contents[$place].= $this->get_content($file);
				}
			}
		}
		return $js_contents;
	}

	/**
	 * enqueue scripts
	 * @param $options
	 */
	function enqueue(&$options){
		foreach ($options['places'] as $place) {
			$paths = $this->paths($place);
			$footer = false;
			if($place == "footer"){
				$footer = true;
			}
			$path = rtrim($paths->url,"/")."/".$paths->file;
			wp_enqueue_script('ph-aggregated-'.$place, $path, array(), null, $footer );
		}
	}

	/**
	 * dequeue scripts
	 * @param $options
	 */
	function dequeue(&$options){
		foreach ($options['js'] as $handle => $script) {
			wp_dequeue_script($handle);
		}
		global $wp_scripts;
		if(!is_array($wp_scripts->queue)){
			return;
		}
		$wp_scripts->all_deps($wp_scripts->queue);
		for($i = 0; $i < count($wp_scripts->to_do); $i++ ){
			$handle = $wp_scripts->to_do[$i];
			if(isset($options['js'][$handle])){
				$wp_scripts->remove($handle);
				unset($wp_scripts->registered[$handle]);
				array_splice($wp_scripts->to_do, $i,1);
				$i--;
			}
		}
	}

	/**
	 * get file content
	 * @param $js
	 * @return mixed|string
	 */
	function get_content($js){
		$js_content="";
		$js_relative_url = $js->path;
		$source_file=fopen($js_relative_url,'r');
		if($source_file){
			$js_content.= "/**\n * Aggregated\n * ". $js_relative_url." extra data:\n */\n";
			$js_content.= $js->extra_data;
			$js_content.= "/**\n content:\n */\n";
			$js_content.= fread($source_file,filesize($js_relative_url))."\n";
			fclose($source_file);
			/**
			 * remove source maps
			 */
			$js_content = str_replace( "sourceMappingURL", "", $js_content);
		}
		return $js_content;
	}

	/**
	 * rewrites the aggregated scripts
	 */
	function rewrite($js_contents){
		foreach ($js_contents as $place => $content) {
			$paths = $this->paths($place);
			if(!is_dir($paths->dir)){
				$success = mkdir($paths->dir);
				if(!$success) return false;
//				chmod($paths->dir, 0777);
			}
			if(!is_writable($paths->dir)) return false;
			$the_file = rtrim($paths->dir,"/")."/".$paths->file;
			$aggregated_file=fopen($the_file, 'w');
			fwrite($aggregated_file, $content);
			fclose($aggregated_file);

//			chmod($the_file, 0777);

			$this->purge($paths->url."/".$paths->file);
		}
		return true;
	}

	/**
	 * purge file url
	 * @param $file_url
	 */
	function purge($file_url){
		wp_remote_request( $file_url, array('method' => 'PURGE') );
	}

	/**
	 * get js to ignore while aggregating
	 * @return mixed|void
	 */
	function get_ignores(){
		return apply_filters("ph_aggregator_ignore", array());
	}
}
new Aggregator();










