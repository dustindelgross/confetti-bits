<?php
defined('ABSPATH') || exit;
function cb_send_bits_form_handler() {

	if ( ! bp_is_post_request() || 
		! cb_is_confetti_bits_component() ||
		! isset( $_POST['cb_send_bits'] ) ) {
		return false;
	}

	$redirect_to = '';
	$feedback    = '';
	$success     = false;

	if ( empty( $_POST['log_entry'] ) || empty( $_POST['amount'] ) ) {

		$success = false;

		if ( empty( $_POST['log_entry'] ) ) {

			$feedback = __('Your transaction was not sent. Please add a log entry.', 'confetti-bits');

		} else {

			$feedback = __('Your transaction was not sent. Please enter an amount.', 'confetti-bits');

		}

	} else if ( ( abs( $_POST['amount'] ) > cb_get_users_transfer_balance( $_POST['recipient_id'] ) ) && ( $_POST['amount'] < 0 ) ) {
		$success     = false;
		$feedback = __( bp_xprofile_get_member_display_name( $_POST['recipient_id'] ) . ' doesn\'t have enough Confetti Bits for that.', 'confetti-bits' );

	} else if ( $_POST['amount'] + cb_get_total_for_current_day() > 20 ) {

		$success     = false;
		$feedback = __('This will put you over the 20 Confetti Bits per day limit. Your counter will reset tomorrow!', 'confetti-bits');

	} else if ( ( $_POST['amount'] > cb_get_users_transfer_balance( $_POST['sender_id'] ) ) && ( !cb_is_user_admin() || cb_is_user_site_admin() ) ) {

		$success     = false;
		$feedback = __('Sorry, it looks like you don\'t have enough bits to send.', 'confetti-bits');

	} else {

		$member_transactions = trailingslashit( bp_loggedin_user_domain() . cb_get_transactions_slug() );

		if ( cb_is_user_admin() && ! cb_is_user_site_admin() ) {

			$send = cb_send_bits(
				array(
					'item_id'			=> $_POST['recipient_id'],
					'secondary_item_id'	=> $_POST['amount'],
					'user_id'			=> bp_current_user_id(),
					'sender_id'			=> bp_current_user_id(),
					'sender_name'		=> bp_get_loggedin_user_fullname(),
					'recipient_id' 		=> $_POST['recipient_id'],
					'recipient_name'	=> bp_xprofile_get_member_display_name( $_POST['recipient_id'] ),
					'identifier'		=> $_POST['recipient_id'],
					'date_sent'			=> bp_core_current_time( false ),
					'log_entry'    		=> str_replace("\\", '', $_POST['log_entry']) . ' - from ' .
					bp_core_get_user_displayname($_POST['sender_id']),
					'component_name'    => 'confetti_bits',
					'component_action'  => 'cb_send_bits',
					'amount'    		=> $_POST['amount'],
					'error_type' 		=> 'wp_error',
				)
			);

			if ( true === is_int( $send ) ) {
				$success     = true;
				$feedback    = __(
					'We successfully sent bits to ' .
					bp_core_get_user_displayname( $_POST['recipient_id'] ) .
					'!',
					'confetti-bits'
				);

				$redirect_to = trailingslashit($member_transactions) . '#cb-send-bits';

			} else {

				$success  = false;
				$feedback = 'Something\'s broken, call Dustin.';

			}
		} else {

			$send = cb_send_bits(
				array(
					'item_id'			=> $_POST['recipient_id'],
					'secondary_item_id'	=> $_POST['amount'],
					'user_id'			=> bp_current_user_id(),
					'sender_id'			=> bp_current_user_id(),
					'sender_name'		=> bp_get_loggedin_user_fullname(),
					'recipient_id' 		=> $_POST['recipient_id'],
					'recipient_name'	=> bp_core_get_user_displayname($_POST['recipient_id']),
					'identifier'		=> $_POST['recipient_id'],
					'date_sent'			=> bp_core_current_time( false ),
					'log_entry'			=> $_POST['log_entry'] . ' - from ' .
					bp_core_get_user_displayname($_POST['sender_id']),
					'component_name'    => 'confetti_bits',
					'component_action'  => 'cb_transfer_bits',
					'amount'    		=> $_POST['amount'],
					'error_type' 		=> 'wp_error',
				)
			);

			$subtract = cb_send_bits(
				array(
					'item_id'			=> $_POST['sender_id'],
					'secondary_item_id'	=> $_POST['amount'],
					'user_id'			=> bp_current_user_id(),
					'sender_id'			=> bp_current_user_id(),
					'sender_name'		=> bp_get_loggedin_user_fullname(),
					'recipient_id' 		=> $_POST['sender_id'],
					'recipient_name'	=> bp_core_get_user_displayname($_POST['sender_id']),
					'identifier'		=> $_POST['sender_id'],
					'date_sent'			=> bp_core_current_time( false ),
					'log_entry'    		=> 'Sent bits to ' . bp_core_get_user_displayname($_POST['recipient_id']),
					'component_name'    => 'confetti_bits',
					'component_action'  => 'cb_transfer_bits',
					'amount'    		=> -$_POST['amount'],
					'error_type' 		=> 'wp_error',
				)
			);

			if (true === is_int($send) && true === is_int($subtract)) {
				$success     = true;
				$feedback    = __(
					'We successfully sent bits to ' .
					bp_core_get_user_displayname($_POST['recipient_id']) .
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
}
add_action('bp_actions', 'cb_send_bits_form_handler');

function cb_send_bits($args = '') {

	$r = wp_parse_args($args, array(
		'item_id'           => 0,
		'secondary_item_id' => 0,
		'user_id'			=> 0,
		'sender_id'         => 0,
		'sender_name'		=> '',
		'recipient_id'		=> 0,
		'recipient_name'	=> '',
		'identifier'		=> 0,
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

	if ( empty( $r['recipient_id'] ) || empty( $r['recipient_name'] ) ) {

		if ( 'wp_error' === $r['error_type'] ) {

			if ( empty( $r['recipient_name'] ) ) {

				$error_code = 'transactions_empty_recipient_name';
				$feedback   = __('Your bits were not sent. We couldn\'t find the recipient.', 'confetti-bits');

			} else {

				$error_code = 'transactions_empty_recipient_id';
				$feedback   = __('Your bits were not sent. We couldn\'t find the recipient.', 'confetti-bits');

			}

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

	if ( abs( $r['amount'] ) > cb_get_total_bits( $r['sender_id'] ) && ( $r['amount'] < 0 ) && !cb_is_user_site_admin() ) {

		if ('wp_error' === $r['error_type']) {

			$error_code = 'transactions_not_enough_bits';
			$feedback   = __('Sorry, it looks like you don\'t have enough bits for that.', 'confetti-bits');

			return new WP_Error($error_code, $feedback);

		} else {

			return false;

		}

	}

	$transaction = new Confetti_Bits_Transactions_Transaction();
	$transaction->item_id 				= $r['item_id'];
	$transaction->secondary_item_id		= $r['secondary_item_id'];
	$transaction->user_id				= $r['user_id'];
	$transaction->sender_id				= $r['sender_id'];
	$transaction->sender_name			= $r['sender_name'];
	$transaction->recipient_id			= $r['recipient_id'];
	$transaction->recipient_name		= $r['recipient_name'];
	$transaction->identifier			= $r['identifier'];
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
	
	$transaction = new Confetti_Bits_Transactions_Transaction();
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
// add_action( 'bp_actions', 'cb_remove_bits' );