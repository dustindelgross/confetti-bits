<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CB Participation Component
 * 
 * Lets us set some global values to use elsewhere.
 *
 * @package Confetti_Bits
 * @subpackage Participation
 * @since 2.2.0
 */
class CB_Participation_Component extends CB_Component {


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

	public function includes( $includes = array() ) {

		$includes = array(
			'functions',
			'template',
		);
		
		parent::includes($includes);

	}

	public function late_includes() {
		if ( cb_is_confetti_bits_component() ) {

		}
	}

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
				'has_directory'         => true,
				'search_string'         => __( 'Search Participation', 'confetti-bits' ),
				'global_tables'         => $global_tables,
			)
		);
		
		$cb->loaded_components[ $this->slug ] = $this->id;

	}

	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {}

	public function setup_admin_bar( $wp_admin_nav = array() ) {}

	public function setup_title() {}

}