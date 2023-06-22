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

		$cb->required_components = [ 'transactions', 'participation', 'ajax', 'requests' ];
		$cb->active_components = [ 'transactions', 'participation', 'ajax', 'requests' ];

		// Loop through required components.
		foreach ( $cb->required_components as $component ) {
			$file_name = "{$cb->plugin_dir}cb-{$component}/cb-{$component}-loader.php";
			if ( file_exists( $file_name ) ) {
				include $file_name;
			}
		}

		// Add Core to required components.
		$cb->required_components[] = 'core';

		do_action( 'cb_core_components_included' );

	}

}
