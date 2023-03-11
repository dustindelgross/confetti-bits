<?php

defined('ABSPATH') || exit;
function cb_export_leadership_history( $user_id, $export_type ) {

	$transaction  = new Confetti_Bits_Transactions_Transaction();
	$user_transactions = $transaction->get_leadership_transactions();

	cb_generate_csv( $user_transactions, $export_type );
}

function cb_export_transaction_history_for_user( $user_id, $export_type ) {

	$args = array(
		'recipient_id'		=> $user_id,
		'component_name'	=> 'confetti_bits',
	);

	$transaction  = new Confetti_Bits_Transactions_Transaction();

	if ( 'leadership' === $export_type && cb_is_user_executive() ) {
		$user_transactions = $transaction->get_leadership_transactions();
	}

	if ( 'self' === $export_type ) {
		$user_transactions = $transaction->get_transactions_for_user( $args );
	}

	if ( 'current_cycle_leaderboard' === $export_type ) {

		$totals = $transaction->get_leaderboard_totals_groupedby_identifier_from_current_cycle();
		$requests = $transaction->get_leaderboard_requests_groupedby_identifier_from_current_cycle();
		$user_transactions = cb_calculate_leaderboard( $totals, $requests );

	}

	if ( 'previous_cycle_leaderboard' === $export_type ) {

		$totals = $transaction->get_leaderboard_totals_groupedby_identifier_from_previous_cycle();
		$requests = $transaction->get_leaderboard_requests_groupedby_identifier_from_previous_cycle();
		$user_transactions = cb_calculate_leaderboard( $totals, $requests );

	}

	if ( 'current_cycle_totals' === $export_type ) {

		$totals = $transaction->get_totals_groupedby_identifier_from_current_cycle();
		$requests = $transaction->get_requests_groupedby_identifier_from_current_cycle();
		$user_transactions = cb_calculate_leaderboard( $totals, $requests );

	}

	if ( 'previous_cycle_totals' === $export_type ) {

		$totals = $transaction->get_totals_groupedby_identifier_from_previous_cycle();
		$requests = $transaction->get_requests_groupedby_identifier_from_previous_cycle();
		$user_transactions = cb_calculate_leaderboard( $totals, $requests );

	}

	if ( 'current_requests' === $export_type ) {
		$requests_args = array(
			'select'		=> array( 'id', 'recipient_id', 'recipient_name', 'date_sent', 'log_entry', 'amount' ),
			'where'			=> array(
				'date_query'		=> array(
					'column'		=> 'date_sent',
					'compare'		=> 'BETWEEN',
					'relation'		=> 'AND',
					'before'		=> $transaction->previous_spending_cycle_end,
					'after'			=> $transaction->previous_spending_cycle_start,
					'inclusive'		=> true,
				),
				'component_name'	=> 'confetti_bits',
				'component_action'	=> 'cb_bits_request',
			),
		);
		
		$user_transactions = $transaction->get_transactions( $requests_args );

	}

	$cb_new_date = new DateTime( 'now' );
	$file_name = 'confetti-bits-export-' . $export_type . '-' . $cb_new_date->format( 'm-d-Y' ) . '.csv';
	cb_csv_send_headers( $file_name );
	cb_generate_csv( $user_transactions, $export_type );
	die();
}

function cb_csv_send_headers( $file_name ) {
	header("Pragma: public");
	header("Expires: 0");
	header("Connection: keep-alive");
	header("Content-Disposition: attachment; filename={$file_name};", true, 200);
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/csv");

}

