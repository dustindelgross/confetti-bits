<?php
/**
 * CB Events Functions
 *
 * These are going to be all of our CRUD functions for
 * the events component.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Creates a new event object and saves it to the database.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_ajax_new_events()
{

	if (
		!isset(
			$_POST['user_id'],
			$_POST['event_title'],
			$_POST['participation_amount'],
			$_POST['event_start'],
			$_POST['event_end'],
		)
	) {
		return;
	}

	$feedback = array(
		'text' => "",
		'type' => 'error'
	);

	$user_id = intval($_POST['user_id']);
	$event_title = sanitize_text_field($_POST['event_title']);
	$participation_amount = intval($_POST['participation_amount']);
	$start = new DateTimeImmutable($_POST['event_start']);
	$end = new DateTimeImmutable($_POST['event_end']);
	$event_start = $start->format('Y-m-d H:i:s');
	$event_end = $end->format('Y-m-d H:i:s');

	if (!empty($_POST['event_desc'])) {
		$event_desc = sanitize_text_field($_POST['event_desc']);
	} else {
		$event_desc = '';
	}

	if (empty($user_id)) {
		$feedback['text'] = 'User ID is required.';
		echo json_encode($feedback);
		die();
	}

	if (empty($event_title)) {
		$feedback['text'] = 'Event title is required.';
		echo json_encode($feedback);
		die();
	}

	if (empty($participation_amount)) {
		$feedback['text'] = 'Participation amount is required.';
		echo json_encode($feedback);
		die();
	}

	if (empty($event_start)) {
		$feedback['text'] = 'Event start date is required.';
		echo json_encode($feedback);
		die();
	}

	if (empty($event_end)) {
		$feedback['text'] = 'Event end date is required.';
		echo json_encode($feedback);
		die();
	}

	$event = new CB_Events_Event();
	$event->user_id = $user_id; 
	$event->date_created = cb_core_current_date();
	$event->date_modified = cb_core_current_date();
	$event->event_title = $event_title;
	$event->event_desc = $event_desc;
	$event->event_start = $event_start;
	$event->event_end = $event_end;
	$event->participation_amount = $participation_amount;

	$save = $event->save();

	if (false === is_int($save)) {
		$feedback['text'] = 'Event failed to save.';
	} else {
		$feedback['text'] = 'Event saved successfully.';
		$feedback['type'] = 'success';
	}

	echo json_encode($feedback);
	die();

}

/**
 * Updates an existing event object and saves it to the database.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_ajax_update_events() {

	if ( ! cb_is_patch_request() ) {
		return;
	}

	$_PATCH = cb_get_patch_data();

	if (!isset(
		$_PATCH['user_id'],
		$_PATCH['event_id'],
		$_PATCH['event_title'],
		$_PATCH['participation_amount'],
		$_PATCH['event_start'],
		$_PATCH['event_end'],
	)) {
		return;
	}

	$feedback = [ 'text' => "", 'type' => 'error'];

	if ( empty( $_PATCH['api_key'] ) ) {
		$feedback['text'] = "Missing Confetti Bits API key. Contact your system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}
	
	if ( ! cb_core_validate_api_key($_PATCH['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}
	
	$user_id = intval($_PATCH['user_id']);
	$timezone = new DateTimeZone('America/New_York');
	$event_id = intval($_PATCH['event_id']);
	$event_title = sanitize_text_field($_PATCH['event_title']);
	$event_desc = !empty($_PATCH['event_desc']) ? sanitize_text_field($_PATCH['event_desc']) : '';
	$participation_amount = intval($_PATCH['participation_amount']);
	$start = new DateTime($_PATCH['event_start'], $timezone);
	$end = new DateTime($_PATCH['event_end'], $timezone);
	$event_start = $start->format('Y-m-d H:i:s');
	$event_end = $end->format('Y-m-d H:i:s');

	$event = new CB_Events_Event($event_id);

	$update_args = [
		'user_id' => $user_id,
		'date_modified' => cb_core_current_date(),
		'event_title' => $event_title,
		'participation_amount' => $participation_amount,
		'event_start' => $event_start,
		'event_end' => $event_end
	];

	$where_args = ['id' => $event_id];

	$updated = $event->update($update_args, $where_args);

	if ($updated) {
		$feedback['text'] = 'Event updated successfully.';
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = 'Event failed to update.';
	}

	echo json_encode($feedback);
	die();

}

/**
 * Deletes an existing event object from the database.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_ajax_delete_events()
{

	if ( ! cb_is_delete_request() ) {
		return;
	}
	
	$_DELETE = cb_get_delete_data();
	
	if (empty($_DELETE['event_id']) || empty( $_DELETE['api_key'])) {
		return;
	}

	$feedback = [ 'text' => "", 'type' => 'error' ];
	
	if ( !cb_core_validate_api_key( $_DELETE['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}
	
	$event_id = intval($_DELETE['event_id']);

	$event = new CB_Events_Event($event_id);

	$deleted = $event->delete();

	if ($deleted) {
		$feedback['text'] = 'Event deleted successfully.';
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = 'Event failed to delete.';
	}

	echo json_encode($feedback);
	die();

}

/**
 * Retrieves event objects from the database.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_ajax_get_events() {

	if ( !cb_is_get_request() ) {
		return;
	}

	$event = new CB_Events_Event();
	$get_args = [];
	$feedback = ['text' => "", 'type' => 'error'];

	if ( empty( $_GET['api_key'] ) ) {
		$feedback['text'] = "Missing or invalid Confetti Bits API key. Contact system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}

	if ( cb_core_validate_api_key( $_GET['api_key'] ) === false ) {
		$feedback['text'] = "Missing or invalid Confetti Bits API key. Contact system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}

	if ( !empty($_GET['count'] ) ) {
		$get_args['select'] = 'COUNT(id) AS total_count';
	} else {
		$get_args = [
			'select' => ! empty( $_GET['select'] ) ? trim( $_GET['select'] ) : '*',
			'pagination' => [
				'page' => empty( $_GET['page'] ) ? 1 : intval($_GET['page']),
				'per_page' => empty( $_GET['per_page'] ) ? 10 : intval($_GET['per_page']),
			],
			'orderby' => ['column' => 'id','order' => 'DESC']
		];
	}

	if ( !empty( $_GET['event_id'] ) ) {
		$get_args['where']['id'] = intval( $_GET['event_id'] );
	}

	if ( !empty( $_GET['date_query'] ) ) {
		$get_args['where']['date_query'] = $_GET['date_query'];
	}

	if ( !empty( $_GET['user_id'] ) ) {
		$get_args['where']['user_id'] = intval( $_GET['user_id']);
	}

	if ( !empty( $_GET['participation_amount'] ) ) {
		$get_args['where']['participation_amount'] = intval( $_GET['participation_amount']);
	}

	if ( !empty( $_GET['event_location'] ) ) {
		$get_args['where']['event_location'] = cb_core_sanitize_string($_GET['event_location']);
	}

	if ( !empty( $_GET['or'] ) ) {
		$get_args['where']['or'] = true;
	}

	$get = $event->get_events($get_args);

	if ( $get ) {
		$feedback['text'] = json_encode($get);
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = false;
	}

	echo json_encode($feedback);
	die();

}

/**
 * Retrieves a list of participants for a given event.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_ajax_get_event_participants()
{

	if (!isset($_GET['event_id'])) {
		return;
	}

	$participation = new CB_Participation_Participation();

	$event_id = intval($_GET['event_id']);

	$feedback = array(
		'text' => "",
		'type' => 'error'
	);

	$participants = $participation->get_participation(
		array(
			'select' => 'applicant_id',
			'where' => array(
				'event_id' => $event_id
			)
		)
	);

	if (!empty($participants)) {
		$feedback['text'] = json_encode($participants);
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = 'Could not find participants.';
	}

	echo json_encode($feedback);
	die();

}

/**
 * Saves contest placements for a given event.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_ajax_new_contest()
{

	if (
		!isset(
			$_POST['event_id'],
			$_POST['placements'],
		)
	) {
		return;
	}


	$event_id = intval($_POST['event_id']);
	$placements = json_decode($_POST['placements'], true);
	$date = new DateTime();
	$feedback = array(
		'text' => "",
		'type' => 'error'
	);

	foreach ($placements as $placement => $value) {
		$contest = new CB_Events_Contest();
		$contest->placement = $placement;
		$contest->amount = $value;
		$contest->event_id = $event_id;
		$contest->date_created = $date->format('Y-m-d H:i:s');
		$contest->date_modified = $date->format('Y-m-d H:i:s');
		$id = $contest->save();

		if (is_int($id)) {
			$feedback['text'] = json_encode($contest);
			$feedback['type'] = 'success';
		} else {
			$feedback['text'] = 'Could not save contest.';
		}

		echo json_encode($feedback);

	}

	die();

}