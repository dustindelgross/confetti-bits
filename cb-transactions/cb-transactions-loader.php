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

function cb_setup_transactions() {
	Confetti_Bits()->transactions = new CB_Transactions_Component();
}
add_action('cb_setup_components', 'cb_setup_transactions', 3);