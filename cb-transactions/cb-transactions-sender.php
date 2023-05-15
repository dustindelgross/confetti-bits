<?php
defined('ABSPATH') || exit;

/*
function cb_send_bits_form_handler() {

	if ( ! bp_is_post_request() || 
		! cb_is_confetti_bits_component() ||
		! isset( $_POST['cb_transaction_send_bits'] ) ) {
		return false;
	}

	$redirect_to = '';
	$feedback    = '';
	$success     = false;
	$sender_name = "";
	$recipient_name = "";
	$log_entry = "";

	if ( empty( $_POST['cb_transactions_log_entry'] ) || empty( $_POST['cb_transactions_amount'] ) ) {

		$success = false;

		if ( empty( $_POST['cb_transactions_log_entry'] ) ) {

			$feedback = __('Your transaction was not sent. Please add a log entry.', 'confetti-bits');

		} else {

			$feedback = __('Your transaction was not sent. Please enter an amount.', 'confetti-bits');

		}

	} else if ( ( abs( $_POST['cb_transactions_amount'] ) > cb_get_users_transfer_balance( $_POST['cb_transactions_recipient_id'] ) ) && ( $_POST['cb_transactions_amount'] < 0 ) ) {
		$success     = false;
		$feedback = __( bp_xprofile_get_member_display_name( $_POST['cb_transactions_recipient_id'] ) . ' doesn\'t have enough Confetti Bits for that.', 'confetti-bits' );

	} else if ( $_POST['cb_transactions_amount'] + cb_get_total_for_current_day() > 20 ) {

		$success     = false;
		$feedback = __('This will put you over the 20 Confetti Bits per day limit. Your counter will reset tomorrow!', 'confetti-bits');

	} else if ( ( $_POST['cb_transactions_amount'] > cb_get_users_transfer_balance( $_POST['cb_transactions_sender_id'] ) ) && ( !cb_is_user_admin() || cb_is_user_site_admin() ) ) {

		$success     = false;
		$feedback = __('Sorry, it looks like you don\'t have enough bits to send.', 'confetti-bits');

	} else {

		$member_transactions = trailingslashit( bp_loggedin_user_domain() . cb_get_transactions_slug() );

		if ( cb_is_user_admin() && ! cb_is_user_site_admin() ) {

			$sender_name = bp_core_get_user_displayname($_POST['cb_transactions_sender_id']);
			$recipient_name = bp_core_get_user_displayname( $_POST['cb_transactions_recipient_id'] );
			$log_entry = str_replace("\\", '', $_POST['cb_transactions_log_entry']);

			$send = cb_send_bits(
				array(
					'item_id'			=> $_POST['cb_transactions_recipient_id'],
					'secondary_item_id'	=> $_POST['cb_transactions_amount'],
					'user_id'			=> bp_current_user_id(),
					'sender_id'			=> bp_current_user_id(),
					'sender_name'		=> bp_get_loggedin_user_fullname(),
					'recipient_id' 		=> $_POST['cb_transactions_recipient_id'],
					'recipient_name'	=> $recipient_name,
					'identifier'		=> $_POST['cb_transactions_recipient_id'],
					'date_sent'			=> bp_core_current_time( false ),
					'log_entry'    		=> "{$log_entry} - from {$sender_name}",
					'component_name'    => 'confetti_bits',
					'component_action'  => 'cb_send_bits',
					'amount'    		=> $_POST['cb_transactions_amount'],
					'error_type' 		=> 'wp_error',
				)
			);

			if ( true === is_int( $send ) ) {
				$success     = true;
				$feedback    = __(
					"We successfully sent bits to {$recipient_name}!",
					'confetti-bits'
				);

				$activity_args = array(
					"content"	=> "<p style='margin-top:1.25rem;'><strong>{$sender_name}</strong> just sent leadership bits to <strong>{$recipient_name}</strong> for:</p>
					<p><b>'{$log_entry}'</b></p>",
					"type"		=> "activity_update",
					"component"	=> "confetti_bits",
					"user_id"	=> $_POST['cb_transactions_sender_id']
				);

				bp_activity_add($activity_args);

				$redirect_to = trailingslashit($member_transactions) . '#cb-send-bits';

			} else {

				$success  = false;
				$feedback = 'Something\'s broken, call Dustin.';

			}
		} else {

			$send = cb_send_bits(
				array(
					'item_id'			=> $_POST['cb_transactions_recipient_id'],
					'secondary_item_id'	=> $_POST['cb_transactions_amount'],
					'user_id'			=> bp_current_user_id(),
					'sender_id'			=> bp_current_user_id(),
					'sender_name'		=> bp_get_loggedin_user_fullname(),
					'recipient_id' 		=> $_POST['cb_transactions_recipient_id'],
					'recipient_name'	=> bp_core_get_user_displayname($_POST['cb_transactions_recipient_id']),
					'identifier'		=> $_POST['cb_transactions_recipient_id'],
					'date_sent'			=> bp_core_current_time( false ),
					'log_entry'			=> $_POST['cb_transactions_log_entry'] . ' - from ' .
					bp_core_get_user_displayname($_POST['cb_transactions_sender_id']),
					'component_name'    => 'confetti_bits',
					'component_action'  => 'cb_transfer_bits',
					'amount'    		=> $_POST['cb_transactions_amount'],
					'error_type' 		=> 'wp_error',
				)
			);

			$subtract = cb_send_bits(
				array(
					'item_id'			=> $_POST['cb_transactions_sender_id'],
					'secondary_item_id'	=> $_POST['cb_transactions_amount'],
					'user_id'			=> bp_current_user_id(),
					'sender_id'			=> bp_current_user_id(),
					'sender_name'		=> bp_get_loggedin_user_fullname(),
					'recipient_id' 		=> $_POST['cb_transactions_sender_id'],
					'recipient_name'	=> bp_core_get_user_displayname($_POST['cb_transactions_sender_id']),
					'identifier'		=> $_POST['cb_transactions_sender_id'],
					'date_sent'			=> bp_core_current_time( false ),
					'log_entry'    		=> 'Sent bits to ' . bp_core_get_user_displayname($_POST['cb_transactions_recipient_id']),
					'component_name'    => 'confetti_bits',
					'component_action'  => 'cb_transfer_bits',
					'amount'    		=> -$_POST['cb_transactions_amount'],
					'error_type' 		=> 'wp_error',
				)
			);

			if (true === is_int($send) && true === is_int($subtract)) {
				$success     = true;
				$feedback    = __(
					'We successfully sent bits to ' .
					bp_core_get_user_displayname($_POST['cb_transactions_recipient_id']) .
					'!',
					'confetti-bits'
				);

				$redirect_to = trailingslashit($member_transactions) . '#cb-send-bits';
			} else {
				$success  = false;
				$feedback = 'Something\'s broken. Call Dustin.';
			}
		}
	}

	if ( ! empty( $feedback ) ) {

		$type = (true === $success)
			? 'success'
			: 'error';

		bp_core_add_message($feedback, $type);
	}

	if (!empty($redirect_to)) {
		bp_core_redirect($redirect_to);
	}
}*/
// add_action('bp_actions', 'cb_send_bits_form_handler');

