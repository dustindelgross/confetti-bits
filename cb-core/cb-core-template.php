<?php 
/** 
 * Confetti Bits Template Functions
 */

function cb_get_transactions_slug() {

	return apply_filters( 'cb_get_transactions_slug', Confetti_Bits()->transactions->slug );

}

function cb_member_locate_template_part ( $template = '' ) {

	$displayed_user = bp_get_displayed_user();

	if ( ! $template || empty( $displayed_user->id ) ) {
		return '';
	}

	$cb_template_parts = array(
		'members/single/confetti-bits-hub/cb-%s.php',
	);

	$templates = array();

	foreach ( $cb_template_parts as $cb_template_part ) {
		$templates[] = sprintf( $cb_template_part, $template );
	}

	return bp_locate_template( apply_filters( 'cb_member_locate_template_part', $templates ), true, true );
}

/**
 * CB Member Get Template Part
 * 
 * Loads a template part based on the template
 * that gets passed in the parameters.
 * 
 * @see cb_member_locate_template_part()
 * 
 * @return An array of the active templates.
 */
function cb_member_get_template_part ( $template = '' ) {

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
 * @return An array of the active templates.
 */
function cb_get_active_templates() {

	$debug = isset( $_GET['cb_debug'] ) ? $_GET['cb_debug'] : false;
	$templates = array();

	switch ( true ) {

		case ( cb_is_user_confetti_bits() && cb_is_user_admin() && ! cb_is_user_site_admin() && ! cb_is_user_executive()  && ! cb_is_user_participation_admin() ) :
			$templates = array (
				'Dashboard Header'	=> 'dashboard-header',
				'Dashboard'			=> 'dashboard',
				'Participation'		=> 'participation',
				'Send Bits'			=> 'send-bits',
				'Dev Requests'		=> 'dev-requests',
				'Requests'			=> 'requests',
			);
			break;

		case ( cb_is_user_confetti_bits() && cb_is_user_site_admin() ) :
			$templates = array (
				'Dashboard Header'		=> 'dashboard-header',
				'Dashboard'				=> 'dashboard',
				'Culture Admin'			=> 'participation-admin',
				'Participation'			=> 'participation',
				'Send Bits'				=> 'send-bits',
				'Dev Requests'			=> 'dev-requests',
				'Requests'				=> 'requests',
			);
			break;

		case ( cb_is_user_confetti_bits() && cb_is_user_executive() && ! cb_is_user_site_admin() ) :
			$templates = array (
				'Dashboard Header'	=> 'dashboard-header',
				'Dashboard'			=> 'dashboard',
				'Culture Admin'		=> 'participation-admin',
				'Participation'		=> 'participation',
				'Send Bits'			=> 'send-bits',
				'Dev Requests'		=> 'dev-requests',
				'Requests'			=> 'requests',
			);
			break;
			
		case ( cb_is_user_confetti_bits() && cb_is_user_participation_admin() && ! cb_is_user_site_admin() && ! cb_is_user_executive() ) :
			$templates = array (
				'Dashboard Header'	=> 'dashboard-header',
				'Dashboard'			=> 'dashboard',
				'Culture Admin'		=> 'participation-admin',
				'Participation'		=> 'participation',
				'Send Bits'			=> 'send-bits',
				'Dev Requests'		=> 'dev-requests',
				'Requests'			=> 'requests',
			);
			break;

		case ( cb_is_user_confetti_bits() ) :
		default :
			$templates = array(
				'Dashboard Header'	=> 'dashboard-header',
				'Dashboard'			=> 'dashboard',
				'Participation'		=> 'participation',
				'Send Bits'			=> 'send-bits',
				'Dev Requests'		=> 'dev-requests',
				'Requests'			=> 'requests',
			);
			break;
	}

	if ( 1 == $debug ) {
		$templates['Debug'] = 'debug';
	}

	return $templates;

}

function cb_member_template_part() {

	if ( bp_is_user_front() ) {

		bp_displayed_user_front_template_part();

	} else {

		$debug = isset( $_GET['cb_debug'] ) ? $_GET['cb_debug'] : false;
		$templates = array_values( cb_get_active_templates() );

		foreach ( $templates as $template ) {
			cb_member_get_template_part( $template );
		}

	}

	do_action( 'cb_after_member_body' );
}

function cb_core_load_template( $templates ) {

	global $wp_query;

	bp_theme_compat_reset_post( array(
		'ID'          => 0,
		'is_404'      => true,
		'post_status' => 'publish',
	) );

	bp_set_theme_compat_active( false );

	$filtered_templates = array();
	foreach ( (array) $templates as $template ) {
		$filtered_templates[] = $template . '.php';
	}

	if ( ! bp_use_theme_compat_with_current_theme() ) {
		$template = locate_template( (array) $filtered_templates, false );

	} else {
		$template = '';
	}

	$located_template = apply_filters( 'bp_located_template', $template, $filtered_templates );

	if ( function_exists( 'is_embed' ) && is_embed() ) {
		$located_template = '';
	}

	if ( !empty( $located_template ) ) {

		status_header( 200 );
		$wp_query->is_page     = true;
		$wp_query->is_singular = true;
		$wp_query->is_404      = false;

		do_action( 'bp_core_pre_load_template', $located_template );

		load_template( apply_filters( 'bp_load_template', $located_template ) );

		do_action( 'bp_core_post_load_template', $located_template );

		exit();

	} else {

		if ( is_buddypress() ) {
			status_header( 200 );
			$wp_query->is_page     = true;
			$wp_query->is_singular = true;
			$wp_query->is_404      = false;
		}

		do_action( 'bp_setup_theme_compat' );
	}
}