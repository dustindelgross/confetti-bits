<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
/**
 * CB Participation Functions
 * 
 * These are going to be all of our CRUD functions for 
 * the participation component.
 * 
 * @package ConfettiBits\Participation
 * @since 2.2.0
 */

/**
 * CB Participation Create Participation
 * 
 * Creates a new participation object and saves it to the database.
 * 
 * @param	array	$args	Associative array of arguments for saving.
 * 							All arguments are optional except for media_filepath. {
 * 		@type	int		$item_id			For BuddyBoss Platform's Notifications API.
 * 											Registers the primary data for the notification.
 * 											We'll use the applicant_id for this.
 * 											----------
 * 											@TODO: We need to make sure this is correct.
 * 
 * 		@type	int		$secondary_item_id	For BuddyBoss Platform's Notifications API.
 * 											Register's the receiver's profile avatar in the notification.
 * 											We'll use the admin_id for this.
 * 											----------
 * 											@TODO: We need to make sure this is correct.
 * 
 * 		@type	int		$applicant_id		The user_id associated with the participation entry.
 * 		@type	int		$admin_id			The user_id of the last admin that modified the entry.
 * 		@type	string	$date_created		A mysql datetime entry for when the participation was registered.
 * 		@type	string	$date_modified		A mysql datetime entry for when the participation was registered.
 * 		@type	string	$event_type			The type of event being registered.
 * 											We typically use these event_types: {  
 * 												"dress_up", 
 * 												"food", 
 * 												"holiday", 
 * 												"activity",
 * 												"awareness",
 * 												"meeting",
 * 												"workshop",
 * 												"contest",
 * 												"other"
 * 											}
 * 		@type	string	$component_name		For BuddyBoss Platform's Notifications API.
 * 											Helps the API know which notification group to use.
 * 		@type	string	$component_action	For BuddyBoss Platform's Notifications API.
 * 											Helps the API know which notification format to use.
 * 		@type	string	$status				The status of the participation entry.
 * 											Common statuses include "new", "approved", "denied", or "pending".
 * }
 * 
 * @package ConfettiBits\Participation
 * @since 2.2.0
 */
function cb_participation_new_participation( array $args ) {

	$r = wp_parse_args(
		$args,
		array(
			'item_id'			=> get_current_user_id(),
			'secondary_item_id'	=> 0,
			'applicant_id'		=> get_current_user_id(),
			'admin_id'			=> 0,
			'date_created'		=> current_time('mysql'),
			'date_modified'		=> current_time('mysql'),
			'event_date'		=> current_time('mysql'),
			'event_type'		=> 'other',
			'event_note'		=> '',
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_participation',
			'status'			=> 'new',
			'transaction_id'	=> 0
		)
	);

	$participation = new CB_Participation_Participation();
	$participation->item_id				= $r['item_id'];
	$participation->secondary_item_id	= $r['secondary_item_id'];
	$participation->applicant_id		= $r['applicant_id'];
	$participation->admin_id			= $r['admin_id'];
	$participation->date_created		= $r['date_created'];
	$participation->date_modified		= $r['date_modified'];
	$participation->event_type			= $r['event_type'];
	$participation->event_date			= $r['event_date'];
	$participation->event_note			= $r['event_note'];
	$participation->status				= $r['status'];
	$participation->component_name		= $r['component_name'];
	$participation->component_action	= $r['component_action'];
	$participation->transaction_id		= $r['transaction_id'];

	$request = $participation->save();

	if ( false === is_int($request) ) {
		return false;
	}

	return $participation->id;

}

/**
 * Confetti Bits Update Request Status
 * 
 * Update the request status for a participation entry.
 * 
 * @package ConfettiBits\Participation
 * @since 2.2.0
 */
