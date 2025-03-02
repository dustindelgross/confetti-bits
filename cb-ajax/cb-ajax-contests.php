<?php
/**
 * All of our CRUD functions for contests.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Accepts either a singular contest object or an array of contest objects
 * and adds them to the database.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_ajax_new_contests()
{

	if ( ! cb_is_post_request() ) {
		return;
	}


	$feedback = [ 'text' => "", 'type' => 'error' ];
	// We'll set this flag later if something goes wrong in the actual data manipulation part.
	$error = false;

	if ( !isset( $_POST['contests'] ) ) {
		$feedback['text'] = "Missing or invalid contest data.";
		echo json_encode($feedback);
		die();
	}

	if ( empty( $_POST['event_id'] ) ) {
		$feedback['text'] = "Missing or invalid event data.";
		echo json_encode($feedback);
		die();
	}

	if ( ! isset( $_POST['api_key'] ) ) {
		$feedback['text'] = "Missing or invalid Confetti Bits API Key.";
		echo json_encode($feedback);
		die();
	}

	if ( ! cb_core_validate_api_key( $_POST['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API Key. Contact your system administrator to generate a new API Key.";
		echo json_encode($feedback);
		die();
	}

	if ( ! is_array( $_POST['contests'] ) ) {
		$feedback['text'] = sprintf('Invalid data type for key "contests" - expected Array but got "%s"', gettype($_POST['contests'] ) );
		echo json_encode($feedback);
		die();
	}

	$event_id = intval($_POST['event_id']);
	$event = new CB_Events_Event($event_id);

	if ( !$event->exists() ) {
		$feedback['text'] = "No event found with the given ID";
		echo json_encode($feedback);
		die();
	}

	$contest_obj = new CB_Events_Contest();
	$feedback['text'] = [];

	// We do a clean sweep of existing placements.
	// Maybe it ain't pretty, but you can't say it doesn't simplify things.
	$contest_obj->delete(['event_id' => $event_id ]);

	foreach ( $_POST['contests'] as $contest ) {

		$response = ['type' => 'error', 'text' => ''];

		if ( empty($contest['placement'] ) ) {
			$response['text'] = 'Missing or invalid value for key "placement".';
			array_push($feedback['text'], $response);
			continue;
		}
		if ( empty( $contest['amount'] ) ) {
			$response['text'] = 'Missing or invalid value for key "amount".';
			array_push( $feedback['text'], $response);
			continue;
		}

		$contest_obj->event_id = $event_id;
		$contest_obj->placement = intval($contest['placement']);
		$contest_obj->amount = intval($contest['amount']);

		$save = $contest_obj->save();

		if ( is_int($save) === false ) {
			// Set the flag so we can give an accurate response for the overall .'~*experience*~'.
			$error = true;
			array_push( $feedback['text'], ['type' => 'error', 'response' => 'Contest item failed to save to the database.']);
			continue;
		} else {
			array_push( $feedback['text'], ['type' => 'success', 'response' => "Successfully saved contest item with ID {$save}"]);
		}
	}

	$feedback['type'] = $error ? 'error' : 'success';
	echo json_encode($feedback);
	die();

}

/*
	$input_data_from_admin_user = [
		['placement' => 1, 'amount' => 20],
		['placement' => 2, 'amount' => 15],
		['placement' => 5, 'amount' => 5]
	];
	*/

/**
 * Updates contest objects for a given event.
 * 
 * I'm a goofy goober. I ended up making this a lot more 
 * dynamic so it can handle a lot of different user interactions.
 * 
 * Sit back and enjoy, friend.
 * 
 * It's important to note that we aren't necessarily always modifying a 
 * singular contest object within the database. Here's a breakdown of 
 * what's going on here:
 * 
 * ##### Arguments
 * - An event_id
 * - An array of contest objects, with 'placement' and 'amount' keys
 * - An API key
 * 
 * ##### Data Logistics
 * 1. Validate that we meet all the above constraints.
 * 2. Validate that the event actually exists in the database.
 * 3. Loop through the contests, and within that loop:
 * 		a. Validate that we have both a placement and an amount.
 * 		b. Check to see if that placement exists, and update it if so.
 * 		c. If the placement doesn't exist, we'll create one.
 * 
 * Should be fairly straightforward, but I've been wrong so many times before.
 * ~~We might just run everything through the new_contests endpoint~~
 * ~~We're almost definitely going to be running updates through the new_contests endpoint.~~
 * 
 * We are absolutely **not** running updates through the new_contests endpoint.
 * 
 * @package ConfettiBits\Events
 * @subpackage Contests
 * @since 3.0.0
 */
