<?php 
// Exit if accessed directly.
defined('ABSPATH') || exit;

/** 
 * Handles HTTP PATCH request_items to update request_items entries.
 * 
 * Processes standard and bulk request_items updates from an 
 * HTTP PATCH request.
 * 
 * @see cb_get_patch_data() for more info on how we handle PATCH 
 * request_items.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_ajax_update_request_items() {

	if ( !cb_is_patch_request() ) {
		return;
	}

	$_PATCH = cb_get_patch_data();
	$feedback = ['text' => '','type' => 'error'];

	if ( !isset( 
		$_PATCH['request_item_id'],
		$_PATCH['api_key'],
	) ) {
		$feedback['text'] = "Missing API key or request item ID.";
		echo json_encode($feedback);
		die();
	}

	if ( !cb_core_validate_api_key( $_PATCH['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to renew the API key.";
		echo json_encode($feedback);
		die();
	}

	$request_item_id = intval($_PATCH['request_item_id']);

	$item = new CB_Requests_Request_Item($request_item_id);

	$amount = !empty( $_PATCH['amount'] ) ? intval( $_PATCH['amount'] ) : $item->amount;
	$item_name = !empty( $_PATCH['item_name'] ) ? trim( $_PATCH['item_name'] ): $item->item_name;
	$item_desc = !empty( $_PATCH['item_desc'] ) ? trim( $_PATCH['item_desc'] ): $item->item_desc;
	$modified = cb_core_current_date();

	$update_args = [
		'amount' => $amount,
		'date_modified' => $modified,
		'item_name' => $item_name,
		'item_desc' => $item_desc
	];

	$where_args = ['id' => $request_item_id];
	
	$updated = $item->update($update_args, $where_args);

	if ( ! is_int( $updated ) ) {
		$feedback['text'] = "Failed to update item.";
		echo json_encode($feedback);
		die();
	} else {
		$feedback['text'] = "Successfully updated {$item_name}.";
		$feedback['type'] = "info";
		echo json_encode($feedback);
		die();
	}

	// Throw something back in case we didn't catch a problem.
	echo json_encode($feedback);
	die();

}

/**
 * CB Ajax New Request Items
 * 
 * We'll use this to process the new request_items entries sent via ajax.
 * 
 * @package ConfettiBits\Request_items
 * @since 2.2.0
 */
function cb_ajax_new_request_items() {

	if ( ! cb_is_post_request() ) {
		return;
	}

	$feedback = ['type' => 'error', 'text' => ''];

	if ( !isset( 
		$_POST['item_name'],
		$_POST['amount'],
		$_POST['api_key'],
	)) {
		$feedback['text'] = "Failed to authenticate request. Missing one of the following: item name, amount, or API key.";
		echo json_encode($feedback);
		die();
	}

	if ( !cb_core_validate_api_key( $_POST['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to renew the API key.";
		echo json_encode($feedback);
		die();
	}

	$item_name = sanitize_text_field($_POST['item_name']);
	$item_desc = !empty($_POST['item_desc']) ? 
		sanitize_text_field( $_POST['item_desc'] )
		: '';
	$amount = intval( $_POST['amount'] );

	$item = new CB_Requests_Request_Item();
	$item->item_name = $item_name;
	$item->item_desc = $item_desc;
	$item->date_created = cb_core_current_date();
	$item->date_modified = cb_core_current_date();
	$item->amount = $amount;

	$save = $item->save();

	if ( ! is_int( $save ) ) {
		$feedback['text'] = "Failed to process new request item.";
		echo json_encode($feedback);
		die();
	} else {
		$feedback['type'] = 'success';
		$feedback['text'] = "Successfully created '{$item_name}'.";
		echo json_encode($feedback);
		die();
	}

	$feedback['text'] = "Something's broken. Call Dustin.";

	echo json_encode($feedback);
	die();

}

/**
 * Deletes request items from the request items table.
 * 
 * @param array $items { 
 *     An array of arguments.
 * 
 *     @type int|array $request_item_id A comma-separated list (or array)
 * 					   of IDs for the request item(s) to remove. Required.
 * 
 * }
 * 
 * @return int The number of rows affected, or false on failure.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_ajax_delete_request_items() {

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
	
	if ( empty($_DELETE['request_item_id'] ) ) {
		$feedback['text'] = "Invalid or missing item id.";
		echo json_encode($feedback);
		die();
	}
	
	$request_item_id = intval( $_DELETE['request_item_id'] );
	

	$item = new CB_Requests_Request_Item($request_item_id);
	$delete = $item->delete([ 'id' => $request_item_id ]);
			
	if ( is_int( $delete ) ) {
		$feedback['type'] = 'success';
		$feedback['text'] = 'Item removed.';
		echo json_encode($feedback);
		die();
	} else {
		$feedback['text'] = "Operation failed: {$delete}";
		echo json_encode($feedback);
		die();
	}

}

/**
 * CB AJAX Get Request_items
 * 
 * Our REST API handler for the endpoint at 
 * "/wp-json/cb-ajax/v1/request_items/get"
 * 
 * @package ConfettiBits\Request_items
 * @since 2.3.0
 */
function cb_ajax_get_request_items() {

	if ( !cb_is_get_request() ) {
		return;
	}

	$request = new CB_Requests_Request_Item();
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

	if ( !empty( $_GET['item_name'] ) ) {
		$get_args['where']['item_name'] = trim( $_GET['item_name'] );
	}

	if ( !empty($_GET['amount'] ) ) {
		$get_args['where']['amount'] = intval( $_GET['amount'] );
	}

	if ( !empty( $_GET['orderby'] ) ) {
		$get_args['orderby']['column'] = !empty($_GET['orderby']['column'] ) ? trim($_GET['orderby']['column']) : 'id';
		$get_args['orderby']['order'] = !empty($_GET['orderby']['order'] ) ? trim($_GET['orderby']['order']) : 'DESC';
	}

	$results = $request->get_request_items($get_args);

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
 * Adds 5 request_items entries for testing purposes.
 * 
 * @package ConfettiBits\Request_items
 * @since 2.3.0
 */
function cb_request_items_add_filler_data() {

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
			'component_action'	=> 'cb_request_items',
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
			'component_action'	=> 'cb_request_items',
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
			'component_action'	=> 'cb_request_items',
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
			'component_action'	=> 'cb_request_items',
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
			'component_action'	=> 'cb_request_items',
			'status'			=> 'new',
			'transaction_id'	=> 0
		],
	];
	foreach ( $entries as $entry ) {
		cb_request_items_new_request_items($entry);	
	}

}
//add_action( 'cb_actions', 'cb_add_a_few_request_items' );