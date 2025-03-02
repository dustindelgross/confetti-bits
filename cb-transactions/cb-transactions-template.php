<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Gets the total number of Confetti Bits
 * that have been sent for the current day and returns
 * a notice to the user.
 * 
 * @return string $notice The notice to be displayed to the user.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 2.2.0
 */
function cb_transactions_get_total_sent_today_notice() {

	if (!cb_is_confetti_bits_component() ) {
		return;
	}

	$amount = cb_transactions_get_total_sent_today();
	$limit = get_option('cb_transactions_transfer_limit', 20);
	$notice = "";
	$notice_markup = "<p style='margin-top:1rem;'>%s</p>";
	$user_id = get_current_user_id();
	$is_admin = cb_is_user_site_admin($user_id);
	$is_leadership = cb_is_user_transactions_admin();

	if ( $is_admin ) {
		return;
	}
	
	if ( $is_leadership ) {
		return;
	}

	if (empty($amount) || $amount == 0) {
		$notice = "You've sent 0 Confetti Bits so far this month. You can send up to {$limit}.";
		return sprintf($notice_markup, $notice);
	}

	if ($amount > 1 && $amount < $limit) {
		$notice = sprintf(
			"You've sent %s Confetti Bits so far this month. You can send up to %s more.",
			$amount, $limit - $amount
		);
		return sprintf($notice_markup, $notice);
	}

	if ($amount === 1) {
		$notice = sprintf(
			"You've sent %s Confetti Bit so far today. You can send up to %s more.",
			$amount, $limit - 1
		);
		return sprintf($notice_markup, $notice);
	}

	if ($amount >= $limit) {
		$notice = sprintf(
			"You've already sent %s Confetti Bits this month. Your counter should reset next month!",
			$amount
		);
		return sprintf($notice_markup, $notice);
	}
	
	return;

}

/**
 * Output the total number of Confetti Bits 
 * that the user has sent for the current day.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 2.2.0
 */
function cb_transactions_total_sent_today_notice() {
	echo cb_transactions_get_total_sent_today_notice();
}


/**
 * Display the users request balance.
 * 
 * @param int $user_id The user ID.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 2.2.0
 */
function cb_transactions_request_balance($user_id = 0) {

	if ($user_id === 0) {
		$user_id = get_current_user_id();
	}

	echo cb_transactions_get_request_balance($user_id);
}

/**
 * Get the users request balance notice.
 * 
 * @param int $user_id The user ID.
 * @return string The users request balance notice.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
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
 * Output the users request balance notice.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
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
 * @package ConfettiBits\Transactions
 * @subpackage Templates
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
		"<div class='my-3 rounded p-3' style='border:1px solid #dbb778;'>
			<h4 style='margin:0;'>Confetti Bits Balances</h4>
			<div class='d-flex gap-3'>
				<div class=''>
					<p style='margin:0;'>Confetti Bits Requests: %s</p>
					<p style='color:#d1cbc1;font-size:.75rem;margin:0;'>Until %s</p>
				</div>
				<div >
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
 * @package ConfettiBits\Transactions
 * @subpackage Templates
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
 * @package ConfettiBits\Transactions
 * @subpackage Templates
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

/**
 * Dynamically gets the markup for the balance notice at the top of the dashboard.
 * 
 * @param string $type The type of balance notice to return.
 * @param int $user_id The ID for the user whose balance we're retrieving.
 * 
 * @return string The markup for the balance notice.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 2.3.0
 */
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

/**
 * Outputs a balance notice of the given type, for the given user.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 2.3.0
 */
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
 * @package ConfettiBits\Transactions
 * @subpackage Templates
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
 * @package ConfettiBits\Transactions
 * @subpackage Templates
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
 * Outputs markup for the request selector.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_transactions_request_selection() {
	echo cb_transactions_get_request_selection();
}