function cb_participation_update_request_status( $id = 0, $admin_id = 0, $date_modified = '', $status = '', $transaction_id = 0 ) {

	if ( $id === 0 || $admin_id === 0 || $date_modified === '' || $status === '' ) {
		return;
	}

	if ( $status !== 'approved' && $status !== 'denied' ) {
		return;
	}

	$participation = new CB_Participation_Participation($id);
	if ( ! is_wp_error( $participation ) ) {
		return 	$participation->update(
			array(
				'admin_id'			=> $admin_id,
				'secondary_item_id'	=> $admin_id,
				'applicant_id'		=> $participation->applicant_id,
				'event_note'		=> $participation->event_note,
				'event_type'		=> $participation->event_type,
				'date_modified'		=> $date_modified,
				'component_action'	=> 'cb_participation_status_update',
				'status'			=> $status,
				'transaction_id'	=> $transaction_id
			),
			array(
				'id'			=> $id
			)
		);
	}
}

/**
 * CB Participation Update Handler
 * 
 * We're probably going to deprecate all over this bad boy as
 * we continue to transition everything to async.
 * 
 * Update: we did indeed do that. B)
 * 
 * @package ConfettiBits\Participation
 * @since 2.1.0
 */
function cb_participation_update_handler() {

	if ( ! cb_is_post_request() || 
		! cb_is_confetti_bits_component() || 
		! cb_is_user_participation_admin() ||
		! wp_verify_nonce( $_POST['cb_participation_admin_nonce'], 'cb_participation_admin_post' )
	   ) {
		return;
	}

	$success = false;
	$feedback = '';
	$redirect_to = bp_loggedin_user_domain() . cb_get_transactions_slug() . '/#cb-participation-admin';

	if ( ! isset( 
		$_POST['cb_participation_admin_id'], 
		$_POST['cb_participation_id'], 
		$_POST['cb_participation_approval_status']
	) ) {
		$feedback = 'Update invalid. Please try again.';
	} else {

		$admin_id = intval( $_POST['cb_participation_admin_id'] );
		$participation_id = intval( $_POST['cb_participation_id'] );
		$status				= trim( $_POST['cb_participation_approval_status'] );
		$transaction_id		= intval( $_POST['cb_participation_transaction_id'] );
		$admin_log_entry	= sanitize_text_field( $_POST['cb_participation_admin_log_entry'] );
		$amount_override	= intval( $_POST['cb_participation_amount_override'] );
		$participation = new Confetti_Bits_Participation_Participation( $participation_id );
		$amount = 0;
		$log_entry = '';
		$new_transaction = 0;

		if ( $admin_id == $participation->applicant_id && ! cb_is_user_site_admin() ) {
			$feedback = "Update unsuccessful. Cannot self-approve culture participation.";
		} else if ( $status === $participation->status ) {
			$feedback = "Update unsuccessful. Status already marked as {$status}.";
		} else {

			$amount = cb_participation_get_amount( 
				$transaction_id, 
				$participation->event_type, 
				$participation->status, 
				$status, 
				$amount_override
			);

			$log_entry = cb_participation_get_log_entry( $participation_id, $admin_log_entry );
			/*
			if ( $log_entry !== $participation->event_note ) {
				$log_entry += " | {$participation->event_note}";
			}
*/
			// Create a transaction if we can.
			if ( $amount !== 0 && $log_entry !== '' ) {

				$modified = current_time('mysql');
				$new_transaction = cb_participation_new_transaction( 
					array(
						'transaction_id'	=> $transaction_id,
						'participation_id'	=> $participation_id,
						'admin_id'			=> $admin_id,
						'modified'			=> $modified,
						'status'			=> $status,
						'amount'			=> $amount,
						'log_entry'			=> $log_entry
					)
				);

				if ( is_int( $new_transaction ) ) {

					$success = true;
					$feedback = "Update successful. Transaction ID: {$new_transaction}";

				} else {
					$feedback = "Update failed. Processing error 3050. Error processing Confetti Bits transaction. {$new_transaction}";
				}
			}

			cb_participation_update_request_status( 
				$participation_id, 
				$admin_id, 
				$modified, 
				$status, 
				$log_entry,
				$new_transaction
			);
		}
	}

	if ( ! empty( $feedback ) ) {
		$type = (true === $success) ? 'success' : 'error';
		bp_core_add_message($feedback, $type);
	}

	if ( !empty( $redirect_to ) ) {
		bp_core_redirect( $redirect_to );
	}

}

