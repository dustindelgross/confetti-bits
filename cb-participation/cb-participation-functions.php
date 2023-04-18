<?php
/**
 * Confetti Bits Participation Functions
 * 
 * These are going to be all of our CRUD functions for 
 * the participation component.
 */
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** 
 * CB AJAX Participation Bulk Update
 * 
 * Processes bulk participation updates from an AJAX post request.
 */
function cb_ajax_update_participation() {
	if ( !isset( 
		$_POST['admin_id'], 
		$_POST['participation_id'],
		$_POST['status'],
		$_POST['transaction_id']
	) ) {
		return;
	}
	$feedback = array(
		'text' => "",
		'type' => 'error'
	);
	$participation_id = intval($_POST['participation_id']);
	$admin_id = intval($_POST['admin_id']);
	$status = $_POST['status'] === 'approved' ? 'approved' : 'denied';
	$participation = new Confetti_Bits_Participation_Participation($participation_id);
	if ( $participation->event_type === 'other' ) {
		$feedback['text'] = "Update unsuccessful. Cannot process 'Other' category event types using the Confetti Bits API.";
		echo json_encode($feedback);
		die();
	}

	$update_args = array(
		'status' => $status,
		'admin_id' => $admin_id,
		'date_modified' => current_time( 'mysql' ),
	);
	$where_args = array(
		'id' => $participation_id
	);

	$transaction_id		= intval( $_POST['transaction_id'] );
	$admin_log_entry	= sanitize_text_field( $_POST['log_entry'] );
	$amount_override	= intval( $_POST['amount_override'] );
	$admin_log_entry	= isset( $_POST['admin_log_entry'] ) 
		? sanitize_text_field( $_POST['admin_log_entry'] )
		: "";
	$amount = 0;
	$log_entry = '';
	$new_transaction = 0;

	if ( $admin_id == $participation->applicant_id && ! cb_is_user_site_admin() ) {
		$feedback['text'] = "Update unsuccessful. Cannot self-approve culture participation.";
		echo json_encode($feedback);
		die();
	} else if ( $status === $participation->status ) {
		$feedback['text'] = "Update unsuccessful. Status already marked as {$status}.";
		echo json_encode($feedback);
		die();
	} else {

		$amount = cb_participation_get_amount( 
			$transaction_id, 
			$participation->event_type, 
			$participation->status, 
			$status, 
			$amount_override
		);

		$log_entry = cb_participation_get_log_entry( $participation_id, $admin_log_entry );

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
				$feedback['text'] = "Update successful. Transaction ID: {$new_transaction}";
				$feedback['type'] = "success";

			} else {
				$feedback['text'] = "Update failed. Processing error 3050. Error processing Confetti Bits transaction. {$new_transaction}";
				echo json_encode($feedback);
				die();
			}
		}

		cb_participation_update_request_status( 
			$participation_id, 
			$admin_id, 
			$modified, 
			$status, 
			$new_transaction
		);
		
		echo json_encode($feedback);
		die();
	}

}
add_action( 'wp_ajax_cb_participation_update_participation', 'cb_ajax_update_participation' );

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
 * 		@type	array	$media_filepath		An indexed array of filepaths to the 
 * 											media files submitted along with the entry.
 * }
 * 
 */
function cb_participation_new_participation( array $args ) {

	if ( ! cb_is_user_confetti_bits() ) {
		return;
	}

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
			'media_filepath'	=> '',
			'transaction_id'	=> 0
		)
	);

	$participation = new Confetti_Bits_Participation_Participation();
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
	$participation->media_filepath		= $r['media_filepath'];
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
 * Confetti Bits Ajax New Participation
 * 
 * We'll use this to process the new participation entries sent via ajax.
 */
