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

	if ( !empty( $_GET['or'] ) ) {
		$get_args['where']['or'] = true;
	}

	$get = $transactions->get_transactions($get_args);

	if ( $get ) {
		$feedback['text'] = $get;
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = false;
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

	$feedback    = array(
		"text"	=> "",
		"type"	=> "error"
	);

	if ( !isset( 
		$_POST['sender_id'],
		$_POST['recipient_id'],
		$_POST['amount'],
		$_POST['api_key'],
	)) {
		$feedback['text'] = "Failed to authenticate request. Missing one of the following: sender ID, recipient ID, amount, or API key.";
		echo json_encode($feedback);
		die();
	}

	if ( !cb_core_validate_api_key( $_POST['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to renew the API key.";
		echo json_encode($feedback);
		die();
	}

	if ( empty( $_POST['sender_id'] ) || !is_numeric( $_POST['sender_id'] ) ) {
		$feedback["text"] = "Invalid or empty Sender ID. Please try again.";
		$feedback["type"] = "error";
		http_response_code(401);
		echo json_encode($feedback);
		die();
	} else {
		$sender_id = intval( $_POST['sender_id'] );
	}
	


	if ( empty( $_POST['recipient_id'] ) || !is_numeric( $_POST['recipient_id'] ) ) {
		$feedback["text"] = "Invalid or empty Recipient ID. Please select a recipient.";
		http_response_code(401);
		$feedback["type"] = "error";
		echo json_encode($feedback);
		die();
	} else {
		$recipient_id = intval( $_POST['recipient_id'] );
	}

	$sender_name = cb_core_get_user_display_name( $sender_id );
	$recipient_name = cb_core_get_user_display_name( $recipient_id );
	$log_entry = "";
	$amount = 0;
	$add_activity = isset( $_POST['add_activity'] );
	$is_admin = cb_core_admin_is_user_admin($sender_id);
	
	$total_today = cb_transactions_get_total_sent_today();

	if ( empty( $_POST['log_entry'] ) ) {
		$feedback["text"] = "Confetti Bits transactions must include a log entry.";
		$feedback["type"] = "error";
		echo json_encode($feedback);
		die();
	} else {
		$log_entry = sanitize_text_field( $_POST['log_entry'] );
	}

	if ( empty( $_POST['amount'] ) || !is_numeric( $_POST['amount'] ) ) {
		$feedback["text"] = "Confetti Bits transactions must include an amount to send.";
		http_response_code(400);
		$feedback["type"] = "error";
		echo json_encode($feedback);
		die();
	} else {
		$amount = intval( $_POST['amount'] );
	}

	if ( ( abs( $amount ) > cb_transactions_get_transfer_balance( $recipient_id ) ) && 
		( $amount < 0 ) 
	   ) {
		$feedback["text"] = "{$recipient_name} doesn't have enough Confetti Bits for that.";
		$feedback["type"] = "warning";
		echo json_encode($feedback);
		die();
	}

	if ( ( $amount + $total_today ) > 20 ) { 
		$feedback["text"] = "Transaction not sent. This would put you over the 20 Confetti Bits per diem limit. Your counter will reset tomorrow!";
		$feedback["type"] = "warning";
		echo json_encode($feedback);
		die();
	}

	if ( ( $amount > cb_transactions_get_transfer_balance( $sender_id ) ) && ( !cb_is_user_admin($sender_id) ) ) {
		$feedback["text"] = "Sorry, it looks like you don't have enough bits to send.";
		$feedback["type"] = "warning";
		echo json_encode($feedback);
		die();
	}
	
	if ( (cb_core_get_doomsday_clock() <= 7 && !cb_is_user_admin($sender_id))
	   ) {
		$feedback["text"] = "Transaction not sent. Cannot transfer Confetti Bits within 7 days prior to, or one month beyond, the cycle reset date.";
		$feedback["type"] = "warning";
		echo json_encode($feedback);
		die();
	}

	$action = $is_admin ? "send" : "transfer";

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

	if ( $add_activity ) {
		$sender_link = bp_core_get_userlink( $sender_id );
		$recipient_link = bp_core_get_userlink( $recipient_id );
		$activity_args = array(
			"action"	=> "<p>{$sender_link} just sent leadership bits to {$recipient_link} for:</p>",
			"content"	=> "<p style='margin:1.25rem;'>\"{$log_entry}\"</p>",
			"type"		=> "activity_update",
			"component"	=> "confetti_bits",
			"user_id"	=> $sender_id
		);
		bp_activity_add($activity_args);
	}

	if ( true === is_int( $send ) ) {

		if ( $is_admin ) {
			http_response_code(200);
			$feedback['text'] = "Successfully sent bits to {$recipient_name}!";
			$feedback['type'] = "success";
			echo json_encode($feedback);
			die();
		} else {
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
			} else {
				http_response_code(500);
				$feedback["text"] = "Something's broken, call Dustin.";
				$feedback["type"] = "error";
				echo json_encode($feedback);
				die();
			}
		}

	} else {
		http_response_code(500);
		$feedback["text"] = "Something's broken, call Dustin.";
		$feedback["type"] = "error";
		echo json_encode($feedback);
		die();
	}

}

/**
 * CB AJAX Send Bits
 * 
 * AJAX handler for sending bits.
 * 
 * All parameters are passed via $_POST.
 * 
 * The following parameters are required:
 * 
 * - sender_id (int) - The ID of the user sending the bits.
 * - recipient_id (int) - The ID of the user receiving the bits.
 * - amount (int) - The amount of bits to send.
 * - log_entry (string) - The log entry to record for this transaction.
 * 
 * @package ConfettiBits\AJAX
 * @since 2.1.1

function cb_ajax_send_bits() {

	if ( ! cb_is_post_request() ) {
		return;
	}

	$feedback    = array(
		"text"	=> "",
		"type"	=> "error"
	);

	if ( empty( $_POST['sender_id'] ) || !is_numeric( $_POST['sender_id'] ) ) {
		$feedback["text"] = "Invalid or empty Sender ID. Please try again.";
		$feedback["type"] = "error";
		http_response_code(401);
		echo json_encode($feedback);
		die();
	} else {
		$sender_id = intval( $_POST['sender_id'] );
	}

	if ( empty( $_POST['recipient_id'] ) || !is_numeric( $_POST['recipient_id'] ) ) {
		$feedback["text"] = "Invalid or empty Recipient ID. Please select a recipient.";
		http_response_code(400);
		$feedback["type"] = "error";
		echo json_encode($feedback);
		die();
	} else {
		$recipient_id = intval( $_POST['recipient_id'] );
	}

	$sender_name = bp_core_get_user_displayname( $sender_id );
	$recipient_name = bp_core_get_user_displayname( $recipient_id );
	$sender_link = bp_core_get_userlink( $sender_id );
	$recipient_link = bp_core_get_userlink( $recipient_id );
	$log_entry = "";
	$amount = 0;
	$add_activity = isset( $_POST['add_activity'] );
	$is_admin = cb_is_user_admin();

	if ( empty( $_POST['log_entry'] ) ) {
		http_response_code(400);
		$feedback["text"] = "Confetti Bits transactions must include a log entry.";
		$feedback["type"] = "error";
		echo json_encode($feedback);
		die();
	} else {
		$log_entry = sanitize_text_field( $_POST['log_entry'] );
	}

	if ( empty( $_POST['amount'] ) || !is_numeric( $_POST['amount'] ) ) {
		$feedback["text"] = "Confetti Bits transactions must include an amount to send.";
		http_response_code(400);
		$feedback["type"] = "error";
		echo json_encode($feedback);
		die();
	} else {
		$amount = intval( $_POST['amount'] );
	}

	if ( ( abs( $amount ) > cb_transactions_get_transfer_balance( $recipient_id ) ) && 
		( $amount < 0 ) 
	   ) {
		http_response_code(403);
		$feedback["text"] = "{$recipient_name} doesn't have enough Confetti Bits for that.";
		$feedback["type"] = "warning";
		echo json_encode($feedback);
		die();
	}

	if ( $amount + cb_transactions_get_total_sent_today() > 20 ) {
		http_response_code(403);
		$feedback["text"] = "This will put you over the 20 Confetti Bits per day limit. Your counter will reset tomorrow!";
		$feedback["type"] = "warning";
		echo json_encode($feedback);
		die();
	}

	if ( ( $amount > cb_transactions_get_transfer_balance( $sender_id ) ) && ( !cb_is_user_admin() ) ) {
		http_response_code(403);
		$feedback["text"] = "Sorry, it looks like you don't have enough bits to send.";
		$feedback["type"] = "warning";
		echo json_encode($feedback);
		die();
	}

	$action = $is_admin ? "send" : "transfer";

	$send = cb_transactions_send_bits(
		array(
			'item_id'			=> $recipient_id,
			'secondary_item_id'	=> $sender_id,
			'sender_id'			=> $sender_id,
			'recipient_id' 		=> $recipient_id,
			'date_sent'			=> bp_core_current_time( false ),
			'log_entry'    		=> "{$log_entry} - from {$sender_name}",
			'component_name'    => 'confetti_bits',
			'component_action'  => "cb_{$action}_bits",
			'amount'    		=> $amount,
			'error_type' 		=> 'wp_error',
		)
	);

	if ( $add_activity ) {
		$activity_args = array(
			"action"	=> "<p>{$sender_link} just sent leadership bits to {$recipient_link} for:</p>",
			"content"	=> "<p style='margin:1.25rem;'>\"{$log_entry}\"</p>",
			"type"		=> "activity_update",
			"component"	=> "confetti_bits",
			"user_id"	=> $sender_id
		);
		bp_activity_add($activity_args);
	}

	if ( true === is_int( $send ) ) {

		if ( $is_admin ) {
			http_response_code(200);
			$feedback['text'] = "Successfully sent bits to {$recipient_name}!";
			$feedback['type'] = "success";
			echo json_encode($feedback);
			die();
		} else {
			$subtract = cb_transactions_send_bits(
				array(
					'item_id'			=> $sender_id,
					'secondary_item_id'	=> $recipient_id,
					'sender_id'			=> get_current_user_id(),
					'recipient_id' 		=> $sender_id,
					'date_sent'			=> bp_core_current_time( false ),
					'log_entry'    		=> "Sent Bits to {$recipient_name}",
					'component_name'    => 'confetti_bits',
					'component_action'  => "cb_{$action}_bits",
					'amount'    		=> -$amount,
					'error_type' 		=> 'wp_error',
				)
			);

			if ( true === is_int($subtract) ) {
				http_response_code(200);
				$feedback["text"] = "Successfully sent bits to {$recipient_name}";
				$feedback["type"] = "success";
				echo json_encode($feedback);
				die();
			} else {
				http_response_code(500);
				$feedback["text"] = "Something's broken, call Dustin.";
				$feedback["type"] = "error";
				echo json_encode($feedback);
				die();
			}
		}

	} else {
		http_response_code(500);
		$feedback["text"] = "Something's broken, call Dustin.";
		$feedback["type"] = "error";
		echo json_encode($feedback);
		die();
	}

}
// add_action('wp_ajax_cb_send_bits', 'cb_ajax_send_bits');

function cb_ajax_get_transactions_by_id() {
	if ( !isset( $_GET['user_id'] ) ) {
		http_response_code(400);
		die();
	}
	$page = 1;
	$per_page = 15;

	if ( isset( $_GET['page'] ) ) {
		$page = intval( $_GET['page'] );
	}

	if ( isset( $_GET['per_page'] ) ) {
		$per_page = intval( $_GET['per_page'] );
	}

	$recipient_id = intval( $_GET['user_id'] );

	$args = array(
		'where' => array(
			'recipient_id'	=> $recipient_id,
			'sender_id'		=> $recipient_id,
			'or'			=> true
		),
		'order' => array( 'id', 'DESC' ),
		'pagination' => array( ($page) * $per_page, $per_page )
	);

	$transaction = new Confetti_Bits_Transactions_Transaction();
	$transactions = $transaction->get_transactions($args);

	echo json_encode($transactions);
	die();

}
//add_action('wp_ajax_cb_participation_get_transactions', 'cb_ajax_get_transactions_by_id');

function cb_ajax_get_total_transactions() {
	if ( !isset( $_GET['user_id'] ) ) {
		http_response_code(400);
		die();
	}

	$recipient_id = intval( $_GET['user_id'] );
	$args = array(
		'select'	=> 'COUNT(id) as total_count',
		'where' => array(
			'recipient_id'	=> $recipient_id,
			'sender_id'		=> $recipient_id,
			'or'			=> true
		),
	);
	$transaction = new CB_Transactions_Transaction();
	$count = $transaction->get_transactions($args);

	echo json_encode($count);
	die();

}
//add_action( 'wp_ajax_cb_participation_get_total_transactions', 'cb_ajax_get_total_transactions' );
*/