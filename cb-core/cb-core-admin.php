<?php 
/**
 * CB Core Admin
 * 
 * This file will register fields for admin settings, using 
 * some of the following actions associated with
 * BuddyBoss Platform:
 * - bp_admin_setting_general_register_fields
 * - bp_admin_setting_xprofile_register_fields
 * - bp_admin_setting_groups_register_fields
 * - bp_admin_setting_forums_register_fields
 * - bp_admin_setting_activity_register_fields
 * - bp_admin_setting_media_register_fields
 * - bp_admin_setting_friends_register_fields
 * - bp_admin_setting_invites_register_fields
 * - bp_admin_setting_search_register_fields
 * We're going to have our own little box for these settings. 
 * 
 * @package ConfettiBits\Core
 * @since 1.0.0
 */

/**
 * CB Core Admin Settings General Register Fields
 * 
 * Registers our settings fields in the general context of 
 * the BuddyBoss settings page environment.
 * Key part of this is the "bp_admin_setting_{ component }_register_fields"
 * `add_section` is a wordpress method to create the settings section. It requires a setting object.
 * `add_field` is a wordpress method that creates a field within the section we just made.
 * `bp_admin_setting_{ component }_register_fields` handles the rest.
 * 
 * @package ConfettiBits\Core
 * @since 1.0.0
 */
