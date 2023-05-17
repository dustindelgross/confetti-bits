<?php 
/**
 * Events Component
 *
 * @package Confetti Bits
 */
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Events Component Initialization
 *
 * @since 1.0.0
 */
function cb_setup_events() {
	Confetti_Bits()->events = new Confetti_Bits_Events_Component();
}
add_action('bp_setup_components', 'cb_setup_events', 5);