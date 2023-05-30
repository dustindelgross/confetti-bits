<?php
/**
 * Plugin Name: Confetti Bits
 * Plugin URI:  https://dustindelgross.com/
 * Description: This is a plugin that works in tandem with the
 * TeamCTG child theme that allows users to register participation
 * at company events to receive points (Confetti Bits), which they
 * can then either redeem for certain items or save up
 * for a chance to win a spot in a company-sponsored retreat.
 * 
 * Author:      Dustin Delgross
 * Author URI:  https://dustindelgross.com/
 * Version:     2.3.0
 * Text Domain: confetti-bits
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Confetti_Bits' ) ) {

	/**
	 * Confetti Bits Class.
	 */
	class Confetti_Bits {

		/**
		 * Flag to enable or disable autoload.
		 *
		 * @var bool
		 */

		public $do_autoload = true;

		/**
		 * Array of active components.
		 *
		 * @var array
		 */
		public $active_components = array();

		/**
		 * Array of active panels.
		 *
		 * @var array
		 */
		public $active_panels = array();

		/**
		 * Array of required components.
		 *
		 * @var array
		 */
		public $required_components = array();

		/**
		 * Array of loaded components.
		 *
		 * @var array
		 */
		public $loaded_components = array();

		/**
		 * Array of optional components.
		 *
		 * @var array
		 */
		public $optional_components = array();

		/**
		 * Class data.
		 *
		 * @var array
		 */
		private $data;

		/**
		 * Retrieves the singleton instance of this class.
		 *
		 * @return Confetti_Bits Singleton instance of this class.
		 */
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

		/**
	 	 * Magic method for checking the existence of a
		 * certain custom field.
		 *
		 * @since Confetti_Bits 2.3.0
		 *
		 * @param string $key Key to check the set status for.
		 *
		 * @return bool
		 */
		public function __isset( $key ) {
			return isset( $this->data[ $key ] ); }

		/**
		 * Magic method for getting BuddyPress variables.
		 *
		 * @since Confetti_Bits 2.3.0
		 *
		 * @param string $key Key to return the value for.
		 *
		 * @return mixed
		 */
		public function __get( $key ) {
			return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null; }

		/**
		 * Magic method for setting Confetti_Bits variables.
		 *
		 * @since Confetti_Bits 2.3.0
		 *
		 * @param string $key   Key to set a value for.
		 * @param mixed  $value Value to set.
		 */
		public function __set( $key, $value ) {
			$this->data[ $key ] = $value; }

		/**
		 * Magic method for unsetting Confetti_Bits variables.
		 *
		 * @since  Confetti_Bits 2.3.0
		 * @param string $key Key to unset a value for.
		 */
		public function __unset( $key ) {
			if ( isset( $this->data[ $key ] ) ) {
				unset( $this->data[ $key ] );
			} }

		/**
		 * Prevents the class from being cloned.
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'confetti-bits' ), '1.0.0' );
		}

		/**
		 * Prevents serialization of the class.
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'confetti-bits' ), '1.0.0' );
		}

		public function __construct() {}

		/**
		 * Define constants.
		 *
		 * @since Confetti_Bits 1.0.0
		 */
		private function define_constants() {

			if ( ! defined( 'CONFETTI_BITS_PLUGIN_FILE' ) ) {
				$this->define( 'CONFETTI_BITS_PLUGIN_FILE', __FILE__ );
			}

			if ( ! defined( 'CONFETTI_BITS_PLUGIN_IS_INSTALLED' ) ) {
				$this->define( 'CONFETTI_BITS_PLUGIN_IS_INSTALLED', 1);
			}

			if ( ! defined( 'CONFETTI_BITS_PLUGIN_VERSION' ) ) {
				$this->define( 'CONFETTI_BITS_PLUGIN_VERSION', '2.3.0');
			}

			if ( ! defined( 'CONFETTI_BITS_PLUGIN_DB_VERSION' ) ) {
				$this->define( 'CONFETTI_BITS_PLUGIN_DB_VERSION', '2.3.0');
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

		/**
		 * Our own define method to centralize all our constants.
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Include required files.
		 *
		 * @since Confetti_Bits 2.3.0
		 */
		public function includes() {

			spl_autoload_register( array( $this, 'load_components' ) );
			require_once $this->plugin_dir . 'functions.php';
			require_once $this->plugin_dir . 'cb-core/cb-core-dependency.php';
			require_once $this->plugin_dir . 'cb-core/cb-core-actions.php';
			require_once $this->plugin_dir . 'cb-core/cb-core-components.php';
			require_once $this->plugin_dir . 'cb-core/cb-core-admin.php';
			require_once $this->plugin_dir . 'cb-core/cb-core-loader.php';
			require_once $this->plugin_dir . 'cb-core/cb-core-install.php';
			require_once $this->plugin_dir . 'cb-core/cb-core-template.php';
			require_once $this->plugin_dir . 'cb-templates/cb-templates-forms.php';
			require_once $this->plugin_dir . 'cb-templates/cb-templates-functions.php';

		}

		/**
		 * Setup globals.
		 *
		 * Globals are accessible via Confetti_Bits()->{$global_name}.
		 *
		 * @since Confetti_Bits 2.3.0
		 */
		private function setup_globals() {

			global $wpdb;

			$this->current_component = '';
			$this->table_prefix = $wpdb->prefix;
			$this->page = trailingslashit(site_url()) . "confetti-bits";

		}

		/**
		 * This method is passed to spl_autoload_register to load classes on demand.
		 *
		 * @since Confetti_Bits 2.3.0
		 */
		public function load_components( $class ) {

			$class_parts = explode( '_', strtolower( $class ) );

			if ( 'confetti' !== $class_parts[0] && 'cb' !== $class_parts[0] ) {
				return;
			}


			$components = array (
				'core',
				'notifications',
				'transactions',
				'participation',
				'ajax',
			);

			$irregular_map = array('CB_Component' => 'core');

			$component = null;

			if ( isset( $irregular_map[ $class ] ) ) {
				$component = $irregular_map[ $class ];
			} else if ( in_array( $class_parts[1], $components, true ) ) {
				$component = $class_parts[1];
			} else if ( in_array( $class_parts[2], $components, true ) ) {

				$component = $class_parts[2];
			}

			$class = strtolower( str_replace( '_', '-', $class ) );
			$path = dirname( __FILE__ ) . "/cb-{$component}/classes/class-{$class}.php";

			if ( ! file_exists( $path ) ) {
				return;
			}

			require $path;

		}

		/**
		 * Get the plugin url.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return string The plugin url.
		 */
		public function plugin_url() {
			return untrailingslashit( plugins_url( '/', __FILE__ ) );
		}

		/**
		 * Get the plugin path.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return string The plugin path.
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		/**
		 * Load the plugin text domain for translation.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function load_plugin_textdomain() {
			$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
			$locale = apply_filters( 'plugin_locale', $locale, 'confetti-bits' );

			unload_textdomain( 'confetti-bits' );
			load_textdomain( 'confetti-bits', WP_LANG_DIR . '/' . plugin_basename( dirname( __FILE__ ) ) . '/' . plugin_basename( dirname( __FILE__ ) ) . '-' . $locale . '.mo' );
			load_plugin_textdomain( 'confetti-bits', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
		}
	}

	/**
	 * The main function responsible for returning the one true Confetti_Bits
	 *
	 * @return Confetti_Bits
	 * @since 1.0.0
	 */
	function Confetti_Bits() {
		return Confetti_Bits::instance();
	}

	/**
	 * Display notice if BuddyBoss Platform is not installed.
	 */
	function confetti_bits_install_bb_platform_notice() {
		echo '<div class="error fade"><p>';
		_e('<strong>Confetti Bits</strong></a> requires the BuddyBoss Platform plugin to work. Please <a href="https://buddyboss.com/platform/" target="_blank">install BuddyBoss Platform</a> first.', 'confetti-bits');
		echo '</p></div>';
	}

	/**
	 * Display notice if BuddyBoss Platform is not updated.
	 */
	function confetti_bits_update_bb_platform_notice() {
		echo '<div class="error fade"><p>';
		_e('<strong>Confetti Bits</strong></a> requires BuddyBoss Platform plugin version 1.2.6 or higher to work. Please update BuddyBoss Platform.', 'confetti-bits');
		echo '</p></div>';
	}

	/**
	 * Check if Confetti Bits is active.
	 */
	function cb_confetti_bits_is_active() {
		if ( defined( 'BP_PLATFORM_VERSION' ) && version_compare( BP_PLATFORM_VERSION,'1.2.6', '>=' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Init the plugin.
	 * @since 1.0.0
	 * @return void
	 */
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
