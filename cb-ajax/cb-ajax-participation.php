<?php 
// Exit if accessed directly.
defined('ABSPATH') || exit;

/** 
 * Handles HTTP PATCH requests to update participation entries.
 * 
 * Processes standard and bulk participation updates from an 
 * HTTP PATCH request.
 * 
 * @see cb_get_patch_data() for more info on how we handle PATCH 
 * requests.
 * 
 * @TODO: Add event_id for Events component compatibility
 * 
 * @package ConfettiBits\Participation
 * @since 2.2.0
 */
function cb_ajax_update_participation() {

	if ( !cb_is_patch_request() ) {
		return;
	}

	$_PATCH = cb_get_patch_data();
	$feedback = ['text' => '','type' => 'error'];

	if ( !isset( 
		$_PATCH['admin_id'], 
		$_PATCH['participation_id'],
		$_PATCH['status'],
		$_PATCH['api_key'],
	) ) {
		$feedback['text'] = "Missing API key, admin ID, participation not found, or invalid status.";
		echo json_encode($feedback);
		die();
	}

	if ( !cb_core_validate_api_key( $_PATCH['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to renew the API key.";
		echo json_encode($feedback);
		die();
	}

	$participation_id = intval($_PATCH['participation_id']);
	$admin_id = intval($_PATCH['admin_id']);
	$amount = !empty($_PATCH['amount']) ? intval($_PATCH['amount']) : 0;
	$log_entry = !empty( $_PATCH['log_entry'] ) ? trim( $_PATCH['log_entry'] ) : "";
	$status = strtolower($_PATCH['status']) === 'approved' ? 'approved' : 'denied';
	$modified = cb_core_current_date();

	$participation = new CB_Participation_Participation($participation_id);

	$is_admin = cb_core_admin_is_user_site_admin($admin_id);

	if ( $admin_id == $participation->applicant_id && !$is_admin ) {
		$feedback['text'] = "Whaddya mean I can't approve my own participation? I participated!";
		echo json_encode($feedback);
		die();
	}

	if ( $status === $participation->status ) {
		$feedback['text'] = "Update unsuccessful. Status already marked as {$status}.";
		echo json_encode($feedback);
		die();
	}

	$update_args = [
		'status' => $status,
		'admin_id' => $admin_id,
		'date_modified' => $modified,
		'component_action' => 'cb_participation_status_update',
	];

	$where_args = ['id' => $participation_id];

	// Attempt to extract an amount for a transaction
	$amount = cb_participation_get_amount( 
		$participation_id,
		$status, 
		$amount
	);

	// Can't bulk approve special event types. This will change in the new update.
	if ( $participation->event_type === 'other' || $participation->event_type === 'contest' ) {
		if ( $amount === 0 && ( !empty( $participation->transaction_id ) || $status === 'approved') ) {
			// Essentially, if the amount is 0, and it really shouldn't be, throw an error.
			$feedback['text'] = "Update unsuccessful. 'Other' and 'Contest' event types must have an amount assigned to the participation submission.";
			echo json_encode($feedback);
			die();
		}
	}

	// Attempt to extract a log entry for a transaction
	$log_entry = cb_participation_get_log_entry( $participation_id, $log_entry );

	// Create a transaction if we can.
	if ( $amount !== 0 ) {

		$new_transaction = cb_participation_new_transaction([
			'participation_id'	=> $participation_id,
			'admin_id'			=> $admin_id,
			'modified'			=> $modified,
			'status'			=> $status,
			'amount'			=> $amount,
			'log_entry'			=> $log_entry,
		]);

		if ( $new_transaction['type'] === 'success' ) {
			$update_args['transaction_id'] = intval( $new_transaction['text'] );
			$feedback['text'] = "Update successful. Transaction ID: {$new_transaction['text']}";
			$feedback['type'] = "success";
			$participation->update($update_args, $where_args);
			echo json_encode($feedback);
			die();
		} else {
			$feedback['text'] = "Update failed. Processing error 3050. Error processing Confetti Bits transaction. {$new_transaction['text']}";
			echo json_encode($feedback);
			die();
		}
	} else {

		$participation->update($update_args, $where_args);
		$user_name = cb_core_get_user_display_name( $participation->applicant_id );
		$feedback['text'] = "{$user_name}'s participation submission for \"{$log_entry}\" has been {$status}.";
		$feedback['type'] = "info";
	}

	echo json_encode($feedback);
	die();

}

/**
 * CB Ajax New Participation
 * 
 * We'll use this to process the new participation entries sent via ajax.
 * 
 * @package ConfettiBits\Participation
 * @since 2.2.0
 */
function cb_ajax_new_participation() {

	if ( ! cb_is_post_request() ) {
		return;
	}

	// TODO: Put logic somewhere in here to check for event_id and user_id on transactions table

	$feedback		= ['type' => 'error', 'text' => ''];
	$applicant_id	= intval( $_POST['applicant_id'] );
	$event_type		= sanitize_text_field( $_POST['event_type'] );
	$event_note		= sanitize_text_field( $_POST['event_note'] );
	$date_input		= sanitize_text_field( $_POST['event_date'] );
	$date = new DateTimeImmutable($date_input);
	$event_date		= $date->format( 'Y-m-d H:i:s' );
	//|| ( $event_type === 'other' && empty( $event_note ) )
	if ( empty( $event_type ) ) {
		$feedback['text'] = "No events selected. Please select or input the event type you are attempting to register.";
	} else if ( empty( $applicant_id ) )  {
		$feedback['text'] = "Authentication error 1000. Failed to authenticate request.";
	} else {

		$send = cb_participation_new_participation([
			'item_id'			=> $applicant_id,
			'secondary_item_id'	=> 0,
			'applicant_id'		=> $applicant_id,
			'admin_id'			=> 0,
			'date_created'		=> current_time('mysql'),
			'date_modified'		=> current_time('mysql'),
			'event_type'		=> $event_type,
			'event_date'		=> $event_date,
			'event_note'		=> $event_note,
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_participation_new',
			'status'			=> 'new',
			'transaction_id'	=> 0
		]);

		if ( false === is_int( $send ) ) {
			$feedback['text'] = "Request processing error 2000.";
		} else {
			$feedback['type'] = 'success';
			$feedback['text'] = 'Your participation has been successfully submitted. You should receive a notification when it has been processed.';
		}
	}

	echo json_encode( $feedback );

	die();
}

/**
 * CB AJAX Get Participation
 * 
 * Our REST API handler for the endpoint at 
 * "/wp-json/cb-ajax/v1/participation/get"
 * 
 * @package ConfettiBits\Participation
 * @since 2.3.0
 */
function cb_ajax_get_participation() {

	if ( !cb_is_get_request() ) {
		return;
	}

	$participation = new CB_Participation_Participation();
	$feedback = ['type' => 'error', 'text' => ''];
	$get_args = [];

	// If count is set, get total count instead of paginated entries
	if ( isset($_GET['count'] ) ) {
		$get_args['select'] = 'COUNT(id) AS total_count';
	} else {
		$get_args = [
			'select' => ! empty( $_GET['select'] ) ? trim( $_GET['select'] ) : '*',
			'pagination' => [
				'page' => empty( $_GET['page'] ) ? 1 : intval($_GET['page']),
				'per_page' => empty( $_GET['per_page'] ) ? 6 : intval($_GET['per_page']),
			],
		];
	}

	if ( ! empty( $_GET['id'] ) ) {
		$get_args['where']['id'] = intval( $_GET['id'] );
	}

	if ( ! empty( $_GET['applicant_id'] ) ) {
		$get_args['where']['applicant_id'] = intval( $_GET['applicant_id'] );
	}

	if ( !empty( $_GET['event_type'] ) ) {
		$get_args['where']['event_type'] = trim( $_GET['event_type'] );
	}

	if ( !empty( $_GET['status'] ) ) {
		$get_args['where']['status'] = trim( $_GET['status'] );
	}
	
	if ( ! empty($_GET['date_query'] ) ) {
		$get_args['where']['date_query'] = $_GET['date_query'];
	}
	
	if ( !empty($_GET['page'] ) && !empty($_GET['per_page']) ) {
		$get_args['pagination'] = ['page' => intval($_GET['page']), 'per_page' => intval($_GET['per_page'])];
	}
	
	if ( !empty( $_GET['orderby'] ) ) {
		$get_args['orderby']['column'] = !empty($_GET['orderby']['column'] ) ? trim($_GET['orderby']['column']) : 'id';
		$get_args['orderby']['order'] = !empty($_GET['orderby']['order'] ) ? trim($_GET['orderby']['order']) : 'DESC';
	}

	$results = $participation->get_participation($get_args);

	if ( ! empty( $results ) ) {
		$feedback['type'] = 'success';
		$feedback['text'] = json_encode( $results );
	} else {
		$feedback['text'] = false;
	}

	http_response_code(200);
	echo json_encode($feedback);
	die();

}

/**
 * Adds 5 participation entries for testing purposes.
 * 
 * @package ConfettiBits\Participation
 * @since 2.3.0
 */
function cb_participation_add_filler_data() {

	$entries = [
		[
			'item_id'			=> 5,
			'secondary_item_id'	=> 0,
			'applicant_id'		=> 5,
			'admin_id'			=> 0,
			'date_created'		=> cb_core_current_date(),
			'date_modified'		=> cb_core_current_date(),
			'event_date'		=> cb_core_current_date(),
			'event_type'		=> 'activity',
			'event_note'		=> 'Test 1',
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_participation',
			'status'			=> 'new',
			'transaction_id'	=> 0
		],
		[
			'item_id'			=> 5,
			'secondary_item_id'	=> 0,
			'applicant_id'		=> 5,
			'admin_id'			=> 0,
			'date_created'		=> cb_core_current_date(),
			'date_modified'		=> cb_core_current_date(),
			'event_date'		=> cb_core_current_date(),
			'event_type'		=> 'activity',
			'event_note'		=> 'Test 2',
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_participation',
			'status'			=> 'new',
			'transaction_id'	=> 0
		],
		[
			'item_id'			=> 5,
			'secondary_item_id'	=> 0,
			'applicant_id'		=> 5,
			'admin_id'			=> 0,
			'date_created'		=> cb_core_current_date(),
			'date_modified'		=> cb_core_current_date(),
			'event_date'		=> cb_core_current_date(),
			'event_type'		=> 'activity',
			'event_note'		=> 'Test 3',
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_participation',
			'status'			=> 'new',
			'transaction_id'	=> 0
		],
		[
			'item_id'			=> 5,
			'secondary_item_id'	=> 0,
			'applicant_id'		=> 5,
			'admin_id'			=> 0,
			'date_created'		=> cb_core_current_date(),
			'date_modified'		=> cb_core_current_date(),
			'event_date'		=> cb_core_current_date(),
			'event_type'		=> 'activity',
			'event_note'		=> 'Test 4',
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_participation',
			'status'			=> 'new',
			'transaction_id'	=> 0
		],
		[
			'item_id'			=> 5,
			'secondary_item_id'	=> 0,
			'applicant_id'		=> 5,
			'admin_id'			=> 0,
			'date_created'		=> cb_core_current_date(),
			'date_modified'		=> cb_core_current_date(),
			'event_date'		=> cb_core_current_date(),
			'event_type'		=> 'activity',
			'event_note'		=> 'Test 5',
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_participation',
			'status'			=> 'new',
			'transaction_id'	=> 0
		],
	];
	foreach ( $entries as $entry ) {
		cb_participation_new_participation($entry);	
	}

}
//add_action( 'cb_actions', 'cb_add_a_few_participation' );