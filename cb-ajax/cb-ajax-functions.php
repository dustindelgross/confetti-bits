<?php
// Exit if accessed directly
defined('ABSPATH') || exit;
/**
 * CB AJAX Transactions API Init
 *
 * Registers our REST API routes for all our transactions data.
 *
 * @package ConfettiBits
 * @subpackage AJAX
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
		register_rest_route( $ajax_slug, $transactions_slug, [
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
 * @package ConfettiBits
 * @subpackage AJAX
 * @since 2.3.0
 */
function cb_ajax_participation_api_init() {

	$cb = Confetti_Bits();
	$ajax_slug = trailingslashit("{$cb->ajax->slug}/v1");
	$participation_slug = trailingslashit($cb->participation->slug);
	$endpoints = [
		"new" => "POST",
		"get" => "GET",
		"update" => "PATCH",
		"delete" => "DELETE",
	];

	foreach ( $endpoints as $endpoint => $method ) {
		register_rest_route( $ajax_slug, $participation_slug, [
			'methods'  => $method,
			'callback' => "cb_ajax_{$endpoint}_{$cb->participation->slug}",
		]);
	}

}
add_action( 'cb_rest_api_init', 'cb_ajax_participation_api_init' );