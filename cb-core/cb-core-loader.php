<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Sets up our Core component, which loads all of our other components.
 *
 * @package ConfettiBits\Core
 * @since 1.0.0
 */
function cb_setup_core() {
	Confetti_Bits()->core = new CB_Core();
}
add_action('cb_setup_components', 'cb_setup_core', 1);