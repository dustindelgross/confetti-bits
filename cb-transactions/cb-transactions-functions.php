<?php
/**
 * Confetti Bits Transactions Functions. 
 * 
 * Hope this works. Good luck!
 */

defined('ABSPATH') || exit;

/**
 * CB Activity Bits
 * 
 * This hooks onto the BP Activity Posted Update 
 * action to give someone Confetti Bits when they
 * post an update.
 */
function cb_activity_bits($content, $user_id, $activity_id)
{

	$today = current_time('D', false);

	if ($today === 'Sat' || $today === 'Sun') {
		return;
	}

	$user_name = bp_get_loggedin_user_fullname();
	$total_count = 0;

	$transaction = new Confetti_Bits_Transactions_Transaction();
	$activity_transactions = $transaction->get_activity_bits_transactions_for_today($user_id);

	if (!empty($activity_transactions[0]['total_count'])) {

		$total_count = $activity_transactions[0]['total_count'];

		if ($total_count >= 1) {
			return;
		}

	}

	$activity_post = cb_send_bits(
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
 * CB Get Total For Current Day
 * 
 * This function gets the total number of Confetti Bits
 * that have been sent for the current day.
 * 
 * @return int $total The total number of Confetti Bits sent for the current day.
 */
function cb_get_total_for_current_day()
{

	$transaction = new Confetti_Bits_Transactions_Transaction();

	$user_id = get_current_user_id();

	if (cb_is_user_admin() && !cb_is_user_site_admin()) {
		$fetched_transactions = $transaction->get_send_bits_transactions_for_today($user_id);
	} else {
		$fetched_transactions = $transaction->get_transfer_bits_transactions_for_today($user_id);
	}

	if (!empty($fetched_transactions)) {
		$total = abs(intval($fetched_transactions[0]['amount']));
	} else {
		$total = 0;
	}

	return $total;

}

/**
 * CB Get Total For Current Day Notice
 * 
 * This function gets the total number of Confetti Bits
 * that have been sent for the current day and returns
 * a notice to the user.
 * 
 * @return string $notice The notice to be displayed to the user.
 */
function cb_get_total_for_current_day_notice()
{

	if (!cb_is_confetti_bits_component() || !cb_is_user_confetti_bits()) {
		return;
	}
	$user_id = get_current_user_id();
	$transaction = new Confetti_Bits_Transactions_Transaction();

	if (cb_is_user_admin() && !cb_is_user_site_admin()) {
		$fetched_transactions = $transaction->get_send_bits_transactions_for_today($user_id);
	} else {
		$fetched_transactions = $transaction->get_transfer_bits_transactions_for_today($user_id);
	}

	$amount = abs(intval($fetched_transactions[0]['amount']));

	if (empty($amount) || $amount == 0) {

		$notice = "You've sent 0 Confetti Bits so far today. You can send up to 20.";

	} else {

		if ($amount > 1 && $amount < 20) {
			$notice = sprintf(
				"You've sent %s Confetti Bits so far today. You can send up to %s more.",
				$amount, 20 - $amount
			);
		}

		if ($amount === 1) {
			$notice = sprintf(
				"You've sent %s Confetti Bit so far today. You can send up to 19 more.",
				$amount
			);
		}

		if ($amount >= 20) {
			$notice = sprintf(
				"You've already sent %s Confetti Bits today. Your counter should reset tomorrow!",
				$amount
			);
		}
	}

	return $notice;
}

/**
 * CB Total For Current Day Notice
 * 
 * This function gets the total number of Confetti Bits
 * that have been sent for the current day and displays
 * a notice to the user.
 */
function cb_total_for_current_day_notice() {
	echo cb_get_total_for_current_day_notice();
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

	cb_update_total_bits($r['recipient_id']);

}
add_action('cb_transactions_after_send', 'cb_transactions_notifications');

function cb_update_total_bits($user_id = 0, $meta_key = 'cb_total_bits', $previous_total = '')
{

	if (!cb_is_confetti_bits_component() || !cb_is_user_confetti_bits()) {
		return;
	}

	if ($user_id == 0) {
		$user_id = get_current_user_id();
	}

	$transaction_logs = new Confetti_Bits_Transactions_Transaction();
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

function cb_get_user_meta($user_id = 0, $meta_key, $unique = true)
{

	if ($user_id === 0) {
		return;
	}

	return get_user_meta($user_id, $meta_key, $unique);
}

function cb_update_user_meta($user_id = 0, $meta_key = '', $meta_value)
{

	if ($user_id === 0) {
		return;
	}

	return update_user_meta($user_id, $meta_key, $meta_value);
}

function cb_get_users_request_balance($user_id = 0)
{

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}
	$transactions = new Confetti_Bits_Transactions_Transaction();
	$total = (!empty($transactions->get_users_request_balance($user_id))) ? $transactions->get_users_request_balance($user_id) : 0;

	return $total;

}

/**
 * CB Users Request Balance
 * 
 * Display the users request balance.
 *
 * @param int $user_id The user ID.
 */
function cb_users_request_balance($user_id = 0) {

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}

	echo cb_get_users_request_balance($user_id);
}

/**
 * CB Get Users Request Balance Notice
 * 
 * Get the users request balance notice.
 *
 *
 * @param int $user_id The user ID.
 * @return string The users request balance notice.
 */
function cb_get_users_request_balance_notice($user_id = 0) {

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}

	$transactions = new Confetti_Bits_Transactions_Transaction();
	$total = $transactions->get_users_request_balance($user_id);
	$reset_date = cb_get_reset_date(array('action' => 'requests', 'cycle' => 'auto'));

	$notice = sprintf( 
		"You have %s Confetti Bits to spend on requests until %s.", 
		$total, $reset_date
	);

	return $notice;

}

