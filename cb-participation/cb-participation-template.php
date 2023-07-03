<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * CB Participation Event Type Filter
 * 
 * Outputs markup for a participation event type select input.
 * 
 * @param bool $is_admin Whether it's for an filter selection.
 * 						 There's probably a better way to do this,
 * 						 but we need a band-aid for now, because we're
 * 						 down to the wire on this update. :D
 * 
 * @TODO: Find a better implementation for this!
 * 
 * @package ConfettiBits\Participation
 * @since 2.2.0
 */
function cb_participation_event_type_filter( $is_admin = false ) {

	$filter_options = array(
		"Please select an option" => array('value' => ''),
		'Dress Up Day' => array('value' => 'dress_up'),
		'Office Lunch' => array('value' => 'lunch'),
		'Holiday' => array( 'value' => 'holiday' ),
		'In-Office Activity' => array( 'value' => 'activity' ),
		'Awareness Day' => array( 'value' => 'awareness' ),
		'Team Meeting' => array( 'value' => 'meeting'),
		"Amanda's Workshop" => array( 'value' => 'workshop' ),
		'Contest' => array( 'value' => 'contest' ),
		'Other' => array( 'value' => 'other' ),
	);
	
	$action = $is_admin ? 'participation_admin' : 'participation';

	return cb_select_input(
		array(
			'name'				=> "cb_{$action}_event_type_filter",
			'label'				=> 'Filter By Event Type',
			'required'			=> false,
			'select_options'	=> $filter_options
		)
	);
}

/**
 * Outputs markup for the participation nav.
 * 
 * @package ConfettiBits\Participation
 * @since 2.2.0
 */
function cb_participation_nav() {
	echo cb_participation_get_nav();
}

/**
 * Formats the nav data for the participation component.
 * 
 * We need a pretty complicated set of arguments for 
 * cb_templates_get_nav() and cb_templates_get_nav_items()
 * and this helps us achieve that in a structured way.
 * 
 * @param array $items A collection of key => value pairs
 * 
 * @return array The list of formatted nav data.
 * 
 * @package ConfettiBits\Participation
 * @since 2.2.0
 */
function cb_participation_format_nav_data( $component = '', $items = array() ) {

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
 * Returns the nav for the participation filtering system.
 * 
 * @return string The nav markup.
 * 
 * @package ConfettiBits\Participation
 * @since 2.2.0
 */
function cb_participation_get_nav() {

	$items = cb_participation_format_nav_data(
		'participation',
		array( 
			'New' => 'new',
			'Approved' => 'approved',
			'Denied' => 'denied',
			'All' => 'all',
		)
	);

	return cb_templates_get_nav( 'participation', $items );

}

/**
 * Outputs the participation admin nav.
 * 
 * @package ConfettiBits\Participation
 * @since 2.2.0
 */
function cb_participation_admin_nav() {
	echo cb_participation_admin_get_nav();
}

/**
 * Returns the nav for the participation admin filtering system.
 * 
 * @return string The nav markup.
 * 
 * @package ConfettiBits\Participation
 * @since 2.2.0
 */
function cb_participation_admin_get_nav() {

	$items = cb_participation_format_nav_data(
		'participation_admin',
		array( 
			'New' => 'new',
			'Approved' => 'approved',
			'Denied' => 'denied',
			'All' => 'all',
		)
	);

	return cb_templates_get_nav( 'participation_admin', $items );

}