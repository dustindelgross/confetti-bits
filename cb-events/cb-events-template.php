<?php 
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CB Events Template
 * 
 * This will house all our template functions for the
 * Events component.
 * 
 * @package ConfettiBits\Events
 * @since 3.0.0
 */

/**
 * Formats markup for the new event module in the 
 * Confetti Bits Events panel.
 * 
 * @return string The formatted markup.
 * 
 * @package ConfettiBits\Events
 * @since 3.0.0

function cb_events_get_new_events_module() {

	$content = [
		'component' => 'events_admin',
		'heading' => 'New Event',
		'inputs' => [
			[ 'type' => 'text', 'args' => [ 'label' => 'Event Title', 'name' => 'event_title', 'required' => true ] ],
			[ 'type' => 'toggle_switch', 'args' => [ 'name' => 'has_contest', 'label' => 'This event has a contest attached', 'container_classes' => [ 'mb-3' ] ] ],
			[ 'type' => 'datetime_local', 'args' => [
				'label' => 'Event Start Date',
				'name' => 'event_start'
			]],
			[ 'type' => 'datetime_local', 'args' => [
				'label' => 'Event End Date',
				'name' => 'event_end'
			]],
			[ 'type' => 'text', 'args' => [ 'label' => 'Event Description', 'name' => 'event_desc', 'textarea' => true ] ],
			[ 'type' => 'number', 'args' => [
				'label' => 'Participation Amount',
				'name' => 'participation_amount',
				'min' => 1,
				'max' => 20,
			] ],
			[  'type' => 'submit', 'args' => [ 'name' => 'submit', 'value' => 'Add' ] ],
		]
	];

	return cb_templates_get_form_module([
		'autocomplete' => 'off',
		'method' => 'post',
		'component' => 'events_admin',
		'output' => $content,
	]);

}
*/
/**
 * Outputs the markup for the new events module.
 * 
 * @package ConfettiBits\Events
 * @since 3.0.0

function cb_events_new_events_module() {
	echo cb_events_get_new_events_module();
}

 */

/**
 * Outputs an AJAX table of events.
 */


/**
 * Gets markup for the calendar header.
 * 
 * @param string $class_prefix The prefix to add to the calendar header.
 * @return string The formatted header markup.
 * 
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_events_get_calendar_header( $class_prefix = '' ) {

	$prev = cb_templates_get_button(['classes' => ["{$class_prefix}prev", 'btn', 'btn-outline-secondary'], 'content' => 'Prev']);
	$month_year = cb_templates_container([
		'container' => 'span',
		'classes' => ["{$class_prefix}month-year"]
	]);
	$next = cb_templates_get_button(['classes' => ["{$class_prefix}next", 'btn', 'btn-outline-secondary'], 'content' => 'Next']);

	return cb_templates_container([
		'classes' => ["{$class_prefix}header"],
		'output' => "{$prev}{$month_year}{$next}"
	]);

}
/**
 * Outputs a calendar view of events.
 * 
 * @return string The formatted markup.
 * 
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_events_get_calendar_view() {

	$content = '';
	$class_prefix = 'cb-events-calendar-';
	$weekdays = [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ];
	$weekday_labels = '';

	foreach ( $weekdays as $weekday ) {
		$weekday_labels .= cb_templates_container([ 'classes' => ["{$class_prefix}weekday"], 'output' => $weekday ]);
	}

	$header = cb_events_get_calendar_header($class_prefix);
	$weekdays = cb_templates_container([
		'classes' => ["{$class_prefix}weekdays"],
		'output' => $weekday_labels
	]);

	$days = cb_templates_container(['classes' => ["{$class_prefix}days"]]);
	$content = "{$header}{$weekdays}{$days}";

	return cb_templates_container([
		'classes' => ['cb-events-calendar'], 
		'output' => $content
	]);



}

/**
 * Outputs the markup for the calendar view.
 * 
 * @package ConfettiBits\Events
 * @subpackage Templates
 * @since 3.0.0
 */
function cb_events_calendar_view() {
	echo cb_events_get_calendar_view();
}

/**
 * Gets the markup for the events list view.
 * 
 * @param bool $is_admin Whether the table is for an admin.
 * 
 * @package ConfettiBits\Events
 * @subpackage Templates
 * @since 3.0.0
 */
function cb_events_get_list_view( $is_admin = false ) {

	$component = $is_admin ? 'events_admin' : 'events';

	return cb_templates_get_ajax_table($component);

}

/**
 * Outputs the markup for the events list view.
 * 
 * @param bool $is_admin Whether the table is for an admin.
 * 
 * @package ConfettiBits\Events
 * @subpackage Templates
 * @since 3.0.0
 */
function cb_events_list_view( $is_admin = false ) {
	echo cb_events_get_list_view($is_admin);
}

