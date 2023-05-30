<?php
defined('ABSPATH') || exit;
/**
 * CB Transactions Get Total Sent Today Notice
 *
 * This function gets the total number of Confetti Bits
 * that have been sent for the current day and returns
 * a notice to the user.
 *
 * @return string $notice The notice to be displayed to the user.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 2.2.0
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
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 2.2.0
 */
function cb_transactions_total_sent_today_notice() {
	echo cb_transactions_get_total_sent_today_notice();
}


/**
 * CB Users Request Balance
 *
 * Display the users request balance.
 *
 * @param int $user_id The user ID.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 2.2.0
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
 * @param int $user_id The user ID.
 * @return string The users request balance notice.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 2.2.0
 */
function cb_transactions_get_request_balance_notice($user_id = 0) {

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}

	$date = new DateTime(Confetti_Bits()->spend_end);
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
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 2.2.0
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
 *
 * @param int $user_id The id of the user whose balance notice
 * we're assembling. Defaults to current logged-in user.
 *
 * @return string $notice the formatted notice markup.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 2.2.0
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
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 2.2.0
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
 *
 * @param int $user_id The id of the user whose balance notice
 * we're assembling. Defaults to current logged-in user.
 *
 * @return string $notice the formatted notice markup.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 2.2.0
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
		"You have %s Confetti Bits to spend on transfers until %s.",
		$total, $date->format( 'F jS, Y')
	);

	return $notice;

}

function cb_transactions_get_balance_notice( $type = '', $user_id = 0 ) {

	if ( empty($type) ) {
		return;
	}

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}


	$reset_date = get_option('cb_reset_date');
	$date = new DateTimeImmutable($reset_date);
	$total = call_user_func("cb_transactions_get_{$type}_balance", $user_id );

	$notice = sprintf(
		"You have %s Confetti Bits to spend on %s until %s.",
		$total, $type, $date->format( 'F jS, Y')
	);

	return $notice;

}

function cb_transactions_balance_notice( $type = '', $user_id = 0 ) {

	if ( empty($type) ) {
		return;
	}

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}

	echo cb_transactions_get_balance_notice($type, $user_id);

}

/**
 * CB Transactions Transfer Balance Notice
 *
 * Output markup that shows a user's transfer balance.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 2.2.0
 */
function cb_transactions_transfer_balance_notice()
{
	echo cb_transactions_get_transfer_balance_notice();
}

/**
 * CB Transactions Get Request Selection
 *
 * Get markup for the Request Selection Input
 *
 * @return string The formatted select input.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 2.3.0
 */
function cb_transactions_get_request_selection() {
	$options = [
		"One PTO Day" => ['value' => 500, 'classes' => ['cb-request-option']],
		"Single Night Hotel Stay" => ['value' => 400, 'classes' => ['cb-request-option']],
		"Dinner/1-on-1 with Company Leader" => ['value' => 350, 'classes' => ['cb-request-option']],
		"Spa Trip" => ['value' => 250, 'classes' => ['cb-request-option']],
		"$25 DoorDash Gift Card" => ['value' => 75, 'classes' => ['cb-request-option' ]],
		"$25 Starbucks Gift Card" => ['value' => 75, 'classes' => ['cb-request-option']],
		"$20 CTG Gift Card" => ['value' => 50, 'classes' => ['cb-request-option']],
		"$10 Starbucks Gift Card" => ['value' => 25, 'classes' => ['cb-request-option']],
	];

	return cb_templates_get_select_input([
		"name" => "cb_request_option",
		"label" => "Request Options",
		"placeholder" => "--- Select an Option ---",
		"select_options" => $options
	]);

}

/**
 * CB Transactions Request Selection
 *
 * Output markup for the request selector.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 2.3.0
 */
function cb_transactions_request_selection() {
	echo cb_transactions_get_request_selection();
}

/**
 * CB Transactions Get Leaderboard Module
 *
 * Returns container markup with transactions leaderboard content inside.
 *
 * @return string The formatted markup
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 2.3.0
 */
function cb_transactions_get_leaderboard_module() {

	$heading = cb_templates_format_heading("Confetti Cannon Top 15");
	$content = cb_transactions_get_formatted_leaderboard();

	return cb_templates_container([
		'classes' => ['cb-module'],
		'output' => "{$heading}{$content}",
	]);

}

/**
 * CB Transactions Leaderboard Module
 *
 * Outputs the markup for the Confetti Bits leaderboard module.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 2.3.0
 */
function cb_transactions_leaderboard_module() {
	echo cb_transactions_get_leaderboard_module();
}
add_action('cb_dashboard', 'cb_transactions_leaderboard_module', 1 );

/**
 * CB Transactions Get Send Bits Module
 *
 * Gets the containerized markup for the send bits module on the Confetti
 * Bits dashboard.
 *
 * @return string The formatted markup.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 2.3.0
 */
function cb_transactions_get_send_bits_module() {

	$content = [
		cb_templates_get_text_input([
			"label"			=> "Team Member",
			"name"			=> "cb_transactions_recipient_name",
			"placeholder"	=> "Search for a team member"
		]),
		cb_templates_get_toggle_switch([
			'name'	=> 'cb_transactions_add_activity',
			'label'	=> "I want this to show up on the activity feed"
		]),
		cb_templates_container([
			'container' => 'ul',
			'id' => 'cb_transactions_member_search_results',
		]),
		cb_templates_get_text_input([
			'label' => "Log Entry",
			'name' => "cb_transactions_log_entry",
			'placeholder' => "Let them know what it's for!"
		]),
		cb_templates_get_number_input([
			'name' => 'cb_transactions_amount',
			'label' => 'Amount to Send',
			'min' => 1,
			'max' => 20,
		]),
		cb_templates_get_hidden_input(['name' => 'cb_transactions_recipient_id']),
		cb_templates_get_hidden_input([
			'name' => 'cb_transactions_sender_id',
			'value' => get_current_user_id(),
			'readonly' => true
		]),
		cb_templates_get_submit_input(['name' => 'cb_transactions_send_bits']),
		cb_transactions_get_total_sent_today_notice(),
	];

	if ( !cb_is_user_admin() || cb_is_user_site_admin() ) {
		array_unshift($content, cb_transactions_get_transfer_balance_notice());
	}

	$form = cb_templates_get_form([
		'name' => 'cb_transactions_send_bits_form',
		'autocomplete' => 'off',
		'method' => 'post',
		'output' => implode('',$content),
	]);

	return cb_templates_container([
		'classes' => ['cb-module'],
		'output' => $form
	]);

}

/**
 * CB Transactions Send Bits Module
 *
 * Outputs the markup for the send bits module.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 2.3.0
 */
function cb_transactions_send_bits_module() {
	echo cb_transactions_get_send_bits_module();
}
add_action( 'cb_dashboard', 'cb_transactions_send_bits_module', 2 );

/**
 * CB Transactions Get Formatted Leaderboard
 *
 * Returns a string of markup that contains listings for the top 15
 * users with the most Confetti Bits.
 *
 * @return string The formatted markup.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 1.0.0
 */
function cb_transactions_get_formatted_leaderboard() {

	$placement_digit = 0;
	$placement_suffix = '';
	$formatted = '';

	$items = cb_transactions_get_leaderboard();
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
		$formatted .= sprintf(
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

	return $formatted;
}

/**
 * CB Transactions Leaderboard
 *
 * Output markup for the transactions leaderboard.
 *
 * @package ConfettiBits
 * @subpackage Transactions
 * @since 1.0.0
 */
function cb_transactions_leaderboard() {
	echo cb_transactions_get_formatted_leaderboard();
}