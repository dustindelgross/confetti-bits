<?php
/**
 * Confetti Bits AJAX Loader.
 *
 * Loads our AJAX component, so we can play nice with Javascript.
 *
 * @since Confetti Bits 2.3.0
 */
// Exit if accessed directly.
defined('ABSPATH') || exit;

function cb_setup_ajax() {
	Confetti_Bits()->ajax = new CB_Ajax_Component();
}
add_action('cb_setup_components', 'cb_setup_ajax', 5);