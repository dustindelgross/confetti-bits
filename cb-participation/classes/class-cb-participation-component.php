<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CB Participation Component
 * 
 * Lets us set some global values to use elsewhere.
 *
 * @package ConfettiBits\Participation
 * @since 2.0.0
 */
class CB_Participation_Component extends CB_Component {

	/**
	 * Initializes participation component.
	 * 
	 * @package ConfettiBits\Participation
	 * @since 2.0.0
	 */
	public function __construct() {
		parent::start(
			'participation',
			__( 'Confetti Bits Participation', 'confetti-bits' ),
			CONFETTI_BITS_PLUGIN_PATH,
			array(
				'adminbar_myaccount_order' => 50,
			)
		);

	}

	/**
	 * Includes required files.
	 * 
	 * @package ConfettiBits\Participation
	 * @since 2.0.0
	 */
	public function includes( $includes = array() ) {

		$includes = array(
			'functions',
			'template',
			'notifications',
		);

		parent::includes($includes);

	}

	/**
	 * Registers API endpoints for the participation component.
	 * 
	 * @package ConfettiBits\Participation
	 * @since 2.0.0
	 */
	public function register_api_endpoints( $components = [] ) {

		$components = ['participation'];
		

		parent::register_api_endpoints($components);
	}

	/**
	 * Enqueues scripts for the participation component.
	 * 
	 * @package ConfettiBits\Participation
	 * @since 3.0.0
	 */
	public function enqueue_scripts( $components = [] ) {

		$components = [
			'participation' => [ 
				'participation' => ['get', 'new', 'update'],
				'dependencies' => ['jquery'],
			],
		];

		if ( cb_is_user_participation_admin() ) {
			$components['participation_admin'] = [
				'participation' => ['get', 'update'], 
				'transactions' => ['get'],
				'dependencies' => ['jquery']
			];
		}

		parent::enqueue_scripts($components);

	}

	/**
	 * Sets component globals for the participation component.
	 * 
	 * @package ConfettiBits\Participation
	 * @since 2.0.0
	 */
	public function setup_globals( $args = array() ) {

		$cb = Confetti_Bits();

		if ( ! defined( 'CONFETTI_BITS_PARTICIPATION_SLUG' ) ) {
			define( 'CONFETTI_BITS_PARTICIPATION_SLUG', 'participation' );
		}

		$global_tables = array(
			'table_name'    		=> $cb->table_prefix . 'confetti_bits_participation',
		);

		parent::setup_globals(
			array(
				'slug'                  => CONFETTI_BITS_PARTICIPATION_SLUG,
				'global_tables'         => $global_tables,
			)
		);

		$cb->loaded_components[ $this->slug ] = $this->id;

	}

}