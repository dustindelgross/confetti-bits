<?php
// Exit if accessed directly
defined('ABSPATH') || exit;
/**
 * CB Transactions Functions
 * 
 * This file will handle the bulk of our CRUD ops for the 
 * Transactions component. Hope this works.
 * Good luck!
 * 
 * @package ConfettiBits\Transactions
 * @since 1.0.0
 */

/**
 * CB Activity Bits
 * 
 * This hooks onto the BP Activity Posted Update 
 * action to give someone Confetti Bits when they
 * post an update.
 * 
 * @param string $content The content of the activity post.
 * @param int $user_id The id of the user associated with the activity post.
 * @param int $activity_id The id of the activity post.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.0.0
 */
function cb_activity_bits($content, $user_id, $activity_id)
{

	if ( !$user_id ) {
		return;
	}

	$date = new DateTimeImmutable();	
	$today = $date->format('D');

	if ($today === 'Sat' || $today === 'Sun') {
		return;
	}

	$args = array(
		'select' => "recipient_id, COUNT(recipient_id) as total_count",
		'where' => array(
			'date_query' => array(
				'year' => $date->format('Y'),
				'month' => $date->format('m'),
				'day' => $date->format('d'),
			),
			'recipient_id' => $user_id,
			'component_action' => 'cb_activity_bits',
		),
	);

	$transaction = new CB_Transactions_Transaction();
	$activity_transactions = $transaction->get_transactions($args);

	if ( $activity_transactions[0]['total_count'] >= 1 ) {
		return;
	}

	$activity_post = cb_transactions_new_transaction([
		'item_id' => $user_id,
		'secondary_item_id' => $user_id,
		'sender_id' => $user_id,
		'recipient_id' => $user_id,
		'date_sent' => cb_core_current_date(),
		'log_entry' => 'Posted a new update',
		'component_name' => 'activity',
		'component_action' => 'cb_activity_bits',
		'amount' => 1
	]);
}
// add_action('bp_activity_posted_update', 'cb_activity_bits', 10, 3);

/**
 * CB Transactions Get Total Sent Today
 * 
 * This function gets the total number of Confetti Bits
 * that have been sent for the current day.
 * 
 * @return int $total The total number of Confetti Bits sent for the current day.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.0.0
 */
function cb_transactions_get_total_sent_today($user_id = 0) {

	$transaction = new CB_Transactions_Transaction();
	$date = new DateTimeImmutable("now");
	if ( empty($user_id) ) {
		$user_id = get_current_user_id();	
	}
	
	$action = cb_is_user_admin($user_id) ? 'cb_send_bits' : 'cb_transfer_bits';
	$args = [
		'select' => "SUM(CASE WHEN amount > 0 AND sender_id = {$user_id} THEN amount ELSE 0 END) as amount",
		'where' => [
			'date_query' => [
				'year' => $date->format('Y'),
				'month' => $date->format('m')
			],
			'component_action' => $action,
		]
	];

	$fetched_transactions = $transaction->get_transactions($args);
	$total = (!empty($fetched_transactions)) ? abs(intval($fetched_transactions[0]['amount'])) : 0;
	return $total;

}

/**
 * CB Transactions Get Request Balance
 * 
 * Get the balance available to a user for the current spending
 * cycle. The request balance resets 1 month after users are done 
 * earning for the year.
 * 
 * So the balance that is returned is a calculation based on the
 * following timeline:
 * 
 * 1. When the earning cycle starts, the available balance for
 * requests is accrued for one year. They may then spend those
 * for an additional month after that.
 * 2. date_sent >= $cb->earn_start, date_sent <= earn_end, amount > 0
 * 3. date_sent >= $cb->earn_start, date_sent <= spend_end, amount < 0
 * 
 * @param int $user_id The ID for the user whose balance we want.
 * @return int The calculated balance available for requests.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.3.0
 */
