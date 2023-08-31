<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * CB Requests Functions
 * 
 * These are going to be all of our CRUD functions for 
 * the requests component.
 * 
 * @package ConfettiBits\Requests
 * @since 2.2.0
 */

/**
 * CB Requests Create Requests
 * 
 * Creates a new requests object and saves it to the database.
 * 
 * @param	array	$args	Associative array of arguments for saving.
 * 							All arguments are optional except for media_filepath. {
 * 		@type	int		$item_id			For BuddyBoss Platform's Notifications API.
 * 											Registers the primary data for the notification.
 * 											We'll use the applicant_id for this.
 * 
 * 		@type	int		$secondary_item_id	For BuddyBoss Platform's Notifications API.
 * 											Register's the receiver's profile avatar in the notification.
 * 											We'll use the admin_id for this.
 * 
 * 		@type	int		$applicant_id		The user_id associated with the requests entry.
 * 		@type	int		$admin_id			The user_id of the last admin that modified the entry.
 * 		@type	string	$date_created		A mysql datetime entry for when the requests was registered.
 * 		@type	string	$date_modified		A mysql datetime entry for when the requests was registered.
 * 		@type	string	$component_name		For BuddyBoss Platform's Notifications API.
 * 											Helps the API know which notification group to use.
 * 		@type	string	$component_action	For BuddyBoss Platform's Notifications API.
 * 											Helps the API know which notification format to use.
 * 		@type	string	$status				The status of the requests entry.
 * 											Common statuses include "new", "approved", "denied", or "pending".
 * 	    @type   int    $request_item_id    The ID of the item being requested.
 * }
 * 
 * @package ConfettiBits\Requests
 * @since 2.2.0
 */
function cb_requests_new_request(array $args)
{

	$r = wp_parse_args($args, [
		'item_id' => get_current_user_id(),
		'secondary_item_id' => 0,
		'applicant_id' => get_current_user_id(),
		'admin_id' => 0,
		'date_created' => cb_core_current_date(),
		'date_modified' => cb_core_current_date(),
		'component_name' => 'confetti_bits',
		'component_action' => 'cb_requests_new_request',
		'status' => 'new',
		'request_item_id' => 0,
	]);

	$requests = new CB_Requests_Request();
	$requests->item_id = $r['item_id'];
	$requests->secondary_item_id = $r['secondary_item_id'];
	$requests->applicant_id = $r['applicant_id'];
	$requests->admin_id = $r['admin_id'];
	$requests->date_created = $r['date_created'];
	$requests->date_modified = $r['date_modified'];
	$requests->component_name = $r['component_name'];
	$requests->component_action = $r['component_action'];
	$requests->status = $r['status'];
	$requests->request_item_id = $r['request_item_id'];

	$request = $requests->save();

	if (false === is_int($request)) {
		return false;
	}

	return $requests->id;

}

