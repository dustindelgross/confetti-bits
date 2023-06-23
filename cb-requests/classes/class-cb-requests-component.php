<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * A component class that gives us access to important globals throughout the app.
 * 
 * Lets us set some global values to use elsewhere.
 *
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
class CB_Requests_Component extends CB_Component {


	public function __construct() {
		parent::start(
			'requests',
			__( 'Confetti Bits Requests', 'confetti-bits' ),
			CONFETTI_BITS_PLUGIN_PATH,
			array(
				'adminbar_myaccount_order' => 50,
			)
		);
	}

	public function includes( $includes = array() ) {

		$includes = array(
			'functions',
			'template',
		);
		
		parent::includes($includes);

	}

	public function setup_globals( $args = [] ) {

		$cb = Confetti_Bits();

		if ( ! defined( 'CONFETTI_BITS_REQUESTS_SLUG' ) ) {
			define( 'CONFETTI_BITS_REQUESTS_SLUG', 'requests' );
		}
		
		if ( !defined('CONFETTI_BITS_REQUEST_ITEMS_SLUG' ) ) {
			define( 'CONFETTI_BITS_REQUEST_ITEMS_SLUG', 'request-items' );
		}

		$global_tables = [
			'table_name'    		=> $cb->table_prefix . 'confetti_bits_requests',
			'table_name_items'		=> $cb->table_prefix . 'confetti_bits_request_items',
			'request_items_slug'	=> 'request-items',
		];

		parent::setup_globals(
			array(
				'slug'                  => CONFETTI_BITS_REQUESTS_SLUG,
				'has_directory'         => true,
				'search_string'         => __( 'Search Requests', 'confetti-bits' ),
				'global_tables'         => $global_tables,
			)
		);
		
		$cb->loaded_components[ $this->slug ] = $this->id;

	}

}