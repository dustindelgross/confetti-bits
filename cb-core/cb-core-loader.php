<?php

/**
 * Confetti Bits Transactions Loader.
 *
 * A transaction component, for users to send bits to each other.
 *
 * @since Confetti Bits 2.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

function cb_setup_confetti_bits_core() {
	Confetti_Bits()->core = new Confetti_Bits_Core();
}
add_action('bp_setup_components', 'cb_setup_confetti_bits_core', 1);