/**
 * CB Users Balances
 * 
 * Display the users balances above the dashboard.
 *
 * @param int $user_id The user ID.
 */
function cb_users_balances() {
	echo cb_get_users_balances();
}

/**
 * CB Get Users Balances
 * 
 * Get the users balances to display above the dashboard.
 *
 *
 * @param int $user_id The user ID.
 * @return string The users balance notice.
 */
function cb_get_users_balances($user_id = 0) {

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}

	$transactions = new Confetti_Bits_Transactions_Transaction();
	$requests = $transactions->get_users_request_balance($user_id);
	$transfers = $transactions->get_users_transfer_balance($user_id);
	$request_reset_date = cb_get_reset_date(array('action' => 'requests', 'cycle' => 'auto'));
	$transfer_reset_date = cb_get_reset_date(array('action' => 'transfers', 'cycle' => 'auto'));

	$notice = sprintf( 
		"<div style='margin:10px;border:1px solid #dbb778;border-radius:10px;padding:.75rem;'>
			<h4 style='padding:0;margin:0;'>Confetti Bits Balances</h4>
			<div style='display:flex;'>
				<div style='flex: 0 1 200px;padding:0;'>
					<p style='margin:0;'>Confetti Bits Requests: %s</p>
					<p style='color:#d1cbc1;font-size:.75rem;margin:0;'>Until %s</p>
				</div>
				<div style='flex: 0 1 200px;padding:0;'>
					<p style='margin:0;'>Confetti Bits Transfers: %s</p>
					<p style='color:#d1cbc1;font-size:.75rem;margin:0;'>Until %s</p>
				</div>
			</div>
		</div>", 
		$requests, $request_reset_date, $transfers, $transfer_reset_date
	);

	return $notice;

}


function cb_users_request_balance_notice()
{
	echo cb_get_users_request_balance_notice();
}

function cb_get_users_transfer_balance($user_id = 0)
{

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}

	$transactions = new Confetti_Bits_Transactions_Transaction();
	$total = (!empty($transactions->get_users_transfer_balance($user_id))) ? $transactions->get_users_transfer_balance($user_id) : 0;

	return $total;
}

function cb_get_users_transfer_balance_notice($user_id = 0)
{

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}

	$total = cb_get_users_transfer_balance($user_id);

	$args = array(
		'action' => 'transfers',
		'cycle' => 'auto'
	);

	$plural = ($total > 1 || $total === 0) ? 'Confetti Bits' : 'Confetti Bit';

	$notice = 'You have ' . $total . ' ' . $plural . ' to spend on transfers until ' . cb_get_reset_date($args);

	return $notice;

}

function cb_users_transfer_balance_notice()
{
	echo cb_get_users_transfer_balance_notice();
}


function cb_get_users_previous_cycle_total($user_id = 0)
{

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}

	$transactions = new Confetti_Bits_Transactions_Transaction();
	$total = $transactions->get_users_balance($user_id);

	if (isset($total)) {
		$retval = $total;
	} else {
		$retval = 0;
	}

	return $retval;

}

