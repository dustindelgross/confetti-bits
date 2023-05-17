<?php
/**
 * Confetti Bits Transactions Functions. 
 * 
 * Hope this works. Good luck!
 */
defined('ABSPATH') || exit;

/**
 * CB AJAX Get Transactions
 * 
 * Gets transactions for a user based on the user_id passed in the $_GET array.
 * 
 * @since 2.3.0
 * @return JSON {
 * 		'text': JSON (JSON encoded array of transactions, or error message),
 * 		'type': string (success or error)
 * }
 */
function cb_ajax_get_transactions() {

	if ( ! cb_is_get_request() || !isset( $_GET['user_id'] ) ) {
		return;
	}

	$feedback = array(
		'text' => '',
		'type' => 'error'
	);

	$select = isset($_GET['total']) ? 'count(id) as total_count' : 'sender_id, recipient_id, amount, log_entry, date_sent';

	$user_id = intval( $_GET['user_id'] );
	$page = isset( $_GET['page'] ) ? intval( $_GET['page'] ) : 1;
	$per_page = isset($_GET['per_page']) ? intval( $_GET['per_page'] ) : 10;
	$transaction = new CB_Transactions_Transaction();
	$paged_transactions = $transaction->get_transactions(array(
		'select' => $select,
		'where' => array(
			'recipient_id' => $user_id,
			'sender_id' => $user_id,
			'or' => true
		),
		'orderby' => array('id', 'DESC'),
		'pagination' => array(
			'page' => $page, 
			'per_page' => $per_page
		)
	));

	if ( $paged_transactions ) {
		$feedback['text'] = $paged_transactions;
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = json_encode("No transactions found");
	}

	echo json_encode( $feedback );
	die();

}
add_action( 'wp_ajax_cb_transactions_get_transactions', 'cb_ajax_get_transactions' );

/**
 * CB Get Transactions Slug
 * 
 * Get the slug for the transactions component. 
 * This is deprecated, and we're working to remove this
 * from the codebase.
 * 
 * @since Confetti_Bits 1.0.0
 * 
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
 * @since Confetti_Bits 2.2.0
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

	$total_count = 0;

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

	if (!empty($activity_transactions[0]['total_count'])) {

		$total_count = $activity_transactions[0]['total_count'];

		if ($total_count >= 1) {
			return;
		}

	}

	$activity_post = cb_transactions_send_bits(
		array(
			'item_id' => 1,
			'secondary_item_id' => $user_id,
			'user_id' => $user_id,
			'sender_id' => $user_id,
			'sender_name' => $user_name,
			'recipient_id' => $user_id,
			'recipient_name' => $user_name,
			'identifier' => $user_id,
			'date_sent' => bp_core_current_time(false),
			'log_entry' => 'Posted a new update',
			'component_name' => 'confetti_bits',
			'component_action' => 'cb_activity_bits',
			'amount' => 1,
			'error_type' => 'wp_error',
		)
	);
}
add_action('bp_activity_posted_update', 'cb_activity_bits', 10, 3);

/**
 * CB Transactions Get Total Sent Today
 * 
 * This function gets the total number of Confetti Bits
 * that have been sent for the current day.
 * 
 * @return int $total The total number of Confetti Bits sent for the current day.
 */
