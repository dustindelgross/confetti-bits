<?php
// Exit if accessed directly
defined('ABSPATH') || exit;
/*
function cb_export_leadership_history( $user_id = 0, $export_type = '' ) {

	if ( $user_id === 0 || $export_type === '' ) {
		return;
	}

	$transaction  = new CB_Transactions_Transaction();

	$user_transactions = $transaction->get_transactions(array(
		'select'	=> 'id, component_action, sender_id, recipient_id, date_sent, log_entry, amount',
		'where'		=> array(
			'component_action' => 'cb_send_bits'
		)
	));

	cb_generate_csv( $user_transactions, $export_type );

}
*/

/**
 * CB Transactions Get History for User
 *
 * Handles the logic behind getting the right data from the database.
 *
 * @todo: Fix this tomfoolery. And type check your variables for the love of god
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 1.0.0
 */
function cb_transactions_get_history_for_user( $user_id = 0, $export_type = '' ) {

	if ( 0 === $user_id || '' === $export_type ) {
		return;
	}

	$cb = Confetti_Bits();
	$transaction  = new CB_Transactions_Transaction();

	$get_args = array(
		'select' => '',
		'where' => array(),
		'orderby' => array(),
	);

	if ( 'leadership' === $export_type && cb_is_user_executive() ) {
		$get_args = array(
			'select' => "sender_id, recipient_id, date_sent, amount, log_entry",
			'where' => array( 'component_action' => 'cb_send_bits' ),
			'orderby' => array( 'id', 'DESC' )
		);
		$user_transactions = $transaction->get_transactions($get_args);
	} else if ( 'self' === $export_type ) {
		$get_args = array(
			'select' => "sender_id, recipient_id, date_sent, log_entry, amount",
			'where' => array(
				'recipient_id' => $user_id,
				'sender_id' => $user_id,
				'or' => true
			),
			'orderby' => array( 'id', 'DESC' )
		);
		$user_transactions = $transaction->get_transactions( $get_args );
	} else if ( 'current_requests' === $export_type ) {

		$requests_args = array(

			'select'		=> "recipient_id, date_sent, log_entry, amount",
			'where'			=> array(
				'date_query'		=> array(
					'column'		=> 'date_sent',
					'compare'		=> 'BETWEEN',
					'relation'		=> 'AND',
					'before'		=> $cb->spend_start,
					'after'			=> $cb->prev_spend_start,
					'inclusive'		=> true,
				),
				'component_action'	=> 'cb_bits_request',
			),
		);

		$user_transactions = $transaction->get_transactions( $requests_args );

	} else {
		$limit = is_int(strpos( $export_type, 'leaderboard' ));
		$prev = is_int(strpos( $export_type, 'previous' ));
		$user_transactions = cb_get_leaderboard( $limit, $prev );
	}

	$cb_new_date = new DateTime( 'now' );
	$file_name = 'confetti-bits-export-' . $export_type . '-' . $cb_new_date->format( 'm-d-Y' ) . '.csv';
	cb_csv_send_headers( $file_name );
	cb_generate_csv( $user_transactions, $export_type );
	die();
}

/**
 * CB CSV Send Headers
 *
 * Manually alters headers to allow for us to modify how a
 * csv reaches an end user, and to ensure safe passage for
 * our noble data wizards.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 1.0.0
 */
function cb_csv_send_headers( $file_name = '' ) {

	// Bail if we're not taking things seriously!
	if ( '' === $file_name ) {
		return;
	}

	header("Pragma: public");
	header("Expires: 0");
	header("Connection: keep-alive");
	header("Content-Disposition: attachment; filename={$file_name};", true, 200);
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/csv");

}

/**
 * CB Generate CSV
 *
 * Responsible for generating csv files for users that request an export of their data.
 *
 * @param array $user_transactions A 2D array of transactions
 * @param string $export_type The type of export the user requested.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 1.0.0
 */
function cb_generate_csv( $user_transactions = array(), $export_type = '' ) {

	if ( '' === $export_type || empty( $user_transactions ) ) {
		return;
	}

	$headers = array(
		'self' => ['Sender', 'Receipient', 'Date Sent', 'Log Entry', 'Amount'],
		'leadership' => ['Sender Name', 'Recipient Name', 'Amount','Log Entry','Date Sent'],
		'current_cycle_leaderboard' => ['Recipient Name','Amount'],
		'previous_cycle_leaderboard' => ['Recipient Name','Amount'],
		'current_cycle_totals' => ['Recipient Name','Amount'],
		'previous_cycle_totals' => ['Recipient Name','Amount'],
		'current_requests' => ['Recipient','Request Date','Request Item','Amount'],
	);

	if ( !isset( $headers[$export_type] ) ) {
		return;
	}

	$fp = fopen( "php://output", "w" );
	fputcsv( $fp, $headers[$export_type] );

	if ( 'self' === $export_type ) {
		foreach ( $user_transactions as $transaction ) {
			$sender_name = bp_core_get_user_displayname($transaction['sender_id']);
			$recipient_name = bp_core_get_user_displayname($transaction['recipient_id']);
			$row = [
				$sender_name,
				$recipient_name,
				$transaction['date_sent'],
				$transaction['log_entry'],
				$transaction['amount'],
			];
			fputcsv( $fp, $row );
		}
	}

	if ( $export_type !== 'self' && (cb_is_user_executive() || cb_is_user_site_admin()) ) {

		$leaderboards = array(
			'current_cycle_leaderboard',
			'previous_cycle_leaderboard',
			'current_cycle_totals',
			'previous_cycle_totals'
		);

		if ( in_array( $export_type, $leaderboards ) ) {
			foreach ( $user_transactions as $transaction ) {
				$recipient_name = bp_core_get_user_displayname( $transaction['recipient_id'] );
				$row = [ $recipient_name, $transaction['calculated_total'] ];
				fputcsv( $fp, $row );
			}
		} else {
			foreach ( $user_transactions as $transaction ) {
				$sender_name = bp_core_get_user_displayname($transaction['sender_id']);
				$recipient_name = bp_core_get_user_displayname($transaction['recipient_id']);

				$row = [
					$sender_name,
					$recipient_name,
					$transaction['amount'],
					$transaction['log_entry'],
					$transaction['date_sent'],
				];

				fputcsv( $fp, $row );
			}
		}
	}

	if ( 'current_requests' === $export_type && ( cb_is_user_requests_fulfillment() || cb_is_user_site_admin() ) ) {
		foreach ( $user_transactions as $request ) {
			$recipient_name = bp_core_get_user_displayname($request['recipient_id']);
			$row = [
				$recipient_name,
				$request['date_sent'],
				$request['log_entry'],
				$request['amount']
			];
			fputcsv( $fp, $row );
		}
	}

	fclose( $fp );
}

/**
 * CB Export
 *
 * Checks to see if export parameters are set on a POST request,
 * exports transactions from the database based on parameters.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 1.0.0
 */
function cb_export() {

	if ( !cb_is_confetti_bits_component() || !isset( $_POST['cb_export_type'], $_POST['cb_export_logs'] ) ) {
		return;
	}

	$user_id = get_current_user_id();
	$export_type = sanitize_text_field($_POST['cb_export_type']);
	cb_transactions_get_history_for_user( $user_id, $export_type );
}
add_action('bp_actions', 'cb_export');