function cb_transactions_get_request_balance($user_id = 0)
{

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}

	$reset_date = get_option('cb_reset_date');
	$cb = Confetti_Bits();
	$transactions = new CB_Transactions_Transaction();
	$date = new DateTimeImmutable($reset_date);
	$spend = "`date_sent` >= '{$cb->spend_start}' AND `date_sent` <= '{$cb->spend_end}' AND amount < 0";
	$earn = "`date_sent` >= '{$cb->earn_start}' AND `date_sent` <= '{$cb->earn_end}' AND amount > 0";

	$args = array(
		'select' => "SUM(CASE WHEN {$spend} THEN amount ELSE 0 END) + 
		SUM(CASE WHEN {$earn} THEN amount ELSE 0 END) AS calculated_total",
		'where' => array(
			'recipient_id' => $user_id,
			'date_query' => array(
				'column'		=> 'date_sent',
				'compare'		=> 'BETWEEN',
				'relation'		=> 'AND',
				'before'		=> $cb->spend_end,
				'after'			=> $cb->earn_start,
				'inclusive'		=> true,
			)
		)
	);

	$results = $transactions->get_transactions($args);
	$total = (!empty($results[0]['calculated_total'])) ? $results[0]['calculated_total'] : 0;

	return $total;

}

/**
 * CB Transactions Get Transfer Balance
 * 
 * Get the balance available to a user for the current 
 * earning cycle. 
 * 
 * @param int $user_id The ID for the user whose balance we want.
 * @return int The calculated balance available for transfers.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.3.0
 */
function cb_transactions_get_transfer_balance($user_id = 0)
{

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}

	$cb = Confetti_Bits();
	$transactions = new CB_Transactions_Transaction();
	$date = new DateTimeImmutable($cb->earn_end);
	$today = new DateTimeImmutable();
	
	if ( $today->format('U') >= $date->modify('-1 week')->format('U') ) {
		return 0;
	}
	
	$spend = "`date_sent` >= '{$cb->earn_start}' AND `date_sent` <= '{$cb->earn_end}' AND amount < 0";
	$earn = "`date_sent` >= '{$cb->earn_start}' AND `date_sent` <= '{$cb->earn_end}' AND amount > 0";

	$args = array(
		'select' => "recipient_id, 
		SUM(CASE WHEN {$spend} THEN amount ELSE 0 END) + 
		SUM(CASE WHEN {$earn} THEN amount ELSE 0 END) AS calculated_total",
		'where' => array(
			'recipient_id' => $user_id,
			'date_query' => array(
				'column'		=> 'date_sent',
				'compare'		=> 'BETWEEN',
				'relation'		=> 'AND',
				'before'		=> $cb->earn_end,
				'after'			=> $cb->earn_start,
				'inclusive'		=> true,
			)
		)
	);

	$results = $transactions->get_transactions($args);
	$total = (!empty($results[0]['calculated_total'])) ? $results[0]['calculated_total'] : 0;

	return $total;

}

/**
 * CB Transactions Check Activity Bits
 * 
 * Checks to see if there were any days throughout the
 * cycle where the user might have posted on the 
 * BuddyBoss activity feed, and didn't receive any 
 * points for it. Helps cover our tail if we accidentally 
 * push some breaking changes or do something silly
 * with how activity bits are registered.
 * 
 * @param int $user_id The ID for the user we want to check.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.3.0
 */
