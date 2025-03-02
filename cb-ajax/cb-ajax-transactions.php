<?php 
// Exit if accessed directly
defined('ABSPATH') || exit;
/**
 * CB AJAX Get Transactions
 * 
 * Gets transactions for a user based on the user_id passed in the $_GET array.
 * 
 * @return JSON {
 * 		'text': JSON (JSON encoded array of transactions, or error message),
 * 		'type': string (success or error)
 * }
 * 
 * @package ConfettiBits\AJAX
 * @since 2.3.0
 */
function cb_ajax_get_transactions() {

	if ( ! cb_is_get_request() ) {
		return;
	}

	$transactions = new CB_Transactions_Transaction();
	$feedback = ['type' => 'error', 'text' => ''];
	$get_args = [];

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

	if ( ! empty( $_GET['recipient_id'] ) ) {
		$get_args['where']['recipient_id'] = intval( $_GET['recipient_id'] );
	}

	if ( !empty( $_GET['sender_id'] ) ) {
		$get_args['where']['sender_id'] = intval( $_GET['sender_id'] );
	}

	if ( !empty( $_GET['event_id'] ) ) {
		$get_args['where']['event_id'] = intval( $_GET['event_id'] );
	}

	if ( !empty( $_GET['or'] ) ) {
		$get_args['where']['or'] = true;
	}

	$get = $transactions->get_transactions($get_args);

	if ( $get ) {
		$feedback['text'] = $get;
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = $get;
	}

	echo json_encode( $feedback );
	die();

}

/**
 * CB AJAX New Transactions
 * 
 * AJAX handler for creating transactions.
 * 
 * All parameters are passed via POST request.
 * 
 * The following parameters are required:
 * 
 * - sender_id (int) - The ID of the user sending the bits.
 * - recipient_id (int) - The ID of the user receiving the bits.
 * - amount (int) - The amount of bits to send.
 * - log_entry (string) - The log entry to record for this transaction.
 * 
 * @package ConfettiBits\AJAX
 * @since 2.3.0
 */
