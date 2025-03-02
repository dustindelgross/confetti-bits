<?php 
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * A component that allows certain users to manage global settings.
 *
 * @package Settings
 * @since 3.1.0
 */
class CB_Settings_Component extends CB_Component {

	/**
	 * Initializes the component.
	 * 
	 * @see CB_Component::start()
	 */
	public function __construct() {

		parent::start(
			'settings',
			__( 'Confetti Bits Settings', 'confetti-bits' ),
			CONFETTI_BITS_PLUGIN_PATH,
			[]		
		);

	}

	/**
	 * Includes required files.
	 * 
	 * @see CB_Component::includes()
	 */
	public function includes( $includes = [] ) {

		$includes = [ 'functions', 'template' ];

		parent::includes($includes);

	}

	/**
	 * Sets up component global values.
	 * 
	 * @package Settings
	 * @since 3.1.0
	 */
	public function setup_globals( $args = [] ) {

		$cb = Confetti_Bits();

		if ( ! defined( 'CONFETTI_BITS_SETTINGS_SLUG' ) ) {
			define( 'CONFETTI_BITS_SETTINGS_SLUG', 'settings' );
		}

		$globals = [
			'slug' => CONFETTI_BITS_SETTINGS_SLUG,
			'global_tables' => [
				'table_name' => $cb->table_prefix . 'confetti_bits_settings'
			],
		];

		parent::setup_globals($globals);

		$cb->loaded_components[ $this->slug ] = $this->id;

	}

	/**
	 * Registers API endpoints for settings. Will be implemented in future if needed.
	 * 
	 * @package Settings
	 * @since 3.1.0
	 */
	public function register_api_endpoints( $components = [] ) {

		$components = ['settings'];

		parent::register_api_endpoints($components);
	}

	/**
	 * Enqueues scripts for the settings component.
	 * 
	 * @package Settings
	 * @since 3.1.0
	 */
	public function enqueue_scripts( $components = [] ) {

		$components = [];

		if ( cb_is_user_site_admin() ) {
			$components = [
				'settings' => [ 
					'settings' => [ 'update', 'get' ],
					'dependencies' => ['jquery'],
				],	
			];
		}

		parent::enqueue_scripts($components);

	}

}