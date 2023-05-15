<?php
/**
 * Confetti Bits Transaction Loader.
 *
 * A component that allows leaders to send bits to users and for users to send bits to each other.
 *
 * @since Confetti Bits 2.0.0
 */

defined( 'ABSPATH' ) || exit;

class Confetti_Bits_Events_Component extends BP_Component {


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
		
		if ( ! empty( $includes ) ) {
			$slashed_path = trailingslashit( $this->path );

			foreach ( (array) $includes as $file ) {

				$paths = array(

					'cb-' . $this->id . '/cb-' . $this->id . '-' . $file . '.php',
					'cb-' . $this->id . '-' . $file . '.php',
					'cb-' . $this->id . '/' . $file . '.php',

					$file,
					'cb-' . $this->id . '-' . $file,
					'cb-' . $this->id . '/' . $file,
				);

				foreach ( $paths as $path ) {
					if ( @is_file( $slashed_path . $path ) ) {
						require $slashed_path . $path;
						break;
					}
				}
			}

		}

		do_action( 'bp_' . $this->id . '_includes' );

	}

	public function setup_globals( $args = array() ) {

		$cb = Confetti_Bits();

		if ( ! defined( 'CONFETTI_BITS_EVENTS_SLUG' ) ) {
			define( 'CONFETTI_BITS_EVENTS_SLUG', 'confetti-bits' );
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