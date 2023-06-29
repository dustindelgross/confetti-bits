<?php
defined('ABSPATH') || exit;

/**
 * CB Transactions Send Bits
 * 
 * Manages sending bits between users... I kinda want to
 * get rid of this tho.
 * 
 * @param array $args An array of arguments that
 * get merged into a set of default values. { 
 * 
 *   @var int $item_id The item ID associated with 
 *     the transaction. Used with BuddyBoss's 
 *     Notifications API to help format some
 *     dynamic information in the notifications. 
 *     We use the sender_id for this.
 * 
 *   @var int $secondary_item_id The secondary
 *     item ID associated with the transaction. Used with
 *     BuddyBoss's Notifications API to help format some
 *     dynamic information in the notifications. 
 *     We use the recipient_id for this.
 * 
 *   @var int $sender_id The ID of the user 
 *     sending the bits.
 * 
 *   @var int $recipient_id The ID of the user
 *     recieving the bits.
 * 
 *   @var datetime $date_sent The date and
 *     time of the transaction.
 * 
 *   @var string $log_entry A note that usually 
 *     references the purpose for the transaction.
 * 
 *   @TODO: Make log entries optional? Oof.
 * 
 *   @var string $component_name The name 
 *     associated with the component that is sending
 *     the bits. Used with BuddyBoss's Notifications
 *     API. This will almost always just be 
 *     'confetti_bits'.
 * 
 *   @var string $component_action The action
 *     associated with the transaction. We use this
 *     to differentiate transaction types to easily
 *     categorize them and run calculations. It
 *     is also used with BuddyBoss's Notifications
 *     API to send certain notifications that are 
 *     associated with certain actions.
 * }
 * 
 * @package Confetti_Bits
 * @subpackage Transactions
 * @since 1.0.0
 */
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
		'date_sent'     	=> cb_core_current_date(),
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

/**
 * CB Transactions Send Bits
 * 
 * Manages sending bits between users. Yikes.
 * 
 * @param array $args An array of arguments that
 * get merged into a set of default values. { 
 * 
 *   @var int $item_id The item ID associated with 
 *     the transaction. Used with BuddyBoss's 
 *     Notifications API to help format some
 *     dynamic information in the notifications. 
 *     We use the sender_id for this.
 * 
 *   @var int $secondary_item_id The secondary
 *     item ID associated with the transaction. Used with
 *     BuddyBoss's Notifications API to help format some
 *     dynamic information in the notifications. 
 *     We use the recipient_id for this.
 * 
 *   @var int $sender_id The ID of the user 
 *     sending the bits.
 * 
 *   @var int $recipient_id The ID of the user
 *     recieving the bits.
 * 
 *   @var datetime $date_sent The date and
 *     time of the transaction.
 * 
 *   @var string $log_entry A note that usually 
 *     references the purpose for the transaction.
 * 
 *   @TODO: Make log entries optional?
 * 
 *   @var string $component_name The name 
 *     associated with the component that is sending
 *     the bits. Used with BuddyBoss's Notifications
 *     API. This will almost always just be 
 *     'confetti_bits'.
 * 
 *   @var string $component_action The action
 *     associated with the transaction. We use this
 *     to differentiate transaction types to easily
 *     categorize them and run calculations. It
 *     is also used with BuddyBoss's Notifications
 *     API to send certain notifications that are 
 *     associated with certain actions.
 * }
 * 
 * @package Confetti_Bits
 * @subpackage Transactions
 * @since 2.3.0
 */
function cb_transactions_send_bits($args = array()) {

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

/**
 * CB Transactions Remove Bits
 * 
 * This is hooked into the delete_user action, so that 
 * transactions get deleted whenever a user is.
 * 
 * @TODO: Still need to implement this... Yikes. Also.
 * why send negative bits, instead of deleting from 
 * the DB? This program doesn't need analytics based
 * on that type of stuff, just delete from the DB.
 * 
 * @package ConfettiBits\Transactions
 * @since 2.3.0
 */
function cb_transactions_remove_bits( $id, $reassign, $user ) {

	$transaction = new CB_Transactions_Transaction();
	$sender_id = get_current_user_id();
	$sender_name = bp_core_get_user_displayname($sender_id);
	$recipient_name = bp_core_get_user_displayname($id);

	$send = cb_send_bits(
		array(
			'item_id'			=> $id,
			'secondary_item_id'	=> $sender_id,
			'sender_id'			=> $sender_id,
			'recipient_id' 		=> $id,
			'date_sent'			=> bp_core_current_time( false ),
			'log_entry'			=> 'User Removed - from ' .
			$sender_name,
			'component_name'    => 'confetti_bits',
			'component_action'  => 'cb_removal_bits',
			'amount'    		=> 0,
			'error_type' 		=> 'wp_error',
		)
	);

	bp_core_add_message(
		var_dump($send),
		'updated'
	);

}

