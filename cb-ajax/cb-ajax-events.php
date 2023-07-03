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
 * CB AJAX Create Event
 *
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
			$_POST['event_date_start'],
			$_POST['event_date_end'],
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
	$event_date_start = date('Y-m-d H:i:s', strtotime(sanitize_text_field($_POST['event_date_start'])));
	$event_date_end = date('Y-m-d H:i:s', strtotime(sanitize_text_field($_POST['event_date_end'])));

	if (isset($_POST['event_desc'])) {
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

	if (empty($event_date)) {
		$feedback['text'] = 'Event date is required.';
		echo json_encode($feedback);
		die();
	}

	$event = new CB_Events_Event();

	$save = $event->save(
		array(
			'user_id' => $user_id,
			'date_created' => current_time('mysql'),
			'date_modified' => current_time('mysql'),
			'event_title' => $event_title,
			'event_desc' => $event_desc,
			'participation_amount' => $participation_amount,
			'event_date_start' => $event_date_start,
			'event_date_end' => $event_date_end,
		)
	);

	if (false === is_int($save)) {
		$feedback['text'] = 'Event failed to save.';
	} else {
		$feedback['text'] = 'Event saved successfully.';
		$feedback['type'] = 'success';
	}

	echo json_encode($feedback);
	die();

}
add_action('wp_ajax_cb_events_new_event', 'cb_ajax_new_event');

/**
 * Updates an existing event object and saves it to the database.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_ajax_update_events()
{
	if (!isset(
		$_POST['user_id'],
		$_POST['event_id'],
		$_POST['event_title'],
		$_POST['participation_amount'],
		$_POST['event_date_start'],
		$_POST['event_date_end']
	)) {
		return;
	}

	$feedback = array(
		'text' => "",
		'type' => 'error'
	);

	$user_id = intval($_POST['user_id']);
	$event_id = intval($_POST['event_id']);
	$event_title = sanitize_text_field($_POST['event_title']);
	$participation_amount = intval($_POST['participation_amount']);
	$event_date_start = date('Y-m-d H:i:s', strtotime(sanitize_text_field($_POST['event_date_start'])));
	$event_date_end = date('Y-m-d H:i:s', strtotime(sanitize_text_field($_POST['event_date_end'])));

	$event = new CB_Events_Event($event_id);

	$update_args = array(
		'user_id' => $user_id,
		'date_modified' => current_time('mysql'),
		'event_title' => $event_title,
		'participation_amount' => $participation_amount,
		'event_date_start' => $event_date_start,
		'event_date_end' => $event_date_end
	);

	$where_args = array(
		'id' => $event_id
	);

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
add_action('wp_ajax_cb_events_update_event', 'cb_ajax_update_event');

/**
 * Deletes an existing event object from the database.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_ajax_delete_events()
{

	if (!isset($_POST['event_id'])) {
		return;
	}

	$feedback = array(
		'text' => "",
		'type' => 'error'
	);

	$event_id = intval($_POST['event_id']);

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
function cb_ajax_get_events()
{

	if (!isset($_GET['event_id'])) {
		return;
	}

	$event_id = intval($_GET['event_id']);

	$event = new CB_Events_Event($event_id);

	$feedback = ['text' => "", 'type' => 'error'];

	if (!empty($event->id)) {
		$feedback['text'] = json_encode($event);
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = 'Could not find event.';
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