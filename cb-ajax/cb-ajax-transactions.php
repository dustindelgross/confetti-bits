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
 * @package ConfettiBits
 * @subpackage AJAX
 * @since 2.3.0
 */
function cb_ajax_get_transactions() {

	if ( ! cb_is_get_request() || empty( $_GET['user_id'] ) ) {
		return;
	}

	$feedback = array(
		'text' => '',
		'type' => 'error'
	);

	$select = isset($_GET['total']) ? 'count(id) as total_count' : 'sender_id, recipient_id, amount, log_entry, date_sent';

	$user_id = intval( $_GET['user_id'] );
	$page = isset( $_GET['page'] ) ? intval( $_GET['page'] ) : 1;
	$per_page = isset($_GET['per_page']) ? intval( $_GET['per_page'] ) : 10;
	$transaction = new CB_Transactions_Transaction();
	$paged_transactions = $transaction->get_transactions(array(
		'select' => $select,
		'where' => array(
			'recipient_id' => $user_id,
			'sender_id' => $user_id,
			'or' => true
		),
		'orderby' => array('id', 'DESC'),
		'pagination' => array(
			'page' => $page,
			'per_page' => $per_page
		)
	));

	if ( $paged_transactions ) {
		$feedback['text'] = $paged_transactions;
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = json_encode("No transactions found");
	}

	echo json_encode( $feedback );
	die();

}
add_action( 'wp_ajax_cb_transactions_get_transactions', 'cb_ajax_get_transactions' );


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
 * @package ConfettiBits
 * @subpackage AJAX
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
			'date_sent'			=> cb_core_current_date(),
			'log_entry'    		=> "{$log_entry} - from {$sender_name}",
			'component_name'    => 'confetti_bits',
			'component_action'  => "cb_{$action}_bits",
			'amount'    		=> $amount
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
					'secondary_item_id'	=> $sender_id,
					'sender_id'			=> $sender_id,
					'recipient_id' 		=> $sender_id,
					'date_sent'			=> cb_core_current_date(),
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
 * @package ConfettiBits
 * @subpackage AJAX
 * @since 2.1.1
 */
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
add_action('wp_ajax_cb_send_bits', 'cb_ajax_send_bits');