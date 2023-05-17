<?php
/**
 * Confetti Bits Events Functions
 *
 * These are going to be all of our CRUD functions for
 * the events component.
 */
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * CB AJAX Create Event
 *
 * Creates a new event object and saves it to the database.
 */
function cb_ajax_create_event()
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

	$event = new Confetti_Bits_Events_Event();

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
 * CB AJAX Update Event
 *
 * Updates an existing event object and saves it to the database.
 */
function cb_ajax_update_event()
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

	$event = new Confetti_Bits_Events_Event($event_id);

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
 * CB AJAX Delete Event
 *
 * Deletes an existing event object from the database.
 */
function cb_ajax_delete_event()
{

	if (!isset($_POST['event_id'])) {
		return;
	}

	$feedback = array(
		'text' => "",
		'type' => 'error'
	);

	$event_id = intval($_POST['event_id']);

	$event = new Confetti_Bits_Events_Event($event_id);

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
add_action('wp_ajax_cb_events_delete_event', 'cb_ajax_delete_event');

/**
 * CB AJAX Get Paged Events
 *
 * Retrieves a paginated list of events.
 */
function cb_ajax_get_paged_events()
{

	if (!isset($_GET['page'])) {
		return;
	}

	$page = intval($_GET['page']);
	$count = isset($_GET['count']) ? true : false;
	$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;

	$event = new Confetti_Bits_Events_Event();
	$feedback = array(
		'text' => "",
		'type' => 'error'
	);

	$pagination = array();
	$select = $count ? 'count(id) as total_count' : '*';

	$where = array(
		'date_query' => array(
			'column' => 'event_date',
			'compare' => 'BETWEEN',
			'before' => date('Y-m-d', strtotime("last day of this month")),
			'after' => date('Y-m-d', strtotime("first day of last month")),
			'inclusive' => true
		)
	);

	if (!empty($_GET['applicant_id'])) {
		$where['user_id'] = intval($_GET['user_id']);
	}

	$pagination['page'] = $page;
	$pagination['per_page'] = $per_page;

	$paged_events = $event->get_event(
		array(
			'select' => $select,
			'where' => $where,
			'orderby' => 'date_modified DESC',
			'pagination' => $pagination
		)
	);

	if (!empty($paged_events)) {
		$feedback['text'] = json_encode($paged_events);
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = 'Could not find any events.';
	}

	echo json_encode($feedback);
	die();

}
add_action('wp_ajax_cb_events_get_paged_events', 'cb_ajax_get_paged_events');

/**
 * CB AJAX Get Event
 *
 * Retrieves a single event object.
 */
function cb_ajax_get_event()
{

	if (!isset($_GET['event_id'])) {
		return;
	}

	$event_id = intval($_GET['event_id']);

	$event = new Confetti_Bits_Events_Event($event_id);

	$feedback = array(
		'text' => "",
		'type' => 'error'
	);

	if (!empty($event->id)) {
		$feedback['text'] = json_encode($event);
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = 'Could not find event.';
	}

	echo json_encode($feedback);
	die();

}
add_action('wp_ajax_cb_events_get_event', 'cb_ajax_get_event');

/**
 * CB AJAX Get Event Participants
 *
 * Retrieves a list of participants for a given event.
 */
function cb_ajax_get_event_participants()
{

	if (!isset($_GET['event_id'])) {
		return;
	}

	$participation = new Confetti_Bits_Participation_Participation();

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
add_action('wp_ajax_cb_events_get_event_participants', 'cb_ajax_get_event_participants');

/**
 * CB AJAX New Contest
 *
 * Saves contest placements for a given event.
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
		$contest = new Confetti_Bits_Events_Contest();
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
add_action('wp_ajax_cb_events_new_contest', 'cb_ajax_new_contest');


function cb_events_new_notifications($data = array())
{

	$r = wp_parse_args(
		$data,
		array(
			'applicant_id' => 0,
			'admin_id' => 0,
			'component_action' => '',
			'event_note' => '',
			'status' => ''
		)
	);

	if (
		empty($data) ||
		empty($r['applicant_id']) ||
		empty($r['admin_id']) ||
		empty($r['status']) ||
		empty($r['component_action'])
	) {
		return;
	}

	$item_id = 0;
	$secondary_item_id = 0;

	switch ($r['component_action']) {

		case ('cb_participation_new'):
			$item_id = $r['admin_id'];
			$secondary_item_id = $r['applicant_id'];

			$unsubscribe_args = array(
				'user_id' => $item_id,
				'notification_type' => 'cb-participation-new',
			);

			$email_args = array(
				'tokens' => array(
					'applicant.name' => bp_core_get_user_displayname($secondary_item_id),
					'participation.note' => $r['event_note'],
					'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
				),
			);

			bp_send_email('cb-participation-new', $item_id, $email_args);
			break;

	}

	bp_notifications_add_notification(
		array(
			'user_id' => $item_id,
			'item_id' => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'component_name' => 'confetti_bits',
			'component_action' => $r['component_action'],
			'date_notified' => current_time('mysql', true),
			'is_new' => 1,
			'allow_duplicate' => true,
		)
	);

}
// add_action('cb_participation_after_save', 'cb_participation_new_notifications');


function cb_events_update_notifications($data = array())
{

	$r = wp_parse_args(
		$data,
		array(
			'applicant_id' => 0,
			'admin_id' => 0,
			'component_action' => '',
			'event_note' => '',
			'status' => '',
			'event_type' => ''
		)
	);

	if (
		empty($data) ||
		empty($r['applicant_id']) ||
		empty($r['admin_id']) ||
		empty($r['status']) ||
		empty($r['component_action'])
	) {
		return;
	}

	$item_id = 0;
	$secondary_item_id = 0;
	$event_type = ucwords(str_replace('_', ' ', $r['event_type']));
	$event_note = '';

	if (!empty($r['event_note'])) {
		$event_note = ucwords(str_replace('_', ' ', $r['event_note']));
	} else {
		$event_note = $event_type;
	}

	switch ($r['component_action']) {

		case ('cb_participation_status_update'):

			$item_id = $r['applicant_id'];
			$secondary_item_id = $r['admin_id'];


			/*
			if ( $r['status'] === 'denied' ) {
			$unsubscribe_args = array(
			'user_id'           => $item_id,
			'notification_type' => 'cb-participation-status-denied',
			);
			$email_args = array(
			'tokens' => array(
			'admin.name' => bp_core_get_user_displayname( $secondary_item_id ),
			'participation.status' => ucfirst( $r['status'] ),
			'participation.type' => $event_type,
			'participation.note' => $event_note,
			'unsubscribe' => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
			),
			);
			bp_send_email( 'cb-participation-status-denied', (int) $item_id, $email_args );
			*/
			//			} else {

			$unsubscribe_args = array(
				'user_id' => $item_id,
				'notification_type' => 'cb-participation-status-update',
			);

			$email_args = array(
				'tokens' => array(
					'admin.name' => bp_core_get_user_displayname($secondary_item_id),
					'participation.status' => ucfirst($r['status']),
					'participation.note' => $event_note,
					'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
				),
			);

			bp_send_email('cb-participation-status-update', (int) $item_id, $email_args);

			//			}

			break;
	}

	bp_notifications_add_notification(
		array(
			'user_id' => $item_id,
			'item_id' => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'component_name' => 'confetti_bits',
			'component_action' => $r['component_action'],
			'date_notified' => current_time('mysql', true),
			'is_new' => 1,
			'allow_duplicate' => false,
		)
	);

}
// add_action('cb_participation_after_update', 'cb_participation_update_notifications');