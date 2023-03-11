<?php
/**
 * Plugin Name: Confetti Bits
 * Plugin URI:  https://dustindelgross.com/
 * Description: This is the TeamCTG platform add-on for the Confetti Bits program.
 * Author:      Dustin Delgross
 * Author URI:  https://dustindelgross.com/
 * Version:     2.2.0
 * Text Domain: confetti-bits
 * Domain Path: /languages/
 * License:     GPLv3 or later (license.txt)
 */
defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'Confetti_Bits' ) ) {

	class Confetti_Bits {

		public $do_autoload = true;

		public $active_components = array();
		
		public $active_panels = array();

		public $required_components = array();

		public $loaded_components = array();

		public $optional_components = array();

		public static function instance() {

			static $instance = null;
			if ( null === $instance ) {
				$instance = new Confetti_Bits();
				$instance->define_constants();
				$instance->setup_globals();
				$instance->includes();
			}
			return $instance;
		}

		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'confetti-bits' ), '1.0.0' );
		}
		
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'confetti-bits' ), '1.0.0' );
		}

		public function __construct() {}

		private function define_constants() {

			if ( ! defined( 'CONFETTI_BITS_PLUGIN_FILE' ) ) {
				$this->define( 'CONFETTI_BITS_PLUGIN_FILE', __FILE__ );				
			}

			if ( ! defined( 'CONFETTI_BITS_PLUGIN_IS_INSTALLED' ) ) {
				$this->define( 'CONFETTI_BITS_PLUGIN_IS_INSTALLED', 1);	
			}

			if ( ! defined( 'CONFETTI_BITS_PLUGIN_VERSION' ) ) {
				$this->define( 'CONFETTI_BITS_PLUGIN_VERSION', '2.2.0');				
			}

			if ( ! defined( 'CONFETTI_BITS_PLUGIN_DB_VERSION' ) ) {
				$this->define( 'CONFETTI_BITS_PLUGIN_DB_VERSION', '2.2.0');				
			}

			if ( ! defined( 'CONFETTI_BITS_PLUGIN_BASENAME' ) ) {
				$this->define( 'CONFETTI_BITS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );				
			}

			if ( ! defined( 'CONFETTI_BITS_PLUGIN_PATH' ) ) {
				$this->define( 'CONFETTI_BITS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );				
			}

			if ( ! defined( 'CONFETTI_BITS_PLUGIN_URL' ) ) {
				$this->define( 'CONFETTI_BITS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );				
			}

			$this->plugin_dir = trailingslashit( constant( 'CONFETTI_BITS_PLUGIN_PATH' ) );
			$this->plugin_url = trailingslashit( constant( 'CONFETTI_BITS_PLUGIN_URL' ) );
		}

		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		public function includes() {
			spl_autoload_register( array( $this, 'load_components' ) );
			require $this->plugin_dir . 'functions.php';
			require $this->plugin_dir . 'cb-core/cb-core-components.php';
			require $this->plugin_dir . 'cb-core/cb-core-admin.php';
			require $this->plugin_dir . 'cb-core/cb-core-loader.php';
			require $this->plugin_dir . 'cb-core/cb-core-components.php';
			require $this->plugin_dir . 'cb-core/cb-core-install.php';
			require $this->plugin_dir . 'cb-core/cb-core-template.php';
			require $this->plugin_dir . 'cb-templates/cb-templates-forms.php';
		}

		private function setup_globals() {
			
			global $wpdb;
			
			$this->current_component = '';
			$this->table_prefix = $wpdb->base_prefix;

		}

		public function load_components( $class ) {

			$class_parts = explode( '_', strtolower( $class ) );

			if ( 'confetti' !== $class_parts[0] ) {
				return;
			}

			$components = array (
				'core',
				'notifications',
				'transactions',
				'participation',
			);

			if ( in_array( $class_parts[2], $components, true ) ) {
				$component = $class_parts[2];
			}

			$class = strtolower( str_replace( '_', '-', $class ) );

			$path = dirname( __FILE__ ) . "/cb-{$component}/classes/class-{$class}.php";

			if ( ! file_exists( $path ) ) {
				return;
			}

			require $path;

		}

		public function plugin_url() {
			return untrailingslashit( plugins_url( '/', __FILE__ ) );
		}

		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		public function load_plugin_textdomain() {
			$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
			$locale = apply_filters( 'plugin_locale', $locale, 'confetti-bits' );

			unload_textdomain( 'confetti-bits' );
			load_textdomain( 'confetti-bits', WP_LANG_DIR . '/' . plugin_basename( dirname( __FILE__ ) ) . '/' . plugin_basename( dirname( __FILE__ ) ) . '-' . $locale . '.mo' );
			load_plugin_textdomain( 'confetti-bits', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
		}
	}

	function Confetti_Bits() {
		return Confetti_Bits::instance();
	}

	function confetti_bits_install_bb_platform_notice() {
		echo '<div class="error fade"><p>';
		_e('<strong>Confetti Bits</strong></a> requires the BuddyBoss Platform plugin to work. Please <a href="https://buddyboss.com/platform/" target="_blank">install BuddyBoss Platform</a> first.', 'confetti-bits');
		echo '</p></div>';
	}

	function confetti_bits_update_bb_platform_notice() {
		echo '<div class="error fade"><p>';
		_e('<strong>Confetti Bits</strong></a> requires BuddyBoss Platform plugin version 1.2.6 or higher to work. Please update BuddyBoss Platform.', 'confetti-bits');
		echo '</p></div>';
	}

	function confetti_bits_is_active() {
		if ( defined( 'BP_PLATFORM_VERSION' ) && version_compare( BP_PLATFORM_VERSION,'1.2.6', '>=' ) ) {
			return true;
		}
		return false;
	}

	function confetti_bits_init() {
		if ( ! defined( 'BP_PLATFORM_VERSION' ) ) {
			add_action( 'admin_notices', 'confetti_bits_install_bb_platform_notice' );
			add_action( 'network_admin_notices', 'confetti_bits_install_bb_platform_notice' );
			return;
		}

		if ( version_compare( BP_PLATFORM_VERSION,'1.2.6', '<' ) ) {
			add_action( 'admin_notices', 'confetti_bits_update_bb_platform_notice' );
			add_action( 'network_admin_notices', 'confetti_bits_update_bb_platform_notice' );
			return;
		}

		Confetti_Bits();
	}

	add_action( 'plugins_loaded', 'confetti_bits_init', 9 );
}