function cb_transactions_check_activity_bits($user_id = 0)
{

	if (!cb_is_confetti_bits_component() ) {
		return;
	}

	$today = current_time('D', false);

	if ($today === 'Sat' || $today === 'Sun') {
		return;
	}

	if ( empty($user_id) ) {
		$user_id = get_current_user_id();
	}


	$transactions = cb_transactions_get_activity_transactions($user_id);
	$activities = cb_transactions_get_activity_posts($user_id);
	$missing_transactions = cb_transactions_calculate_activity_bits($activities, $transactions);

	if (!empty($missing_transactions)) {
		foreach ($missing_transactions as $date_sent => $id) {
			$activity_post = cb_send_bits(
				array(
					'item_id' => $id,
					'secondary_item_id' => $id,
					'sender_id' => $id,
					'recipient_id' => $id,
					'date_sent' => date('Y-m-d H:i:s', strtotime($date_sent)),
					'log_entry' => 'Posted a new update',
					'component_name' => 'activity',
					'component_action' => 'cb_activity_bits',
					'amount' => 1,
				)
			);
		}
	}
}
// add_action('bp_actions', 'cb_transactions_check_activity_bits', 10, 1);

/**
 * CB Transactions Get Activity Posts
 * 
 * Returns an array of activity posts for the given user.
 * Uses BuddyBoss's global value for the activities table name.
 * 
 * @param int $user_id The ID of the user whose posts we want.
 * 
 * @return array An array of activity posts, if any.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.3.0
 */
function cb_transactions_get_activity_posts( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$transactions = new CB_Transactions_Transaction();
	return $transactions->get_activity_posts_for_user($user_id);

}

/**
 * CB Transactions Get Activity Transactions
 * 
 * Retrieves a list of all transactions from the current
 * earning cycle that were registered by a user posting 
 * on the BuddyBoss activity feed.
 * 
 * @param int $user_id The user's ID. Default current user ID.
 * 
 * @return array An array of transactions, if there are any.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.3.0
 */
function cb_transactions_get_activity_transactions( $user_id = 0 ) {

	if ( $user_id === 0 ) {
		$user_id = get_current_user_id();
	}

	$cb = Confetti_Bits();
	$transactions = new CB_Transactions_Transaction();

	$activity_bits_args = array(
		"select" => "recipient_id, date_sent, component_name, component_action",
		"where" => array(
			"recipient_id" => $user_id,
			"date_query" => array(
				'before'		=> $cb->earn_end,
				'after'			=> $cb->earn_start,
				'inclusive'		=> true,
			),
			"component_action" => "cb_activity_bits",
		)
	);

	return $transactions->get_transactions($activity_bits_args);

}

/**
 * CB Transactions Calculate Activity Bits
 * 
 * Calculates how many points a user should receive 
 * according to the number of unaccounted for activity
 * posts they've sent out, that don't have an 
 * accompanying Confetti Bits transaction on that same
 * day.
 * 
 * @param array $activities An array of activity posts
 * @param array $transactions An array of transactions
 * 
 * @return array An array of activity posts that are 
 * missing an accompanying transaction on a given day.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.3.0
 */
function cb_transactions_calculate_activity_bits($activities = array(), $transactions = array() )
{

	$activity_data = array();
	$transaction_data = array();

	if (empty($activities) || !isset($transactions, $activities)) {
		return;
	}

	foreach ($activities as $activity) {

		$activity_id = $activity['user_id'];
		$activity_date = date('Y-m-d', strtotime($activity['date_recorded']));
		$a_weekend_check = date('D', strtotime($activity_date));

		if ($a_weekend_check === 'Sat' || $a_weekend_check === 'Sun') {
			continue;
		} else {
			$activity_data[$activity_date] = $activity_id;
		}

	}

	foreach ($transactions as $transaction) {

		$transaction_id = $transaction['recipient_id'];
		$transaction_date = date('Y-m-d', strtotime($transaction['date_sent']));
		$t_weekend_check = date('D', strtotime($transaction_date));

		if ($t_weekend_check === 'Sat' || $t_weekend_check === 'Sun') {
			continue;
		} else {
			$transaction_data[$transaction_date] = $transaction_id;
		}

	}

	$missing_transactions = array_diff_key($activity_data, $transaction_data);

	return $missing_transactions;

}