function cb_transactions_get_total_sent_today()
{

	$transaction = new CB_Transactions_Transaction();
	$date = new DateTimeImmutable("now");
	$user_id = get_current_user_id();
	$action = cb_is_user_admin() ? 'cb_send_bits' : 'cb_transfer_bits';
	$args = array(
		'select' => "recipient_id, SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as amount",
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
 * CB Bits Request Sender Email Notification
 * 
 * This function sends an email notification to the request sender
 * 
 * @param array $args The arguments for the email notification.
 * 
 * @var int $recipient_id The ID of the recipient.
 * @var int $sender_id The ID of the sender.
 * @var int $amount The amount of Confetti Bits being sent.
 * @var string $request_item The item being requested.
 * 
 */
function cb_bits_request_sender_email_notification($args = array()) {

	$r = wp_parse_args(
		$args,
		array(
			'recipient_id' => 0,
			'sender_id' => 0,
			'amount' => 0,
			'request_item' => '',
		)
	);

	$request_fulfillment_name = bp_core_get_user_displayname($r['sender_id']);

	if ('no' != bp_get_user_meta($r['recipient_id'], 'cb_bits_request', true)) {

		$unsubscribe_args = array(
			'user_id' => $r['recipient_id'],
			'notification_type' => 'cb-send-bits-request-email',
		);

		$email_args = array(
			'tokens' => array(
				'request_fulfillment.name' => $request_fulfillment_name,
				'request_sender.item' => $r['request_item'],
				'request.amount' => abs($r['amount']),
				'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
			),
		);

		// the address that gets this email is going to be for the person that sends the request
		bp_send_email('cb-send-bits-request-email', $r['recipient_id'], $email_args);
	}

	do_action('cb_transactions_sent_request_email_notification', $args);

}

function cb_bits_request_fulfillment_email_notification($args = array())
{

	$r = wp_parse_args(
		$args,
		array(
			'recipient_id' => 0,
			'sender_id' => 0,
			'email_address' => '',
			'amount' => 0,
			'request_item' => '',
		)
	);

	$request_recipient_name = bp_core_get_user_displayname($r['recipient_id']);

	if ('no' != bp_get_user_meta($r['recipient_id'], 'cb_bits_request', true)) {

		$unsubscribe_args = array(
			'user_id' => $r['recipient_id'],
			'notification_type' => 'cb-bits-request-email',
		);

		$email_args = array(
			'tokens' => array(
				'request_sender.id' => $r['recipient_id'],
				'request_sender.name' => $request_recipient_name,
				'request_sender.item' => $r['request_item'],
				'request.amount' => $r['amount'],
				'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
			),
		);

		if ($r['email_address'] === 'payables') {

			bp_send_email('cb-bits-request-email', 'payables@celebrationtitlegroup.com', $email_args);

		} else {

			bp_send_email('cb-bits-request-email', 'dustin@celebrationtitlegroup.com', $email_args);

		}

	}

	do_action('cb_transactions_sent_request_fulfillment_email_notification', $args);
}

function cb_transactions_notifications($data = array())
{

	$r = wp_parse_args(
		$data,
		array(
			'item_id' => '',
			'sender_id' => '',
			'recipient_id' => '',
			'component_action' => '',
			'amount' => 0,
			'log_entry' => ''
		)
	);

	if (
		empty($data) ||
		empty($r['sender_id']) ||
		empty($r['recipient_id']) ||
		empty($r['component_action'])
	) {
		return;
	}

	switch ($r['component_action']) {

		case ('cb_bits_request'):

			bp_notifications_add_notification(
				array(
					'user_id' => $r['sender_id'],
					'item_id' => $r['sender_id'],
					'secondary_item_id' => $r['recipient_id'],
					'component_name' => 'confetti_bits',
					'component_action' => $r['component_action'],
					'date_notified' => current_time('mysql', true),
					'is_new' => 1,
					'allow_duplicate' => true,
				)
			);

			cb_bits_request_fulfillment_email_notification(
				array(
					'recipient_id' => $r['recipient_id'],
					'sender_id' => $r['sender_id'],
					'request_item' => $r['log_entry'],
				)
			);

			cb_bits_request_fulfillment_email_notification(
				array(
					'recipient_id' => $r['recipient_id'],
					'sender_id' => $r['sender_id'],
					'email_address' => 'payables',
					'request_item' => $r['log_entry'],
				)
			);


			// the id for this notification is in the array
			cb_bits_request_sender_email_notification(
				array(
					'recipient_id' => $r['recipient_id'],
					'sender_id' => $r['sender_id'],
					'request_item' => $r['log_entry'],
					'amount' => $r['amount'],
				)
			);
			break;

		case ('cb_send_bits'):

			bp_notifications_add_notification(
				array(
					'user_id' => $r['recipient_id'],
					'item_id' => $r['sender_id'],
					'secondary_item_id' => $r['sender_id'],
					'component_name' => 'confetti_bits',
					'component_action' => $r['component_action'],
					'date_notified' => current_time('mysql', true),
					'is_new' => 1,
					'allow_duplicate' => true,
				)
			);
			break;

		case ('cb_activity_bits'):

			bp_notifications_add_notification(
				array(
					'user_id' => $r['recipient_id'],
					'item_id' => $r['amount'],
					'secondary_item_id' => $r['sender_id'],
					'component_name' => 'confetti_bits',
					'component_action' => $r['component_action'],
					'date_notified' => current_time('mysql', true),
					'is_new' => 1,
				)
			);
			break;

		case ('cb_import_bits'):

			bp_notifications_add_notification(
				array(
					'user_id' => $r['recipient_id'],
					'item_id' => $r['sender_id'],
					'secondary_item_id' => $r['sender_id'],
					'component_name' => 'confetti_bits',
					'component_action' => $r['component_action'],
					'date_notified' => current_time('mysql', true),
					'is_new' => 1,
				)
			);
			break;

		case ('cb_participation_status_update'):

			bp_notifications_add_notification(
				array(
					'user_id' => $r['recipient_id'],
					'item_id' => $r['recipient_id'],
					'secondary_item_id' => $r['sender_id'],
					'component_name' => 'confetti_bits',
					'component_action' => $r['component_action'],
					'date_notified' => current_time('mysql', true),
					'is_new' => 1,
				)
			);
			break;

		case ('cb_birthday_bits'):

			$unsubscribe_args = array(
				'user_id' => $r['recipient_id'],
				'notification_type' => 'cb-birthday-bits',
			);

			$email_args = array(
				'tokens' => array(
					'user.first_name' => xprofile_get_field_data(1, $r['recipient_id']),
					'user.cb_url' => bp_core_get_user_domain($r['recipient_id']) . 'confetti-bits/',
					'transaction.amount' => $r['amount'],
					'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
				)
			);

			bp_notifications_add_notification(
				array(
					'user_id' => $r['recipient_id'],
					'item_id' => $r['recipient_id'],
					'secondary_item_id' => $r['recipient_id'],
					'component_name' => 'confetti_bits',
					'component_action' => $r['component_action'],
					'date_notified' => current_time('mysql', true),
					'is_new' => 1,
					'allow_duplicate' => true,
				)
			);

			bp_send_email('cb-birthday-bits', $r['recipient_id'], $email_args);
			break;

		case ('cb_anniversary_bits'):

			$unsubscribe_args = array(
				'user_id' => $r['recipient_id'],
				'notification_type' => 'cb-anniversary-bits',
			);

			$email_args = array(
				'tokens' => array(
					'user.first_name' => xprofile_get_field_data(1, $r['recipient_id']),
					'user.cb_url' => bp_core_get_user_domain($r['recipient_id']) . 'confetti-bits/',
					'transaction.amount' => $r['amount'],
					'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
				)
			);

			bp_notifications_add_notification(
				array(
					'user_id' => $r['recipient_id'],
					'item_id' => $r['recipient_id'],
					'secondary_item_id' => $r['recipient_id'],
					'component_name' => 'confetti_bits',
					'component_action' => $r['component_action'],
					'date_notified' => current_time('mysql', true),
					'is_new' => 1,
					'allow_duplicate' => true,
				)
			);

			bp_send_email('cb-anniversary-bits', $r['recipient_id'], $email_args);
			break;


		default:

			bp_notifications_add_notification(
				array(
					'user_id' => $r['recipient_id'],
					'item_id' => $r['sender_id'],
					'secondary_item_id' => $r['recipient_id'],
					'component_name' => 'confetti_bits',
					'component_action' => $r['component_action'],
					'date_notified' => current_time('mysql', true),
					'is_new' => 1,
				)
			);

	}

	// cb_update_total_bits($r['recipient_id']);

}
add_action('cb_transactions_after_send', 'cb_transactions_notifications');

/*
function cb_update_total_bits($user_id = 0, $meta_key = 'cb_total_bits', $previous_total = '')
{

	if (!cb_is_confetti_bits_component() || !cb_is_user_confetti_bits()) {
		return;
	}

	if ($user_id == 0) {
		$user_id = get_current_user_id();
	}

	$transaction_logs = new CB_Transactions_Transaction();
	$transaction_query = $transaction_logs->get_users_balance($user_id);

	$total = $transaction_query;

	return update_user_meta($user_id, $meta_key, $total, $previous_total);

}


function cb_get_total_bits($user_id, $meta_key = 'cb_total_bits', $unique = true)
{

	if ($user_id === 0) {
		return;
	}

	$total = get_user_meta($user_id, $meta_key, $unique);

	return $total;

}

function cb_get_total_bits_notice($user_id, $meta_key = 'cb_total_bits', $unique = true)
{

	if ($user_id === 0) {
		return;
	}

	$notice = '';
	$total = get_user_meta($user_id, $meta_key, $unique);

	if ($total == 1) {
		$notice = 'You currently have ' . $total . ' Confetti Bit.';
	}

	if ($total < 1 || $total == 0) {
		$notice = 'You don\'t currently have any Confetti Bits.';
	}

	if ($total > 1) {
		$notice = 'You currently have ' . $total . ' Confetti Bits.';
	}

	return $notice;

}

function cb_get_user_meta($user_id = 0, $meta_key = '', $unique = true)
{

	if ($user_id === 0) {
		return;
	}

	return get_user_meta($user_id, $meta_key, $unique);
}

function cb_update_user_meta($user_id = 0, $meta_key = '', $meta_value = '')
{

	if ($user_id === 0) {
		return;
	}

	return update_user_meta($user_id, $meta_key, $meta_value);
}

*/

/**
 * CB Transactions Get Request Balance
 * 
 * Get the balance available to a user for the current spending
 * cycle. 
 * 
 * @since Confetti_Bits 1.3.0
 * @param int $user_id The ID for the user whose balance we want.
 * @return int The calculated balance available for requests.
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
 * @since Confetti_Bits 1.3.0
 * @param int $user_id The ID for the user whose balance we want.
 * @return int The calculated balance available for transfers.
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

/*

function cb_get_reset_date($args = array())
{

	$r = wp_parse_args(
		$args,
		array(
			'action' => '',
			'cycle' => 'auto',
		)
	);

	$transaction = new CB_Transactions_Transaction();
	$format = 'F jS, Y';
	$reset_date = get_option("cb_reset_date");
	$date = new DateTimeImmutable($reset_date);
	$earn_start = $date->modify("- 1 year")->format($format);
	$spend_start = $date->modify("- 1 year + 1 month")->format($format);
	$spend_end = $date->modify("+ 1 month")->format($format);

	if ('requests' === $r['action']) {
		if ('current' === $r['cycle']) {
			$notice_date = $date->format($format);
		}

		if ('previous' === $r['cycle']) {
			$notice_date = $spend_start;
		}

		if ('auto' === $r['cycle']) {
			if (
				$transaction->current_date > $earn_start ||
				$transaction->current_date > $date->format($format)
			) {
				$notice_date = $spend_end;
			} else {
				$notice_date = $spend_start;
			}
		}
	}

	if ('transfers' === $r['action']) {
		if ('current' === $r['cycle']) {
			$notice_date = $date->format($format);
		}

		if ('previous' === $r['cycle']) {
			$notice_date = $earn_start;
		}

		if ('auto' === $r['cycle']) {
			if (
				$transaction->current_date > $earn_start ||
				$transaction->current_date > $date->format($format)
			) {
				$notice_date = $date->format($format);
			} else {
				$notice_date = $earn_start;
			}
		}
	}

	return $notice_date;
}

function cb_get_reset_date_notice()
{

	$transaction = new CB_Transactions_Transaction();
	$current_spending_cycle_end = date_create($transaction->current_spending_cycle_end);
	$previous_spending_cycle_end = date_create($transaction->previous_spending_cycle_end);
	$current_date = date_create($transaction->current_date);
	$notice = 'Today\'s date is ' . $current_date->format('l, M jS, Y') . '. ';
	if (
		$transaction->current_date > $transaction->previous_spending_cycle_end ||
		$transaction->current_date > strtotime($transaction->current_cycle_end . ' - 1 week')
	) {
		$notice .= 'The current Confetti Bits Spending Cycle ends on ' . $current_spending_cycle_end->format('F jS, Y.');
	} else {
		$countdown = date_diff($current_date, $previous_spending_cycle_end);
		$notice .= 'The current Confetti Bits Spending Cycle ends in ' . $countdown->format('%d days.');
	}

	return $notice;

}


function cb_reset_date()
{
	echo cb_get_reset_date();
}
*/

/**
 * CB Calculate Activity Bits
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
 * @since Confetti_Bits 1.3.0
 */
function cb_calculate_activity_bits($activities = array(), $transactions = array() )
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
 * @since Confetti_Bits 1.3.0
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
		),
		"orderby" => array( "id", "DESC" )
	);

	return $transactions->get_transactions($activity_bits_args);

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
 * @since Confetti_Bits 1.3.0
 * 
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
	$missing_transactions = cb_calculate_activity_bits($activities, $transactions);

	if (!empty($missing_transactions)) {
		foreach ($missing_transactions as $date_sent => $id) {
			$activity_post = cb_send_bits(
				array(
					'item_id' => 1,
					'secondary_item_id' => $id,
					'sender_id' => $id,
					'recipient_id' => $id,
					'date_sent' => date('Y-m-d H:i:s', strtotime($date_sent)),
					'log_entry' => 'Posted a new update',
					'component_name' => 'confetti_bits',
					'component_action' => 'cb_activity_bits',
					'amount' => 1,
					'error_type' => 'wp_error',
				)
			);
		}
	}
}
add_action('bp_actions', 'cb_transactions_check_activity_bits', 10, 1);

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
 * @since Confetti_Bits 1.3.0
 */
