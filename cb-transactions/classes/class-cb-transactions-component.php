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

	public function __construct() {
		parent::start(
			'transactions', __( 'Confetti Bits Transactions', 'confetti-bits' ), CONFETTI_BITS_PLUGIN_PATH, [] );
	}

	public function includes( $includes = array() ) {

		// Files to include.
		$includes = array(
			'functions',
			'search',
			'log',
			'requests',
			'exports',
			'imports',
			'sender',
			'transfers',
			'template',
			'notifications',
		);
		
		parent::includes($includes);

	}

	public function late_includes() {
		if ( cb_is_user_confetti_bits() ) {
			require_once $this->path . 'cb-transactions/screens/confetti-bits.php';
		}
	}

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