/**
 * CB Requests New Transaction
 * 
 * Add or subtract Confetti Bits based on request approval status.
 * 
 * @param array $args An array of parameters for us to work with. { 
 * 		@type int 		$transaction_id		A transaction ID for us to check for.
 * 		@type int 		$request_id	A requests ID for us to check for.
 * 		@type int 		$admin_id			An admin ID for us to check for.
 * 		@type string	$status 			The status of the requests entry.
 * 		@type string	$modified 			The date of the last modification 
 * 												for the requests entry.
 * }
 * 
 * @return int|string Transaction ID on success, error on failure.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_requests_new_transaction($args = []) {

	$r = wp_parse_args($args, [
		'request_id' => 0,
		'admin_id' => 0,
		'amount' => 0
	]);

	$feedback = ['type' => 'error', 'text' => ''];

	if (
		$r['request_id'] === 0 ||
		$r['admin_id'] === 0 ||
		$r['amount'] === 0
	) {
		$feedback['text'] = "One of the following parameters is missing: Request ID, admin ID, or amount.";
		return $feedback;
	}

	$request = new CB_Requests_Request($r['request_id']);

	$date = $request->date_created;
	$date_obj = new DateTimeImmutable($date);
	$date_log_entry = $date_obj->format('m/d/Y');
	$admin_name = cb_core_get_user_display_name($r['admin_id']);
	$log_entry = cb_requests_get_item_name($request->request_item_id);
	$amount = intval($r['amount']);

	// Always make sure we're subtracting the request amount.
	if ( $amount > 0 ) {
		$amount = - $amount;
	}
	
	if ( $amount < 0 && abs($amount) > cb_transactions_get_request_balance($request->applicant_id) ) {
		$feedback['text'] = "User does not have enough Confetti Bits for that item.";
		return $feedback;
	}

	$log_entry .= " on {$date_log_entry} - from {$admin_name}";

	$transaction = new CB_Transactions_Transaction();

	$transaction->item_id = $request->applicant_id;
	$transaction->secondary_item_id = $request->applicant_id;
	$transaction->sender_id = $request->applicant_id;
	$transaction->recipient_id = $request->applicant_id;
	$transaction->date_sent = $date;
	$transaction->log_entry = $log_entry;
	$transaction->component_name = 'confetti_bits';
	$transaction->component_action = 'cb_requests_status_update';
	$transaction->amount = $amount;

	$send = $transaction->send_bits();

	if (is_int($send)) {
		$feedback['text'] = $send;
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = "Processing Error 3060. Failed to create transaction.";
	}

	return $feedback;

}

/**
 * Attempts to extract a predetermined value from a request item.
 * 
 * @param int $request_item_id	The ID of the request item with the request.
 * 
 * @return int The amount we extracted.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_requests_get_amount( $request_item_id = 0 ) {

	// Prevent redundant submissions.
	if ( $request_item_id === 0 ) {
		return;
	}

	$request_item = new CB_Requests_Request_Item($request_item_id);

	return $request_item->amount;

}

/**
 * Gets the name of a request item.
 * 
 * @param int		$request_item_id	The ID for the request item we're checking.
 * 
 * @return string	The item name we extracted.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_requests_get_item_name( $request_item_id = 0 ) {

	if ($request_item_id === 0) {
		return;
	}

	$request_item = new CB_Requests_Request_Item($request_item_id);

	return $request_item->item_name;

}

/**
 * Lists all available request items.
 * 
 * There's a built-in limit of 15 items, but that can be
 * overridden in the arguments.
 * 
 * @param array $args { 
 *     An optional array of arguments.
 * 
 *     @type int $page The page of request items.
 *     @type int $per_page How many request items should
 * 						   appear on each page.
 * }
 * 
 * @return array A 2D array of request items, where each
 * 				 request item is itself an associative
 * 				 array.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_requests_get_request_items( $args = [] ) {

	$r = wp_parse_args( $args, [
		'select' => 'id, item_name',
		'pagination' => [
			'page' => 1,
			'per_page' => 15
		]
	]);

	$get_args = [ 'select' => $r['select'], 'pagination' => $r['pagination'] ];

	$request_items = new CB_Requests_Request_Item();

	return $request_items->get_request_items($get_args);

}

/**
 * Gets a transaction object, if any are associated with a request.
 * 
 * Use this to check if a transaction already exists for a given request.
 * 
 * @param int $transaction_id The ID for the transaction we're looking for.
 * 
 * @return array|bool Transaction if the object exists, false if not or we get an error.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_requests_get_transaction($transaction_id = 0) {

	if ($transaction_id === 0) {
		return false;
	}

	$transaction = new CB_Transactions_Transaction($transaction_id);

	return (!empty($transaction)) ? $transaction : false;

}

/**
 * Gets an array of requests from the database.
 * 
 * @param array $args { 
 *   An associative array of arguments.
 *   @see CB_Requests_Request::get_requests()
 * }
 * 
 * @package ConfettiBits\Requests
 * @since 3.0.0
 */
function cb_requests_get_requests( $args = [] ) {
	
	$r = wp_parse_args( $args, [
		'select' => '*',
		'where' => [
			'applicant_id' => get_current_user_id(),
		],
		'pagination' => ['page' => 1, 'per_page' => 10],
	]);
	
	$requests = new CB_Requests_Request();
	return $requests->get_requests($r);
	
}

/**
 * Returns the total amount of points the user has in open requests.
 * 
 * @param int $user_id The ID of the user to check.
 * @return int The total amount of points that the user has in open requests.
 * 
 * @package ConfettiBits\Requests
 * @since 3.0.0
 */
function cb_requests_get_active_request_total( $user_id = 0 ) {
	
	if ( $user_id === 0 ) {
		$user_id = get_current_user_id();
	}
	
	$total = 0;
	
	$get_request_args = [
		'select' => 'request_item_id',
		'where' => [
			'applicant_id' => $user_id,
			'status' => ['new', 'in_progress'],
		],
		'pagination' => [],
	];
	
	$requests = cb_requests_get_requests($get_request_args);
	
	if ( ! empty( $requests ) ) {
		foreach ( $requests as $request ) {
			$request_item = new CB_Requests_Request_Item($request['request_item_id']);
			$total += $request_item->amount;
		}
	}
	
	return $total;
	
}

/**
 * Checks to see if a user can request the given item.
 * 
 * @param int $user_id The ID of the user we're checking
 * @param int $item_id The ID of the item they want
 * 
 * @return boolean True if they have enough to get the item, false otherwise.
 * 
 * @package ConfettiBits\Requests
 * @since 3.0.0
 */