function cb_transactions_get_activity_posts( $user_id = 0 ) {
	
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}
	
	$transactions_obj = new CB_Transactions_Transaction();
	return $transactions_obj->get_activity_posts_for_user($user_id);
	
}


/**
 * Confetti Bits Multiarray Check
 * 
 * Checks if the parameter is a multi-dimensional array.
 * 
 * @param array $arr The array to check.
 * 
 */
function cb_is_multi_array(array $arr)
{
	rsort($arr);
	return (isset($arr[0]) && is_array($arr[0]));
}

function cb_send_sitewide_notice()
{
	if (
		!cb_is_confetti_bits_component() ||
		!cb_is_post_request() ||
		!wp_verify_nonce($_POST['cb_sitewide_notice_nonce'], 'cb_sitewide_notice_post')
	) {
		return;
	}


	$redirect_to = Confetti_Bits()->page;
	$success = false;
	$feedback = '';

	$username = bp_core_get_user_displayname(intval($_POST['cb_sitewide_notice_user_id']));
	$subject = !empty($_POST['cb_sitewide_notice_heading']) ? 
		trim($_POST['cb_sitewide_notice_heading']) : '';
	$message = !empty($_POST['cb_sitewide_notice_body']) ? 
		trim($_POST['cb_sitewide_notice_body']) . " - {$username}" : '';

	if (messages_send_notice($subject, $message)) {
		$success = true;
		$feedback = 'Sitewide notice was successfully posted.';
	} else {
		$feedback = 'Failed to send sitewide notice.';
	}

	if (!empty($feedback)) {
		$type = (true === $success)
			? 'success'
			: 'error';
		bp_core_add_message($feedback, $type);
	}

	bp_core_redirect($redirect_to);

}
add_action('bp_actions', 'cb_send_sitewide_notice');