/**
 * Returns container markup with transactions leaderboard content inside.
 * 
 * @return string The formatted markup
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_transactions_get_leaderboard_module() {

	$heading = cb_templates_get_heading("Confetti Cannon Top 15");
	$content = cb_transactions_get_formatted_leaderboard();

	return cb_templates_container([
		'classes' => ['cb-module'],
		'output' => "{$heading}{$content}",
	]);

}

/**
 * Outputs the markup for the Confetti Bits leaderboard module.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_transactions_leaderboard_module() {
	echo cb_transactions_get_leaderboard_module();
}
add_action('cb_dashboard', 'cb_transactions_leaderboard_module', 1 );

/**
 * Gets the containerized markup for the send bits module on the Confetti
 * Bits dashboard.
 * 
 * @return string The formatted markup.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_transactions_get_send_bits_module() {

	$limit = get_option('cb_transactions_transfer_limit');
	$user_id = get_current_user_id();
	$is_admin = cb_is_user_site_admin($user_id);
	$amount = cb_transactions_get_total_sent_today();
	$limit = get_option('cb_transactions_transfer_limit', 20);

	if ( !cb_is_user_admin($user_id) ) {
		if ( cb_settings_get_blackout_status() ) {
			return cb_templates_container([
				'classes' => ['cb-module'],
				'output' => "<div style='width:full;display:flex;flex-flow:row wrap;justify-content:center;align-items:center;'>Confetti Bits transfers are unavailable right now.</div>"
			]);
		}
		if ( $amount >= $limit ) {
			return cb_templates_container([
				'classes' => ['cb-module'],
				'output' => "<div style='width:full;display:flex;flex-flow:row wrap;justify-content:center;align-items:center;'>You have reached the Confetti Bits transfer limit this month.</div>"
			]);
		}
	}

	$content = [
		cb_templates_get_heading('Send Bits to Team Members'),
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
			'name' => 'cb_transactions_member_search_results',
		]),
		cb_templates_get_text_input([
			'label' => "Log Entry",
			'name' => "cb_transactions_log_entry",
			'placeholder' => "Let them know what it's for!",
			'required' => true,
		]),
		cb_templates_get_number_input([
			'name' => 'cb_transactions_amount',
			'label' => 'Amount to Send',
			'min' => $is_admin ? -999 : 1,
			'max' => $is_admin ? 999 : $limit,
			'required' => true,
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

	if ( !cb_is_user_transactions_admin() ) {
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
 * Outputs the markup for the send bits module.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_transactions_send_bits_module() {
	echo cb_transactions_get_send_bits_module();
}
add_action( 'cb_dashboard', 'cb_transactions_send_bits_module', 2 );

/**
 * Returns a string of markup that contains listings for the top 15
 * users with the most Confetti Bits.
 * 
 * @return string The formatted markup.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 1.0.0
 */
