<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Adds component globals and includes related files.
 *
 * @package ConfettiBits\Transactions
 * @since 1.0.0
 */
class CB_Transactions_Component extends CB_Component {

	/**
	 * Initializes the transactions component.
	 * 
	 * @package ConfettiBits\Transactions
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::start(
			'transactions', 
			__( 'Confetti Bits Transactions', 'confetti-bits' ), 
			CONFETTI_BITS_PLUGIN_PATH, 
			[] 
		);
	}

	/**
	 * Includes required files.
	 * 
	 * @package ConfettiBits\Transactions
	 * @since 1.0.0
	 */
	public function includes( $includes = array() ) {

		// Files to include.
		$includes = array(
			'functions',
			'log',
			'exports',
			'imports',
			'template',
			'notifications',
		);

		parent::includes($includes);

	}

	/**
	 * Registers API endpoints for the transactions component.
	 * 
	 * @package ConfettiBits\Transactions
	 * @since 2.3.1
	 */
	public function register_api_endpoints( $components = [] ) {

		$components = ['transactions', 'spot_bonuses', 'volunteers'];

		parent::register_api_endpoints($components);

	}

	/**
	 * Enqueue scripts for the transactions component.
	 * 
	 * @package ConfettiBits\Transactions
	 * @since 3.0.0
	 */
	public function enqueue_scripts( $components = [] ) {

		$components = [
			'transactions' => [
				'spot_bonuses' => ['get'],
				'transactions' => ['new'],
				'dependencies' => ['jquery'],
			]
		];
		
		if ( cb_is_user_staffing_admin() ) {
			$components['staffing_admin'] = [
				'spot_bonuses' => ['new', 'get', 'update', 'delete' ],
				'dependencies' => ['jquery', 'jquery-ui-datepicker'],
			];
		}
		
		if ( cb_is_user_admin() ) {
			$components['volunteers'] = [
				'volunteers' => ['new'],
				'events' => ['get'],
				'dependencies' => ['jquery'],
			];
		}

		parent::enqueue_scripts($components);

	}

	/**
	 * Sets up component globals for the transactions component.
	 * 
	 * @package ConfettiBits\Transactions
	 * @since 1.0.0
	 */
	public function setup_globals( $args = array() ) {

		$cb = Confetti_Bits();

		// Define a slug, if necessary.
		if ( ! defined( 'CONFETTI_BITS_TRANSACTIONS_SLUG' ) ) {
			define( 'CONFETTI_BITS_TRANSACTIONS_SLUG', 'transactions' );
		}

		// Global tables for messaging component.
		$global_tables = [
			'table_name'    		=> "{$cb->table_prefix}confetti_bits_transactions",
			'table_name_spot_bonuses' => "{$cb->table_prefix}confetti_bits_spot_bonuses",
		];

		parent::setup_globals([
			'slug'                  => CONFETTI_BITS_TRANSACTIONS_SLUG,
			'global_tables'         => $global_tables
		]);

		$cb->loaded_components[ $this->slug ] = $this->id;

	}

}