<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 19.04.17
 * Time: 08:36
 */

namespace Aggregator;


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

		//TODO: localization test

		if(is_archive()){
			wp_enqueue_script('aggregator-test-header-archive', $this->plugin->url."/test/header-archive.js");
			wp_localize_script('aggregator-test-header-archive', 'AggregatorHeader', array("works" => 1));
			wp_enqueue_script('aggregator-test-footer-archive', $this->plugin->url."/test/footer-archive.js", null, 1, true);
			wp_localize_script('aggregator-test-footer-archive', 'AggregatorFooter', array("works" => 1));
		} else if( is_singular()){
			wp_enqueue_script('aggregator-test-header-single', $this->plugin->url."/test/header-single.js");
			wp_localize_script('aggregator-test-header-single', 'AggregatorHeaderSingle', array("works" => 1));
			wp_enqueue_script('aggregator-test-footer-single', $this->plugin->url."/test/footer-single.js", null, 1 , true);
			wp_localize_script('aggregator-test-footer-single', 'AggregatorFooterSingle', array("works" => 1));
		}
	}
}