function cb_core_admin_setting_general_register_fields( $setting ) {

	$active_components = $cb->active_components;
	/*
	$default_components  = cb_core_admin_get_components( 'default' );
	$optional_components = cb_core_admin_get_components( 'optional' );
	$required_components = cb_core_admin_get_components( 'required' );
	$all_components = $required_components + $optional_components;
	*/
	//	$roles	= cb_core_get_roles();
	//	$panels	= cb_core_get_panels();
	//	$active_panels = bp_get_option( "cb_active_panels" );

	if ( empty( $active_components ) ) {
		return;
	}

	//	$inactive_components = array_diff( array_keys( $all_components ), array_keys( $active_components ) );
	//	$current_components = $all_components;

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
//add_action( 'bp_admin_setting_general_register_fields', 'cb_core_admin_setting_general_register_fields' );




function cb_core_admin_components_settings() { 
	cb_admin_components_options();
}

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

/**
 * Sets some useful globals for specialized roles for later use.
 * 
 * The idea here is that once these globals are set, we can access
 * them to grant certain permissions to certain roles, which will
 * help us determine who can see what in our template loop.
 * 
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
function cb_core_set_role_globals() {

	$cb = Confetti_Bits();

	$roles = [
		'executive' => [
			'label' => 'Confetti Bits Executive',
			'caps' => [
				'manage_options' => true,
				'edit_users' => true,
				'list_users' => true,
				'promote_users' => true,
				'create_users' => true,
				'add_users' => true,
				'delete_users' => true,
				'edit_courses' => true,
				'edit_groups' => true,
				'edit_assignments' => true,
				'cb_executive' => true,
				'cb_participation_admin' => true,
				'cb_requests_admin' => true,
				'cb_events_admin' => true,
				'cb_staffing_admin' => true,
				'cb_admin' => true,
				'cb_transactions_admin' => true,
			]
		],
		'leadership' => [
			'label' => 'Confetti Bits Leadership',
			'caps' => [
				'read' => true,
				'cb_participation_admin' => true,
				'cb_staffing_admin' => true,
				'create_users' => true,
				'add_users' => true,
				'list_users' => true,
				'edit_users' => true,
				'edit_courses' => true,
				'edit_groups' => true,
				'edit_assignments' => true,
				'cb_requests_admin' => true,
				'cb_events_admin' => true,
				'cb_admin' => true,
				'cb_transactions_admin' => true,
			]
		],
		'requests_admin' => [
			'label' => 'Confetti Bits Requests Admin',
			'caps' => [
				'read' => true,
				'cb_requests_admin' => true
			]
		],
		'participation_admin' => [
			'label' => 'Confetti Bits Participation Admin',
			'caps' => [
				'read' => true,
				'cb_participation_admin' => true
			]
		],
		'events_admin' => [
			'label' => 'Confetti Bits Events Admin',
			'caps' => [
				'read' => true,
				'cb_events_admin' => true,
			]
		],
		'staffing_admin' => [
			'label' => 'Confetti Bits Staffing Admin',
			'caps' => [
				'read' => true,
				'cb_staffing_admin' => true,
				'edit_users' => true,
				'list_users' => true,
				'promote_users' => true,
				'create_users' => true,
				'add_users' => true,
				'delete_users' => true,
				'edit_courses' => true,
				'edit_groups' => true,
				'edit_assignments' => true
			]
		],
	];

	foreach ( $roles as $id => $args ) {
		$cb->roles->{$id} = new CB_Core_Role( $id, $args['label'], $args['caps'] );
	}

}


/**
 * Grants all capabilities to administrator users.
 * 
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
function cb_core_add_admin_caps() {

	$admin_role = get_role( 'administrator' );
	$cb = Confetti_Bits();

	$roles = [
		'executive',
		'leadership',
		'requests_admin',
		'participation_admin',
		'events_admin',
		'staffing_admin',
		'transactions_admin',
	];

	foreach ( $roles as $role ) {

		$capabilities = [];

		if ( isset($cb->roles->{$role} ) ) {
			$capabilities = $cb->roles->{$role}->caps;
		}

		foreach ( $capabilities as $capability => $value ) {
			$admin_role->add_cap( $capability, $value );
		}
	}
}

/**
 * An alias for current_user_can()
 * 
 * @param string $cap The capability to check for.
 * 
 * @return bool Whether the user has the capability.
 * 
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
function cb_core_current_user_can( $cap = '' ) {
	return current_user_can($cap);
}

/**
 * Checks whether a user is an admin of the given component.
 * 
 * @param string $component The component to check for.
 * 
 * @return bool Whether the user is an admin for the component.
 * 
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
function cb_core_is_component_admin( $component = '' ) {
	return current_user_can( "cb_{$component}_admin" );
}

/**
 * Checks whether user has certain administrative privileges.
 * 
 * These include:
 * 		- cb_participation_admin
 * 		- cb_events_admin
 * 		- cb_requests_admin
 * 
 * It's important to note that the cb_admin capability
 * is shared by both the cb_leadership role as well as
 * the cb_executive role, so checking for this capability
 * will return true for both of those roles, along with
 * users that have the 'administrator' capability.
 * 
 * @return bool Whether the current user is a cb_admin.
 * 
 * @package ConfettiBits\Core
 * @since 1.0.0
 */
function cb_is_user_admin( $user_id = 0 ) {
	return cb_core_admin_is_user_admin($user_id);
}

/**
 * Checks to see if the user is an executive user.
 * 
 * This role carries specific, high-level privileges
 * in certain areas of the application, so it exists
 * as its own separate capability.
 * 
 * @package ConfettiBits\Core
 * @since 1.3.0
 */
function cb_is_user_executive() {
	return current_user_can('cb_executive');
}

/**
 * Checks if the user has site admin privileges.
 * 
 * Checks whether a user has administrative privileges.
 * Also, @see cb_core_admin_is_user_site_admin(), because
 * we're deprecating this for the sake of our API and 
 * sanity (sorry besties).
 * 
 * @TODO: Figure out a better dev environment setup; this
 * isn't particularly good to use as a feature-blocker.
 * 
 * @return bool Whether a user has administrative privileges.
 * 
 * @package ConfettiBits\Core
 * @since 1.0.0
 */
function cb_is_user_site_admin( $user_id = 0 ) {
	return cb_core_admin_is_user_site_admin( $user_id );
}

/**
 * CB Core Admin Is User Site Admin
 * 
 * Checks whether a user has administrative privileges.
 * 
 * @TODO: Figure out a better dev environment setup; this
 * isn't particularly good to use as a feature-blocker.
 * 
 * @return bool Whether a user has administrative privileges.
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_core_admin_is_user_site_admin( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		return current_user_can('administrator');
	}

	$user = new WP_User($user_id);

	return $user->has_cap('administrator');
	
}

/**
 * Checks whether a user has the cb_admin capability.
 * 
 * @return bool Whether a user has cb_admin privileges.
 * 
 * @package ConfettiBits\Core
 * @since 2.3.1
 */
function cb_core_admin_is_user_admin( $user_id = 0 ) {

	$user_id = intval($user_id);
	
	if ( $user_id === 0 ) {
		return current_user_can('cb_admin');
	}

	$user = new WP_User($user_id);

	return $user->has_cap('cb_admin');
	
}

/**
 * Checks to see if the user is a participation admin.
 * 
 * Checks whether a user has administrative privileges over the 
 * participation component. These privileges are granted by
 * assigning a role on the Edit User admin page.
 * 
 * @return bool Whether a user is a participation admin.
 * 
 * @package ConfettiBits\Core
 * @since 1.0.0
 */
function cb_is_user_participation_admin() {
	return cb_core_is_component_admin('participation');
}

/**
 * Checks to see if the user is a requests admin.
 * 
 * Checks whether a user has administrative privileges over the 
 * requests component. These privileges are granted by
 * assigning a role on the Edit User admin page.
 * 
 * @return bool Whether a user is a requests admin.
 * 
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
function cb_is_user_requests_admin() {
	return cb_core_is_component_admin('requests');
}

/**
 * Checks to see if the user is a staffing admin.
 * 
 * Checks whether a user has administrative privileges for
 * user moderation. These privileges are granted by
 * assigning a role on the Edit User admin page.
 * 
 * @return bool Whether a user is a staffing admin.
 * 
 * @package ConfettiBits\Core
 * @since 1.0.0
 */
function cb_is_user_staffing_admin() {
	return cb_core_is_component_admin('staffing');
}

/**
 * Checks to see if the user is an events admin.
 * 
 * Checks whether a user has administrative privileges over the 
 * events component. These privileges are granted by
 * assigning a role on the Edit User admin page.
 * 
 * @return bool Whether a user is an events admin.
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_is_user_events_admin() {
	return cb_core_is_component_admin('events');
}

/**
 * Checks to see if the user is a transactions admin.
 * 
 * Checks whether a user has administrative privileges to 
 * send bits to other users.
 * 
 * @param int $user_id User ID.
 * @return bool Whether a user is a transactions admin.
 * 
 * @package Core
 * @since 3.1.1
 */
function cb_is_user_transactions_admin( $user_id = 0 ) {
	
	$user_id = intval($user_id);
	
	if ( $user_id === 0 ) {
		return current_user_can('cb_transactions_admin');
	}

	$user = new WP_User($user_id);

	return $user->has_cap('cb_transactions_admin');
	
}




/** 
 * Let Editors manage users, and run this only once.
 * Version: 1.0.0
 */
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
add_action( 'init', 'editor_manage_users' );

/**
 * Remove privileged menus from the admin area.
 * 
 * @package ConfettiBits\Core
 * @since 1.2.0
 */
function get_rid_of_the_menus() {
	if ( is_user_logged_in() ) {
		$this_user = wp_get_current_user();
		if ( $this_user->has_cap('manage_options') && ( ! cb_is_user_site_admin() ) ) {
			wp_enqueue_script('hide_the_menus', get_stylesheet_directory_uri() . '/assets/js/hide-it.js', ['jquery']);
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