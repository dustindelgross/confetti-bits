<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Gets markup for the settings form.
 * 
 * @return string The formatted markup.
 * 
 * @package Settings
 * @subpackage Templates
 * @since 3.1.0
 */
function cb_settings_get_settings_form() {

	$component = 'settings';
	// $tz = new DateTimeZone(date_default_timezone_get());
	$reset_date_option = get_option('cb_reset_date');
	$blackout_start_option = get_option('cb_core_transactions_blackout_start');
	$blackout_end_option = get_option('cb_transactions_blackout_end');
	$reset_date_obj = !empty($reset_date_option) ? new DateTimeImmutable($reset_date_option) : '';
	$blackout_start_date_obj = new DateTimeImmutable($blackout_start_option);
	$blackout_end_date_obj = new DateTimeImmutable($blackout_end_option);
	$volunteer_amount = get_option('cb_core_volunteer_amount', 20 );
	$spot_bonus_amount = get_option('cb_core_spot_bonus_amount', 50 );
	$transactions_transfer_limit = get_option('cb_core_transactions_send_limit', 10 );
	$transactions_blackout_active = get_option('cb_transactions_blackout_active', false );

	return cb_templates_get_form_module([
		'method' => 'GET',
		'component' => $component,
		'output' => [
			'heading' => 'Confetti Bits Settings',
			'component' => $component,
			'inputs' => [
				[ 'type' => 'date', 'args' => [
					'name' => 'reset_date', 
					'label' => 'Reset Date', 
					'component' => "{$component}_reset_date",
					'value' => $reset_date_obj->format('m/d/Y')
				] ],
				['type' => 'time_selector', 'args' => [
					'component' => "{$component}_reset_date",
					'name' => 'reset_date_time',
					'label' => 'Reset Time',
					'value' => explode(':', $reset_date_obj->format('H:i'))
				]],
				[ 'type' => 'date', 'args' => [
					'name' => 'transactions_blackout_start', 
					'label' => 'Transactions Blackout Start', 
					'component' => "{$component}_transactions_blackout_start",
					'value' => $blackout_start_date_obj->format('m/d/Y')
				] ],
				['type' => 'time_selector', 'args' => [
					'component' => "{$component}_transactions_blackout_start",
					'name' => 'transactions_blackout_start_time',
					'label' => 'Blackout Start Time',
					'value' => explode(':', $blackout_start_date_obj->format('H:i'))
				]],
				[ 'type' => 'date', 'args' => [
					'name' => 'transactions_blackout_end', 
					'label' => 'Transactions Blackout End', 
					'component' => "{$component}_transactions_blackout_end",
					'value' => $blackout_end_date_obj->format('m/d/Y')
				] ],
				['type' => 'time_selector', 'args' => [
					'component' => "{$component}_transactions_blackout_end",
					'name' => 'transactions_blackout_end_time',
					'label' => 'Blackout End Time',
					'value' => explode(':', $blackout_end_date_obj->format('H:i'))
				]],
				[ 'type' => 'toggle_switch', 'args' => [ 'name' => 'transactions_blackout_active', 'label' => 'Transactions Blackout Active', 'checked' => $transactions_blackout_active ] ],
				[ 'type' => 'number', 'args' => [ 'name' => 'spot_bonus_amount', 'label' => 'Spot Bonus Amount', 'value' => $spot_bonus_amount ] ],
				[ 'type' => 'number', 'args' => [ 'name' => 'volunteer_hours_amount', 'label' => 'Volunteer Hours Amount (per hour)', 'value' => $volunteer_amount ] ],
				[ 'type' => 'number', 'args' => [ 'name' => 'transfer_limit', 'label' => 'Confetti Bits Transfer Limit (per month)', 'value' => $transactions_transfer_limit ] ],
				[ 'type' => 'submit', 'args' => [ 'name' => 'submit', 'value' => 'Save' ] ],
			]
		]
	]);
}

/**
 * Outputs markup for request items form.
 * 
 * @package Settings
 * @subpackage Templates
 * @since 3.1.0
 */
function cb_settings_form() {
	echo cb_settings_get_settings_form();
}

/**
 * Outputs the setting field for the reset date.
 * 
 * @package Core
 * @since 3.0.0
 *//*
function cb_core_admin_reset_date_setting() {

	echo '<input type="date" name="cb_reset_date" value="' . esc_attr($value) . '" />';

}
*/