/**
 * Confetti Bits Participation New Transaction
 * 
 * Add or subtract Confetti Bits based on participation approval status.
 * We're probably going to deprecate all over this bad boy as we continue
 * to transition everything to async.
 * 
 * @param array $args An array of parameters for us to work with. { 
 * 		@type int 		$transaction_id		A transaction ID for us to check for.
 * 		@type int 		$participation_id	A participation ID for us to check for.
 * 		@type int 		$admin_id			An admin ID for us to check for.
 * 		@type string	$status 			The status of the participation entry.
 * 		@type string	$modified 			The date of the last modification 
 * 												for the participation entry.
 * 		@type int		$amount				An amount to check for in the transaction.
 * }
 * 
 * 
 * @return int|string Transaction ID on success, error on failure.
 * 
 * @package ConfettiBits\Participation
 * @since 2.1.0
 */
function cb_participation_new_transaction( $args = array() ) {

	$r = wp_parse_args(
		$args,
		array(
			'transaction_id'	=> 0,
			'participation_id'	=> 0,
			'admin_id'			=> 0,
			'modified'			=> '',
			'status'			=> '',
			'amount'			=> 0,
			'log_entry'			=> ''
		)
	);

	$success = false;
	$feedback = '';

	if ( $r['participation_id'] === 0 || 
		$r['modified'] === '' || 
		$r['status'] === '' || 
		$r['admin_id'] === 0 ||
		$r['amount'] === 0
	   ) {
		$feedback = "One of the following parameters is missing: Participation ID, date modified, status, admin ID, amount.";
	}

	$participation = new CB_Participation_Participation( $r['participation_id'] );
	$admin_name = bp_core_get_user_displayname( $r['admin_id'] );
	$log_entry = $r['log_entry'];
	$amount = $r['amount'];

	if ( ! empty( $participation->event_date ) ) {
		$event_date = date_format( date_create( $participation->event_date ), 'm/d/Y' );
	} else {
		$event_date = date_format( date_create( $r['modified'] ), 'm/d/Y' );
	}

	if ( empty( $participation->event_type ) ) {
		$feedback = 'Event type not found.';
	} else if ( $r['amount'] === 0 ) {
		$feedback = "Invalid or undefined amount.";
	} else if ( $r['log_entry'] === '' ) {
		$feedback = "Invalid or undefined log entry.";
	} else {

		$log_entry .= " on {$event_date} - from {$admin_name}";

		$transaction = new CB_Transactions_Transaction();

		$transaction->item_id			= $participation->applicant_id;
		$transaction->secondary_item_id	= $r['admin_id'];
		$transaction->sender_id			= $r['admin_id'];
		$transaction->recipient_id		= $participation->applicant_id;
		$transaction->date_sent			= $r['modified'];
		$transaction->log_entry			= $log_entry;
		$transaction->component_name	= 'confetti_bits';
		$transaction->component_action	= 'cb_participation_status_update';
		$transaction->amount			= $amount;

		$send = $transaction->send_bits();
		$success = is_int( $send );

		$feedback = is_int( $send ) ? $send : "Processing Error 3060. Transaction failed.";
	}

	return $feedback;
}

/**
 * CB Participation Get Amount
 * 
 * Attempt to extract a predetermined value from valid event types.
 * 
 * @param int $transaction_id	The ID of the transaction (if any) associated
 * 								with the participation entry.
 * @param int $participation_id	The participation entry to check against
 * @param string $status		The status that we are updating to.
 * @param string $override		An amount override, typically submitted 
 * 								via form submission or API request.
 * 
 * @return int $amount The amount we extracted.
 * 
 * @package ConfettiBits\Participation
 * @since 2.1.0
 */
