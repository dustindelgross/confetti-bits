<?php 
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * CB AJAX Transactions API Init
 * 
 * Registers our REST API routes for all our transactions data.
 * 
 * @package ConfettiBits\AJAX
 * @since 2.3.0
 */
function cb_ajax_transactions_api_init() {

	$cb = Confetti_Bits();
	$ajax_slug = trailingslashit("{$cb->ajax->slug}/v1");
	$transactions_slug = trailingslashit($cb->transactions->slug);

	$endpoints = [
		"new" => "POST", 
		"get" => "GET",
		"update" => "PATCH", 
		"delete" => "DELETE", 
	];

	foreach ( $endpoints as $endpoint => $method ) {
		register_rest_route( $ajax_slug, "{$transactions_slug}{$endpoint}", [
			'methods'  => $method,
			'callback' => "cb_ajax_{$endpoint}_{$cb->transactions->slug}",
		]);
	}

}
add_action( 'cb_rest_api_init', 'cb_ajax_transactions_api_init' );

/**
 * CB AJAX Participation API Init
 * 
 * Registers our REST API routes for all our participation data.
 * 
 * @package ConfettiBits\AJAX
 * @since 2.3.0
 */
function cb_ajax_participation_api_init() {

	$cb = Confetti_Bits();
	$ajax_slug = trailingslashit("/{$cb->ajax->slug}/v1");
	$participation_slug = trailingslashit($cb->participation->slug);
	$endpoints = [
		"new" => "POST", 
		"get" => "GET",
		"update" => "PATCH", 
		"delete" => "DELETE", 
	];

	foreach ( $endpoints as $endpoint => $method ) {
		register_rest_route( $ajax_slug, "{$participation_slug}{$endpoint}", [	
			'methods'  => $method,
			'callback' => "cb_ajax_{$endpoint}_{$cb->participation->slug}",
		]);
	}

}
add_action( 'cb_rest_api_init', 'cb_ajax_participation_api_init' );

/**
 * Registers our REST API routes for all our request data.
 * 
 * @package ConfettiBits\AJAX
 * @since 2.3.0
 */
/*
function cb_ajax_requests_api_init() {
	$cb = Confetti_Bits();
	$ajax_slug = trailingslashit("/{$cb->ajax->slug}/v1");
	$requests_slug = trailingslashit($cb->requests->slug);
	$endpoints = [
		"new" => "POST",
		"get" => "GET",
		"update" => "PATCH", 
		"delete" => "DELETE", 
	];

	foreach ( $endpoints as $endpoint => $method ) {
		register_rest_route( $ajax_slug, "{$requests_slug}{$endpoint}", [	
			'methods'  => $method,
			'callback' => "cb_ajax_{$endpoint}_{$cb->requests->slug}",
		]);
	}
}
add_action( 'cb_rest_api_init', 'cb_ajax_requests_api_init' );
*/
/**
 * Registers our REST API routes for all our requests data.
 * 
 * @package ConfettiBits\AJAX
 * @since 2.3.0
 */
function cb_ajax_requests_api_init() {
	cb_ajax_register_rest_route( 'requests', [
		"new" => "POST",
		"get" => "GET",
		"update" => "PATCH", 
		"delete" => "DELETE"
	]);
}
add_action( 'cb_rest_api_init', 'cb_ajax_requests_api_init' );

/**
 * Registers our REST API routes for all our request item data.
 * 
 * @package ConfettiBits\AJAX
 * @since 2.3.0
 */
function cb_ajax_request_items_api_init() {
	cb_ajax_register_rest_route( 'request_items', [
		"new" => "POST",
		"get" => "GET",
		"update" => "PATCH", 
		"delete" => "DELETE"
	]);
}
add_action( 'cb_rest_api_init', 'cb_ajax_request_items_api_init' );

/**
 * Dynamically registers a REST route.
 * 
 * @param string $component The component to register a route for.
 * @param array $endpoints An associative array of endpoints 
 * 						   and HTTP methods.
 * 
 * @package ConfettiBits\AJAX
 * @since 2.3.0
 */
function cb_ajax_register_rest_route( $component = '', $endpoints = [] ) {

	$cb = Confetti_Bits();
	$ajax_slug = trailingslashit( "/{$cb->ajax->slug}/v1");
	$with_dashes = str_replace( '_', '-', $component );
	$component_slug = trailingslashit( $with_dashes );

	foreach ( $endpoints as $endpoint => $method ) {
		register_rest_route( $ajax_slug, "{$component_slug}{$endpoint}", [	
			'methods'  => $method,
			'callback' => "cb_ajax_{$endpoint}_{$component}",
		]);
	}

}