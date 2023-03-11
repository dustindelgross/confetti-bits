<?php 
/**
 * Register fields for settings hooks
 * bp_admin_setting_general_register_fields
 * bp_admin_setting_xprofile_register_fields
 * bp_admin_setting_groups_register_fields
 * bp_admin_setting_forums_register_fields
 * bp_admin_setting_activity_register_fields
 * bp_admin_setting_media_register_fields
 * bp_admin_setting_friends_register_fields
 * bp_admin_setting_invites_register_fields
 * bp_admin_setting_search_register_fields
 * 
 * We're going to have our own little box for these settings. 
 */

/*
 * Registers our settings field in the context of the BuddyBoss settings page environment.
 * Key part of this is the "bp_admin_setting_{ component }_register_fields"
 * 
 * add_section is a wordpress method to create the settings section. requires a setting object
 * add_field is a wordpress method that creates a field within the section we just made
 * 
 * bp_admin_setting_{ component }_register_fields handles the rest
 * 
 * */

if ( ! function_exists( 'confetti_bits_bp_admin_setting_general_register_fields' ) ) {
	function confetti_bits_bp_admin_setting_general_register_fields( $setting ) {

		$active_components = bp_get_option('cb_active_components');
		$default_components  = cb_core_admin_get_components( 'default' );
		$optional_components = cb_core_admin_get_components( 'optional' );
		$required_components = cb_core_admin_get_components( 'required' );
		$all_components = $required_components + $optional_components;
		$roles	= cb_core_get_roles();
		$panels	= cb_core_get_panels();
		$active_panels = bp_get_option( "cb_active_panels" );

		if ( empty( $active_components ) ) {
			$active_components = $default_components;
		}

		$inactive_components = array_diff( array_keys( $all_components ), array_keys( $active_components ) );
		$current_components = $all_components;

		// Main General Settings Section
		$setting->add_section( 'confetti_bits_general', __( 'Confetti Bits Settings', 'confetti-bits' ) );
		$setting->add_field( 'cb_reset_date', __( 'Set the Reset Date for the Confetti Bits cycle', 'confetti-bits' ), 'cb_admin_reset_date_options' );

		foreach ( $roles as $role => $role_labels ) {
			$setting->add_section(
				"cb_{$role}_panels",
				__( $role_labels['title'], 'confetti-bits' )
			);

			foreach ( $panels as $panel => $panel_labels ) {
				$setting->add_checkbox_field(
					"cb_panels[{$role}_{$panel}]",
					__( 'Enable ' . $panel_labels['title'], 'confetti-bits' ),
					array(
						'input_name'		=> "cb_panels[{$role}_{$panel}]",
						'input_id'			=> "cb_panels[{$role}_{$panel}]",
						'input_text'		=> $panel_labels['title'],
						'input_description'	=> $panel_labels['description'] . ' ',
						'input_value'		=> isset( $active_panels[$role . '_' . $panel] ) ? '1' : '',
					)
				);
			}
		}

		$setting->add_section( 'cb_components_settings', __( 'Confetti Bits Components', 'confetti-bits' ) );

		if ( ! empty( $current_components ) ) {

			foreach ( $current_components as $name => $labels ) {
				$setting->add_checkbox_field(
					"cb_components[{$name}]", 
					__( 'Enable ' . $labels['title'], 'confetti-bits' ), 
					array(
						'input_name'		=> "cb_components[{$name}]",
						'input_id'			=> "cb_components[{$name}]",
						'input_text'		=> $labels['title'],
						'input_description'	=> $labels['description'] . ' ',
						'input_value'		=> isset( $active_components[$name] ) ? '1' : '',
					)
				);
			}
		}
	}
	add_action( 'bp_admin_setting_general_register_fields', 'confetti_bits_bp_admin_setting_general_register_fields' );
}

if ( ! function_exists( 'cb_admin_components_settings' ) ) {
	function cb_admin_components_settings() { 
		cb_admin_components_options();
	}
}

