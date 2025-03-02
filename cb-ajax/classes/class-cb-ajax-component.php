<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * A component that helps organize our AJAX endpoints.
 * 
 * Use this to include files that house API handlers
 * for each component. The naming convention for our
 * endpoints is:
 * /cb-ajax/{{version}}/{{component}}/{{action}}
 * 
 * @see CB_Component
 * @package ConfettiBits\AJAX
 * @since 2.3.0
 */
class CB_Ajax_Component extends CB_Component {

	/**
	 * Bring out the dancing wizard.
	 * 
	 * @see CB_Component::start()
	 * @package ConfettiBits\AJAX
	 * @since 2.3.0
	 */
	public function __construct() {
		parent::start(
			'ajax',
			__( 'Confetti Bits AJAX', 'confetti-bits' ),
			CONFETTI_BITS_PLUGIN_PATH,
			array()
		);

	}

	/**
	 * Include files for the AJAX component.
	 * 
	 * @see CB_Component::includes()
	 * @package ConfettiBits\AJAX
	 * @since 2.3.0
	 */
	public function includes( $includes = array() ) {

		// Files to include.
		$includes = [
			'settings',
			'transactions',
			'participation',
			'requests',
			'request-items',
			'events',
			'contests',
			'spot-bonuses',
			'volunteers',
			
		];
		
		parent::includes($includes);

	}

	/**
	 * Setup component globals for the AJAX component.
	 * 
	 * @see CB_Copmonent::setup_globals()
	 * @package ConfettiBits\AJAX
	 * @since 2.3.0
	 */
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