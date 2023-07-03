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


	/**
	 * Initializes the requests component.
	 * 
	 * @package ConfettiBits\Requests
	 * @since 2.3.0
	 */
	public function __construct() {
		parent::start(
			'requests',
			__( 'Confetti Bits Requests', 'confetti-bits' ),
			CONFETTI_BITS_PLUGIN_PATH,
			[]
		);
	}

	/**
	 * Includes required files.
	 * 
	 * @package ConfettiBits\Requests
	 * @since 2.3.0
	 */
	public function includes( $includes = array() ) {

		$includes = array(
			'functions',
			'template',
		);

		parent::includes($includes);

	}

	/**
	 * Registers API endpoints for the requests component.
	 * 
	 * @package ConfettiBits\Requests
	 * @since 2.3.1
	 */
	public function register_api_endpoints( $components = [] ) {

		$components = ['requests', 'request_items'];

		parent::register_api_endpoints($components);

	}

	/**
	 * Enqueues scripts for the requests component.
	 * 
	 * @package ConfettiBits\Requests
	 * @since 3.0.0
	 */
	public function enqueue_scripts( $components = [] ) {
		
		$components = [
			'requests' => [
				'requests' => ['new', 'get', 'update', 'delete' ],
				'request_items' => ['get'],
				'dependencies' => ['jquery'],
			],
		];

		if ( cb_is_user_requests_admin() ) {
			$components['requests_admin'] = [
				'requests' => ['get', 'update', 'delete' ],
				'request_items' => ['new', 'get', 'update', 'delete' ],
				'dependencies' => ['jquery'],
			];
		}

		parent::enqueue_scripts($components);

	}

	/**
	 * Sets component globals for the requests component.
	 * 
	 * @package ConfettiBits\Requests
	 * @since 2.3.0
	 */
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
				'global_tables'         => $global_tables,
			)
		);

		$cb->loaded_components[ $this->slug ] = $this->id;

	}

}