if ( ! function_exists( 'cb_admin_reset_date_options' ) ) {

	function cb_admin_reset_date_options() {

		$cb_cycle_reset_date = apply_filters( 'cb_cycle_reset_date', bp_get_option('cb_cycle_reset_date') );
		$page      = bp_core_do_network_admin() ? 'admin.php' : 'admin.php';

?>
<input id="<?php echo esc_attr( "cb_cycle_reset_date" ) ?>" 
	   name="<?php echo esc_attr( "cb_cycle_reset_date" ) ?>" 
	   type="date"
	   value="<?php echo date( 'Y-m-d', strtotime( bp_get_option('cb_reset_date') ) ); ?>"
	   />
<?php
	}
}

if ( ! function_exists( 'cb_admin_template_options' ) ) {
	function cb_admin_template_options() {

	}
}

if ( ! function_exists( 'cb_admin_settings_handler' ) ) {
	function cb_admin_settings_handler() {

		if ( ! isset( $_GET['page'] ) ) {
			return;
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] != 'bp-settings' ) {
			return;
		}


		if ( isset( $_POST['cb_cycle_reset_date'] ) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$reset_date = date( 'Y-m-d H:i:s', strtotime( $_POST['cb_cycle_reset_date'] ) );

			bp_update_option( 'cb_reset_date', $reset_date );

			$base_url = bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-settings',
						'cb_cycle_reset_date'  => 'updated',
						'updated' => 'true',
					),
					'admin.php'
				)
			);
		}

		if ( isset( $_POST['cb_components'] ) ) {
			$cb = Confetti_Bits();
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			require_once( $cb->plugin_dir . '/cb-core/cb-core-install.php' );

			$submitted = stripslashes_deep( $_POST['cb_components'] );
			$new_active_components = array();
			$required = cb_core_admin_get_components('required');

			foreach ( $submitted as $name => $value ) {
				if ( 1 == $value || isset( $required[$name] ) ) {
					$new_active_components[$name] = $value;
				}
			}

			bp_update_option( 'cb_active_components', $new_active_components );
			$cb->active_components = $new_active_components;
			cb_core_install( $cb->active_components );
			$current_action = 'success';

			$base_url = bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-settings',
						'cb_action' => $current_action,
					),
					'admin.php'
				)
			);
		}

		if ( isset( $_POST['cb_panels'] ) ) {
			$roles	= cb_core_get_roles();
			$panels	= cb_core_get_panels();
			$submitted_panels = stripslashes_deep( $_POST['cb_panels'] );
			$new_active_panels = array();
			$cb = Confetti_Bits();
			foreach( $submitted_panels as $name => $value ) {
				if ( 1 == $value ) {
					$new_active_panels[$name] = $value;
				}
			}
			$cb->active_panels = $new_active_panels;
			bp_update_option( "cb_active_panels", $new_active_panels );
			wp_safe_redirect( $base_url );
			die();
		}
	}
}
add_action( 'bp_admin_init', 'cb_admin_settings_handler' );

if ( ! function_exists( 'cb_admin_components_options' ) ) {

	function cb_admin_components_options() {

		$active_components = bp_get_option('cb_active_components');
		$default_components  = cb_core_admin_get_components( 'default' );
		$optional_components = cb_core_admin_get_components( 'optional' );
		$required_components = cb_core_admin_get_components( 'required' );

		$all_components = $required_components + $optional_components;

		if ( empty( $active_components ) ) {
			$active_components = $default_components;
		}

		$inactive_components = array_diff( array_keys( $all_components ), array_keys( $active_components ) );
		$current_components = $all_components;
		$page      = bp_core_do_network_admin() ? 'admin.php' : 'admin.php';

		if ( ! empty( $current_components ) ) :
		foreach ( $current_components as $name => $labels ) {
			$bp=buddypress();
			echo bp_get_option( 'cb_fail' );
			$disabled_attr = ( isset( $required_components[$name] ) ) ? 'disabled' : '';
?>

<input id="<?php echo esc_attr( "cb_components[$name]" ) ?>" 
	   name="<?php echo esc_attr( "cb_components[$name]" ) ?>" type="checkbox"
	   value="1"
	   <?php echo $disabled_attr; ?>
	   <?php checked( isset( $active_components[ esc_attr( $name ) ] ) ); ?> />
<label for="<?php echo esc_attr( "cb_components[$name]" ) ?>">
	<?php echo esc_html( $labels['title'], 'confetti-bits' ); ?>
</label>

<p><?php echo esc_html($labels['description']); ?></p>
<?php } ?>

<input id="cb_admin_component_submit" name="cb_admin_component_submit" value="true" type="hidden">

<?php else : ?>

<tr class="no-items">
	<td class="colspanchange" colspan="3"><?php _e( 'No components found.', 'buddyboss' ); ?></td>
</tr>

<?php endif;
	}
}

