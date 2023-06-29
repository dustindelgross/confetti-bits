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
 * CB Get Transactions Slug
 * 
 * Get the slug for the transactions component. 
 * This is deprecated, and we're working to remove this
 * from the codebase.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.0.0
 */
function cb_get_transactions_slug() {
	$cb = Confetti_Bits();
	return $cb->transactions->slug;
}


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
		'component_name' => 'confetti_bits',
		'component_action' => 'cb_activity_bits',
		'amount' => 1
	]);
}
add_action('bp_activity_posted_update', 'cb_activity_bits', 10, 3);

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
function cb_transactions_get_total_sent_today() {

	$transaction = new CB_Transactions_Transaction();
	$date = new DateTimeImmutable("now");
	$user_id = get_current_user_id();
	$action = ( cb_is_user_admin() && !cb_is_user_site_admin() ) ? 'cb_send_bits' : 'cb_transfer_bits';
	$args = array(
		'select' => "SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as amount",
		'where' => array(
			'date_query' => array(
				'year' => $date->format('Y'),
				'month' => $date->format('m'),
				'day' => $date->format('d'),
			),
			'sender_id' => $user_id,
			'component_action' => $action,
		)
	);

	$fetched_transactions = $transaction->get_transactions($args);
	$total = (!empty($fetched_transactions)) ? abs(intval($fetched_transactions[0]['amount'])) : 0;
	return $total;

}

/**
 * CB Transactions Get Request Balance
 * 
 * Get the balance available to a user for the current spending
 * cycle. 
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
	$spend = "`date_sent` >= '{$cb->spend_start}' AND amount < 0";
	$earn = "`date_sent` >= '{$cb->earn_start}' AND amount > 0";

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

	$reset_date = get_option('cb_reset_date');
	$cb = Confetti_Bits();
	$transactions = new CB_Transactions_Transaction();
	$date = new DateTimeImmutable($reset_date);
	$spend = "`date_sent` >= '{$cb->spend_start}' AND amount < 0";
	$earn = "`date_sent` >= '{$cb->earn_start}' AND amount > 0";

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
					'component_name' => 'confetti_bits',
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
 * CB Is Multiarray
 * 
 * Checks if the parameter is a multi-dimensional array.
 * 
 * @param array $arr The array to check.
 * 
 * @package ConfettiBits\Core
 * @since 1.3.0
 */
function cb_is_multi_array(array $arr)
{
	rsort($arr);
	return (isset($arr[0]) && is_array($arr[0]));
}

/**
 * Sends out a sitewide notice.
 * 
 * Use this to send out non-critical updates that are 
 * intended to be informative or nice to know, such as
 * an upcoming or recent update, new feature, etc.
 * 
 * @package ConfettiBits\Core
 * @since 1.2.0
 */
function cb_core_send_sitewide_notice()
{
	if (
		!cb_is_confetti_bits_component() ||
		!cb_is_post_request() || 
		$_POST['cb_sitewide_notice_heading'] === '' || 
		$_POST['cb_sitewide_notice_body'] === ''
	) {
		return;
	}

	$redirect_to = Confetti_Bits()->page;
	$feedback = ['type' => 'error', 'text' => ''];

	$username = cb_core_get_user_display_name(intval($_POST['cb_sitewide_notice_user_id']));
	$subject = trim($_POST['cb_sitewide_notice_heading']);
	$message = trim($_POST['cb_sitewide_notice_body']) . " - {$username}";

	$notice = messages_send_notice($subject, $message);

	if ($notice) {
		$feedback['type'] = 'success';
		$feedback['text'] = 'Sitewide notice was successfully posted.';
	} else {
		$feedback['text'] = 'Failed to send sitewide notice.';
	}

	bp_core_add_message($feedback['text'], $feedback['type']);
	bp_core_redirect($redirect_to);

}
add_action('cb_actions', 'cb_core_send_sitewide_notice');

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
		$transaction->component_name = 'confetti_bits';
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
		$transaction->component_name = 'confetti_bits';
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
		'date_sent'			=> '',
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
		$feedback = "Transaction failed. Please enter a valid amount.";
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

	$send = $transaction->send_bits();

	if ( false === is_int( $send ) ) {
		$feedback["text"] = "Transaction failed to process. Contact system administrator.";
		return $feedback;
	}

	do_action( 'cb_transactions_new_transaction', $r );

	return $transaction->id;

}