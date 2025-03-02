<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * CB Setup Settings
 *
 * Sets up our settings component, so we have easy access to component globals.
 *
 * @package Settings
 * @since 3.1.0
 */
function cb_setup_settings() {
	Confetti_Bits()->settings = new CB_Settings_Component();
}
add_action( 'cb_setup_components', 'cb_setup_settings', 10 );