function cb_participation_get_amount( $transaction_id = 0, $participation_id = 0, $status = '', $override = 0 ) {

	// Prevent redundant submissions.
	if ( empty( $participation_id ) || $status === '' ) {
		return;
	}

	$participation = new CB_Participation_Participation( $participation_id );
	$transaction = new CB_Transactions_Transaction( $transaction_id );
	$amount = 0;

	// If this is the first time we're updating the status...
	// Only set an amount if it was approved
	if ( $participation->status === 'new' && $status === 'approved' ) {

		// If it's set in the override input, use that
		if ( $override !== 0 ) {
			return intval( $override );
			// Otherwise, try to format the amount based on event type
		} else {
			switch ( $participation->event_type ) {
				case 'holiday':
				case 'dress_up':
				case 'lunch':
				case 'awareness':
				case 'meeting':
				case 'activity':
					$amount	= 5;
					break;
				case 'workshop':
					$amount = 10;
					break;
				default:
					$amount = 0;
					break;
			}
		}
	}

	// If we've updated the status before, reverse the amount in the transaction.
	if ( $participation->status === 'denied' || $participation->status === 'approved' ) {
		$amount = - $transaction->amount;
	}

	return $amount;

}

/**
 * CB Participation Get Log Entry
 * 
 * Attempt to extract a predetermined log entry from valid event types.
 * 
 * @param int		$participation_id	The ID for the participation entry we're checking.
 * @param string	$admin_log_entry	An optional admin log entry override.
 * 
 * @return string	$log_entry The log entry we extracted.
 * 
 * @package ConfettiBits\Participation
 * @since 2.1.0
 */
function cb_participation_get_log_entry( $participation_id = 0, $admin_log_entry = '' ) {

	if ( $participation_id === 0 ) {
		return;
	}

	$log_entry = ''; 

	// Prioritize the admin post
	if ( $admin_log_entry !== '' ) {
		return ucwords( str_replace( '_', ' ', $admin_log_entry ) );
	}

	$participation = new CB_Participation_Participation( $participation_id );
	$log_entry = $participation->event_type;
	// Second priority, if the applicant supplied a note
	if ( $participation->event_note !== '' ) {
		return ucwords( str_replace( '_', ' ', $participation->event_note ) );
	}

	// Third priority, based on the event type
	switch ( $participation->event_type ) {
		case 'workshop':
			$log_entry	= "Amanda's Workshop";
			break;
		case 'holiday':	
			$log_entry	= "In-Office Holiday Event";
			break;
		case 'dress_up':
			$log_entry	= "Office Dress-Up Day";
			break;
		case 'lunch':
			$log_entry	= "In-Office Lunch Day";
			break;
		case 'awareness':
			$log_entry	= "Awareness Day";
			break;
		case 'meeting':
			$log_entry	= "Team CTG Monthly Meeting";
			break;
		case 'contest':
			$log_entry	= "Contest Placement";
			break;
		default:
			$log_entry	= "Unspecified participation entry.";
			break;
	}

	return $log_entry;

}

/**
 * CB Get Transaction
 * 
 * We need to check if a transaction exists for a given participation entry, 
 * and perform operations based on that result.
 * 
 * @param int $transaction_id The ID for the transaction we're looking for.
 * 
 * @return array|bool Transaction if the object exists, false if not or we get an error.
 * 
 * @package ConfettiBits\Participation
 * @since 2.1.0
 */
function cb_participation_get_transaction( $transaction_id = 0 ) {

	if ( $transaction_id === 0 ) {
		return false;
	}

	$transaction = new CB_Transactions_Transaction( $transaction_id );

	return ( ! empty( $transaction ) ) ? $transaction : false;

}

