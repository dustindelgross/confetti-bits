<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CB Transactions Component
 *
 * Gives us access to global values elsewhere, and includes
 * files associated with the component.
 *
 * @package Confetti_Bits
 * @subpackage Transactions
 * @since 1.0.0
 */
class CB_Transactions_Component extends CB_Component {

	public function __construct() {
		parent::start(
			'transactions',
			__( 'Confetti Bits Transactions', 'confetti-bits' ),
			CONFETTI_BITS_PLUGIN_PATH,
			array(
				'adminbar_myaccount_order' => 50,
			)
		);

	}

	public function includes( $includes = array() ) {

		// Files to include.
		$includes = array(
			'functions',
			'search',
			'log',
			'requests',
			'exports',
			'imports',
			'sender',
			'transfers',
			'template',
			'notifications',
		);
		
		parent::includes($includes);

	}

	public function late_includes() {
		if ( cb_is_user_confetti_bits() ) {
			require_once $this->path . 'cb-transactions/screens/confetti-bits.php';
		}
	}

	public function setup_globals( $args = array() ) {

		$cb = Confetti_Bits();

		// Define a slug, if necessary.
		if ( ! defined( 'CONFETTI_BITS_TRANSACTIONS_SLUG' ) ) {
			define( 'CONFETTI_BITS_TRANSACTIONS_SLUG', 'transactions' );
		}

		// Global tables for messaging component.
		$global_tables = array(
			'table_name'    		=> $cb->table_prefix . 'confetti_bits_transactions',
		);

		parent::setup_globals(
			array(
				'slug'                  => CONFETTI_BITS_TRANSACTIONS_SLUG,
				'search_string'         => __( 'Search Transactions', 'confetti-bits' ),
				'global_tables'         => $global_tables,
			)
		);
		
		$cb->loaded_components[ $this->slug ] = $this->id;

	}

	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// Determine user to use.
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		$access             = bp_core_can_edit_settings();
		$slug               = "confetti-bits";
		$transactions_link = trailingslashit( $user_domain . $slug );

		$nav_name = __( 'Confetti Bits', 'confetti-bits' );

		// Add 'Notifications' to the main navigation.
		$main_nav = array(
			'name'                    => $nav_name,
			'slug'                    => $slug,
			'position'                => 30,
			'show_for_displayed_user' => $access,
			'screen_function'         => 'cb_screen_view',
			'item_css_id'             => $this->id,
		);

		// Add the subnav items to the profile.
		$sub_nav[] = array();

		parent::setup_nav( $main_nav, $sub_nav );

	}

	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables.
			$transactions_link = trailingslashit( bp_loggedin_user_domain() . CONFETTI_BITS_TRANSACTIONS_SLUG );			
			$title  = __( 'Confetti Bits', 'buddyboss' );

			// Add the "My Account" sub menus.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => $transactions_link,
			);

		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	public function setup_title() {

		// Adjust title.
		if ( cb_is_confetti_bits_component() ) {
			$cb = Confetti_Bits();
			$bp = buddypress();

			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'Confetti Bits', 'confetti-bits' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar(
					array(
						'item_id' => bp_displayed_user_id(),
						'type'    => 'thumb',
						'alt'     => sprintf( __( 'Profile photo of %s', 'buddyboss' ), bp_get_displayed_user_fullname() ),
					)
				);
				$bp->bp_options_title  = bp_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}

	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups(
			array(
				'confetti_bits_transactions',
				'confetti_bits_transactions_recipients',
			)
		);

		parent::setup_cache_groups();
	}

}