/**
 * CB Transactions Has Bits
 * 
 * Checks whether the user has gotten 
 * Confetti Bits for a specific action this year.
 * 
 * @param string $action The action to check the database for.
 *   Usually either "birthday" or "anniversary".
 * 
 * @return bool Whether we found an entry for the given action
 *   within the past year.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.3.0
 */
function cb_transactions_has_bits($action = '')
{

	$user_id = get_current_user_id();
	$current_year = date('Y');

	$transaction = new CB_Transactions_Transaction();

	return $transaction->get_transactions(
		array(
			'select' => 'id',
			'where' => array(
				'recipient_id' => $user_id,
				'component_action' => "cb_{$action}_bits",
				'date_query' => array(
					'year' => $current_year
				)
			)
		)
	);

}

/**
 * CB Transactions Birthday Bits
 * 
 * Gives the user Confetti Bits on their birthday.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.3.0
 */
function cb_transactions_birthday_bits()
{

	$birthday_bits = cb_transactions_has_bits('birthday');

	if (!empty($birthday_bits)) {
		return;
	}

	$user_id = get_current_user_id();
	$user_name = bp_core_get_user_displayname($user_id);
	$user_birthday = date_create(xprofile_get_field_data(51, $user_id));

	if (date('m-d') >= $user_birthday->format('m-d')) {

		$transaction = new CB_Transactions_Transaction();

		$transaction->item_id = $user_id;
		$transaction->secondary_item_id = $user_id;
		$transaction->sender_id = $user_id;
		$transaction->recipient_id = $user_id;
		$transaction->date_sent = current_time('mysql');
		$transaction->log_entry = "Happy birthday!";
		$transaction->component_name = 'transactions';
		$transaction->component_action = 'cb_birthday_bits';
		$transaction->amount = 25;

		$send = $transaction->send_bits();

	}

}
add_action('cb_actions', 'cb_transactions_birthday_bits');

/**
 * CB Transactions Anniversary Bits
 * 
 * Gives the user Confetti Bits on their anniversary.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.3.0
 */
function cb_transactions_anniversary_bits()
{

	$anniversary_bits = cb_transactions_has_bits('anniversary');

	if (!empty($anniversary_bits)) {
		return;
	}

	$user_id = get_current_user_id();
	$user_name = bp_core_get_user_displayname($user_id);
	$user_anniversary = date_create(xprofile_get_field_data(52, $user_id));

	if (date('m-d') >= $user_anniversary->format('m-d')) {


		$amount = cb_transactions_get_amount_from_anniversary($user_anniversary);

		if ($amount === 0) {
			return;
		}

		$transaction = new CB_Transactions_Transaction();

		$transaction->item_id = $user_id;
		$transaction->secondary_item_id = $user_id;
		$transaction->sender_id = $user_id;
		$transaction->recipient_id = $user_id;
		$transaction->date_sent = current_time('mysql');
		$transaction->log_entry = "Happy anniversary!";
		$transaction->component_name = 'transactions';
		$transaction->component_action = 'cb_anniversary_bits';
		$transaction->amount = $amount;

		$send = $transaction->send_bits();

	}

}
add_action('cb_actions', 'cb_transactions_anniversary_bits');

/**
 * CB Transactions Get Amount From Anniversary
 * 
 * @param DateTime $date
 * 
 * @return int The transaction amount appropriate for the anniversary date
 * 
 * @package ConfettiBits\Transactions
 * @since 1.3.0
 */
function cb_transactions_get_amount_from_anniversary($date) {

	$year_count = intval( date('Y') ) - intval( $date->format('Y') );

	if ( !is_int( $year_count ) ) {
		return 0;
	}

	$amounts = [ 25, 35, 45, 55, 75, 75, 75, 75, 75, 100 ];

	if ($year_count < 1) {
		return 0;
	}

	if ($year_count > 10) {
		return 100;
	}

	if ( !isset( $amounts[ $year_count - 1 ] ) ) {
		return 0;
	}

	return $amounts[ $year_count - 1 ];

}

