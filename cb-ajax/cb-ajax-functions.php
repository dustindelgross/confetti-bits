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
 * @package ConfettiBits\AJAX
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
		register_rest_route( $ajax_slug, "{$component_slug}{$endpoint}", [	
			'methods'  => $method,
			'callback' => "cb_ajax_{$endpoint}_{$component}",
		]);
	}

}