function cb_get_users_total_earnings_from_previous_cycle($user_id = 0)
{

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}

	$transactions = new Confetti_Bits_Transactions_Transaction();
	$total = $transactions->get_users_earnings_from_previous_cycle($user_id);

	if (!empty($total)) {
		$retval = $total[0]['amount'];
	} else {
		$retval = 0;
	}

	return $retval;

}

function cb_get_reset_date($args = array())
{

	$r = wp_parse_args(
		$args,
		array(
			'action' => '',
			'cycle' => 'auto',
		)
	);

	$transaction = new Confetti_Bits_Transactions_Transaction();
	$current_spending_cycle_end = date_create($transaction->current_spending_cycle_end);
	$previous_spending_cycle_end = date_create($transaction->previous_spending_cycle_end);
	$current_cycle_end = date_create($transaction->current_cycle_end);
	$previous_cycle_end = date_create($transaction->previous_cycle_end);
	$current_date = date_create($transaction->current_date);

	if ('requests' === $r['action']) {
		if ('current' === $r['cycle']) {
			$notice_date = $current_spending_cycle_end->format('F jS, Y');
		}

		if ('previous' === $r['cycle']) {
			$notice_date = $previous_spending_cycle_end->format('F jS, Y');
		}

		if ('auto' === $r['cycle']) {
			if (
				$transaction->current_date > $transaction->previous_spending_cycle_end ||
				$transaction->current_date > strtotime($transaction->current_cycle_end . ' - 1 week')
			) {
				$notice_date = $current_spending_cycle_end->format('F jS, Y');
			} else {
				$notice_date = $previous_spending_cycle_end->format('F jS, Y');
			}
		}
	}

	if ('transfers' === $r['action']) {
		if ('current' === $r['cycle']) {
			$notice_date = $current_cycle_end->format('F jS, Y');
		}

		if ('previous' === $r['cycle']) {
			$notice_date = $previous_cycle_end->format('F jS, Y');
		}

		if ('auto' === $r['cycle']) {
			if (
				$transaction->current_date > $transaction->previous_cycle_end ||
				$transaction->current_date > strtotime($transaction->current_cycle_end . ' - 1 week')
			) {
				$notice_date = $current_cycle_end->format('F jS, Y');
			} else {
				$notice_date = $previous_cycle_end->format('F jS, Y');
			}
		}
	}

	return $notice_date;
}