/**
 * CB Transactions Get Leaderboard
 * 
 * Queries the database for the top 15 users by Confetti Bits balance
 * Also includes the current user if they aren't in the top 15
 *
 * @return array $results The top 15 users by Confetti Bits balance,
 * or the top 15 users by Confetti Bits balance with the current user included
 * 
 * @package ConfettiBits\Transactions
 * @since 1.3.0
 */
function cb_transactions_get_leaderboard( $limit = true, $previous = false ) {

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

	$results = $transaction->get_transactions([
		"select" => "recipient_id, SUM(CASE WHEN {$spend_modifier} AND amount < 0 THEN amount ELSE 0 END) + SUM(CASE WHEN {$earn_modifier} AND amount > 0 THEN amount ELSE 0 END) AS calculated_total",
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
		"groupby" => 'recipient_id',
		"orderby" => [ 'column' => "calculated_total", 'order' => "DESC" ]
	]);

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
 * CB Transactions Delete Transaction
 * 
 * Delete a transaction from the database. Do this at
 * your own risk, you cannot undo this action.
 * 
 * @param array $args { 
 *     An associative array of keys and values to check the
 *     database for. Accepts any property of a 
 *     CB_Transactions_Transaction object. 
 *     Example: ['recipient_id' => 5, 'component_action' => 'cb_activity_bits']
 *     Passing the above will delete all transactions where the
 *     recipient_id is 5, and the component_action is cb_activity_bits.
 * }
 * 
 * @return int The number of rows affected. @see $wpdb::delete()
 * 
 * @package ConfettiBits\Transactions
 * @since 2.3.0
 */
function cb_transactions_delete_transaction( $args = [] ) {

	$transaction = new CB_Transactions_Transaction();
	return $transaction->delete($args);

}

/**
 * CB Transactions New Transaction
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
 * @package ConfettiBits\Transactions
 * @since 3.0.0
 */
function cb_transactions_new_transaction($args = []) {

	$r = wp_parse_args($args, array(
		'item_id'           => 0,
		'secondary_item_id' => 0,
		'sender_id'         => 0,
		'recipient_id'		=> 0,
		'log_entry'			=> '',
		'component_name'    => '',
		'component_action'  => '',
		'date_sent'     	=> cb_core_current_date(),
		'amount'			=> 0
	));

	$feedback = ["type" => "error", "text" => ""];

	if ( empty($r['sender_id'] ) ) {
		$feedback["text"] = "Transaction failed. Invalid sender.";
		return $feedback;
	}

	if ( empty( $r['log_entry'] ) ) {
		$feedback["text"] = "Transaction failed. Please add a log entry.";
		return $feedback;
	}

	if ( empty( $r['recipient_id'] ) ) {
		$feedback["text"] = "Transaction failed. Invalid recipient.";
		return $feedback;
	}

	if ( empty( $r['amount'] ) ) {
		$feedback["text"] = "Transaction failed. Please enter a valid amount.";
		return $feedback;
	}

	if ( abs( $r['amount'] ) > cb_transactions_get_transfer_balance( $r['sender_id'] ) && ( $r['amount'] < 0 ) && !cb_is_user_admin() ) {
		$feedback["text"] = "Sorry, it looks like you don't have enough bits for that.";
		return $feedback;
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

	$send = $transaction->save();

	if ( false === is_int( $send ) ) {
		$feedback["text"] = "Transaction failed to process. Contact system administrator.";
		return $feedback;
	}

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
	$sender_name = cb_core_get_user_display_name($sender_id);
	$recipient_name = cb_core_get_user_display_name($id);

	$send = cb_transactions_new_transaction(
		array(
			'item_id'			=> $id,
			'secondary_item_id'	=> $sender_id,
			'sender_id'			=> $sender_id,
			'recipient_id' 		=> $id,
			'date_sent'			=> cb_core_current_date(),
			'log_entry'			=> 'User Removed - from ' .
			$sender_name,
			'component_name'    => 'transactions',
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

/**
 * A supplemental helper function that will let us process
 * event-based transactions without modifying existing
 * API stuff too much.
 * 
 * We'll likely add more robust error handling later on.
 * It's just getting down to the wire and we gotta push
 * this out ASAP.
 * 
 * @param array $args { 
 * 		An array of arguments. All required.
 * 		
 * 		@type int $event_id The ID of the event object.
 * 		@type int $recipient_id The ID of the recipient user.
 * 
 * }
 * 
 * @return int|bool|string Transaction ID on full success, false if something
 * 						   is missing, an error message if something when wrong.
 * 
 * @package ConfettiBits\Transactions
 * @since 3.0.0
 */
function cb_transactions_new_events_transaction( $args = [] ) {
	
	if ( empty( $args['event_id'] ) ) {
		return "Missing event ID.";
	}
	
	if ( empty( $args['recipient_id'] ) ) {
		return "Missing recipient ID.";
	}
	
	$event_id = intval($args['event_id']);
	$recipient_id = intval($args['recipient_id']);
	$event = new CB_Events_Event($event_id);
	
	if ( empty( $event->event_title ) ) {
		return "Event title not found.";
	}
	
	if ( empty( $event->participation_amount ) ) {
		return "Event participation amount not found.";
	}
	
	
	$transaction = new CB_Transactions_Transaction();
	$transaction->item_id = $event_id;
	$transaction->secondary_item_id = $recipient_id;
	$transaction->recipient_id = $recipient_id;
	$transaction->sender_id = $recipient_id;
	$transaction->component_name = "events";
	$transaction->component_action = "cb_events_new_transactions";
	$transaction->date_sent = cb_core_current_date();
	$transaction->amount = intval($event->participation_amount);
	$transaction->log_entry = $event->event_title;
	$transaction->event_id = $event_id;
	
	$save = $transaction->send_bits();
	
	return $save;
	
}

/**
 * A supplemental helper function that will let us process
 * contest-based transactions without modifying existing
 * API stuff too much.
 * 
 * We'll likely add more robust error handling later on.
 * It's just getting down to the wire and we gotta push
 * this out ASAP.
 * 
 * @param array $args { 
 * 		An array of arguments. All required.
 * 		
 * 		@type int $event_id The ID of the event object.
 * 		@type int $recipient_id The ID of the recipient user.
 * 
 * }
 * 
 * @return int|bool|string Transaction ID on full success, false if something
 * 						   is missing, an error message if something when wrong.
 * 
 * @package ConfettiBits\Transactions
 * @since 3.0.0
 */
function cb_transactions_new_contests_transaction( $args = [] ) {
	
	if ( empty( $args['contest_id'] ) ) {
		return "Missing contest ID.";
	}
	
	if ( empty( $args['recipient_id'] ) ) {
		return "Missing recipient ID.";
	}
	
	$contest_id = intval($args['contest_id']);
	$recipient_id = intval($args['recipient_id']);
	
	$contest = new CB_Events_Contest($contest_id);
	$event = new CB_Events_Event($contest->event_id);
	$transaction = new CB_Transactions_Transaction();
	$placement = cb_core_ordinal_suffix($contest->placement);
	
	if ( empty( $event->event_title ) ) {
		return "Event title not found.";
	}
	
	$transaction->item_id = $contest->event_id;
	$transaction->secondary_item_id = $recipient_id;
	$transaction->recipient_id = $recipient_id;
	$transaction->sender_id = $recipient_id;
	$transaction->component_name = "events";
	$transaction->component_action = "cb_events_contest_new_transactions";
	$transaction->date_sent = cb_core_current_date();
	$transaction->amount = intval($contest->amount);
	$transaction->log_entry = "{$placement} Place - {$event->event_title}";
	$transaction->event_id = $event_id;
	
	$save = $transaction->send_bits();
	
	return $save;
	
}