function cb_requests_can_request( $applicant_id = 0, $item_id = 0 ) {
	
	if ( $item_id === 0 || $applicant_id === 0 ) {
		return false;
	}
	
	$item = new CB_Requests_Request_Item($item_id);
	$request_balance = cb_transactions_get_request_balance($applicant_id);
	$active_requests_balance = cb_requests_get_active_request_total($applicant_id);
	
	return ( $request_balance - ( $active_requests_balance + $item->amount ) ) > 0;
	
}

/**
 * Checks to see if a user can update their request item.
 * 
 * It basically subtracts the existing item from the active request total,
 * adds on the cost of the new item, and checks to see if the items cost
 * more than they have to spend on requests.
 * 
 * @param int $applicant_id The ID of the user to check.
 * @param int $prev_item_id The ID of the previous item.
 * @param int $updated_item_id The ID of the new item that they would like to change to.
 * 
 * @return bool Whether the item would put the user over their request balance.
 * 
 * @package ConfettiBits\Requests
 * @since 3.0.0
 */
function cb_requests_can_update( $applicant_id = 0, $prev_item_id = 0, $updated_item_id = 0 ) {
	
	if ( $prev_item_id === 0 || $applicant_id === 0 || $updated_item_id === 0 ) {
		return false;
	}
	
	$prev_item = new CB_Requests_Request_Item($prev_item_id);
	$updated_item = new CB_Requests_Request_Item($updated_item_id);
	$request_balance = cb_transactions_get_request_balance($applicant_id);
	$active_requests_balance = cb_requests_get_active_request_total($applicant_id) - $prev_item->amount;
	
	return ( $request_balance - ( $active_requests_balance + $updated_item->amount ) ) > 0;
	
}

/**
 * Sends a notification email when a request comes in.
 * 
 * @TODO: Implement this, maybe. Not a priority, but would be nice.
 *        We'll need to get a list of people who need to be notified.
 *        Probably could get that by fetching a list of all users with
 *        a particular role.
 * 
 * @param array $args { 
 *     An array of arguments.
 * 
 *     @type int	$applicant_id		The user ID for the applicant who sent the request. Required.
 *     @type int	$request_item_id	The ID of the item being requested. Required.
 * 
 * }
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
function cb_requests_new_notifications($data = [])
{

	$r = wp_parse_args( $args, [ 'applicant_id' => 0,'request_item_id' => 0 ]);

	if ( empty($r['applicant_id']) || empty($r['request_item_id'] )	) {
		return;
	}

	$item_id = 0;
	$secondary_item_id = 0;
	$item_id = $r['applicant_id']; // Temporary. Not the real deal.
	$secondary_item_id = $r['applicant_id'];

	$unsubscribe_args = array(
		'user_id' => $item_id,
		'notification_type' => 'cb-requests-new',
	);

	$email_args = array(
		'tokens' => array(
			'applicant.name' => bp_core_get_user_displayname($secondary_item_id),
			'requests.note' => $r['event_note'],
			'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
		),
	);

	bp_send_email('cb-requests-new', $item_id, $email_args);

	bp_notifications_add_notification(
		array(
			'user_id' => $item_id,
			'item_id' => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'component_name' => 'confetti_bits',
			'component_action' => 'cb_requests_new',
			'date_notified' => cb_core_current_date(),
			'is_new' => 1,
			'allow_duplicate' => true,
		)
	);

}
// add_action('cb_requests_after_save', 'cb_requests_new_notifications');

/**
 * CB Requests Update Notifications
 * 
 * This will notify a user after a requests entry 
 * has been updated to a new status.
 * 
 * @TODO: Add support for denied requests.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 *//*
function cb_requests_update_notifications($data = array())
{

	$r = wp_parse_args(
		$data,
		array(
			'applicant_id' => 0,
			'admin_id' => 0,
			'component_action' => '',
			'event_note' => '',
			'status' => '',
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

		case ('cb_requests_status_update'):

			$item_id = $r['applicant_id'];
			$secondary_item_id = $r['admin_id'];

			$unsubscribe_args = array(
				'user_id' => $item_id,
				'notification_type' => 'cb-requests-status-update',
			);

			$email_args = array(
				'tokens' => array(
					'admin.name' => cb_core_get_user_display_name($secondary_item_id),
					'requests.status' => ucfirst($r['status']),
					'requests.note' => $event_note,
					'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
				),
			);

			bp_send_email('cb-requests-status-update', (int) $item_id, $email_args);

			break;
	}

	bp_notifications_add_notification(
		array(
			'user_id' => $item_id,
			'item_id' => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'component_name' => 'confetti_bits',
			'component_action' => $r['component_action'],
			'date_notified' => cb_core_get_current_date(),
			'is_new' => 1,
			'allow_duplicate' => false,
		)
	);

}*/
// add_action('cb_requests_after_update', 'cb_requests_update_notifications');