function cb_ajax_new_participation() {

	if ( ! bp_is_post_request() || 
		! cb_is_confetti_bits_component() || 
		! cb_is_user_confetti_bits() //||
		// ! wp_verify_nonce( $_POST['cb_participation_upload_nonce'], 'cb_participation_post' )
	   ) {
		return false;
	}

	$success		= false;
	$feedback		= '';
	$applicant_id	= intval( $_POST['cb_applicant_id'] );	
	$event_type		= sanitize_text_field( $_POST['cb_participation_event_type'] );
	$event_note		= sanitize_text_field( $_POST['cb_participation_event_note'] );
	$date_input		= sanitize_text_field( $_POST['cb_participation_event_date'] );
	$event_date		= date_format( date_create( $date_input ), 'Y-m-d H:i:s' );
	$media = sanitize_text_field( $_POST['cb_participation_media_file'] );

	if ( empty( $event_type ) || ( $event_type === 'other' && empty( $event_note ) ) ) {
		$feedback	= 'No events selected. Please select or input the event type you are attempting to register.';
	} else if ( empty( $media ) ) {
		$feedback	= 'No file selected. Please select an image that indicates your participation.';
	} else if ( ! is_string( $media ) ) {
		$feedback	= 'Process error 2005. Media not found.';
	} else if ( empty( $applicant_id ) )  {
		$feedback = "Authentication error 1000. Failed to authenticate request.";
	} else if ( ! isset( $_POST['cb_participation_upload_nonce'] ) ) {
		$feedback = 'Nonce not set.';
	} else if ( ! wp_verify_nonce( $_POST['cb_participation_upload_nonce'], 'cb_participation_post' ) ) {
		$feedback = 'Invalid nonce.';
	} else {

		$success = true;

		$send = cb_participation_new_participation(
			array(
				'item_id'			=> $applicant_id,
				'secondary_item_id'	=> 0,
				'applicant_id'		=> $applicant_id,
				'admin_id'			=> 0,
				'date_created'		=> current_time('mysql'),
				'date_modified'		=> current_time('mysql'),
				'event_type'		=> $event_type,
				'event_date'		=> $event_date,
				'event_note'		=> $event_note,
				'component_name'	=> 'confetti_bits',
				'component_action'	=> 'cb_participation_new',
				'status'			=> 'new',
				'media_filepath'	=> $media,
				'transaction_id'	=> 0
			)
		);

		if ( false === is_int( $send ) ) {
			$feedback = "Request processing error 2000.";
		} else {
			$feedback = 'Your participation has been successfully submitted. You should receive a notification when it has been processed.';
		}
	}

	echo json_encode(
		array(
			'success'	=> $success,
			'response'	=> $feedback
		)
	);

	die();
}
add_action( 'wp_ajax_cb_participation_create_participation', 'cb_ajax_new_participation' );


/**
 * CB Get Paged Participation
 * 
 * @param	string	$status	Status type to return. Default 'all'.
 * @param	int		$page	Page number to return. Default 0.
 * 
 * @return	array	Array of 6 paged participation entries.
 */
function cb_participation_get_paged_participation( $status = '', $page = 0, $per_page = 6, $count = false, $event_type = '' ) {

	$participation = new Confetti_Bits_Participation_Participation();
	$feedback = '';
	$pagination = array();
	$select =  $count ? 'count(id) as total_count' : '*';
	$status	= ( empty( $_GET['status'] ) ) ? 'all' : $_GET['status'];
<<<<<<< HEAD
=======

>>>>>>> 4bd4bbb (The Big Commit of April 2023)
	$event_type = empty( $_GET['event_type'] )
		? $event_type 
		: trim( $_GET['event_type'] );
	$where = array(
		'status'		=> $status,
		'date_query'	=> array(

			'column'	=> 'event_date',
			'compare'	=> 'BETWEEN',
			'before'	=> date( 'Y-m-d' , strtotime("last day of this month")),
			'after'		=> date( 'Y-m-d', strtotime("first day of last month")),
			'inclusive'	=> true

		)
	);
<<<<<<< HEAD
=======
	
	if ( ! empty( $_GET['applicant_id'] ) ) {
		$where['applicant_id'] = intval( $_GET['applicant_id'] );
	}
>>>>>>> 4bd4bbb (The Big Commit of April 2023)

	if ( !empty($event_type) ) {
		$where['event_type'] = trim( $event_type );
	}

	$pagination['page'] = ( empty( $_GET['page'] ) ) ? $page : $_GET['page'];
	$pagination['per_page'] = ( empty( $_GET['per_page'] ) ) ? $per_page : $_GET['per_page'];

	$paged_participation = $participation->get_participation(
		array(
			'select'		=> $select,
			'where'			=> $where,
			'orderby'		=> 'date_modified',
			'pagination'	=> $pagination
		)
	);

<<<<<<< HEAD
	$feedback .= ( ! empty( $paged_participation ) ) ? json_encode( $paged_participation ) : json_encode('Empty results set.');
=======
	$feedback .= ( ! empty( $paged_participation ) ) ? json_encode( $paged_participation ) : json_encode('Could not find any participation entries of specified type.');
>>>>>>> 4bd4bbb (The Big Commit of April 2023)

	echo $feedback;
	die();

}
add_action( 'wp_ajax_cb_participation_get_paged_participation', 'cb_participation_get_paged_participation' );

/**
 * Confetti Bits Get Total Participation
 * 
 * @return Total of all participation entries.
 */
