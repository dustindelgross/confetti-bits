<?php 
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * A component that allows certain users to create and manage company events.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
class CB_Events_Component extends CB_Component {

	/**
	 * Initializes the component.
	 * 
	 * @see CB_Component::start()
	 */
	public function __construct() {

		parent::start(
			'events',
			__( 'Confetti Bits Events', 'confetti-bits' ),
			CONFETTI_BITS_PLUGIN_PATH,
			[]		
		);

	}

	/**
	 * Includes required files.
	 * 
	 * @see CB_Component::includes()
	 */
	public function includes( $includes = [] ) {

		$includes = [ 'functions', 'template', 'notifications' ];

		parent::includes($includes);

	}

	/**
	 * Sets up component global values.
	 * 
	 * @package ConfettiBits\Events
	 * @since 3.0.0
	 */
	public function setup_globals( $args = [] ) {

		$cb = Confetti_Bits();

		if ( ! defined( 'CONFETTI_BITS_EVENTS_SLUG' ) ) {
			define( 'CONFETTI_BITS_EVENTS_SLUG', 'events' );
		}

		$globals = [
			'slug' => CONFETTI_BITS_EVENTS_SLUG,
			'global_tables' => [
				'table_name' => $cb->table_prefix . 'confetti_bits_events', 
				'table_name_contests' => $cb->table_prefix . 'confetti_bits_contests', 
			],
		];

		parent::setup_globals($globals);

		$cb->loaded_components[ $this->slug ] = $this->id;

	}

	/**
	 * Registers API endpoints for events and contests.
	 * 
	 * @package ConfettiBits\Events
	 * @since 3.0.0
	 */
	public function register_api_endpoints( $components = [] ) {

		$components = ['events', 'contests', 'transactions', 'bda'];

		parent::register_api_endpoints($components);
	}

	/**
	 * Enqueues script for the events component.
	 * 
	 * @package ConfettiBits\Events
	 * @since 3.0.0
	 */
	public function enqueue_scripts( $components = [] ) {

		$components = [
			'events' => [
				'events' => ['get'],
				'bda' => ['get'],
				'contests' => ['get', 'update' ],
				'transactions' => ['get', 'new'],
				'dependencies' => ['jquery']
			],
		];

		if ( cb_is_user_events_admin() ) {
			$components['events_admin'] = [
				'events' => [ 'new', 'get', 'update', 'delete' ],
				'contests' => [ 'new', 'get', 'update', 'delete' ],
				'transactions' => ['get'],
				'dependencies' => ['jquery', 'jquery-ui-dialog'],
			];
		}

		parent::enqueue_scripts($components);

	}

}