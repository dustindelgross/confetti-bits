<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * CB Setup Core
 *
 * Sets up our Core component, which loads all of our other components.
 *
 * @package Confetti_Bits
 * @subpackage Core
 * @since 1.0.0
 */
function cb_setup_core() {
	Confetti_Bits()->core = new CB_Core();
}
add_action('cb_setup_components', 'cb_setup_core', 1);