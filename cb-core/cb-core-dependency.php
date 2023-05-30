<?php

/**
 * CB Core Dependencies
 *
 * The purpose of this file is to allow plugins to hook into the core
 * of the CB plugin.
 *
 */

/**
 * Fires the cb_admin_enqueue_scripts action, where plugins can enqueue admin scripts.
 */
function cb_admin_enqueue_scripts() {
	do_action( 'cb_admin_enqueue_scripts' );
}

/**
 * Fires the cb_loaded action, where plugins can load their files.
 */
function cb_loaded() {
	do_action( 'cb_loaded' );
}

/**
 * Fires the cb_init action, where plugins can initialize their components.
 */
function cb_init() {
	do_action( 'cb_init' );
}

/**
 * Fires the cb_customize_register action, where plugins can register their customizer settings.
 */
function cb_customize_register( WP_Customize_Manager $customizer ) {
	do_action( 'cb_customize_register', $customizer );
}

/**
 * Fires the cb_ready action, where plugins can do things after the CB plugin is ready.
 */
function cb_ready() {
	do_action( 'cb_ready' );
}

/**
 * Fires the cb_setup_current_user action, where plugins can do things after the current user is setup.
 */
function cb_setup_current_user() {
	do_action( 'cb_setup_current_user' );
}

/**
 * Fires the cb_setup_theme action, where plugins can modify what happens
 * while the theme is being setup.
 */
function cb_setup_theme() {
	do_action( 'cb_setup_theme' );
}

/**
 * Fires the cb_after_setup_theme action, where plugins can do things after the theme is setup.
 */
function cb_after_setup_theme() {
	do_action( 'cb_after_setup_theme' );
}

/**
 * Fires the cb_enqueue_scripts action, where plugins can register their
 * front-end scripts.
 */
function cb_enqueue_scripts() {
	do_action( 'cb_enqueue_scripts' );
}

/**
 * Fires the cb_template_redirect action, where plugins can do things
 * before the template is loaded.
 */
function cb_template_redirect() {
	do_action( 'cb_template_redirect' );
}

/**
 * Fires the cb_widgets_init action, where plugins can register their widgets.
 */
function cb_widgets_init() {
	do_action( 'cb_widgets_init' );
}

/**
 * Fires the cb_generate_rewrite_rules action, where plugins can add their rewrite rules.
 */
function cb_generate_rewrite_rules( $wp_rewrite ) {
	do_action_ref_array( 'cb_generate_rewrite_rules', array( &$wp_rewrite ) );
}

/**
 * Fires the cb_setup_components action, where plugins can register their components.
 */
function cb_setup_components() {
	do_action( 'cb_setup_components' );
}

/**
 * Fires the cb_include action, where plugins can include their files.
 */
function cb_include() {
	do_action( 'cb_include' );
}

/**
 * Fires the cb_register_post_types action, where plugins can register their post types.
 */
function cb_register_post_types() {
	do_action( 'cb_register_post_types' );
}

/**
 * Fires the cb_register_taxonomies action, where plugins can register their taxonomies.
 */
function cb_register_taxonomies() {
	do_action( 'cb_register_taxonomies' );
}

/**
 * Fires the cb_setup_globals action, where plugins can register their globals.
 */
function cb_setup_globals() {
	do_action( 'cb_setup_globals' );
}

/**
 * Fires the cb_setup_canonical_stack action, where plugins can
 * register their canonical stack.
 */
function cb_setup_canonical_stack() {
	do_action( 'cb_setup_canonical_stack' );
}

/**
 * Fires the cb_setup_nav action, where plugins can register their nav items.
 */
function cb_setup_nav() {
	do_action( 'cb_setup_nav' );
}

/**
 * Fires the cb_setup_title action, where plugins can register their title.
 */
function cb_setup_title() {
	do_action( 'cb_setup_title' );
}

/**
 * Fires the cb_add_rewrite_tags action, where plugins can add their rewrite tags.
 */
function cb_add_rewrite_tags() {
	do_action( 'cb_add_rewrite_tags' );
}

/**
 * Fires the cb_add_rewrite_rules action, where plugins can add their rewrite rules.
 */
function cb_add_rewrite_rules() {
	do_action( 'cb_add_rewrite_rules' );
}

/**
 * Fires the cb_add_permastructs action, where plugins can add their permastructs.
 */
function cb_add_permastructs() {
	do_action( 'cb_add_permastructs' );
}

/**
 * Fires the cb_register_member_types action, where plugins can register their member types.
 */
function cb_register_member_types() {
	do_action( 'cb_register_member_types' );
}

/**
 * Fires the cb_actions action, where plugins can register their actions.
 */
function cb_actions() {
	do_action( 'cb_actions' );
}

/**
 * Fires the cb_screens action, where plugins can register their screens.
 */
function cb_screens() {
	do_action( 'cb_screens' );
}


/**
 * Fires the cb_late_include action, where plugins can add include files after
 * the canonical stack has been established.
 */
function cb_late_include() {
	do_action( 'cb_late_include' );
}

/**
 * Fires the cb_post_request action, where plugins can register their post requests.
 */
function cb_post_request() {

	if ( ! cb_is_post_request() ) {
		return;
	}

	if ( empty( $_POST['action'] ) ) {
		return;
	}

	$action = sanitize_key( $_POST['action'] );

	do_action( 'cb_post_request_' . $action );

	do_action( 'cb_post_request', $action );
}

/**
 * Fires the cb_get_request action, where plugins can register their get requests.
 */
function cb_get_request() {

	if ( ! cb_is_get_request() ) {
		return;
	}

	if ( empty( $_GET['action'] ) ) {
		return;
	}

	$action = sanitize_key( $_GET['action'] );

	do_action( 'cb_get_request_' . $action );

	do_action( 'cb_get_request', $action );
}

/**
 * Fires the cb_head action, where plugins can register their head items.
 */
function cb_head() {
	do_action( 'cb_head' );
}

/**
 * Fires the cb_request action, where plugins can modify their queries.
 */
function cb_request( $query_vars = array() ) {
	return apply_filters( 'cb_request', $query_vars );
}

/**
 * Fires the cb_login_redirect action, where plugins can modify the login redirect.
 *
 * @param string           $redirect_to     The redirect destination URL.
 * @param string           $redirect_to_raw The requested redirect destination URL passed as a parameter.
 * @param WP_User|WP_Error $user            WP_User object if login was successful, WP_Error object otherwise.
 */
function cb_login_redirect( $redirect_to = '', $redirect_to_raw = '', $user = false ) {

	/**
	 * @param string           $redirect_to     The redirect destination URL.
	 * @param string           $redirect_to_raw The requested redirect destination URL passed as a parameter.
	 * @param WP_User|WP_Error $user            WP_User object if login was successful, WP_Error object otherwise.
	 */
	return apply_filters( 'cb_login_redirect', $redirect_to, $redirect_to_raw, $user );
}

/**
 * Fires the cb_template_include action, where plugins can modify the template.
 *
 * @param string $template The template to include.
 */
function cb_template_include( $template = '' ) {
	return apply_filters( 'cb_template_include', $template );
}

/**
 * Fires the cb_allowed_themes action, where plugins can modify the allowed themes.
 *
 * @param array $themes The allowed themes.
 */
function cb_allowed_themes( $themes ) {
	return apply_filters( 'cb_allowed_themes', $themes );
}

/**
 * CB REST API Init
 *
 * Fires the cb_rest_api_init action, so we (and others) can easily hook into our REST API.
 *
 * @package ConfettiBits
 * @since 2.3.0
 */
function cb_rest_api_init() {
	do_action('cb_rest_api_init');
}