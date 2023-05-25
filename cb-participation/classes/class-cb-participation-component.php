<?php
/**
 * Confetti Bits Transaction Loader.
 *
 * A component that allows leaders to send bits to users and for users to send bits to each other.
 *
 * @since Confetti Bits 2.0.0
 */

defined( 'ABSPATH' ) || exit;

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

	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

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

		$main_nav = array(
			'name'                    => $nav_name,
			'slug'                    => $slug,
			'position'                => 30,
			'show_for_displayed_user' => $access,
			'screen_function'         => 'cb_screen_view',
			'item_css_id'             => $this->id,
		);

		$sub_nav[] = array(
			'name'				=> __( 'Confetti Bits', 'confetti-bits' ),
			'slug'				=> 'participation',
			'parent_url'		=> $transactions_link,
			'parent_slug'		=> $slug,
			'screen_function'	=> '',
			'position'			=> 30,
			'user_has_access'	=> $access,
		);

		parent::setup_nav( $main_nav, $sub_nav );

	}

	public function setup_admin_bar( $wp_admin_nav = array() ) {

		if ( is_user_logged_in() ) {

			$transactions_link = trailingslashit( bp_loggedin_user_domain() . CONFETTI_BITS_PARTICIPATION_SLUG );
			$title  = __( 'Confetti Bits', 'buddyboss' );

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

		if ( cb_is_confetti_bits_component() ) {
			$cb = Confetti_Bits();
			$bp = buddypress();

			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'Confetti Bits Participation', 'confetti-bits' );
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

}