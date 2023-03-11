<?php
defined('ABSPATH') || exit;

function cb_leaderboard() {

	$transaction	= new Confetti_Bits_Transactions_Transaction();
	/*/
	$totals_args 	= array(
		'select'		=> array( "identifier", "SUM(amount) as amount" ),
		'where' 		=> array(
			'component_name'	=> 'confetti_bits',
			'excluded_action'	=> 'cb_bits_request',
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'before'		=> $this->current_cycle_end,
				'after'			=> $this->current_cycle_start,
				'inclusive'		=> true,
			)
		),
		'pagination'	=> array( 0, 15 ),
		'group'			=> 'identifier'
	);/*/
	$requests_args	= array();
	$totals = $transaction->get_leaderboard_totals_groupedby_identifier_from_current_cycle();
	$requests = $transaction->get_leaderboard_requests_groupedby_identifier_from_current_cycle();
	$leaderboard_data = cb_calculate_leaderboard( $totals, $requests );
	$placement_digit = 0;
	$placement_suffix = '';
	$user_display_name = '';
	foreach ( $leaderboard_data as $key => $value ) {


		$user_display_name = bp_xprofile_get_member_display_name( $key );
		if ( $user_display_name == '' ) {
			continue;
		}

		$placement_digit++;
		$user_profile_url = bp_core_get_user_domain( $key );
		switch ( $placement_digit ) {

			case ( $placement_digit === 1 ):
				$placement_suffix = 'st';
				break;
			case ( $placement_digit === 2 ):
				$placement_suffix = 'nd';
				break;
			case ( $placement_digit === 3 ):
				$placement_suffix = 'rd';
				break;
			case ( $placement_digit >= 4 && $placement_digit !== "/[2-9][1-3]/" ):
				$placement_suffix = 'th';
		}
		echo sprintf(
			'<div class="cb-leaderboard-entry">
	<span class="cb-leaderboard-entry-item cb-placement">%d%s</span>
	<span class="cb-leaderboard-entry-item cb-user-link"><a href="%s">%s</a></span>
	<span class="cb-leaderboard-entry-item cb-user-leaderboard-bits">%d</span>
	</div>',
			$placement_digit,
			$placement_suffix,
			$user_profile_url,
			$user_display_name,
			$value,
		);
	}
}

function cb_calculate_leaderboard( $totals, $requests ) {

	$request_data = array();
	$standard_data = array();
	$unfiltered_leaderboard_data = array();

	foreach ( $requests as $request ) {

		$request_id		= $request['identifier'];
		$request_amount	= $request['amount'];

		$request_data[$request_id] = $request_amount;

		if ( isset( $unfiltered_leaderboard_data[$request_id] ) ) {
			$unfiltered_leaderboard_data[$request_id] += $request_amount;
		} else {
			$unfiltered_leaderboard_data[$request_id] = $request_amount;	
		}

	}

	foreach ( $totals as $standard_entry ) {
		$standard_id		= $standard_entry['identifier'];
		$standard_amount	= $standard_entry['amount'];
		$standard_data[$standard_id] = $standard_amount;
		if ( isset( $unfiltered_leaderboard_data[$standard_id] ) ) {
			$unfiltered_leaderboard_data[$standard_id] += $standard_amount;
		} else {
			$unfiltered_leaderboard_data[$standard_id] = $standard_amount;
		}

	}

	$leaderboard_filter = array_diff_key( $request_data, $standard_data );
	$leaderboard = array_diff_key( $unfiltered_leaderboard_data, $leaderboard_filter );
	arsort( $leaderboard );

	return $leaderboard;

}