function cb_ajax_update_contests() {

	if ( ! cb_is_patch_request() ) {
		return;
	}

	$_PATCH = cb_get_patch_data();

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

	if ( !empty( $_PATCH['id'] ) ) {

		$contest_id = intval($_PATCH['id']);

		if ( empty($_PATCH['recipient_id']) ) {
			$feedback['text'] = "Failed to update contest entry. Missing recipient ID";
			echo json_encode($feedback);
			die();
		} else {
			$recipient_id = intval($_PATCH['recipient_id']);
		}

		$contest_obj = new CB_Events_Contest();
		$update = $contest_obj->update(['recipient_id' => $recipient_id], ['id' => $contest_id]);

		if ( $update ) {
			$feedback['text'] = "You should be all set. Congrats!";
			$feedback['type'] = 'success';
			echo json_encode($feedback);
			die();
		} else {
			$feedback['text'] = "Failed to process contest update.";
			echo json_encode($feedback);
			die();
		}
	}

	if ( empty( $_PATCH['event_id'] ) ) {
		$feedback['text'] = "Missing or invalid event ID.";
		echo json_encode($feedback);
		die();
	}

	if ( empty($_PATCH['contests'] ) ) {
		$feedback['text'] = "Missing or invalid contest data.";
		echo json_encode($feedback);
		die();
	}

	if ( ! is_array( $_PATCH['contests'] ) ) {
		$feedback['text'] = sprintf('Invalid data type for key "contests" - expected Array but got "%s"', gettype($_PATCH['contests'] ) );
		echo json_encode($feedback);
		die();
	}

	$event_id = intval($_PATCH['event_id']);
	$events_obj = new CB_Events_Event($event_id);

	if ( !$events_obj->exists() ) {
		$feedback['text'] = "Event object not found.";
		echo json_encode($feedback);
		die();
	}

	$contest_obj = new CB_Events_Contest();
	$contest_obj->event_id = $event_id;
	$feedback['text'] = [];
	$existing_entries_map = [];
	$input_entries_map = [];
	$entries_to_update = [];
	$entries_to_remove = [];
	$entries_to_add = [];

	$existing_entries_get_args = [
		'where' => [
			'event_id' => $event_id
		]
	];
	$existing_entries = $contest_obj->get_contests($existing_entries_get_args);

	foreach ($existing_entries as $entry) {
		$existing_entries_map[$entry['placement']] = $entry;
	}
	
	foreach ( $_PATCH['contests'] as $entry ) {
		$input_entries_map[$entry['placement']] = $entry;
	}

	foreach ($_PATCH['contests'] as $contest) {
		$placement = $contest['placement'];
		$amount = $contest['amount'];

		if (isset($existing_entries_map[$placement])) {
			// Entry already exists, check if it needs an update
			$existing_entry = $existing_entries_map[$placement];
			if ($amount != $existing_entry['amount']) {
				$existing_entry['amount'] = $amount;
				$entries_to_update[] = ['update' => $existing_entry, 'where' => [
					'event_id' => $event_id,
					'placement' => $placement
				]];
			}
		} else {
			$entries_to_add[] = [
				'placement' => $placement,
				'amount' => $amount,
			];
		}
		
	}

	$response = ['type' => 'error', 'text' => ''];
	
	// Identify entries to remove
	foreach ($existing_entries as $existing_entry) {
		$placement = $existing_entry['placement'];
		if (!isset($input_entries_map[$placement])) {
			$entries_to_remove[] = $existing_entry;
		}
	}

	foreach ( $entries_to_add as $add ) {

		$pretty_placement = cb_core_ordinal_suffix($add['placement']);
		$contest_obj->placement = intval($add['placement']);
		$contest_obj->amount = intval($add['amount']);
		$added = $contest_obj->save();

		if ( is_int( $added ) ) {
			$response['type'] = "success";
			$response['text'] = "Successfully added {$pretty_placement} place.";
		} else {
			$response['type'] = "error";
			$response['text'] = "Failed to add {$pretty_placement} place.";
		}

		$feedback['text'][] = $response;

	}

	foreach ( $entries_to_update as $update ) {

		$pretty_placement = cb_core_ordinal_suffix($update['update']['placement']);
		$updated = $contest_obj->update($update['update'], $update['where']);
		if ( is_int( $updated ) ) {
			$response['type'] = "success";
			$response['text'] = "Successfully updated {$pretty_placement} place.";
		} else {
			$response['type'] = "error";
			$response['text'] = "Failed to update {$pretty_placement} place.";
		}

		$feedback['text'][] = $response;

	}

	foreach ( $entries_to_remove as $remove ) {

		$pretty_placement = cb_core_ordinal_suffix($remove['placement']);
		$response['text'] = $entries_to_remove;
		$deleted = $contest_obj->delete($remove);
		if ( is_int( $deleted ) ) {
			$response['type'] = "success";
			$response['text'] = "Successfully removed {$pretty_placement} place.";
		} else {
			$response['type'] = "error";
			$response['text'] = "Failed to remove {$pretty_placement} place.";
		}

		$feedback['text'][] = $response;

	}

	$feedback['type'] = "info";


	echo json_encode($feedback);
	die();

}