function cb_send_bits($args = '') {

	$r = wp_parse_args($args, array(
		'item_id'           => 0,
		'secondary_item_id' => 0,
		'sender_id'         => 0,
		'recipient_id'		=> 0,
		'date_sent'			=> '',
		'log_entry'			=> '',
		'component_name'    => '',
		'component_action'  => '',
		'date_sent'     	=> bp_core_current_time( false ),
		'amount'			=> 0,
		'error_type'		=> 'bool',
	));

	if ( empty($r['sender_id'] ) || empty( $r['log_entry'] ) ) {

		if ( 'wp_error' === $r['error_type'] ) {

			if ( empty( $r['sender_id'] ) ) {

				$error_code = 'transactions_empty_sender_id';
				$feedback   = __('Your transaction was not sent. We couldn\'t find a sender.', 'confetti-bits');

			} else {

				$error_code = 'transactions_empty_log_entry';
				$feedback   = __('Your transaction was not sent. Please add a log entry.', 'confetti-bits');

			}

			return new WP_Error( $error_code, $feedback );

		} else {

			return false;

		}
	}

	if ( empty( $r['recipient_id'] ) ) {

		if ( 'wp_error' === $r['error_type'] ) {

				$error_code = 'transactions_empty_recipient_id';
				$feedback   = __('Your bits were not sent. We couldn\'t find the recipient.', 'confetti-bits');

			return new WP_Error( $error_code, $feedback );

		} else {

			return false;

		}
	}

	if ( empty( $r['amount'] ) ) {
		if ( 'wp_error' === $r['error_type'] ) {

			$error_code = 'transactions_empty_amount';
			$feedback   = __('Your bits were not sent. Please enter a valid amount.', 'confetti-bits');

			return new WP_Error($error_code, $feedback);

		} else {

			return false;

		}
	}

	if ( abs( $r['amount'] ) > cb_transactions_get_transfer_balance( $r['sender_id'] ) && ( $r['amount'] < 0 ) && !cb_is_user_site_admin() ) {

		if ('wp_error' === $r['error_type']) {

			$error_code = 'transactions_not_enough_bits';
			$feedback   = __('Sorry, it looks like you don\'t have enough bits for that.', 'confetti-bits');

			return new WP_Error($error_code, $feedback);

		} else {

			return false;

		}

	}

	$transaction = new CB_Transactions_Transaction();
	$transaction->item_id 				= $r['item_id'];
	$transaction->secondary_item_id		= $r['secondary_item_id'];
	$transaction->sender_id				= $r['sender_id'];
	$transaction->recipient_id			= $r['recipient_id'];
	$transaction->date_sent				= $r['date_sent'];
	$transaction->log_entry				= $r['log_entry'];
	$transaction->component_name		= $r['component_name'];
	$transaction->component_action		= $r['component_action'];
	$transaction->amount				= $r['amount'];

	$send = $transaction->send_bits();

	if ( false === is_int( $send ) ) {

		if ( 'wp_error' === $r['error_type'] ) {

			if ( is_wp_error( $send ) ) {

				return $send;

			} else {

				return new WP_Error(
					'transaction_generic_error',
					__(
						'Bits were not sent. Please try again.',
						'confetti-bits'
					)
				);

			}
		}

		return false;
	}

	do_action( 'cb_send_bits', $r );

	return $transaction->id;

}

function cb_remove_bits( $id, $reassign, $user ) {

	$transaction = new CB_Transactions_Transaction();
	$sender_id = get_current_user_id();
	$sender_name = bp_core_get_user_displayname($sender_id);
	$recipient_name = bp_core_get_user_displayname($id);

	$send = cb_send_bits(
		array(
			'item_id'			=> $id,
			'secondary_item_id'	=> $sender_id,
			'user_id'			=> $id,
			'sender_id'			=> $sender_id,
			'sender_name'		=> $sender_name,
			'recipient_id' 		=> $id,
			'recipient_name'	=> $recipient_name,
			'identifier'		=> $id,
			'date_sent'			=> bp_core_current_time( false ),
			'log_entry'			=> 'User Removed - from ' .
			$sender_name,
			'component_name'    => 'confetti_bits',
			'component_action'  => 'cb_removal_bits',
			'amount'    		=> -379,
			'error_type' 		=> 'wp_error',
		)
	);

	bp_core_add_message(
		var_dump($send),
		'updated'
	);

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
 * @since 2.2.1
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

	$send = cb_send_bits(
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
			$subtract = cb_send_bits(
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