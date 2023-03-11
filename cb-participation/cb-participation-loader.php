<?php

/**
 * Confetti Bits Participation Loader.
 *
 * A participation component, for admins to track culture participation.
 *
 * @since Confetti Bits 2.2.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

function cb_setup_confetti_bits_participation() {
	Confetti_Bits()->participation = new Confetti_Bits_Participation_Component();
}
add_action('bp_setup_components', 'cb_setup_confetti_bits_participation', 14);