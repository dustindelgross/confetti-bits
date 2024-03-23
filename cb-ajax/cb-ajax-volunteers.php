<?php
/**
 * These are going to be all of our CRUD functions for volunteer transactions.
 *
 * @package Transactions
 * @subpackage Volunteers
 * @since 3.0.1
 */
// Exit if accessed directly.
defined('ABSPATH') || exit;

function cb_ajax_update_volunteers() {}
function cb_ajax_get_volunteers() {}
function cb_ajax_delete_volunteers() {}

/**
 * Creates a new spot bonus and saves it to the database.
 *
 * @package Transactions
 * @subpackage Volunteers
 * @since 3.0.1
 */
function cb_ajax_new_volunteers() {

	$feedback = ['text' => "", 'type' => 'error'];

	if ( empty( $_POST['recipient_id'] ) ) {
		$feedback['text'] = "Missing recipient data.";
		echo json_encode($feedback);
		die();
	}
	
	if ( empty( $_POST['hours'] ) ) {
		$feedback['text'] = "Missing recipient volunteer data.";
		echo json_encode($feedback);
		die();
	}

	/*
	if ( empty( $_POST['volunteers'] ) ) {
		$feedback['text'] = "Missing volunteer data.";
		echo json_encode($feedback);
		die();
	}
	*/

	if ( empty( $_POST['sender_id'] ) ) {
		$feedback['text'] = "Missing sender data.";
		echo json_encode($feedback);
		die();
	}

	if ( empty( $_POST['event_id'] ) ) {
		$feedback['text'] = "Missing event data.";
		echo json_encode($feedback);
		die();
	}

	if ( empty( $_POST['api_key'] ) ) {
		$feedback['text'] = "Missing Confetti Bits API key. Contact your system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}

	if ( ! cb_core_validate_api_key($_POST['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}


	$sender_id = intval($_POST['sender_id']);
	$event_id = intval($_POST['event_id']);
	$event = new CB_Events_Event($event_id);
	$transaction = new CB_Transactions_Transaction();
	$amount = intval( Confetti_Bits()->volunteer_amount ) * intval($_POST['hours']);
	$recipient_id = intval($_POST['recipient_id']);
	
	$transaction->item_id = $event_id;
	$transaction->secondary_item_id = $recipient_id;
	$transaction->recipient_id = $recipient_id;
	$transaction->sender_id = $sender_id;
	$transaction->log_entry = "{$event->event_title} Volunteer Hours";
	$transaction->component_name = "transactions";
	$transaction->component_action = "cb_transactions_volunteer_bits";
	$transaction->amount = $amount;
	$transaction->date_sent = cb_core_current_date();
	$transaction->event_id = $event_id;
	
	$user_name = cb_core_get_user_display_name($recipient_id);
	$save = $transaction->save();
	
	if ( is_int( $save ) ) {
		$feedback['text'] = "Successfully added {$user_name}'s volunteer hours.";
		$feedback['type'] = "success";
	} else {
		$feedback['text'] = "Failed to log {$user_name}'s volunteer hours.";
	}

	// @TODO: Maybe we'll implement multiple volunteer additions someday.
	/*
	if ( !empty( $_POST['volunteers'] ) ) {
		$feedback['text'] = [];
		foreach ( $_POST['volunteers'] as $volunteer ) {

			$result = ['text' => '', 'type' => 'error'];

			if ( empty( $volunteer['recipient_id'] ) ) {
				$result['text'] = "Missing recipient";
				$feedback['text'][] = $result;
				continue;
			}

			if ( empty( $volunteer['hours'] ) ) {
				$result['text'] = "Missing hours";
				$feedback['text'][] = $result;
				continue;
			}

			$recipient_id = intval($volunteer['recipient_id']);

			$transaction->item_id = $event_id;
			$transaction->secondary_item_id = $recipient_id;
			$transaction->recipient_id = $recipient_id;
			$transaction->sender_id = $sender_id;
			$transaction->log_entry = "{$event->event_title} Volunteer Hours";
			$transaction->amount = $amount;
			$transaction->date_sent = cb_core_current_date();
			$transaction->event_id = $event_id;

			$save = $transaction->save();
			$user_name = cb_core_get_user_display_name($recipient_id);

			if ( is_int( $save ) ) {
				$result['text'] = "Successfully added {$user_name}'s volunteer hours.";
				$result['type'] = "success";
				$feedback['text'][] = $result;
			} else {
				$result['text'] = "Failed to log {$user_name}'s volunteer hours.";
				$feedback['text'][] = $result;
			}
		}
	}
	*/
	echo json_encode($feedback);
	die();

}

/**
 * Updates an existing spot bonus and saves it to the database.
 *
 * @package ConfettiBits\Transactions
 * @subpackage SpotBonuses
 * @since 3.0.0
 *//*
function cb_ajax_update_spot_bonuses() {

	if ( ! cb_is_patch_request() ) {
		return;
	}

	$_PATCH = cb_get_patch_data();

	if (!isset(
		$_PATCH['recipient_id'],
		$_PATCH['sender_id'],
		$_PATCH['spot_bonus_date'],
		$_PATCH['spot_bonus_id']
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

	if ( empty( $_PATCH['spot_bonus_id'] ) ) {
		$feedback['text'] = 'Spot bonus ID required.';
		echo json_encode($feedback);
		die();
	}

	$spot_bonus_id = intval($_PATCH['spot_bonus_id']);
	$recipient_id = intval($_PATCH['recipient_id']);
	$sender_id = intval($_PATCH['sender_id']);
	$timezone = new DateTimeZone('America/New_York');
	$date = new DateTime($_PATCH['spot_bonus_date'], $timezone);
	$spot_bonus_date = $date->format('Y-m-d H:i:s');

	$spot_bonus = new CB_Transactions_Spot_Bonus($spot_bonus_id);

	$update_args = [
		'recipient_id' => $recipient_id,
		'sender_id' => $sender_id,
		'spot_bonus_date' => $spot_bonus_date,
	];

	$where_args = ['id' => $spot_bonus_id];

	$updated = $spot_bonus->update($update_args, $where_args);

	if ($updated) {
		$feedback['text'] = 'Spot bonus updated successfully.';
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = 'Spot bonus failed to update.';
	}

	echo json_encode($feedback);
	die();

}

/**
 * Deletes an existing spot bonus from the database.
 *
 * @package ConfettiBits\Transactions
 * @subpackage SpotBonuses
 * @since 3.0.0
 *//*
function cb_ajax_delete_spot_bonuses()
{

	if ( ! cb_is_delete_request() ) {
		return;
	}

	$_DELETE = cb_get_delete_data();

	if (empty($_DELETE['spot_bonus_id']) || empty( $_DELETE['api_key'])) {
		return;
	}

	$feedback = [ 'text' => "", 'type' => 'error' ];

	if ( !cb_core_validate_api_key( $_DELETE['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}

	$spot_bonus_id = intval($_DELETE['spot_bonus_id']);

	$spot_bonus = new CB_Transactions_Spot_Bonus($spot_bonus_id);

	$deleted = $spot_bonus->delete(['id' => $spot_bonus_id]);

	if ($deleted) {
		$feedback['text'] = 'Spot bonus deleted successfully.';
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = 'Spot bonus failed to delete.';
	}

	echo json_encode($feedback);
	die();

}

/**
 * Retrieves spot bonuses from the database.
 *
 * @package ConfettiBits\Transactions
 * @subpackage SpotBonuses
 * @since 3.0.0
 *//*
function cb_ajax_get_spot_bonuses() {

	if ( !cb_is_get_request() ) {
		return;
	}

	$spot_bonus = new CB_Transactions_Spot_Bonus();
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
			'orderby' => ['column' => 'id','order' => 'DESC']
		];
	}

	if ( !empty($_GET['page'] ) ) {
		$get_args['pagination']['page'] = intval($_GET['page']);
	}

	if ( !empty( $_GET['per_page'] ) ) {
		$get_args['pagination']['per_page'] = intval($_GET['per_page']);
	}

	if ( !empty( $_GET['spot_bonus_id'] ) ) {
		$get_args['where']['id'] = intval( $_GET['spot_bonus_id'] );
	}

	if ( !empty( $_GET['date_query'] ) ) {
		$get_args['where']['date_query'] = $_GET['date_query'];
	}

	if ( !empty( $_GET['sender_id'] ) ) {
		$get_args['where']['sender_id'] = intval( $_GET['sender_id']);
	}

	if ( !empty( $_GET['recipient_id'] ) ) {
		$get_args['where']['recipient_id'] = intval( $_GET['recipient_id']);
	}

	if ( !empty( $_GET['or'] ) ) {
		$get_args['where']['or'] = true;
	}

	$get = $spot_bonus->get_spot_bonuses($get_args);
	$feedback['text'] = $get;
	echo json_encode($feedback);
	die();

	if ( $get ) {
		$feedback['text'] = json_encode($get);
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = false;
	}

	echo json_encode($feedback);
	die();

}*/