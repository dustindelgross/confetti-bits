<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * CB Setup Transactions
 *
 * Sets up our transactions component, so we have easy access to component globals.
 *
 * @package Confetti_Bits
 * @subpackage Transactions
 * @since 1.0.0
 */
function cb_setup_transactions() {
	Confetti_Bits()->transactions = new CB_Transactions_Component();
}
add_action('cb_setup_components', 'cb_setup_transactions', 3);