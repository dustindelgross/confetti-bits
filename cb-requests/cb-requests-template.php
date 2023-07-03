<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * CB Requests Nav
 * 
 * Outputs the requests nav.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_requests_nav() {
	echo cb_requests_get_nav();
}

/**
 * CB Requests Format Nav Data
 * 
 * Formats the nav data for the requests component.
 * We need a pretty complicated set of arguments for 
 * cb_templates_get_nav() and cb_templates_get_nav_items()
 * and this helps us achieve that in a structured way.
 * 
 * @param array $items A collection of key => value pairs
 * 
 * @return array The list of formatted nav data.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_requests_format_nav_data( $component = '', $items = array() ) {

	if ( empty( $items ) || empty($component) ) {
		return;
	}

	$formatted = array();
	$with_dashes = str_replace( '_', '-', $component );

	foreach ( $items as $key => $val ) {
		$formatted[$key] = array(
			'value' => $val,
			'custom_attr' => array("no_data_cb-{$with_dashes}-status-type" => $val ),
			'href' => "#cb-{$with_dashes}-filter-{$val}"
		);
		if ( $val === 'new' ) {
			$formatted[$key]['active'] = true;
		}
	}

	return $formatted;

}

/**
 * CB Requests Get Nav
 * 
 * Returns the nav for the requests filtering system.
 * 
 * @return string The nav markup.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_requests_get_nav() {

	$items = cb_requests_format_nav_data( 'requests', [
		'New' => 'new',
		'Approved' => 'approved',
		'Denied' => 'denied',
		'In Progress', 'in_progress',
		'All' => 'all',
	]);

	return cb_templates_get_nav( 'requests', $items );

}

/**
 * CB Requests Admin Nav
 * 
 * Outputs the requests admin nav.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_requests_admin_nav() {
	echo cb_requests_admin_get_nav();
}

/**
 * CB Requests Admin Get Nav
 * 
 * Returns the nav for the requests admin filtering system.
 * 
 * @return string The nav markup.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_requests_admin_get_nav() {

	$items = cb_requests_format_nav_data(
		'requests_admin',
		array( 
			'New' => 'new',
			'Approved' => 'approved',
			'Denied' => 'denied',
			'All' => 'all',
		)
	);

	return cb_templates_get_nav( 'requests_admin', $items );

}

/**
 * Gets markup for the request items form.
 * 
 * @return string The formatted markup.
 * 
 * @package ConfettiBits\Requests
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_requests_get_request_items_form() {
	return cb_templates_get_form_module([
		'method' => 'POST',
		'component' => 'request_items',
		'output' => [
			'heading' => 'Add New Request Item',
			'component' => 'request_items',
			'inputs' => [
				[ 'type' => 'text', 'args' => ['name' => 'item_name', 'label' => 'Item Name' ] ],
				[ 'type' => 'text', 'args' => [ 'name' => 'item_desc', 'label' => 'Item Description', 'textarea' => 'true' ] ],
				[  'type' => 'number', 'args' => [ 'name' => 'amount', 'label' => 'Amount' ] ],
				[  'type' => 'submit', 'args' => [ 'name' => 'submit', 'value' => 'Add' ] ],
			]
		]
	]);
}

/**
 * Outputs markup for request items form.
 * 
 * @package ConfettiBits\Requests
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_request_items_form() {
	echo cb_requests_get_request_items_form();
}

/**
 * Gets markup for the request items form.
 * 
 * @return string The formatted markup.
 * 
 * @package ConfettiBits\Requests
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_requests_get_requests_form() {
	
	$items = cb_requests_get_request_items([
		'select' => 'id, item_name, amount',
		'pagination' => [
			'page' => 1,
			'per_page' => 15,
		]
	]);
	$options = [];
	
	foreach ( $items as $item ) {
		$options["{$item['item_name']} - {$item['amount']}"] = ['value' => $item['id'] ];
	}
	
	return cb_templates_get_form_module([
		'method' => 'POST',
		'component' => 'requests',
		'output' => [
			'heading' => 'Submit Request',
			'component' => 'requests',
			'inputs' => [
				[ 'type' => 'select', 'args' => [ 'name' => 'request_item_id', 'label' => 'Item Selection', 'select_options' => $options, 'placeholder' => 'Please select an item' ] ],
				[ 'type' => 'number', 'args' => [ 'name' => 'amount', 'label' => 'Amount', 'disabled' => true ] ],
				[ 'type' => 'hidden', 'args' => ['name' => 'applicant_id'] ],
				[ 'type' => 'submit', 'args' => [ 'name' => 'submit', 'value' => 'Request' ] ],
			]
		]
	]);
}

/**
 * Outputs markup for request items form.
 * 
 * @package ConfettiBits\Requests
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_requests_form() {
	echo cb_requests_get_requests_form();
}