function cb_participation_get_total_participation() {

	if ( !cb_is_user_confetti_bits() || ! bp_is_get_request() ) { 
		return;
	}

	$status = ( !empty( $_GET['status'] ) && is_string( $_GET['status'] ) ) ? $_GET['status'] : 'new';

	$valid_statuses = array( 'new', 'all', 'approved', 'denied' );

	$status = ( in_array( $status, $valid_statuses ) ) ? $status : 'new';
	$participation = new Confetti_Bits_Participation_Participation();
	$where = array(
		'status'		=> $status,
		'date_query'	=> array(
			'column'	=> 'event_date',
			'compare'	=> 'BETWEEN',
			'before'	=> date( 'Y-m-d' , strtotime("last day of this month")),
			'after'		=> date( 'Y-m-d', strtotime("first day of last month")),
			'inclusive'	=> true
		)
	);
<<<<<<< HEAD
=======
	
	if ( ! empty( $_GET['applicant_id'] ) ) {
		$where['applicant_id'] = intval( $_GET['applicant_id'] );
	}
>>>>>>> 4bd4bbb (The Big Commit of April 2023)

	if ( !empty( $_GET['event_type'] ) ) {
		$where['event_type'] = trim($_GET['event_type']);
	}


	$all_participation = $participation->get_participation(
		array(
			'select' => 'count(id) as total_count',
			'where' => $where
		)
	);

	echo json_encode( $all_participation );

	die();

}
add_action( 'wp_ajax_cb_participation_get_participation_total', 'cb_participation_get_total_participation' );

/**
 * Confetti Bits Update Request Status
 * 
 * Update the request status for a participation entry.
 */
