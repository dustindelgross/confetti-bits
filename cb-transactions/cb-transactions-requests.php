<?php 
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * CB Requests
 * 
 * In the future we ~~will set this via admin panel settings~~ (get
 * rid of this completely). This is for processing when a user
 * cashes in their Confetti Bits.
 * 
 * @package Confetti_Bits
 * @subpackage Transactions
 * @since 1.1.0
 */
function cb_requests() {

	if (! cb_is_confetti_bits_component() || 
		! isset( $_POST['cb_send_bits_request'] ) ) {
		return false;
	}

	$redirect_to = '';
	$feedback    = '';
	$success     = false;
	$user_id = get_current_user_id();
	

	if ( empty( $_POST['cb_request_option'] ) || empty( $_POST['cb_request_amount'] ) ) {

		$success = false;
		$feedback = __('The request was not sent. Please select a request option.', 'confetti-bits');

	}
	
	$amount = intval( $_POST['cb_request_amount'] );
	$cb = Confetti_Bits();
	
	if ( abs( $amount ) > cb_transactions_get_request_balance( $user_id ) ) {

		$success     = false;
		$feedback = __('Sorry, but you don\'t have enough Confetti Bits for that.', 'confetti-bits');

	} else {

		$member_transactions = $cb->page;
		
		$subtract = cb_send_request(
			array(
				'item_id'			=> $user_id,
				'secondary_item_id'	=> $user_id,
				'sender_id'			=> $user_id,
				'recipient_id' 		=> $user_id,
				'date_sent'			=> cb_core_current_date(),
				'log_entry'    		=> str_replace( "\\", '', $_POST['cb_request_option'] ),
				'component_name'    => 'confetti_bits',
				'component_action'  => 'cb_bits_request',
				'amount'    		=> -$_POST['cb_request_amount'],
				'error_type' 		=> 'wp_error',
			)
		);

		if ( true === is_int( $subtract ) ) {
			$success     = true;
			$feedback    = __(
				'Request received! Your request should be fulfilled within 4-6 weeks.',
				'confetti-bits'
			);

			$redirect_to = trailingslashit( $member_transactions );
		} else {

			$success  = false;
			$feedback = 'Something went wonky. Call Dustin!';

		}
	}

	if ( ! empty( $feedback ) ) {

		$type = (true === $success)
			? 'success'
			: 'error';

		bp_core_add_message($feedback, $type);
	}

	if ( !empty( $redirect_to ) ) {
		bp_core_redirect( $redirect_to );
	}

}
add_action('bp_actions', 'cb_requests');

function cb_send_request($args = '') {

	$r = wp_parse_args($args, array(
		'item_id'           => 0,
		'secondary_item_id' => 0,
		'sender_id'         => 0,
		'recipient_id'		=> 0,
		'date_sent'			=> '',
		'log_entry'			=> '',
		'component_name'    => '',
		'component_action'  => '',
		'date_sent'     	=> cb_core_current_date(),
		'amount'			=> 0,
		'error_type'		=> 'bool',
	));

	if ( empty($r['sender_id'] ) || empty( $r['log_entry'] ) ) {
		if ('wp_error' === $r['error_type']) {
			if ( empty($r['sender_id'] ) ) {
				$error_code = 'transactions_empty_sender_id';
				$feedback   = __('Your transaction was not sent. We couldn\'t find a sender.', 'confetti-bits');
			} else {
				$error_code = 'transactions_empty_log_entry';
				$feedback   = __('Your transaction was not sent, please add a log entry.', 'confetti-bits');
			}

			return new WP_Error($error_code, $feedback);
		} else {

			return false;
		}
	}

	if ( empty($r['recipient_id'] ) ) {
		if ( 'wp_error' === $r['error_type'] ) {

				$error_code = 'transactions_empty_recipient_id';
				$feedback   = __('Your bits were not sent. We couldn\'t find the recipient.', 'confetti-bits');

			return new WP_Error( $error_code, $feedback );

		} else {
			return false;
		}
	}

	if ( empty($r['amount'] ) ) {
		if ( 'wp_error' === $r['error_type'] ) {

			$error_code = 'transactions_empty_amount';
			$feedback   = __( 'Your bits were not sent. Please enter a valid amount.', 'confetti-bits' );

			return new WP_Error( $error_code, $feedback );
		} else {
			return false;
		}
	}

	if ( abs( $r['amount'] ) > cb_transactions_get_request_balance( $r['recipient_id'] ) && ( $r['amount'] < 0) ) {
		if ( 'wp_error' === $r['error_type'] ) {

			$error_code = 'transactions_not_enough_bits';
			$feedback   = __('Sorry, it looks like you don\'t have enough bits for that.', 'confetti-bits');

			return new WP_Error( $error_code, $feedback );
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
			if ( is_wp_error($send) ) {
				return $send;
			} else {
				return new WP_Error(
					'transaction_generic_error',
					__(
						'Your bits were not sent. Please try again.',
						'confetti-bits'
					)
				);
			}
		}

		return false;
	}

	do_action('cb_request_bits', $r);

	return $transaction->id;
}