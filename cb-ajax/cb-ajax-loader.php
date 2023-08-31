<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Loads our AJAX component, so we can play nice with Javascript.
 *
 * @package ConfettiBits\AJAX
 * @since 2.3.0
 */
function cb_setup_ajax() {
	Confetti_Bits()->ajax = new CB_Ajax_Component();
}
add_action('cb_setup_components', 'cb_setup_ajax', 2);