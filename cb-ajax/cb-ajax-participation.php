<?php 
// Exit if accessed directly.
defined('ABSPATH') || exit;

/** 
 * CB AJAX Participation Bulk Update
 * 
 * Processes bulk participation updates from an HTTP PATCH request.
 * @see cb_get_patch_data() for more info on how we handle PATCH 
 * requests.
 * 
 * @package ConfettiBits\Participation
 * @since 2.2.0
 */
function cb_ajax_update_participation() {

	if ( !cb_is_patch_request() ) {
		return;
	}

	$_PATCH = cb_get_patch_data();

	if ( !isset( 
		$_PATCH['admin_id'], 
		$_PATCH['participation_id'],
		$_PATCH['status'],
		$_PATCH['transaction_id']
	) ) {
		return;
	}

	$feedback = ['text' => '','type' => 'error'];
	$participation_id = intval($_PATCH['participation_id']);
	$admin_id = intval($_PATCH['admin_id']);
	$status = strtolower($_PATCH['status']) === 'approved' ? 'approved' : 'denied';
	
	$participation = new CB_Participation_Participation($participation_id);
	
	if ( $participation->event_type === 'other' ) {
		$feedback['text'] = "Update unsuccessful. Cannot process 'Other' category event types using the Confetti Bits API.";
		echo json_encode($feedback);
		die();
	}

	$update_args = array(
		'status' => $status,
		'admin_id' => $admin_id,
		'date_modified' => cb_core_current_date(),
	);

	$where_args = array(
		'id' => $participation_id
	);

	$transaction_id		= intval( $_PATCH['transaction_id'] );
	$admin_log_entry	= sanitize_text_field( $_PATCH['log_entry'] );
	$amount_override	= intval( $_PATCH['amount_override'] );
	$admin_log_entry	= isset( $_PATCH['admin_log_entry'] ) 
		? sanitize_text_field( $_PATCH['admin_log_entry'] )
		: "";
	$amount = 0;
	$log_entry = '';
	$new_transaction = 0;

	if ( $admin_id == $participation->applicant_id && ! cb_is_user_site_admin() ) {
		$feedback['text'] = "Update unsuccessful. Cannot self-approve culture participation.{$admin_id}";
		echo json_encode($feedback);
		die();
	} else if ( $status === $participation->status ) {
		$feedback['text'] = "Update unsuccessful. Status already marked as {$status}.";
		echo json_encode($feedback);
		die();
	} else {

		$amount = cb_participation_get_amount( 
			$transaction_id, 
			$participation->event_type, 
			$participation->status, 
			$status, 
			$amount_override
		);

		$log_entry = cb_participation_get_log_entry( $participation_id, $admin_log_entry );

		// Create a transaction if we can.
		if ( $amount !== 0 && $log_entry !== '' ) {

			$modified = current_time('mysql');
			$new_transaction = cb_participation_new_transaction( 
				array(
					'transaction_id'	=> $transaction_id,
					'participation_id'	=> $participation_id,
					'admin_id'			=> $admin_id,
					'modified'			=> $modified,
					'status'			=> $status,
					'amount'			=> $amount,
					'log_entry'			=> $log_entry
				)
			);

			if ( is_int( $new_transaction ) ) {

				$success = true;
				$feedback['text'] = "Update successful. Transaction ID: {$new_transaction}";
				$feedback['type'] = "success";

			} else {
				$feedback['text'] = "Update failed. Processing error 3050. Error processing Confetti Bits transaction. {$new_transaction}";
				echo json_encode($feedback);
				die();
			}
		}

		cb_participation_update_request_status( 
			$participation_id, 
			$admin_id, 
			$modified, 
			$status, 
			$new_transaction
		);

		echo json_encode($feedback);
		die();
	}

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
		$feedback['text'] = "No events selected. Please select or input the event type you are attempting to register.{$event_type} {$event_note}" . print_r($_POST);
	} else if ( empty( $applicant_id ) )  {
		$feedback['text'] = "Authentication error 1000. Failed to authenticate request.";
	} else {

		$send = cb_participation_new_participation(
			array(
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
			)
		);

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

	if ( ! empty( $_GET['applicant_id'] ) ) {
		$get_args['where']['applicant_id'] = intval( $_GET['applicant_id'] );
	}

	if ( !empty( $_GET['event_type'] ) ) {
		$get_args['where']['event_type'] = trim( $_GET['event_type'] );
	}

	if ( !empty( $_GET['status'] ) ) {
		$get_args['where']['status'] = trim( $_GET['status'] );
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