/**
 * Adds rewrite rules to the events component so we don't get a false 404 when we update plugins.
 * 
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_events_rewrite_rules() {
	add_rewrite_rule( 'cb-events/([0-9]+)[/]?$', 'index.php?event_id=$matches[1]', 'top' );
}
add_action( 'cb_events_add_rewrite_rules', 'cb_events_rewrite_rules' );

function cb_events_add_query_vars( $query_vars ) {
	$query_vars[] = 'event_id';
	return $query_vars;
}
add_filter( 'query_vars', 'cb_events_add_query_vars' );

function cb_events_template_include( $template ) {
	if ( intval( get_query_var( 'event_id' ) ) == false || get_query_var( 'event_id' ) == '' ) {
		return $template;
	}

	return get_stylesheet_directory() . '/single-cb-events.php';
}
add_filter( 'template_include', 'cb_events_template_include' );

/**
 * Returns markup for a create or update form, determined by the given string.
 * 
 * @param bool $is_edit_form Whether the form is for editing event data.
 * @return string The form markup.
 * 
 * @package Events\Templates
 * @since 3.0.0
 */
function cb_events_get_form_module( $is_edit_form = false ) {

	$today = new DateTime();
	$component = $is_edit_form ? 'events_admin_edit_event' : 'events_admin';
	$inputs = [
		['type' => 'text', 'args' => ['name' => 'event_title', 'label' => 'Event Title', 'classes' => ['cb-form-line', 'form-control']]],
		['type' => 'text', 'args' => ['name' => 'event_desc', 'label' => 'Event Description', 'textarea' => true, 'classes' => ['form-control']]],
	];

	if ( !$is_edit_form ) {
		$inputs[] = [ 'type' => 'toggle_switch', 'args' => [ 'name' => 'has_contest', 'label' => 'This event has a contest attached', 'container_classes' => [ 'mb-3' ] ] ];
	}

	$inputs = array_merge($inputs, [
		['type' => 'date', 'args' => [
			'component' => "{$component}_event_start",
			'label' => 'Start Date', 
			'placeholder' => $today->format('m/d/Y')
		]],
		['type' => 'time_selector', 'args' => [
			'component' => "{$component}_event_start",
			'name' => 'start_time',
			'label' => 'Start Time'
		]],
		['type' => 'date', 'args' => [
			'component' => "{$component}_event_end",
			'label' => 'End Date', 
			'placeholder' => $today->format('m/d/Y')
		]],
		['type' => 'time_selector', 'args' => [
			'component' => "{$component}_event_end",
			'name' => 'end_time',
			'label' => 'End Time'
		]],
		['type' => 'number', 'args' => ['name' => 'event_participation_amount', 'label' => 'Participation Amount']],
		['type' => 'container', 'args' => [
			'name' => 'action_buttons',
			'classes' => ['gap-3', 'modal-footer'],
			'output' => cb_templates_get_form_button([
				'name' => "cb_{$component}_submit", 
				'type' => 'submit',
				'value' => 'Done', 
				'classes' => ['btn', 'btn-outline-primary', 'rounded'],
				'custom_attrs' => ['data-bs-dismiss' => 'modal'],
			])
			. cb_templates_get_form_button([
				'name' => "cb_{$component}_cancel",
				'value' => 'Cancel', 
				'custom_attrs' => ['data-bs-dismiss' => 'modal'],
				'classes' => ['btn', 'btn-outline-secondary'],
			])
		]]
	]);

	$form_module_args = [
		'component' => $component,
		'method' => 'POST',
		'container_classes' => ['modal'],
		'classes' => ['modal-dialog', 'd-flex', 'flex-column'],
		'output' => [
			'modal' => true,
			'component' => $component,
			'heading' => "Schedule Event",
			'inputs' => $inputs
		]
	];

	return cb_templates_get_form_module($form_module_args);
}

/**
 * Gets the markup for the new events form module.
 * 
 * @package Events\Templates
 * @since 3.0.0
 */
function cb_events_get_new_event_module() {
	return cb_events_get_form_module();
}

/**
 * Outputs the markup for the new events module.
 * 
 * @package Events\Templates
 * @since 3.0.0
 */
function cb_events_new_event_module() {
	echo '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cb_events_admin_form_container">
  Schedule Event
</button>';
	echo cb_events_get_new_event_module();
}
add_action('cb_events_admin_template', 'cb_events_new_event_module', 1);

/**
 * Returns markup for the edit_event form module.
 * 
 * @package Events\Templates
 * @since 3.0.0
 */
function cb_events_get_edit_event_module() {
	return cb_events_get_form_module(true);
}

/**
 * Outputs markup for the edit_events module.
 * 
 * @package Events\Templates
 * @since 3.0.0
 */
function cb_events_edit_event_module() {
	echo cb_events_get_edit_event_module();	
}
add_action('cb_events_admin_template', 'cb_events_edit_event_module');


/**
 * Adds a table for spot bonuses to the transactions tab.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 3.0.0
 */
function cb_events_admin_table_module() {
	if ( cb_is_user_events_admin() ) {
		echo cb_templates_get_table('events_admin', 'Events');
	}
}
add_action('cb_events_admin_template', 'cb_events_admin_table_module', 2);