<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * CB Setup Participation
 *
 * Sets up our participation component, so we have easy access to component globals.
 *
 * @package Confetti_Bits
 * @subpackage Participation
 * @since 2.2.0
 */
function cb_setup_participation() {
	Confetti_Bits()->participation = new CB_Participation_Component();
}
add_action( 'cb_setup_components', 'cb_setup_participation', 4 );