if ( ! function_exists( 'cb_create_executive') ) {

	function cb_create_executive() {

		if ( get_option( 'cb_create_executives' ) != 'done' ) {
			add_role( 
				'cb_executive',
				'Confetti Bits Executive',
				array(
					'manage_options'	=> true,
					'edit_users'		=> true,
					'list_users'		=> true,
					'promote_users'		=> true,
					'create_users'		=> true,
					'add_users'			=> true,
					'delete_users'		=> true,
					'edit_courses'		=> true,
					'edit_groups'		=> true,
					'edit_assignments'	=> true,
				)
			);

			$cb_executive_role = get_role( 'cb_executive' );
			$cb_site_admin = get_role( 'administrator' );
			$cb_site_admin->add_cap('cb_can_executive');
			$cb_executive_role->add_cap( 'cb_can_executive' );
			$cb_executive_role->add_cap( 'cb_participation_admin' );
			$cb_executive_role->add_cap( 'cb_admin' );

			update_option( 'cb_create_executives', 'done' );

		}
	}
}
add_action( 'init', 'cb_create_executive' );

if ( ! function_exists( 'cb_create_participation_admin') ) {

	function cb_create_participation_admin() {

		if ( get_option( 'cb_create_participation_admins' ) != 'done' ) {
			add_role( 
				'cb_participation_admin',
				'Confetti Bits Participation Admin',
				array(
					'read'	=> true,
				)
			);
			/*/
			delete_others_pages
				delete_others_posts
				delete_private_pages
				delete_private_posts
				delete_published_pages
				delete_published_posts
				delete Reusable Blocks
				edit_others_pages
				edit_others_posts

				edit_private_pages
				edit_private_posts
				edit_published_pages
				edit_published_posts
				create Reusable Blocks
				edit Reusable Blocks
				manage_categories
				manage_links
				moderate_comments
				read_private_pages
				read_private_posts
				unfiltered_html (not with Multisite)
/*/

			$cb_participation_admin_role = get_role( 'cb_participation_admin' );
			$cb_site_admin = get_role( 'administrator' );
			$cb_site_admin->add_cap('cb_participation_admin');
			$cb_participation_admin_role->add_cap( 'cb_participation_admin' );
			$cb_participation_admin_role->add_cap( 'cb_admin' );

			update_option( 'cb_create_participation_admins', 'done' );

		}
	}
}
add_action( 'init', 'cb_create_participation_admin' );

if ( ! function_exists( 'cb_create_requests_fulfillment') ) {

	function cb_create_requests_fulfillment() {

		if ( get_option( 'cb_create_requests_fulfillment' ) != 'complete' ) {
			add_role( 
				'cb_requests_fulfillment',
				'Confetti Bits Requests Fulfillment',
				array(
					'read'					=> true,
					'level_0'				=> true,
					'spectate'				=> true,
					'participate'			=> true,
					'read_private_forums'	=> true,
					'publish_topics'		=> true,
					'edit_topics'			=> true,
					'edit_replies'			=> true,
					'assign_topic_tags'		=> true,
					'subscriber'			=> true,
					'bbp_participant'		=> true,
				)
			);

			$cb_requests_role = get_role( 'cb_requests_fulfillment' );
			$cb_site_admin = get_role( 'administrator' );
			$cb_site_admin->add_cap('cb_requests');
			$cb_requests_role->add_cap( 'cb_requests' );

			update_option( 'cb_create_requests_fulfillment', 'complete' );

		}
	}
}
add_action( 'init', 'cb_create_requests_fulfillment' );