function cb_participation_update_request_status( $id = 0, $admin_id = 0, $date_modified = '', $status = '', $transaction_id = 0 ) {

	if ( $id === 0 || $admin_id === 0 || $date_modified === '' || $status === '' ) {
		return;
	}

	if ( $status !== 'approved' && $status !== 'denied' ) {
		return;
	}

	$participation = new Confetti_Bits_Participation_Participation($id);
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
 * Confetti Bits Update Participation Handler
 */
function cb_participation_update_handler() {

	if ( ! bp_is_post_request() || 
		! cb_is_confetti_bits_component() || 
		! cb_is_user_confetti_bits() ||
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
<<<<<<< HEAD

=======
			/*
			if ( $log_entry !== $participation->event_note ) {
				$log_entry += " | {$participation->event_note}";
			}
*/
>>>>>>> 4bd4bbb (The Big Commit of April 2023)
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
<<<<<<< HEAD
=======
				$log_entry,
>>>>>>> 4bd4bbb (The Big Commit of April 2023)
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
add_action( 'bp_actions', 'cb_participation_update_handler' );



/**
 * Confetti Bits Participation New Transaction
 * 
 * Add or subtract Confetti Bits based on participation approval status.
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

	$participation = new Confetti_Bits_Participation_Participation( $r['participation_id'] );
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

		$transaction = new Confetti_Bits_Transactions_Transaction();

		$transaction->item_id			= $participation->applicant_id;
		$transaction->secondary_item_id	= $r['admin_id'];
		$transaction->user_id			= $participation->applicant_id;
		$transaction->sender_id			= $r['admin_id'];
		$transaction->sender_name		= $admin_name;
		$transaction->recipient_id		= $participation->applicant_id;
		$transaction->recipient_name	= bp_core_get_user_displayname($participation->applicant_id);
		$transaction->identifier		= $participation->applicant_id;
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
 * Confetti Bits Participation Get Amount From Event Type
 * 
 * Attempt to extract a predetermined value from valid event types.
 * 
 * @param string $event_type	The event type to check against.
 * @param string $prestatus		The preexisting status to check against.
 * @param string $status		The status that we are changing into.
 * @param string $override		The amount override submitted in the form.
 * 
 * @return int $amount The amount we extracted.
 * 
 */
function cb_participation_get_amount( $transaction_id, $event_type = '', $prestatus = '', $status = '', $override = 0 ) {

	// Prevent redundant submissions.
	if ( $event_type === '' || $prestatus === '' || $status === '' ) {
		return;
	}

	$transaction = new Confetti_Bits_Transactions_Transaction( $transaction_id );
	$amount = 0;

	// If this is the first time we're updating the status...
	// Only set an amount if it was approved
	if ( $prestatus === 'new' && $status === 'approved' ) {

		// If it's set in the override input, use that
		if ( $override !== 0 ) {
			return intval( $override );
			// Otherwise, try to format the amount based on event type
		} else {
			switch ( $event_type ) {
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

	if ( $prestatus === 'denied' || $prestatus === 'approved' ) {
		$amount = - $transaction->amount;
	}

	return $amount;

}

/**
 * Confetti Bits Participation Get Log Entry
 * 
 * Attempt to extract a predetermined log entry from valid event types.
 * 
 * @param int		$participation_id	The ID for the participation entry we're checking.
 * @param string	$admin_log_entry	An optional admin log entry override.
 * 
 * @return string	$log_entry The log entry we extracted.
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

	$participation = new Confetti_Bits_Participation_Participation( $participation_id );
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
 * Confetti Bits Get Transaction
 * 
 * We need to check if a transaction exists for a given participation entry, 
 * and perform operations based on that result.
 * 
 * @param int $transaction_id The ID for the transaction we're looking for.
 * 
 * @return array|bool Transaction if the object exists, false if not or we get an error.
 */
function cb_participation_get_transaction( $transaction_id = 0 ) {

	if ( $transaction_id === 0 ) {
		return false;
	}

	$transaction = new Confetti_Bits_Transactions_Transaction( $transaction_id );

	return ( ! empty( $transaction ) ) ? $transaction : false;

}

/*
 * Confetti Bits Get Uploads Directory
 * 
 * We're going to use wp_mkdir_p() to create our media directory for the 
 * Confetti Bits Participation attachments. 
 * 
 * @return bool Whether the directory was made, already exists, or failed to create.
 * 
 * @todo: We'll store the names in the wp_confetti_bits_participation table under media_filepath.
 * We'll create a unique name for each file upload. This will be in a separate function.
 * 
 * */
function cb_get_upload_dir() {

	$cb = Confetti_Bits();
	$upload_dir = wp_upload_dir();
	$confetti_bits_uploads_dir = trailingslashit( $upload_dir['basedir'] ) . 'confetti-bits';
	$cb->upload_dir = $confetti_bits_uploads_dir;
	wp_mkdir_p( $confetti_bits_uploads_dir );

	return $cb->upload_dir;

}

/*
 * Confetti Bits Create File from Upload
 * 
 * We're trying to create a new file from the participation form.
 * Once we verify that it's a legit file, we'll give it a new name
 * and stow it away in our uploads folder.
 * 
 * */
function cb_ajax_create_file_from_upload() {

	if ( !cb_is_user_confetti_bits() || ! bp_is_post_request() ) { 
		return;
	}

	status_header(200);
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	require_once( ABSPATH . 'wp-admin/includes/media.php' );

	$success		= false;
	$feedback		= '';
	$filename		= '';
	$upload_dir		= cb_get_upload_dir();
	$files_loaded	= isset(
		$_FILES['cb_participation_image_uploads'],
		$_FILES['cb_participation_image_uploads']['tmp_name'] 
	);

	if ( ! $files_loaded ) {
		$feedback = 'File Processing Error 2002: File not found.';
	} else {

		$file = $_FILES['cb_participation_image_uploads'];
		$filename	= wp_unique_filename( $upload_dir, $file['name'] );
		$new_file	= $upload_dir . "/{$filename}";
		$success	= move_uploaded_file( $file['tmp_name'], $new_file );
		$feedback	.= $success ? $filename : 'File processing error: 2001';

	}

	echo json_encode(
		array(
			'success'	=> $success,
			'filename'	=> $filename,
			'response'	=> $feedback
		)
	);

	die();

}
add_action( 'wp_ajax_cb_upload_media', 'cb_ajax_create_file_from_upload' );

/*
 * Confetti Bits Delete File from Upload
 * 
 * 
 * */
function cb_delete_file_from_upload() {

	if ( !cb_is_user_confetti_bits() || !cb_is_user_site_admin() || ! bp_is_post_request() ) { 
		return; 
	}

	status_header(200);

	$feedback = '';
	$upload_dir = cb_get_upload_dir();

	if ( isset( $_REQUEST['cb_participation_image_filename'] ) ) {

		$filepath = trim( "{$upload_dir}/{$_REQUEST['cb_participation_image_filename']}" );
		$file = file_exists( $filepath ) ? $filepath : false;
		if ( $file ) {
			$feedback = unlink( $file ) ? 'File removed.' : ' Process error 2003: Could not find or delete file.';
		} else {
			$feedback = 'Process error 2004: File does not exist.';
		}

		echo $feedback;

	}

	die();

}
add_action( 'wp_ajax_cb_delete_media', 'cb_delete_file_from_upload' );


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