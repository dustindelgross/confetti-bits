<?php 
// Exit if accessed directly.
defined('ABSPATH') || exit;

/** 
 * Handles HTTP PATCH requests to update requests entries.
 * 
 * Processes standard and bulk requests updates from an 
 * HTTP PATCH request.
 * 
 * @see cb_get_patch_data() for more info on how we handle PATCH 
 * requests.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_ajax_update_requests() {

	if ( !cb_is_patch_request() ) {
		return;
	}

	$_PATCH = cb_get_patch_data();
	$feedback = ['text' => '','type' => 'error'];

	if ( !isset( 
		$_PATCH['request_id'],
		$_PATCH['api_key'],
	) ) {
		$feedback['text'] = "Missing API key or request not found.";
		echo json_encode($feedback);
		die();
	}

	if ( !cb_core_validate_api_key( $_PATCH['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to renew the API key.";
		echo json_encode($feedback);
		die();
	}

	$request_id = intval($_PATCH['request_id']);
	$request = new CB_Requests_Request($request_id);
	$modified = cb_core_current_date();
	$item_name = cb_requests_get_item_name( $request->request_item_id );
	$admin_id = 0;
	$request_item_id = 0;
	$status = '';
	$amount = 0;

	$update_args = [
		'date_modified' => $modified,
	];

	$where_args = ['id' => $request_id];

	if ( !empty( $_PATCH['admin_id'] ) ) {

		$admin_id = intval($_PATCH['admin_id']);
		$is_admin = cb_core_admin_is_user_site_admin($admin_id);

		if ( $admin_id == $request->applicant_id && !$is_admin ) {
			$feedback['text'] = "Whaddya mean I can't approve my own requests? I protest!";
			echo json_encode($feedback);
			die();
		}

		$update_args['admin_id'] = $admin_id;
		$update_args['secondary_item_id'] = $admin_id;

	}

	if ( !empty($_PATCH['status'] ) ) {

		$status = strtolower($_PATCH['status']);
		
		if ( ! in_array( $status, ['complete', 'in_progress', 'inactive'] ) && !$is_admin ) {
			$feedback['text'] = "Invalid status. Please try again with a valid status applied to the request update.";
			echo json_encode($feedback);
			die();
		}

		if ( $status === $request->status ) {
			$feedback['text'] = "Update unsuccessful. Status already marked as {$status}.";
			echo json_encode($feedback);
			die();
		}

		$update_args['status'] = $status;
		$update_args['component_action'] = 'cb_requests_status_update';
		$amount = cb_requests_get_amount( $request->request_item_id );

	}

	if ( !empty( $_PATCH['request_item_id'] ) ) {
		$request_item_id = intval($_PATCH['request_item_id']);
		$item_name = cb_requests_get_item_name($request_item_id);
		$update_args['request_item_id'] = $request_item_id;
	}

	if ( $amount !== 0 && $status === 'complete' ) {

		$new_transaction = cb_requests_new_transaction([
			'request_id'	=> $request_id,
			'admin_id'		=> $admin_id,
			'amount'		=> $amount,
		]);

		if ( $new_transaction['type'] === 'success' ) {
			$update_args['transaction_id'] = intval( $new_transaction['text'] );
			$feedback['text'] = "Update successful. Transaction ID: {$new_transaction['text']}";
			$feedback['type'] = "success";
			$request->update($update_args, $where_args);
			echo json_encode($feedback);
			die();
		} else {
			$feedback['text'] = "Update failed. Processing error 3050. Error processing Confetti Bits transaction. {$new_transaction['text']}";
			echo json_encode($feedback);
			die();
		}

	}
	
	$request->update($update_args, $where_args);
	$user_name = cb_core_get_user_display_name( $request->applicant_id );
	$feedback['text'] = "{$user_name}'s request for \"{$item_name}\" has successfully been updated.";
	$feedback['type'] = "info";

	echo json_encode($feedback);
	die();

}

/**
 * CB Ajax New Requests
 * 
 * We'll use this to process the new requests entries sent via ajax.
 * 
 * @package ConfettiBits\Requests
 * @since 2.2.0
 */