function cb_generate_csv( $user_transactions, $export_type ) {

	if ( ! empty( $user_transactions ) ) {

		$fp = fopen( "php://output", "w" );
		if ( 'self' === $export_type ) {
			$header = ['Transaction ID', 'Receipient Name', 'Date Sent', 'Log Entry', 'Amount'];
			fputcsv( $fp, $header );

			foreach ( $user_transactions as $transaction ) {

				$row = [
					$transaction['id'],
					$transaction['recipient_name'],
					$transaction['date_sent'],
					$transaction['log_entry'],
					$transaction['amount'],
				];

				fputcsv( $fp, $row );
			}
		}

		if ( 'leadership' === $export_type && cb_is_user_executive() ) {
			$header = ['Transaction ID', 
					   'Component Action', 
					   'Sender Name', 
					   'Amount',
					   'Recipient Name', 
					   'Log Entry',
					   'Date Sent',
					  ];
			fputcsv( $fp, $header );
			foreach ( $user_transactions as $transaction ) {
				$sender_name = preg_match('/from\\s(\\w+\\s\\w+)/', $transaction['log_entry'], $matches );

				$row = [
					$transaction['id'],
					$transaction['component_action'],
					$matches[1],
					$transaction['amount'],
					$transaction['recipient_name'],
					$transaction['log_entry'],
					$transaction['date_sent'],
				];

				fputcsv( $fp, $row );
			}
		}

		if ( 'current_cycle_leaderboard' === $export_type && cb_is_user_executive() ) {
			$header = [
				'Recipient Name',
				'Amount',
			];
			fputcsv( $fp, $header );
			foreach ( $user_transactions as $id => $amount ) {
				$recipient_name = bp_core_get_user_displayname( $id );
				$row = [
					$recipient_name,
					$amount,
				];

				fputcsv( $fp, $row );
			}
		}
		if ( 'previous_cycle_leaderboard' === $export_type && cb_is_user_executive() ) {
			$header = [
				'Recipient Name',
				'Amount',
			];
			fputcsv( $fp, $header );
			foreach ( $user_transactions as $id => $amount ) {

				$recipient_name = bp_core_get_user_displayname( $id );
				$row = [
					$recipient_name,
					$amount,
				];

				fputcsv( $fp, $row );
			}
		}
		if ( 'current_cycle_totals' === $export_type && cb_is_user_executive() ) {
			$header = [
				'Recipient Name',
				'Amount',
			];
			fputcsv( $fp, $header );
			foreach ( $user_transactions as $id => $amount ) {
				$recipient_name = bp_core_get_user_displayname( $id );
				$row = [
					$recipient_name,
					$amount,
				];

				fputcsv( $fp, $row );
			}
		}
		if ( 'previous_cycle_totals' === $export_type && cb_is_user_executive() ) {
			$header = [
				'Recipient Name',
				'Amount',
			];
			fputcsv( $fp, $header );
			foreach ( $user_transactions as $id => $amount ) {
				$recipient_name = bp_core_get_user_displayname( $id );
				$row = [
					$recipient_name,
					$amount,
				];

				fputcsv( $fp, $row );
			}
		}
		
		if ( 'current_requests' === $export_type && cb_is_user_requests_fulfillment() ) {
			$header = [
				'Transaction ID',
				'Recipient Name',
				'Request Date',
				'Request Item',
				'Amount',
			];
			fputcsv( $fp, $header );
			foreach ( $user_transactions as $request ) {
				$row = [
					$request['id'],
					$request['recipient_name'],
					$request['date_sent'],
					$request['log_entry'],
					$request['amount']
				];

				fputcsv( $fp, $row );
			}
		}
	}

	fclose( $fp );
}

function cb_export() {

	if ( !cb_is_confetti_bits_component() || !cb_is_user_confetti_bits() ) {
		return;
	}

	$user_id = get_current_user_id();
	if( isset( $_POST['cb_export_logs'] ) && isset( $_POST['cb_export_type'] ) ) {
		$export_type = $_POST['cb_export_type'];

		cb_export_transaction_history_for_user( $user_id, $export_type );
	}
}
add_action('bp_actions', 'cb_export');

