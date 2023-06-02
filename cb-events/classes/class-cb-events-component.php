<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CB Events Component
 *
 * A component that allows certain users to create and manage company events.
 *
 * @package Confetti_Bits
 * @subpackage Events
 * @since 2.3.0
 */
class CB_Events_Component extends CB_Component {


	public function __construct() {
		parent::start(
			'events',
			__( 'Confetti Bits Events', 'confetti-bits' ),
			CONFETTI_BITS_PLUGIN_PATH,
			array(
				'adminbar_myaccount_order' => 50,
			)
		);

	}

	public function includes( $includes = array() ) {

		$includes = array(
			'functions',
		);
		
		parent::includes($includes);

		do_action( 'cb_' . $this->id . '_includes' );

	}

	public function setup_globals( $args = array() ) {

		$cb = Confetti_Bits();

		if ( ! defined( 'CONFETTI_BITS_EVENTS_SLUG' ) ) {
			define( 'CONFETTI_BITS_EVENTS_SLUG', 'events' );
		}

		$global_tables = array(
			'table_name'    		=> $cb->table_prefix . 'confetti_bits_events',
		);

		parent::setup_globals(
			array(
				'slug'                  => CONFETTI_BITS_EVENTS_SLUG,
				'has_directory'         => true,
				'search_string'         => __( 'Search Events', 'confetti-bits' ),
				'global_tables'         => $global_tables,
			)
		);
		
		$cb->loaded_components[ $this->slug ] = $this->id;

	}

}