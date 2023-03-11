<?php

/**
 * Confetti Bits Transactions Loader.
 *
 * A transaction component, for users to send bits to each other.
 *
 * @since Confetti Bits 2.0.1
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

function cb_setup_confetti_bits_transactions() {
	Confetti_Bits()->transactions = new Confetti_Bits_Transactions_Component();
}
add_action('bp_setup_components', 'cb_setup_confetti_bits_transactions', 13);