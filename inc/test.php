<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 19.04.17
 * Time: 08:36
 */

namespace Aggregator;


/**
 * @property \Aggregator\Plugin plugin
 */
class Test {

	const GET_TEST = "_aggregator_test";
	const GET_TEST_VALUE = "YES";

	/**
	 * Test constructor.
	 *
	 * @param Plugin $plugin
	 */
	function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
		if( isset( $_GET[self::GET_TEST]) && $_GET[self::GET_TEST] == self::GET_TEST_VALUE){
			add_action('wp_enqueue_scripts', array($this, 'enqueue'));
		}
	}

	/**
	 * enqueue test scripts
	 */
	function enqueue(){

		$deps = array('jquery');

		if(is_archive()){
			wp_enqueue_script('aggregator-test-header-archive', $this->plugin->url."/test/header-archive.js", $deps);
			wp_localize_script('aggregator-test-header-archive', 'AggregatorHeader', array("works" => 1));
			wp_enqueue_script('aggregator-test-footer-archive', $this->plugin->url."/test/footer-archive.js", $deps, 1, true);
			wp_localize_script('aggregator-test-footer-archive', 'AggregatorFooter', array("works" => 1));
		} else if( is_singular()){
			wp_enqueue_script('aggregator-test-header-single', $this->plugin->url."/test/header-single.js", $deps);
			wp_localize_script('aggregator-test-header-single', 'AggregatorHeaderSingle', array("works" => 1));
			wp_enqueue_script('aggregator-test-footer-single', $this->plugin->url."/test/footer-single.js", $deps, 1 , true);
			wp_localize_script('aggregator-test-footer-single', 'AggregatorFooterSingle', array("works" => 1));
		}

		// external test
		wp_enqueue_script('external-underscore', 'http://underscorejs.org/underscore-min.js');
		wp_enqueue_script('external-jquery-ssl', 'https://code.jquery.com/jquery-3.2.1.js');
		wp_enqueue_script('external-jquery-slash', '//code.jquery.com/jquery-2.2.4.js');
		wp_enqueue_script('error-script', '//this-is-no-valid-path.de');

	}
}