function cb_ajax_new_requests() {

	if ( ! cb_is_post_request() ) {
		return;
	}

	$feedback = ['type' => 'error', 'text' => ''];

	if ( !isset( 
		$_POST['applicant_id'],
		$_POST['request_item_id'],
		$_POST['api_key'],
	)) {
		$feedback['text'] = "Failed to authenticate request. Missing one of the following: applicant ID, request item, or API key.";
		echo json_encode($feedback);
		die();
	}

	if ( !cb_core_validate_api_key( $_POST['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to renew the API key.";
		echo json_encode($feedback);
		die();
	}

	$applicant_id = intval( $_POST['applicant_id'] );
	$request_item_id = intval( $_POST['request_item_id'] );

	$send = cb_requests_new_request([
		'applicant_id'		=> $applicant_id,
		'admin_id'			=> 0,
		'date_created'		=> cb_core_current_date(),
		'date_modified'		=> cb_core_current_date(),
		'component_name'	=> 'confetti_bits',
		'component_action'	=> 'cb_requests_new',
		'status'			=> 'new',
		'request_item_id'	=> $request_item_id
	]);

	if ( false === is_int( $send ) ) {
		$feedback['text'] = "Failed to process new request.";
		echo json_encode($feedback);
		die();
	} else {
		$feedback['type'] = 'success';
		$feedback['text'] = 'Your request has been successfully submitted. You should receive a notification when it has been processed.';
		echo json_encode($feedback);
		die();
	}
}

/**
 * CB AJAX Get Requests
 * 
 * Our REST API handler for the endpoint at 
 * "/wp-json/cb-ajax/v1/requests/get"
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_ajax_get_requests() {

	if ( !cb_is_get_request() ) {
		return;
	}

	$request = new CB_Requests_Request();
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

	if ( !empty( $_GET['status'] ) ) {
		$get_args['where']['status'] = trim( $_GET['status'] );
	}

	if ( !empty($_GET['request_item_id'] ) ) {
		$get_args['where']['request_item_id'] = intval( $_GET['request_item_id'] );
	}

	if ( !empty( $_GET['orderby'] ) ) {
		$get_args['orderby']['column'] = !empty($_GET['orderby']['column'] ) ? trim($_GET['orderby']['column']) : 'id';
		$get_args['orderby']['order'] = !empty($_GET['orderby']['order'] ) ? trim($_GET['orderby']['order']) : 'DESC';
	}


	$results = $request->get_requests($get_args);

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
 * Deletes requests from the requests table.
 * 
 * @param array $items { 
 *     An array of arguments.
 * 
 *     @type int|array $request_id A comma-separated list (or array)
 * 					   of IDs for the request(s) to remove. Required.
 * 
 * }
 * 
 * @return int The number of rows affected, or false on failure.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_ajax_delete_requests() {

	if ( !cb_is_delete_request() ) {
		return;
	}

	$_DELETE = cb_get_delete_data();
	$feedback = ['type' => 'error', 'text' => ''];

	if ( !cb_core_validate_api_key( $_DELETE['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to renew the API key.";
		echo json_encode($feedback);
		die();
	}

	if ( empty($_DELETE['request_id'] ) ) {
		$feedback['text'] = "Invalid or missing id.";
		echo json_encode($feedback);
		die();
	}

	$request_id = intval( $_DELETE['request_id'] );

	$request = new CB_Requests_Request($request_id);
	$delete = $request->delete(['id' => $request_id]);
	
	if ( is_int( $delete ) ) {
		$feedback['type'] = 'success';
		$feedback['text'] = $delete === 1 ? 'Request removed.' : 'Requests removed.';
		echo json_encode($feedback);
		die();
	} else {
		$feedback['text'] = 'Operation failed.';
		echo json_encode($feedback);
		die();
	}

}