function cb_get_reset_date_notice()
{

	$transaction = new Confetti_Bits_Transactions_Transaction();
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

function cb_calculate_activity_bits($activities, $transactions)
{

	$activity_data = array();
	$transaction_data = array();

	if (!isset($activities, $transactions)) {
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

		$transaction_id = $transaction['user_id'];
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

function cb_update_user_activity_bits_for_current_cycle($user_id = 0)
{

	if (!cb_is_confetti_bits_component() || !cb_is_user_confetti_bits()) {
		return;
	}

	$today = current_time('D', false);

	if ($today === 'Sat' || $today === 'Sun') {
		return;
	}

	if ($user_id === 0 || empty($user_id)) {
		$user_id = get_current_user_id();
	}

	$transaction_object = new Confetti_Bits_Transactions_Transaction();
	$transactions = $transaction_object->get_activity_bits_transactions_from_current_cycle($user_id);
	$activities = $transaction_object->get_activity_posts_for_user($user_id);
	$missing_transactions = cb_calculate_activity_bits($activities, $transactions);

	$user_name = bp_core_get_user_displayname($user_id);

	if (!empty($missing_transactions)) {
		foreach ($missing_transactions as $date_sent => $id) {
			$activity_post = cb_send_bits(
				array(
					'item_id' => 1,
					'secondary_item_id' => $id,
					'user_id' => $id,
					'sender_id' => $id,
					'sender_name' => $user_name,
					'recipient_id' => $id,
					'recipient_name' => $user_name,
					'identifier' => $id,
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
add_action('bp_actions', 'cb_update_user_activity_bits_for_current_cycle', 10, 1);


function cb_groups_activity_notifications($content, $user_id, $group_id, $activity_id)
{

	$group = bp_groups_get_activity_group($group_id);
	$user_ids = BP_Groups_Member::get_group_member_ids($group_id);

	foreach ((array) $user_ids as $notified_user_id) {

		if ('no' === bp_get_user_meta($notified_user_id, 'cb_group_activity', true)) {
			continue;
		}

		$unsubscribe_args = array(
			'user_id' => $notified_user_id,
			'notification_type' => 'cb-groups-activity-post',
		);

		$args = array(
			'tokens' => array(
				'group_member.name' => bp_core_get_user_displayname($user_id),
				'group.name' => $group->name,
				'group.id' => $group_id,
				'group.url' => esc_url(bp_get_group_permalink($group)),
				'group_activity.content' => esc_html($content),
				'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
			),
		);
		bp_notifications_add_notification(
			array(
				'user_id' => $notified_user_id,
				'item_id' => $group_id,
				'secondary_item_id' => $user_id,
				'component_name' => 'groups',
				'component_action' => 'activity_update',
				'allow_duplicate' => true,
			)
		);
		bp_send_email('cb-groups-activity-post', (int) $notified_user_id, $args);
	}
}
add_action('bp_groups_posted_update', 'cb_groups_activity_notifications', 10, 4);

function cb_add_confetti_captain_badges()
{
	if ((!cb_is_user_site_admin() || !bp_is_user_profile()) && !bp_is_activity_component()) {
		return;
	}

	$cb = Confetti_Bits();

	wp_enqueue_script('cb_member_profile_badge_js', $cb->plugin_url . '/assets/js/cb-member-profile.js', array('jquery'));
	wp_enqueue_style('cb_member_profile_badge_css', $cb->plugin_url . '/assets/css/cb-member-profile.css');

}
add_action('wp_enqueue_scripts', 'cb_add_confetti_captain_badges');

function cb_member_confetti_captain_class($class, $item_id)
{

	$is_confetti_captain = groups_is_user_member($item_id, 1);
	if (is_int($is_confetti_captain)) {
		$class .= ' confetti-captain';
	}
	return $class;
}
add_filter('bp_core_avatar_class', 'cb_member_confetti_captain_class', 10, 2);

function cb_member_confetti_captain_profile_badge()
{

	$badge = '';
	$user_id = bp_displayed_user_id();
	$is_confetti_captain = groups_is_user_member($user_id, 1);
	if (is_int($is_confetti_captain)) {
		$badge .= '<div class="confetti-captain-profile-label-container"><div class="confetti-captain-badge-container"><div class="confetti-captain-badge-medium"></div></div><p class="confetti-captain-profile-label">Confetti Captain</p></div>';
	}
	echo $badge;
}
add_filter('bp_before_member_in_header_meta', 'cb_member_confetti_captain_profile_badge');

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
		!cb_is_user_confetti_bits() ||
		!bp_is_post_request() ||
		!cb_is_confetti_bits_component() ||
		!wp_verify_nonce($_POST['cb_sitewide_notice_nonce'], 'cb_sitewide_notice_post')
	) {
		return;
	}

	$redirect_to = bp_loggedin_user_domain() . cb_get_transactions_slug();
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

	$transaction = new Confetti_Bits_Transactions_Transaction();

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
	$user_birthday = date_create(xprofile_get_field_data(51, $user_id));

	if (date('m-d') >= $user_birthday->format('m-d')) {

		$user_name = bp_get_loggedin_user_fullname();
		$user_id = get_current_user_id();

		$transaction = new Confetti_Bits_Transactions_Transaction();

		$transaction->item_id = $user_id;
		$transaction->secondary_item_id = $user_id;
		$transaction->user_id = $user_id;
		$transaction->sender_id = $user_id;
		$transaction->sender_name = $user_name;
		$transaction->recipient_id = $user_id;
		$transaction->recipient_name = $user_name;
		$transaction->identifier = $user_id;
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
	$user_anniversary = date_create(xprofile_get_field_data(52, $user_id));

	if (date('m-d') >= $user_anniversary->format('m-d')) {

		$user_name = bp_get_loggedin_user_fullname();
		$user_id = get_current_user_id();
		$amount = cb_get_amount_from_anniversary($user_anniversary);

		if ($amount === 0) {
			return;
		}

		$transaction = new Confetti_Bits_Transactions_Transaction();

		$transaction->item_id = $user_id;
		$transaction->secondary_item_id = $user_id;
		$transaction->user_id = $user_id;
		$transaction->sender_id = $user_id;
		$transaction->sender_name = $user_name;
		$transaction->recipient_id = $user_id;
		$transaction->recipient_name = $user_name;
		$transaction->identifier = $user_id;
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