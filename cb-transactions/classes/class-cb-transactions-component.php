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

		$components = ['transactions'];

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
				'transactions' => ['new'],
				'dependencies' => ['jquery'],
			]
		];
		
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
		$global_tables = array(
			'table_name'    		=> $cb->table_prefix . 'confetti_bits_transactions',
		);

		parent::setup_globals(
			array(
				'slug'                  => CONFETTI_BITS_TRANSACTIONS_SLUG,
				'global_tables'         => $global_tables,
			)
		);

		$cb->loaded_components[ $this->slug ] = $this->id;

	}

}