/**
 * Deletes existing contest objects from the database.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_ajax_delete_contests()
{

	if ( ! cb_is_delete_request() ) {
		return;
	}

	$_DELETE = cb_get_delete_data();

	$feedback = [ 'text' => "", 'type' => 'error' ];
	$delete_args = [];

	if ( empty( $_DELETE['api_key']) ) {
		$feedback['text'] = "Missing or invalid Confetti Bits API key. Contact your system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}

	if ( !cb_core_validate_api_key( $_DELETE['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}

	if ( !empty( $_DELETE['id'] ) ) {
		$delete_args['id'] = intval($_DELETE['id']);
	}

	if ( !empty( $_DELETE['event_id'] ) ) {
		$delete_args['event_id'] = intval($_DELETE['event_id']);
	}

	$contest = new CB_Events_Contest();

	$deleted = $contest->delete($delete_args);

	if ($deleted) {
		$feedback['text'] = 'Successfully removed contest item(s).';
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = 'Deletion failed.';
	}

	echo json_encode($feedback);
	die();

}

/**
 * Retrieves contest objects from the database.
 * 
 * Typically used to retrieve a list of placements based on a given event_id.
 *
 * @package ConfettiBits\Events
 * @subpackage Contests
 * @since 3.0.0
 */
function cb_ajax_get_contests() {

	if ( !cb_is_get_request() ) {
		return;
	}

	$contest = new CB_Events_Contest();
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
			'orderby' => ['id' => 'DESC']
		];
	}

	if ( !empty($_GET['id'] ) ) {
		$get_args['where']['id'] = intval( $_GET['id'] );
	}

	if ( !empty( $_GET['event_id'] ) ) {
		$get_args['where']['event_id'] = intval( $_GET['event_id'] );
	}

	if ( !empty( $_GET['recipient_id'] ) ) {
		$get_args['where']['recipient_id'] = intval( $_GET['recipient_id'] );
	}

	if ( !empty( $_GET['amount'] ) ) {
		$get_args['where']['amount'] = intval( $_GET['amount'] );
	}

	if ( !empty( $_GET['placement'] ) ) {
		$get_args['where']['placement'] = intval( $_GET['placement'] );
	}

	if ( !empty( $_GET['or'] ) ) {
		$get_args['where']['or'] = true;
	}

	if ( !empty( $_GET['pagination'] ) ) {
		$get_args['pagination'] = [
			'page' => empty( $_GET['pagination']['page'] ) ? 1 : intval($_GET['pagination']['page']),
			'per_page' => empty( $_GET['pagination']['per_page'] ) ? 10 : intval($_GET['pagination']['per_page']),
		];
	}

	$get = $contest->get_contests($get_args);

	if ( $get ) {
		$feedback['text'] = $get;
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = false;
	}

	echo json_encode($feedback);
	die();

}

/**
 * Saves contest placements for a given event.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0

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

}*/