if ( ! function_exists( 'cb_is_user_admin' ) )  {
	function cb_is_user_admin() {

		$cb_admin = current_user_can( 'cb_admin' );

		$current_user_id = get_current_user_id();
		$not_admins = array( 76, 9 );

		if( !in_array( $current_user_id, $not_admins ) && $cb_admin ) {
			return true;
		}

	}
}

if ( ! function_exists( 'cb_is_user_executive' ) )  {
	function cb_is_user_executive() {
		return bp_current_user_can('cb_can_executive');
	}
}

if ( ! function_exists( 'cb_is_user_requests_fulfillment' ) )  {
	function cb_is_user_requests_fulfillment() {
		return bp_current_user_can('cb_requests');
	}
}

if ( ! function_exists( 'cb_is_user_site_admin' ) ) {
	function cb_is_user_site_admin() {
		return current_user_can('edit_plugins');
	}
}

if ( ! function_exists( 'cb_is_user_participation_admin' ) ) {
	function cb_is_user_participation_admin() {
		return current_user_can('cb_participation_admin');
	}
}

if ( ! function_exists( 'cb_core_admin_get_active_components_from_submitted_settings' ) ){
	function cb_core_admin_get_active_components_from_submitted_settings( $submitted, $action = 'all' ) {
		$current_action = $action;

		if ( isset( $_GET['cb_action'] ) && in_array( $_GET['cb_action'], array( 'active', 'inactive' ) ) ) {
			$current_action = $_GET['cb_action'];
		}

		$current_components = Confetti_Bits()->active_components;

		switch ( $current_action ) {
			case 'inactive' :
				$components = array_merge( $submitted, $current_components );
				break;

			case 'all' :
			case 'active' :
			default :
				$components = $submitted;
				break;
		}

		return $components;
	}

}

if ( ! function_exists( 'cb_core_get_panels' ) ) {

	function cb_core_get_panels() {

		$panels	= array(

			'imports' => array(
				'title'       => __( 'Confetti Bits Imports', 'confetti-bits' ),
				'description' => __( 'Allow site admins to mass import Confetti Bits.', 'confetti-bits' ),
			),

			'exports' => array(
				'title'       => __( 'Confetti Bits Exports', 'confetti-bits' ),
				'description' => __( 'Allow users to export Confetti Bits Transactions data.', 'confetti-bits' ),
			),

			'requests' => array(
				'title'       => __( 'Confetti Bits Requests', 'confetti-bits' ),
				'description' => __( 'Allow users to send in Confetti Bits Requests.', 'confetti-bits' ),
			),

			'leaderboard' => array(
				'title'       => __( 'Confetti Bits Dashboard', 'confetti-bits' ),
				'description' => __( 'Display the Confetti Bits Leaderboard.', 'confetti-bits' ),
			),

			'logs' => array(
				'title'       => __( 'Confetti Bits Logs', 'confetti-bits' ),
				'description' => __( 'Allow users to page through their Confetti Bits Transaction history.', 'confetti-bits' ),
			),

			'transfers' => array(
				'title'       => __( 'Confetti Bits Transfers', 'confetti-bits' ),
				'description' => __( 'Allow users to send Confetti Bits to each other.', 'confetti-bits' ),
			),

			'imports' => array(
				'title'       => __( 'Confetti Bits Imports', 'confetti-bits' ),
				'description' => __( 'Allow site admins to mass import Confetti Bits.', 'confetti-bits' ),
			),

			'debug' => array(
				'title'       => __( 'Confetti Bits Debug', 'confetti-bits' ),
				'description' => __( 'Allow site admins to debug the Confetti Bits plugin.', 'confetti-bits' ),
			),

		);

		return $panels;

	}
}

