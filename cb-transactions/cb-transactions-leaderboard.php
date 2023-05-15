<?php
defined('ABSPATH') || exit;

/**
 * CB Get Leaderboard
 * Queries the database for the top 15 users by Confetti Bits balance
 * Also includes the current user if they aren't in the top 15
 *
 * @package Confetti Bits
 * @since 1.0.0
 * @return array $results The top 15 users by Confetti Bits balance,
 * or the top 15 users by Confetti Bits balance with the current user included
 */
function cb_get_leaderboard( $limit = true, $previous = false ) {

	$cb = Confetti_Bits();
	$transaction = new CB_Transactions_Transaction();
	$user_id = get_current_user_id();
	$spend_modifier = "";
	$earn_modifier = "";
	
	if ( $previous ) {
		$spend_modifier = "`date_sent` BETWEEN '{$cb->prev_spend_start}' AND '{$cb->spend_start}'";
		$earn_modifier = "`date_sent` BETWEEN '{$cb->prev_earn_start}' AND '{$cb->earn_start}'";
		$before_modifier = $cb->spend_start;
		$after_modifier = $cb->prev_earn_start;
	} else {
		$spend_modifier = "`date_sent` >= '{$cb->spend_start}'";
		$earn_modifier = "`date_sent` >= '{$cb->earn_start}'";
		$before_modifier = $cb->spend_end;
		$after_modifier = $cb->earn_start;
	}

	$results = $transaction->get_transactions(
		array(
			"select" => "recipient_id,
			SUM(CASE WHEN {$spend_modifier} AND amount < 0 THEN amount ELSE 0 END) + SUM(CASE WHEN {$earn_modifier} AND amount > 0 THEN amount ELSE 0 END) AS calculated_total",
			"where" => array(
				"date_query" => array(
					'column'		=> 'date_sent',
					'compare'		=> 'BETWEEN',
					'relation'		=> 'AND',
					'before'		=> $before_modifier,
					'after'			=> $after_modifier,
					'inclusive'		=> true,
				)
			),
			"groupby" => array("recipient_id"),
			"orderby" => array( "calculated_total", "DESC" )
		)
	);

	$user_placement = null;
	$user_calculated_total = 0;
	$count = 0;

	foreach ($results as $result) {
		$count++;
		if ($result['recipient_id'] == $user_id) {
			$user_placement = $count;
			$user_calculated_total = $result['calculated_total'];
			break;
		}
	}

	if ( $limit ) {
		$results = array_slice($results, 0, 15);
	}

	if ($user_placement !== null) {
		$results[] = array(
			'recipient_id' => $user_id,
			'calculated_total' => $user_calculated_total,
			'placement' => $user_placement
		);
	}

	return $results;

}

/**
 * CB Leaderboard
 * Displays the top 15 users by Confetti Bits balance
 *
 * @package Confetti Bits
 * @since 1.0.0
 * @return void
 * @uses cb_get_leaderboard()
 * @uses bp_core_get_user_displayname()
 * @uses bp_core_get_user_domain()
 *
 */
function cb_leaderboard() {

	$placement_digit = 0;
	$placement_suffix = '';

	$items = cb_get_leaderboard();
	foreach( $items as $item ) {
		$dn = bp_core_get_user_displayname($item['recipient_id']);
		if ( empty($dn) )
			continue;
		$placement_digit++;
		if ( isset( $item['placement'] ) ) {
			$placement_digit = $item['placement'];
		}
		$url = bp_core_get_user_domain($item['recipient_id']);
		switch ( $placement_digit ) {

			case ( $placement_digit === 1 ):
			case ($placement_digit == "/[2-9][1]/" ):
				$placement_suffix = 'st';
				break;
			case ( $placement_digit === 2 ):
			case ( $placement_digit == "/[2-9][2]/" ):
				$placement_suffix = 'nd';
				break;
			case ( $placement_digit === 3 ):
			case ( $placement_digit == "/[2-9][3]/" ):
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
			$url,
			$dn,
			$item['calculated_total'],
		);
	}
}