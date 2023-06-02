<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CB AJAX Component
 *
 * A component that helps organize our AJAX endpoints.
 *
 * @package Confetti_Bits
 * @subpackage AJAX
 * @since 2.3.0
 */
class CB_Ajax_Component extends CB_Component {


	public function __construct() {
		parent::start(
			'ajax',
			__( 'Confetti Bits AJAX', 'confetti-bits' ),
			CONFETTI_BITS_PLUGIN_PATH,
			array()
		);

	}

	public function includes( $includes = array() ) {

		// Files to include.
		$includes = array(
			'transactions',
			'participation',
			'functions',
		);
		
		parent::includes($includes);

	}

	public function late_includes() {}

	public function setup_globals( $args = array() ) {

		$cb = Confetti_Bits();

		// Define a slug, if necessary.
		if ( ! defined( 'CONFETTI_BITS_AJAX_SLUG' ) ) {
			define( 'CONFETTI_BITS_AJAX_SLUG', 'cb-ajax' );
		}

		parent::setup_globals(
			array(
				'slug'                  => CONFETTI_BITS_AJAX_SLUG,
				'has_directory'         => true,
				'search_string'         => __( 'Search Transactions', 'confetti-bits' ),
			)
		);
		
		$cb->loaded_components[ $this->slug ] = $this->id;

	}

}