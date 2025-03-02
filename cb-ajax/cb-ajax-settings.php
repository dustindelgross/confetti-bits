<?php 
// Exit if accessed directly.
defined('ABSPATH') || exit;

/** 
 * Handles HTTP PATCH requests to update settings.
 * 
 * Processes standard and bulk setting updates from an 
 * HTTP PATCH request.
 * 
 * @see cb_get_patch_data() for more info on how we handle PATCH 
 * requests.
 * 
 * 
 * @package Settings
 * @since 3.1.0
 */
function cb_ajax_update_settings() {

	if ( !cb_is_patch_request() ) {
		return;
	}

	$_PATCH = cb_get_patch_data();
	$feedback = ['text' => '','type' => 'error'];

	if ( !isset( 
		$_PATCH['admin_id'], 
		$_PATCH['api_key'],
	) ) {

		$feedback['text'] = "Missing API key or admin ID.";
		echo json_encode($feedback);
		die();
	}

	if ( !cb_core_validate_api_key( $_PATCH['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to renew the API key.";
		echo json_encode($feedback);
		die();
	}

	$admin_id = intval($_PATCH['admin_id']);

	$is_admin = cb_core_admin_is_user_site_admin($admin_id);
	$tz = new DateTimeZone(date_default_timezone_get());
	$updated = [];

	if ( !$is_admin ) {
		$feedback['text'] = "You do not have permission to update Confetti Bits settings.";
		echo json_encode($feedback);
		die();
	}

	if ( !empty( $_PATCH['reset_date'] ) ) {
		$reset_date = new DateTimeImmutable($_PATCH['reset_date'], $tz);
		$updated['reset_date'] = update_option('cb_reset_date', $reset_date->format('Y-m-d H:i:s') );
	}

	if ( !empty( $_PATCH['volunteer_amount'] ) ) {
		$updated['volunteer_amount'] = update_option('cb_core_volunteer_amount', intval($_PATCH['volunteer_amount'] ) );
	}

	if ( !empty( $_PATCH['spot_bonus_amount'] ) ) {
		$updated['spot_bonus_amount'] = update_option('cb_core_spot_bonus_amount', intval( $_PATCH['spot_bonus_amount'] ) );
	}

	if ( isset( $_PATCH['blackout'] ) ) {
		$updated['blackout'] = update_option('cb_transactions_blackout_active', boolval( $_PATCH['blackout'] ) );
	}

	if ( !empty( $_PATCH['transactions_blackout_start'] ) && !empty( $_PATCH['transactions_blackout_end'] ) ) {

		$blackout_start = new DateTimeImmutable($_PATCH['transactions_blackout_start'], $tz);
		$blackout_end = new DateTimeImmutable($_PATCH['transactions_blackout_end'], $tz);

		if ( $blackout_start > $blackout_end ) {
			$updated['transactions_blackout_start'] = update_option('cb_transactions_blackout_start', $blackout_end->format('Y-m-d H:i:s'));
			$updated['transactions_blackout_end'] = update_option('cb_transactions_blackout_end', $blackout_start->format('Y-m-d H:i:s'));
		} else {
			$updated['transactions_blackout_start'] = update_option('cb_transactions_blackout_start', $blackout_start->format('Y-m-d H:i:s'));
			$updated['transactions_blackout_end'] = update_option('cb_transactions_blackout_end', $blackout_end->format('Y-m-d H:i:s'));
		}

	}

	if ( !empty( $_PATCH['transfer_limit'] ) ) {
		$updated['transfer_limit'] = update_option('cb_transactions_transfer_limit', intval($_PATCH['transfer_limit']));
	}

	$updated_counter = 0;

	foreach( $updated as $item => $status ) {
		if ( $status == true ) {
			$item = ucwords(str_replace( '_', ' ', $item ) );
			$feedback['text'] .= "Updated {$item}. ";
			$updated_counter++;
		}

	}

	if ($updated_counter === 0) {
		$feedback['text'] = "No items were updated.";
	}

	$feedback['type'] = 'success';

	echo json_encode($feedback);
	die();

}

/** 
 * Handles HTTP GET requests to retrieve settings.
 * 
 * Processes standard GET requests for Confetti Bits settings.
 * 
 * @package Settings
 * @since 3.1.0
 */
function cb_ajax_get_settings() {

	if ( !cb_is_get_request() ) {
		return;
	}

	$feedback = ['text' => '','type' => 'error'];

	if ( !isset( 
		$_GET['api_key'],
	) ) {
		$feedback['text'] = "Missing API key.";
		echo json_encode($feedback);
		die();
	}

	if ( !cb_core_validate_api_key( $_GET['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to renew the API key.";
		echo json_encode($feedback);
		die();
	}

	$admin_id = intval($_GET['admin_id']);

	$is_admin = cb_core_admin_is_user_site_admin($admin_id);
	$data = [];

	if ( !$is_admin ) {
		$feedback['text'] = "You do not have permission to update Confetti Bits settings.";
		echo json_encode($feedback);
		die();
	}

	if ( !empty( $_GET['reset_date'] ) ) {
		$data['reset_date'] = get_option('cb_reset_date');
	}

	if ( !empty( $_GET['volunteer_amount'] ) ) {
		$date['volunteer_amount'] = get_option('cb_core_volunteer_amount', intval($_GET['volunteer_amount'] ) );
	}

	if ( !empty( $_GET['spot_bonus_amount'] ) ) {
		$data['spot_bonus_amount'] = get_option('cb_core_spot_bonus_amount', intval( $_GET['spot_bonus_amount'] ) );
	}

	if ( isset( $_GET['blackout'] ) ) {
		$data['blackout'] = get_option('cb_transactions_blackout_active', boolval( $_GET['blackout'] ) );
	}

	if ( !empty( $_GET['transactions_blackout_start'] ) ) {
		$data['transactions_blackout_start'] = get_option('cb_transactions_blackout_start');
	}

	if ( !empty( $_GET['transactions_blackout_end'] ) ) {
		$data['transactions_blackout_end'] = get_option('cb_transactions_blackout_end');
	}

	if ( !empty( $_GET['transfer_limit'] ) ) {
		$data['transfer_limit'] = get_option('cb_transactions_transfer_limit');
	}

	$feedback['text'] = $data;
	$feedback['type'] = 'success';

	echo json_encode($feedback);
	die();

}

function cb_ajax_new_settings() {}
function cb_ajax_delete_settings() {}