if ( ! function_exists( 'cb_core_get_roles' ) ) {

	function cb_core_get_roles( $type = '' ) {

		$roles	= array(

			'executive' => array(
				'title'       => __( 'Confetti Bits Executives', 'confetti-bits' ),
				'description' => __( 'Configure panels for Confetti Bits Executives.', 'confetti-bits' ),
			),

			'requests' => array(
				'title'       => __( 'Confetti Bits Requests Fulfillment', 'confetti-bits' ),
				'description' => __( 'Configure panels for Confetti Bits Requests Fulfillment.', 'confetti-bits' ),
			),

			'editor' => array(
				'title'       => __( 'Wordpress Editors', 'confetti-bits' ),
				'description' => __( 'Configure panels for the WP Editor role.', 'confetti-bits' ),
			),

			'subscriber' => array(
				'title'       => __( 'Standard Users', 'confetti-bits' ),
				'description' => __( 'Configure panels for standard users.', 'confetti-bits' ),
			),

		);

		if ( empty( $type ) || $type === 'all' ) {
			$retval = $roles;
		} else {
			if ( is_array( $type )  ) {
				foreach( $type as $key ) {
					if ( isset( $roles[$key] ) ) {
						$retval[$key] = $roles[$key];
					}
				}
			} else if ( isset( $roles[$type] ) ) {
				$retval = array( $type => $roles[$type] );
			}
		}

		return $retval;

	}
}

/* 
 * Let Editors manage users, and run this only once.
 * Version: 1.0.0
 */

if ( !function_exists( 'editor_manage_users' ) ) {


	function editor_manage_users() {


		if ( get_option( 'give_editor_caps' ) != 'done' ) {

			$user_editor = get_role('editor'); // Get the user role
			$user_editor->add_cap('manage_options');
			$user_editor->add_cap('edit_users');
			$user_editor->add_cap('list_users');
			$user_editor->add_cap('promote_users');
			$user_editor->add_cap('create_users');
			$user_editor->add_cap('add_users');
			$user_editor->add_cap('delete_users');
			$user_editor->add_cap('edit_courses');
			$user_editor->add_cap('edit_groups');
			$user_editor->add_cap('edit_assignments');

			update_option( 'give_editor_caps', 'done' );

		} 
	}
}
add_action( 'init', 'editor_manage_users' );

if ( !function_exists( 'get_rid_of_the_menus' ) ) {
	function get_rid_of_the_menus() {
		if ( is_user_logged_in() ) {
			$this_user = wp_get_current_user();
			if ( $this_user->has_cap('manage_options') && ( ! cb_is_user_site_admin() ) ) {
				wp_enqueue_script('hide_the_menus', get_stylesheet_directory_uri() . '/assets/js/hide-it.js', 'jquery');
			}
		}
	}
}
add_action('admin_enqueue_scripts','get_rid_of_the_menus');

if ( !class_exists('TeamCTG_User_Caps') ) {

	class TeamCTG_User_Caps {

		// Add our filters
		function __construct() {
			add_filter( 'editable_roles', array(&$this, 'editable_roles'));
			add_filter( 'map_meta_cap', array(&$this, 'map_meta_cap'),10,4);
		}
		// Remove 'Administrator' from the list of roles if the current user is not an admin
		function editable_roles( $roles ){
			if( isset( $roles['administrator'] ) && !current_user_can('administrator') ){
				unset( $roles['administrator']);
			}
			return $roles;
		}
		// If someone is trying to edit or delete an
		// admin and that user isn't an admin, don't allow it
		function map_meta_cap( $caps, $cap, $user_id, $args ){
			switch( $cap ){
				case 'edit_user':
				case 'remove_user':
				case 'promote_user':
					if( isset($args[0]) && $args[0] == $user_id )
						break;
					else if( !isset($args[0]) )
						$caps[] = 'do_not_allow';
					$other = new WP_User( absint($args[0]) );
					if( $other->has_cap( 'administrator' ) ) {
						if( ! current_user_can( 'administrator' ) ) {
							$caps[] = 'do_not_allow';
						}
					}
					break;
				case 'delete_user':
				case 'delete_users':
					if( !isset($args[0]) )
						break;
					$other = new WP_User( absint($args[0]) );
					if( $other->has_cap( 'administrator' ) ){
						if(!current_user_can('administrator')){
							$caps[] = 'do_not_allow';
						}
					}
					break;
				default:
					break;
			}
			return $caps;
		}

	}

	$teamctg_user_cap_reset = new TeamCTG_User_Caps();
}