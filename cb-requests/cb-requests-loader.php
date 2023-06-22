<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * CB Setup Requests
 *
 * Sets up our requests component, so we have easy access to component globals.
 *
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_setup_requests() {
	Confetti_Bits()->requests = new CB_Requests_Component();
}
add_action( 'cb_setup_components', 'cb_setup_requests', 6 );