/**
 * CB Has Bits
 * 
 * Checks whether the user has gotten 
 * Confetti Bits for a specific action this year.
 * 
 */
function cb_has_bits($action = '')
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
 * CB Birthday Bits
 * 
 * Gives the user Confetti Bits on their birthday.
 */
function cb_birthday_bits()
{

	$birthday_bits = cb_has_bits('birthday');

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
add_action('bp_actions', 'cb_birthday_bits');

/**
 * CB Anniversary Bits
 * 
 * Gives the user Confetti Bits on their anniversary.
 */
function cb_anniversary_bits()
{

	$anniversary_bits = cb_has_bits('anniversary');

	if (!empty($anniversary_bits)) {
		return;
	}

	$user_id = get_current_user_id();
	$user_name = bp_core_get_user_displayname($user_id);
	$user_anniversary = date_create(xprofile_get_field_data(52, $user_id));

	if (date('m-d') >= $user_anniversary->format('m-d')) {


		$amount = cb_get_amount_from_anniversary($user_anniversary);

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
add_action('bp_actions', 'cb_anniversary_bits');

/**
 * CB Get Amount From Anniversary
 * 
 * @param DateTime $date
 * @return int $amount The transaction amount appropriate for the anniversary date
 */
function cb_get_amount_from_anniversary($date)
{

	$amount = 0;
	$current_year = date('Y');
	$anniversary_year = $date->format('Y');
	$year_count = $current_year - $anniversary_year;

	if ($year_count < 1) {
		$amount = 0;
	} elseif ($year_count > 10) {
		$amount = 100;
	} else {
		switch ($year_count) {
			case 1:
				$amount = 25;
				break;
			case 2:
				$amount = 35;
				break;
			case 3:
				$amount = 45;
				break;
			case 4:
				$amount = 55;
				break;
			case 5:
			case 6:
			case 7:
			case 8:
			case 9:
				$amount = 75;
				break;
			case 10:
				$amount = 100;
				break;
		}
	}

	return $amount;

}