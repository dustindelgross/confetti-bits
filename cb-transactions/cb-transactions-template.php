<?php
defined('ABSPATH') || exit;
/**
 * CB Transactions Get Total Sent Today Notice
 * 
 * This function gets the total number of Confetti Bits
 * that have been sent for the current day and returns
 * a notice to the user.
 * 
 * @since Confetti_Bits 2.2.0
 * @return string $notice The notice to be displayed to the user.
 */
function cb_transactions_get_total_sent_today_notice()
{

	if (!cb_is_confetti_bits_component() ) {
		return;
	}

	$amount = cb_transactions_get_total_sent_today();

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
 * CB Transactions Total Sent Today Notice
 * 
 * Output the total number of Confetti Bits
 * that the user has sent for the current day.
 * 
 * @since Confetti_Bits 2.2.0
 */
function cb_transactions_total_sent_today_notice() {
	echo cb_transactions_get_total_sent_today_notice();
}


/**
 * CB Users Request Balance
 * 
 * Display the users request balance.
 * @since Confetti_Bits 2.2.0
 * @param int $user_id The user ID.
 */
function cb_transactions_request_balance($user_id = 0) {

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}

	echo cb_transactions_get_request_balance($user_id);
}

/**
 * CB Transactions Get Request Balance Notice
 * 
 * Get the users request balance notice.
 * 
 * @since Confetti_Bits 2.2.0
 * @param int $user_id The user ID.
 * @return string The users request balance notice.
 */
function cb_transactions_get_request_balance_notice($user_id = 0) {

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}

	$total = cb_transactions_get_request_balance($user_id);
	$notice = sprintf( 
		"You have %d Confetti Bits to spend on requests until %s.", 
		$total, $date->format( 'F jS, Y')
	);

	return $notice;

}

/**
 * CB Transactions Request Balance Notice
 * 
 * Output the users request balance notice.
 * @since Confetti_Bits 2.2.0
 */
function cb_transactions_request_balance_notice()
{
	echo cb_transactions_get_request_balance_notice();
}


/**
 * CB Transactions Balances Notice
 * 
 * Assemble the markup for both the user's 
 * transfer balance and their request balance.
 * 
 * @since Confetti_Bits 2.3.0
 * 
 * @param int $user_id The id of the user whose balance notice
 * we're assembling. Defaults to current logged-in user.
 * 
 * @return string $notice the formatted notice markup.
 */
function cb_transactions_get_balances_notice($user_id = 0) {

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}

	$cb = Confetti_Bits();
	$spend_end = new DateTimeImmutable($cb->spend_end);
	$earn_end = new DateTimeImmutable($cb->earn_end);
	$transactions = new CB_Transactions_Transaction();
	$requests = cb_transactions_get_request_balance($user_id);
	$transfers = cb_transactions_get_transfer_balance($user_id);
	

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
		$requests, $spend_end->format('F jS, Y'), $transfers, $earn_end->format('F jS, Y')
	);

	return $notice;

}


/**
 * CB Transactions Balances Notice
 * 
 * Display the users balances above the dashboard.
 */
function cb_transactions_balances_notice() {
	echo cb_transactions_get_balances_notice();
}



/**
 * CB Transactions Get Transfer Balance Notice
 * 
 * Assemble the transfer balance markup for the user's
 * transfer balance notice.
 * 
 * @since Confetti_Bits 2.2.0
 * 
 * @param int $user_id The id of the user whose balance notice
 * we're assembling. Defaults to current logged-in user.
 * 
 * @return string $notice the formatted notice markup.
 * 
 */
function cb_transactions_get_transfer_balance_notice($user_id = 0)
{

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}
	$reset_date = get_option('cb_reset_date');
	$date = new DateTimeImmutable($reset_date);
	$total = cb_transactions_get_transfer_balance($user_id);

	$notice = sprintf( 
		"You have %s Confetti Bits to spend on requests until %s.", 
		$total, $date->format( 'F jS, Y')
	);

	return $notice;

}

/**
 * CB Transactions Transfer Balance Notice
 * 
 * Output markup that shows a user's transfer balance.
 * 
 * @since Confetti_Bits 2.2.0
 */
function cb_transactions_transfer_balance_notice()
{
	echo cb_transactions_get_transfer_balance_notice();
}



