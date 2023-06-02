<?php 
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * CB Setup Events
 * 
 * Loads our Events component to give us easy access to global values.
 * 
 * @package Confetti_Bits
 * @subpackage Events
 * @since 2.3.0
 */
function cb_setup_events() {
	Confetti_Bits()->events = new CB_Events_Component();
}
add_action('cb_setup_components', 'cb_setup_events', 5);