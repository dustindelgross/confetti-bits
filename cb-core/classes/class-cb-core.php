<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

class CB_Core extends CB_Component {

	public function __construct() {

		parent::start(
			'core',
			__('Confetti Bits Core', 'confetti-bits'),
			CONFETTI_BITS_PLUGIN_PATH
		);
		$this->bootstrap();
	}

	private function bootstrap() {

		$this->includes();
		$this->load_components();

	}

	public function load_components() {

		$cb = Confetti_Bits();

		$cb->optional_components = array_keys( cb_core_get_components( 'optional' ) );
		$cb->required_components = array( 'transactions', 'participation', 'ajax' );
		$active_components = get_option( 'cb_active_components' );
		$deactivated_components = get_option( 'cb_deactivated_components' );

		if ( $active_components ) {

			$cb->active_components = $active_components;

			$cb->deactivated_components = array_values( 
				array_diff(
					array_values( array_merge( $cb->optional_components, $cb->required_components ) ), 
					array_keys( $cb->active_components )
				)
			);

		} else if ( $deactivated_components ) {

			// Trim off namespace and filename.
			foreach ( array_keys( (array) $deactivated_components ) as $component ) {
				$trimmed[] = str_replace( '.php', '', str_replace( 'cb-', '', $component ) );
			}

			$cb->deactivated_components = $trimmed;

			// Setup the active components.
			$active_components = array_fill_keys( array_diff( array_values( array_merge( $cb->optional_components, $cb->required_components ) ), array_values( $cb->deactivated_components ) ), '1' );

			$cb->active_components = $active_components;

			// Default to all components active.

		} else {

			// Set globals.
			$cb->deactivated_components = array();

			// Setup the active components.
			$active_components = array_fill_keys( array_values( array_merge( $cb->optional_components, $cb->required_components ) ), '1' );

			$cb->active_components = apply_filters( 'cb_active_components', $active_components );
		}

		// Loop through optional components.
		foreach ( $cb->optional_components as $component ) {
			if ( file_exists( $cb->plugin_dir . 'cb-' . $component . '/cb-' . $component . '-loader.php' ) ) {
				include $cb->plugin_dir . 'cb-' . $component . '/cb-' . $component . 
					'-loader.php';
			}
		}

		// Loop through required components.
		foreach ( $cb->required_components as $component ) {
			if ( file_exists( $cb->plugin_dir . 'cb-' . $component . '/cb-' . $component . 
							 '-loader.php' ) ) {
				include $cb->plugin_dir . 'cb-' . $component . '/cb-' . $component . 
					'-loader.php';
			}
		}

		// Add Core to required components.
		$cb->required_components[] = 'core';

		do_action( 'cb_core_components_included' );

	}

	private function load_integrations() {}

	public function includes( $includes = array() ) {}

	public function setup_globals( $args = array() ) {}

}