function cb_transactions_get_formatted_leaderboard() {

	$placement_digit = 0;
	$placement_suffix = '';
	$formatted = '';

	$items = cb_transactions_get_leaderboard();

	foreach( $items as $item ) {
		$dn = cb_core_get_user_display_name($item['recipient_id']);
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
 * Outputs markup for the transactions leaderboard.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 1.0.0
 */
function cb_transactions_leaderboard() {
	echo cb_transactions_get_formatted_leaderboard();
}

/**
 * Formats the markup for the "Import Birthdays" module.
 * 
 * Uses a bunch of our new templating structure to clean up 
 * some of the excess markup lying around here.
 * 
 * @return string The formatted markup.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_transactions_get_import_bda_module() {
	$heading = cb_templates_get_heading("Import Birthdays and Anniversaries");
	$file_input = cb_templates_get_file_input(['name' => 'cb_transactions_bda_import', 'label' => 'Please choose a .csv file from your computer', 'accepts' => ['.csv'] ]);
	$submit = cb_templates_get_submit_input(['value' => "Import"]);
	$form = cb_templates_get_form([
		'name' => 'cb_bda_import_form',
		'method' => 'post',
		'output' => $file_input . $submit
	]);

	return cb_templates_container(['classes' => ['cb-module'], 'output' => "{$heading}{$form}" ]);

}

/**
 * Outputs the "Import B-Days & Anniversaries" markup.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_import_bda_module() {
	echo cb_transactions_get_import_bda_module();
}

function cb_transactions_get_spot_bonus_module() {

	$today = new DateTime();

	return cb_templates_get_form_module([
		'component' => 'transactions_spot_bonus',
		'method' => 'POST',
		'container_classes' => ['modal'],
		'classes' => ['modal-dialog', 'd-flex', 'flex-column'],
		'output' => [
			'modal' => true,
			'component' => 'transactions_spot_bonus',
			'heading' => "Schedule a Spot Bonus",
			'inputs' => [
				['type' => 'text', 'args' => ['name' => 'user', 'label' => 'Search for a user', 'classes' => ['cb-form-line', 'form-control']]],
				['type' => 'container', 'args' => ['name' => 'search_results', 'container' => 'ul', 'classes' => ['list-group', 'ml-0']]],
				['type' => 'date', 'args' => [
					'component' => 'transactions_spot_bonus', 
					'label' => 'Schedule a date to send out the spot bonus', 
					'placeholder' => $today->format('m/d/Y')
				]],
				['type' => 'hidden', 'args' => ['name' => 'user_id']],
				['type' => 'container', 'args' => [
					'name' => 'action_buttons',
					'classes' => ['gap-3', 'modal-footer'],
					'output' => cb_templates_get_submit_input([
						'name' => 'transactions_spot_bonus_submit', 
						'value' => 'Schedule', 
						'classes' => ['btn', 'btn-outline-primary', 'rounded'],
						'custom_attrs' => ['data-bs-dismiss' => 'modal'],
					])
					. cb_templates_get_form_button([
						'name' => 'cb_transactions_spot_bonus_cancel', 
						'value' => 'Cancel', 
						'custom_attrs' => ['data-bs-dismiss' => 'modal'],
						'classes' => ['btn', 'btn-outline-secondary'],
					])
				]]
			]
		]
	]);
	/*
	 * 
	 *  
	 * 
	 * 
			add this to the inputs call
	 * add this back to the last container call
	 * 
	 * 
					*/

	/*
	return cb_templates_recursive_helper([
		'node_content' => [
			'type' => 'container',
			'args' => [
				'type' => 'container',
				'args' => ['name' => 'search_results', 'container' => 'ul']
			]
		]
	]);
	*/

}

function cb_transactions_get_volunteers_module() {

	$component = 'transactions_volunteers';

	return cb_templates_get_form_module([
		'component' => $component,
		'method' => 'POST',
		'container_classes' => ['modal'],
		'classes' => ['modal-dialog', 'd-flex', 'flex-column'],
		'output' => [
			'modal' => true,
			'component' => $component,
			'heading' => "Log Volunteer Hours",
			'inputs' => [
				['type' => 'text', 'args' => ['name' => 'user', 'label' => 'Search for a user', 'classes' => ['cb-form-line', 'form-control']]],
				['type' => 'container', 'args' => ['name' => 'search_results', 'container' => 'ul', 'classes' => ['list-group', 'm-0']]],
				['type' => 'hidden', 'args' => ['name' => 'user_id']],
				['type' => 'text', 'args' => ['name' => 'event', 'label' => 'Search for an event', 'classes' => ['cb-form-line', 'form-control']]],
				['type' => 'container', 'args' => ['name' => 'event_search_results', 'container' => 'ul', 'classes' => ['list-group', 'm-0']]],
				['type' => 'hidden', 'args' => ['name' => 'event_id']],
				['type' => 'number', 'args' => ['name' => 'hours', 'label' => 'Number of Volunteer Hours', 'classes' => ['cb-form-line', 'form-control']]],
				['type' => 'container', 'args' => [
					'name' => 'action_buttons',
					'classes' => ['gap-3', 'modal-footer'],
					'output' => cb_templates_get_submit_input([
						'name' => "{$component}_submit", 
						'value' => 'Log Hours', 
						'classes' => ['btn', 'btn-outline-primary', 'rounded'],
						'custom_attrs' => ['data-bs-dismiss' => 'modal'],
					])
					. cb_templates_get_form_button([
						'name' => "cb_{$component}_cancel", 
						'value' => 'Cancel', 
						'custom_attrs' => ['data-bs-dismiss' => 'modal'],
						'classes' => ['btn', 'btn-outline-secondary'],
					])
				]]
			]
		]
	]);

}

/**
 * Adds a table for spot bonuses to the transactions tab.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 3.0.0
 */
function cb_transactions_spot_bonus_table_module() {
	if ( cb_is_user_admin() ) {
		echo cb_templates_get_table('transactions_spot_bonuses', 'Spot Bonuses');
	}
}
add_action('cb_transactions_template', 'cb_transactions_spot_bonus_table_module', 2);

/**
 * Adds the spot bonus form to the transactions tab.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Templates
 * @since 3.0.0
 */
function cb_transactions_spot_bonus_module() {
	if ( cb_is_user_admin() ) {
		echo '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cb_transactions_spot_bonus_form_container">
  Schedule Spot Bonus
</button>';
		echo cb_transactions_get_spot_bonus_module();
	}
}
add_action('cb_transactions_template', 'cb_transactions_spot_bonus_module', 1);

function cb_transactions_volunteer_hours_module() {
	if ( cb_is_user_admin() ) {
		echo '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cb_transactions_volunteers_form_container">
  Log Volunteer Hours
</button>';
		echo cb_transactions_get_volunteers_module();	
	}
}
add_action('cb_transactions_template', 'cb_transactions_volunteer_hours_module', 1);