<?php 
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Dynamically registers a REST route.
 * 
 * @param string $component The component to register a route for.
 * @param array $endpoints An associative array of endpoints 
 * 						   and HTTP methods.
 * 
 * @package AJAX
 * @since 2.3.0
 */
function cb_ajax_register_rest_route( $component = '' ) {

	$cb = Confetti_Bits();
	$ajax_slug = trailingslashit( "/{$cb->ajax->slug}/v1");
	$with_dashes = str_replace( '_', '-', $component );
	$component_slug = trailingslashit( $with_dashes );
	$endpoints = [
		"new" => "POST",
		"get" => "GET",
		"update" => "PATCH", 
		"delete" => "DELETE"
	];

	foreach ( $endpoints as $endpoint => $method ) {
		try {
			
			$register = register_rest_route( $ajax_slug, "{$component_slug}{$endpoint}", [	
				'methods'  => $method,
				'callback' => "cb_ajax_{$endpoint}_{$component}",
			], true);
			
			if ( $register === false ) {
				throw new Exception("No components passed.");
			} 

		} catch (Exception $e) {
			echo $e->getMessage();
		}

	}

}

/**
 * Adds a REST endpoint so we can send out updates to the team with each commit.
 */
function cb_setup_gh_commit_notifications() {

	$cb = Confetti_Bits();
	$ajax_slug = trailingslashit( "/{$cb->ajax->slug}/v1");

	register_rest_route($ajax_slug, '/gh-commits', [
		'methods' => 'POST',
		'callback' => 'cb_handle_gh_commits',
	]);
}
// add_action('rest_api_init', 'cb_setup_gh_commit_notifications');

function cb_handle_gh_commits(WP_REST_Request $request) {

	$data = $request->get_json_params();
	$content = '';

	if ( empty($data['api_key'] ) ) {
		return new WP_REST_Response( 'Missing or invalid API key.', 403 );
	}

	if ( ! cb_core_validate_api_key($data['api_key'])) {
		return new WP_REST_Response( 'Invalid API key.', 403 );
	}

	if (isset($data['commits'])) {
		echo print_r($data['commits']);
		foreach ($data['commits'] as $commit) {

			/*
            bp_activity_add([
                'post_title'   => substr($commit['message'], 0, 40), // First 40 characters of the commit message
                'post_content' => $commit['message'], // Full commit message
                'post_status'  => 'publish',
                'post_author'  => 1, // or another user ID
                'post_type'    => 'post', // or 'page', 'custom_post_type', etc.
            ]);
			*/
		}
	}

	return new WP_REST_Response('Webhook received!', 200);
}
