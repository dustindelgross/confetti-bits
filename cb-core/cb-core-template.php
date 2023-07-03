<?php 
/** 
 * CB Core Template Functions
 * 
 * This file is going to store all of our core template functionality.
 * This includes locating and loading templates at specified locations,
 * typically on the confetti-bits dashboard page.
 * 
 * @package ConfettiBits\Core
 * @subpackage Templates
 * @since 1.0.0
 */
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * CB Member Locate Template Part
 * 
 * Attempts to locate the specified template in the TeamCTG 
 * Child Theme, located at '/cb-template-parts/cb-{$template}.php'.
 * 
 * @param string $template The template to look for.
 * 
 * @return string The template, if found.
 * 
 * @package ConfettiBits\Core
 * @subpackage Templates
 * @since 1.0.0
 */
function cb_member_locate_template_part ( $template = '' ) {

	if ( ! $template  ) {
		return '';
	}

	$cb_template_parts = array(
		'cb-template-parts/cb-%s.php',
	);

	$templates = array();

	foreach ( $cb_template_parts as $cb_template_part ) {
		$templates[] = sprintf( $cb_template_part, $template );
	}

	return locate_template( $templates, true, true );
}

/**
 * CB Member Get Template Part
 * 
 * Loads a template part based on the template
 * that gets passed in.
 * 
 * @see cb_member_locate_template_part()
 * 
 * @return An array of the active templates.
 * 
 * @package ConfettiBits\Core
 * @subpackage Templates
 * @since 1.0.0
 */
function cb_member_get_template_part( $template = '' ) {

	$located = cb_member_locate_template_part( $template );

	if ( false !== $located ) {
		$slug = str_replace( '.php', '', $located );
		$name = null;

		do_action( 'get_template_part_' . $slug, $slug, $name );

		load_template( $located, true );
	}

	return $located;
}

/**
 * Confetti Bits Get Active Templates
 * 
 * Sets up the templates to show users based on permissions.
 * 
 * @return An array of the active templates.
 * 
 * @package ConfettiBits\Core
 * @subpackage Templates
 * @since 1.0.0
 */
function cb_get_active_templates() {

	$debug = isset( $_GET['cb_debug'] ) ? $_GET['cb_debug'] : false;
	$templates = [
		'Dashboard Header'	=> 'dashboard-header',
		'Dashboard'			=> 'dashboard',
		'My Participation'	=> 'participation',
		'My Transactions'	=> 'transactions',
		'My Requests'		=> 'requests',
	];
	
	if ( cb_is_user_participation_admin() ) {
		$templates['Participation Admin'] = 'participation-admin';
	}
	
	if ( cb_is_user_requests_admin() ) {
		$templates['Requests Admin'] = 'requests-admin';
	}
	
	if ( cb_is_user_events_admin() ) {
		
	}

	if ( 1 == $debug ) {
		$templates['Debug'] = 'debug';
	}

	if ( cb_is_user_site_admin() ) {
		$templates['Events'] = 'events';
		$templates['Events Admin'] = 'events-admin';
	}

	return $templates;

}


/**
 * Renders the member template part appropriate for the current page.
 * 
 * Right now? We only have the one page. Oof.
 * 
 * @package ConfettiBits\Core
 * @since 1.0.0
 */
function cb_member_template_part() {

	$templates = array_values( cb_get_active_templates() );

	foreach ( $templates as $template ) {
		cb_member_get_template_part( $template );
	}

	do_action( 'cb_after_member_body' );
}

/**
 * Adds Confetti Captain badges to the user's member profile page
 * as well as to the activity feed, if they are a Confetti Captain.
 * 
 * @package ConfettiBits\Core
 * @subpackage Templates
 * @since 1.2.0
 */
function cb_core_add_confetti_captain_badges()
{
	if ((!cb_is_user_site_admin() || !bp_is_user_profile()) && !bp_is_activity_component()) {
		return;
	}

	$cb = Confetti_Bits();

	wp_enqueue_script('cb_member_profile_badge_js', $cb->plugin_url . '/assets/js/cb-member-profile.js', array('jquery'));
	wp_enqueue_style('cb_member_profile_badge_css', $cb->plugin_url . '/assets/css/cb-member-profile.css');

}
add_action('wp_enqueue_scripts', 'cb_core_add_confetti_captain_badges');

/**
 * Adds our custom 'confetti-captain' class to the BuddyBoss user avatar
 * so that we can add a cute little sparkler icon using JS.
 * 
 * @package ConfettiBits\Core
 * @subpackage Templates
 * @since 1.2.0
 */
function cb_core_confetti_captain_class($class, $item_id)
{

	$is_confetti_captain = groups_is_user_member($item_id, 1);
	if (is_int($is_confetti_captain)) {
		$class .= ' confetti-captain';
	}
	return $class;
}
add_filter('bp_core_avatar_class', 'cb_core_confetti_captain_class', 10, 2);

/**
 * Adds a cute litte sparkler badge on the profile page of users
 * who are designated as "Confetti Captains", which meand they are
 * part of the Confetti Captains group.
 * 
 * @package ConfettiBits\Core
 * @subpackage Templates
 * @since 1.2.0
 */
function cb_core_confetti_captain_profile_badge()
{

	$badge = '';
	$user_id = bp_displayed_user_id();
	$is_confetti_captain = groups_is_user_member($user_id, 1);
	if (is_int($is_confetti_captain)) {
		$badge .= '<div class="confetti-captain-profile-label-container"><div class="confetti-captain-badge-container"><div class="confetti-captain-badge-medium"></div></div><p class="confetti-captain-profile-label">Confetti Captain</p></div>';
	}
	echo $badge;
}
add_filter('bp_before_member_in_header_meta', 'cb_core_confetti_captain_profile_badge');