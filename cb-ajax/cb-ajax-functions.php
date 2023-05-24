<?php 

function cb_ajax_rest_api_init() {

	$cb = Confetti_Bits();

	$ajax_slug = trailingslashit("{$cb->ajax->slug}/v1");
	//	$transactions_endpoint = $cb->transactions->slug

	register_rest_route( $ajax_slug, 'transactions', array(
		'methods'  => 'GET',
		'callback' => 'custom_api_callback1',
	) );

	register_rest_route( 'custom/v1', '/endpoint2/', array(
		'methods'  => 'POST',
		'callback' => 'custom_api_callback2',
	) );

	register_rest_route( 'custom/v1', '/endpoint3/(?P<id>\d+)', array(
		'methods'  => 'GET',
		'callback' => 'custom_api_callback3',
	) );

}
//add_action( 'rest_api_init', 'cb_ajax_rest_api_init' );

function custom_api_callback1( $request ) {
	// Handle Endpoint 1 logic
}

function custom_api_callback2( $request ) {
	// Handle Endpoint 2 logic
}

function custom_api_callback3( $request ) {
	// Handle Endpoint 3 logic
}

function cb_ajax_transactions_api_init() {

	$cb = Confetti_Bits();
	$ajax_slug = trailingslashit("{$cb->ajax->slug}/v1");
	$transactions_endpoint = $cb->transactions->slug;

	register_rest_route( $ajax_slug, $transactions_endpoint, array(
		'methods'  => 'GET',
		'callback' => 'cb_ajax_get_transactions',
	));

}
add_action( 'cb_rest_api_init', 'cb_ajax_transactions_api_init' );