/*/

function cb_export_transaction_history_for_user( $user_id, $export_type ) {

	$args = array(
		'recipient_id'		=> $user_id,
		'component_name'	=> 'confetti_bits',
	);

	$transaction  = new Confetti_Bits_Transactions_Transaction();

	if ( 'leadership' === $export_type && cb_is_user_executive() ) {
		$user_transactions = $transaction->get_leadership_transactions();
	}

	if ( 'self' === $export_type ) {
		$user_transactions = $transaction->get_transactions_for_user( $args );
	}

	if ( 'leaderboard' === $export_type ) {
		$user_transactions = $transaction->get_totals_groupedby_identifier();
	}

	if ( 'previous_cycle_leaderboard' === $export_type ) {
		$user_transactions = $transaction->get_totals_groupedby_identifier_from_previous_cycle();
	}

	$cb_new_date = new DateTime( 'now' );
	$file_name = 'confetti-bits-export-' . $export_type . '-' . $cb_new_date->format( 'm-d-Y' ) . '.csv';
	cb_csv_send_headers( $file_name );
	cb_generate_csv( $user_transactions, $export_type );
	die();
}

function cb_csv_send_headers( $file_name ) {
	header("Pragma: public");
	header("Expires: 0");
	header("Connection: keep-alive");
	header("Content-Disposition: attachment; filename={$file_name};", true, 200);
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/csv");

}

function cb_generate_csv( $user_transactions, $export_type ) {

	if ( ! empty( $user_transactions ) ) {

		$fp = fopen( "php://output", "w" );
		if ( 'self' === $export_type ) {
			$header = ['Transaction ID', 'Receipient Name', 'Date Sent', 'Log Entry', 'Amount'];
			fputcsv( $fp, $header );

			foreach ( $user_transactions as $transaction ) {

				$row = [
					$transaction['id'],
					$transaction['recipient_name'],
					$transaction['date_sent'],
					$transaction['log_entry'],
					$transaction['amount'],
				];

				fputcsv( $fp, $row );
			}
		}

		if ( 'leadership' === $export_type && cb_is_user_executive() ) {
			$header = ['Transaction ID', 
					   'Component Action', 
					   'Sender Name', 
					   'Amount',
					   'Recipient Name', 
					   'Log Entry',
					   'Date Sent',
					  ];
			fputcsv( $fp, $header );
			foreach ( $user_transactions as $transaction ) {
				$sender_name = preg_match('/from\\s(\\w+\\s\\w+)/', $transaction['log_entry'], $matches );

				$row = [
					$transaction['id'],
					$transaction['component_action'],
					$matches[1],
					$transaction['amount'],
					$transaction['recipient_name'],
					$transaction['log_entry'],
					$transaction['date_sent'],
				];

				fputcsv( $fp, $row );
			}
		}

		if ( 'leaderboard' === $export_type && cb_is_user_executive() ) {
			$header = [
				'Recipient Name',
				'Amount',
			];
			fputcsv( $fp, $header );
			foreach ( $user_transactions as $transaction ) {
				$recipient_name = bp_core_get_user_displayname( $transaction['identifier'] );
				$row = [
					$recipient_name,
					$transaction['amount'],
				];

				fputcsv( $fp, $row );
			}
		}
		if ( 'previous_cycle_leaderboard' === $export_type && cb_is_user_executive() ) {
			$header = [
				'Recipient Name',
				'Amount',
			];
			fputcsv( $fp, $header );
			foreach ( $user_transactions as $transaction ) {
				$recipient_name = bp_core_get_user_displayname( $transaction['identifier'] );
				$row = [
					$recipient_name,
					$transaction['amount'],
				];

				fputcsv( $fp, $row );
			}
		}
	}

	fclose( $fp );
}

function cb_export() {

	if ( !cb_is_confetti_bits_component() || !cb_is_user_confetti_bits() ) {
		return;
	}

	$user_id = get_current_user_id();
	if( isset( $_POST['cb_export_logs'] ) && isset( $_POST['cb_export_type'] ) ) {
		$export_type = $_POST['cb_export_type'];

		cb_export_transaction_history_for_user( $user_id, $export_type );
	}
}
add_action('bp_actions', 'cb_export');
/*/