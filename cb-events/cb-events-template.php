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
 * CB Events Get New Event Module
 * 
 * Gets the containerized markup for the new event module in the 
 * Confetti Bits Events panel.
 * 
 * @return string The formatted markup.
 * 
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_events_get_new_events_module() {

	$content = [
		cb_templates_get_heading('New Event'),
		cb_templates_get_text_input([
			'label' => 'Event Title',
			'name' => 'cb_event_title',
			'required' => true,
		]),
		cb_templates_get_toggle_switch([
			'name'	=> 'cb_recurring_event',
			'label'	=> 'This is a recurring event'
		]),
		cb_templates_get_text_input([
			'label'	=> 'Event Start Date',
			'name'	=> 'cb_event_start_date',
			'required' => true
		]),
		cb_templates_get_text_input([
			'label' => 'Event End Date',
			'name' => 'cb_event_end_date',
			'required' => true,
		]),
		cb_templates_get_text_input([
			'label' => 'Event Description',
			'name' => 'cb_event_description',
			'textarea' => true,
		]),
		cb_templates_get_number_input([
			'label' => 'Participation Amount',
			'name' => 'cb_participation_amount',
			'min' => 1,
			'max' => 20,
			'required' => true,
		]),
	];

	$form = cb_templates_get_form([
		'name' => 'cb_events_new_event_form',
		'autocomplete' => 'off',
		'method' => 'post',
		'output' => implode('',$content),
	]);

	return cb_templates_container([
		'classes' => ['cb-module'],
		'output' => $form
	]);

}

/**
 * Outputs the markup for the new events module.
 * 
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_events_new_events_module() {
	echo cb_events_get_new_events_module();
}