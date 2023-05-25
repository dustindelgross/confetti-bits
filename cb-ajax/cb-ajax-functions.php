<?php 

/**
 * CB AJAX Transactions API Init
 * 
 * Registers our REST API routes for all our transactions data.
 * 
 * @since Confetti_Bits 2.3.0
 */
function cb_ajax_transactions_api_init() {

	$cb = Confetti_Bits();
	$ajax_slug = trailingslashit("{$cb->ajax->slug}/v1");
	$transactions_endpoint = "/{$cb->transactions->slug}/";
	$participation_new_endpoint = "/{$cb->participation->slug}/new/";

	register_rest_route( $ajax_slug, $transactions_endpoint, array(
		'methods'  => 'GET',
		'callback' => 'cb_ajax_get_transactions',
	));
	
	register_rest_route( $ajax_slug, $participation_new_endpoint, array(
		'methods'  => 'POST',
		'callback' => 'cb_ajax_new_participation',
	));

}
add_action( 'cb_rest_api_init', 'cb_ajax_transactions_api_init' );