function cb_ajax_new_transactions() {

	if ( ! cb_is_post_request() ) {
		return;
	}

	$feedback = ["text"	=> "", "type" => "error" ];

	if ( !isset( $_POST['api_key'] ) ) {
		$feedback['text'] = "Missing Confetti Bits API Key. Contact system administrator.";
		echo json_encode($feedback);
		die();
	}

	if ( !cb_core_validate_api_key( $_POST['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to renew the API key.";
		echo json_encode($feedback);
		die();
	}

	if ( empty( $_POST['recipient_id'] ) ) {
		$feedback['text'] = "Failed to authenticate transaction. Missing recipient.";
		echo json_encode($feedback);
		die();
	}

	$recipient_id = intval($_POST['recipient_id']);

	if ( !empty( $_POST['event_id'] ) ) {

		$event_id = intval($_POST['event_id']);
		$event_transaction = cb_transactions_new_events_transaction([
			'event_id' => $event_id,
			'recipient_id' => $recipient_id
		]);

		if ( $event_transaction == false ) {
			$feedback['text'] = "Failed to process events transaction: {$event_transaction}";
		} else {
			$feedback['text'] = "You're all set, thanks for participating!";
			$feedback['type'] = 'success';

		}
		echo json_encode($feedback);
		die();
	}

	if ( !empty( $_POST['contest_id'] ) ) {

		$contest_id = intval($_POST['contest_id']);

		$contest_transction = cb_transactions_new_contests_transaction([
			'contest_id' => $contest_id,
			'recipient_id' => $recipient_id
		]);

		if ( $contest_transction == false ) {
			$feedback['text'] = "Failed to process contest transaction: {$contest_transaction}";
		} else {
			$feedback['text'] = "You're all set, congratulations!";
			$feedback['type'] = 'success';
		}
		echo json_encode($feedback);
		die();

	}

	if ( empty( $_POST['sender_id'] ) || empty( $_POST['amount'] ) ) {
		$feedback['text'] = "Failed to authenticate request. Missing sender ID or transaction amount.";
		echo json_encode($feedback);
		die();
	}

	if ( !is_numeric( $_POST['sender_id'] ) ) {
		$feedback["text"] = "Invalid or empty Sender ID. Please try again.";
		$feedback["type"] = "error";
		http_response_code(401);
		echo json_encode($feedback);
		die();
	}

	if ( empty( $_POST['recipient_id'] ) || !is_numeric( $_POST['recipient_id'] ) ) {
		$feedback["text"] = "Invalid or empty Recipient ID. Please select a recipient.";
		http_response_code(401);
		$feedback["type"] = "error";
		echo json_encode($feedback);
		die();
	}
	
	if ( empty( $_POST['log_entry'] ) ) {
		$feedback["text"] = "Confetti Bits transactions must include a log entry.";
		$feedback["type"] = "error";
		echo json_encode($feedback);
		die();
	}
	
	if ( empty( $_POST['amount'] ) || !is_numeric( $_POST['amount'] ) ) {
		$feedback["text"] = "Confetti Bits transactions must include an amount to send.";
		http_response_code(400);
		$feedback["type"] = "error";
		echo json_encode($feedback);
		die();
	}
	
	$sender_id = intval( $_POST['sender_id'] );
	$recipient_id = intval( $_POST['recipient_id'] );
	$sender_name = cb_core_get_user_display_name( $sender_id );
	$recipient_name = cb_core_get_user_display_name( $recipient_id );
	$log_entry = "";
	$amount = 0;
	$add_activity = isset( $_POST['add_activity'] );
	$is_admin = cb_is_user_transactions_admin($sender_id);
	$blackout_active = cb_settings_get_blackout_status();
	$total_today = cb_transactions_get_total_sent_today($sender_id);
	$limit = get_option('cb_transactions_transfer_limit');
	$log_entry = sanitize_text_field( $_POST['log_entry'] );
	$amount = intval( $_POST['amount'] );
	$recipient_transfer_balance = cb_transactions_get_transfer_balance( $recipient_id );
	$sender_transfer_balance = cb_transactions_get_transfer_balance( $sender_id );

	if ( !$is_admin && $blackout_active ) {
		$feedback['text'] = "Confetti Bits transfers are currently in an active blackout period. Contact your administrator for more information.";
		$feedback['type'] = 'error';
		echo json_encode($feedback);
		die();	
	}

	if ( ( $amount > $sender_transfer_balance ) && !$is_admin ) {
		$feedback["text"] = "Sorry, it looks like you don't have enough bits to send.";
		$feedback["type"] = "warning";
		echo json_encode($feedback);
		die();
	}
	
	if ( ( abs( $amount ) > $recipient_transfer_balance ) && ( $amount < 0 ) ) {
		$feedback["text"] = "{$recipient_name} doesn't have enough Confetti Bits for that.";
		$feedback["type"] = "warning";
		echo json_encode($feedback);
		die();
	}

	if ( ( $amount + $total_today ) >= $limit && !$is_admin ) { 
		$feedback["text"] = "Transaction not sent. This would put you over the Confetti Bits transfer limit. Your counter will reset next month!";
		$feedback["type"] = "warning";
		echo json_encode($feedback);
		die();
	}

	if ( (cb_core_get_doomsday_clock() <= 7 && !$is_admin ) ) {
		$feedback["text"] = "Transaction not sent. Cannot transfer Confetti Bits within 7 days prior to, or one month beyond, the cycle reset date.";
		$feedback["type"] = "warning";
		echo json_encode($feedback);
		die();
	}

	$action = $is_admin ? "send" : "transfer";
	$transaction_type = $is_admin ? " leadership " : "";

	$send = cb_transactions_new_transaction([
		'item_id'			=> $recipient_id,
		'secondary_item_id'	=> $sender_id,
		'sender_id'			=> $sender_id,
		'recipient_id' 		=> $recipient_id,
		'date_sent'			=> cb_core_current_date(),
		'log_entry'    		=> "{$log_entry} - from {$sender_name}",
		'component_name'    => 'confetti_bits',
		'component_action'  => "cb_{$action}_bits",
		'amount'    		=> $amount
	]);

	$sent = is_int($send);

	if ( $add_activity ) {
		$sender_link = bp_core_get_userlink( $sender_id );
		$recipient_link = bp_core_get_userlink( $recipient_id );
		$activity_args = [
			"action"	=> "<p>{$sender_link} just sent{$transaction_type}bits to {$recipient_link} for:</p>",
			"content"	=> "<p style='margin:1.25rem;'>\"{$log_entry}\"</p>",
			"type"		=> "activity_update",
			"component"	=> "confetti_bits",
			"user_id"	=> $sender_id
		];
		bp_activity_add($activity_args);
	}

	if ( $is_admin && $sent ) {
		http_response_code(200);
		$feedback['text'] = "Successfully sent bits to {$recipient_name}!";
		$feedback['type'] = "success";
		echo json_encode($feedback);
		die();
	}

	$subtract = cb_transactions_new_transaction([
		'item_id'			=> $sender_id,
		'secondary_item_id'	=> $sender_id,
		'sender_id'			=> $sender_id,
		'recipient_id' 		=> $sender_id,
		'date_sent'			=> cb_core_current_date(),
		'log_entry'    		=> "Sent Bits to {$recipient_name}",
		'component_name'    => 'confetti_bits',
		'component_action'  => "cb_{$action}_bits",
		'amount'    		=> -$amount
	]);

	if ( true === is_int($subtract) ) {
		http_response_code(200);
		$feedback["text"] = "Successfully sent bits to {$recipient_name}";
		$feedback["type"] = "success";
		echo json_encode($feedback);
		die();
	}

	http_response_code(500);
	$feedback["text"] = "Something's broken, call Dustin.";
	$feedback["type"] = "error";
	echo json_encode($feedback);
	die();

}