function cb_participation_new_notifications( $data = array() ) {

	$r = wp_parse_args(
		$data,
		array(
			'applicant_id'			=> 0,
			'admin_id'				=> 0,
			'component_action'		=> '',
			'event_note'			=> '',
			'status'				=> ''
		)
	);

	if ( 
		empty( $data ) ||
		empty( $r['applicant_id'] ) ||
		empty( $r['admin_id'] ) ||
		empty( $r['status'] ) ||
		empty( $r['component_action'] )
	) {
		return;
	}

	$item_id = 0;
	$secondary_item_id = 0;

	switch ( $r['component_action'] ) {

		case ( 'cb_participation_new' ) :
			$item_id = $r['admin_id'];
			$secondary_item_id = $r['applicant_id'];

			$unsubscribe_args = array(
				'user_id'           => $item_id,
				'notification_type' => 'cb-participation-new',
			);

			$email_args = array(
				'tokens' => array(
					'applicant.name' => bp_core_get_user_displayname( $secondary_item_id ),
					'participation.note' => $r['event_note'],
					'unsubscribe' => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
				),
			);

			bp_send_email( 'cb-participation-new', $item_id, $email_args );
			break;

	}

	bp_notifications_add_notification(
		array(
			'user_id'           => $item_id,
			'item_id'           => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'component_name'    => 'confetti_bits',
			'component_action'  => $r['component_action'],
			'date_notified'     => current_time( 'mysql', true ),
			'is_new'            => 1,
			'allow_duplicate'	=> true,
		)
	);

}
add_action( 'cb_participation_after_save', 'cb_participation_new_notifications' );

/**
 * CB Participation Update Notifications
 * 
 * This will notify a user after a participation entry 
 * has been updated to a new status.
 * 
 * @TODO: Add support for denied requests.
 * 
 * @package ConfettiBits\Participation
 * @since 2.2.0
 */
function cb_participation_update_notifications( $data = array() ) {

	$r = wp_parse_args(
		$data,
		array(
			'applicant_id'			=> 0,
			'admin_id'				=> 0,
			'component_action'		=> '',
			'event_note'			=> '',
			'status'				=> '',
			'event_type'			=> ''
		)
	);

	if ( 
		empty( $data ) ||
		empty( $r['applicant_id'] ) ||
		empty( $r['admin_id'] ) ||
		empty( $r['status'] ) ||
		empty( $r['component_action'] )
	) {
		return;
	}

	$item_id = 0;
	$secondary_item_id = 0;
	$event_type = ucwords( str_replace( '_', ' ', $r['event_type'] ) );
	$event_note = '';

	if ( ! empty( $r['event_note'] ) ) {
		$event_note = ucwords( str_replace( '_', ' ', $r['event_note'] ) );
	} else {
		$event_note = $event_type;
	}

	switch ( $r['component_action'] ) {

		case ( 'cb_participation_status_update' ) : 

			$item_id = $r['applicant_id'];
			$secondary_item_id = $r['admin_id'];


			/*
			if ( $r['status'] === 'denied' ) {
				$unsubscribe_args = array(
					'user_id'           => $item_id,
					'notification_type' => 'cb-participation-status-denied',
				);

				$email_args = array(
					'tokens' => array(
						'admin.name' => bp_core_get_user_displayname( $secondary_item_id ),
						'participation.status' => ucfirst( $r['status'] ),
						'participation.type' => $event_type,
						'participation.note' => $event_note,
						'unsubscribe' => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
					),
				);

				bp_send_email( 'cb-participation-status-denied', (int) $item_id, $email_args );
*/
			//			} else {

			$unsubscribe_args = array(
				'user_id'           => $item_id,
				'notification_type' => 'cb-participation-status-update',
			);

			$email_args = array(
				'tokens' => array(
					'admin.name' => bp_core_get_user_displayname( $secondary_item_id ),
					'participation.status' => ucfirst( $r['status'] ),
					'participation.note' => $event_note,
					'unsubscribe' => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
				),
			);

			bp_send_email( 'cb-participation-status-update', (int) $item_id, $email_args );

			//			}

			break;
	}

	bp_notifications_add_notification(
		array(
			'user_id'           => $item_id,
			'item_id'           => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'component_name'    => 'confetti_bits',
			'component_action'  => $r['component_action'],
			'date_notified'     => current_time( 'mysql', true ),
			'is_new'            => 1,
			'allow_duplicate'	=> false,
		)
	);

}
add_action( 'cb_participation_after_update', 'cb_participation_update_notifications' );