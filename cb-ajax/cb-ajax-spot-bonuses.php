<?php
/**
 * These are going to be all of our CRUD functions for spot bonuses.
 *
 * @package ConfettiBits\Transactions
 * @subpackage SpotBonuses
 * @since 3.0.0
 */
// Exit if accessed directly.
defined('ABSPATH') || exit;

function cb_dev_func_add_spot_bonuses() {

	$spot_bonus = new CB_Transactions_Spot_Bonus();
	$date = new DateTime();
	$i = 0;

	if ( cb_is_user_site_admin() ) {

		while ( $i < 100 ) {

			$spot_bonus->recipient_id = 5;
			$spot_bonus->sender_id = 5;
			$spot_bonus->spot_bonus_date = $date->modify(' +1 day ')->format('Y-m-d H:i:s');
			$spot_bonus->save();

			$i++;
		}
	}
}


/**
 * Creates a new spot bonus and saves it to the database.
 *
 * @package ConfettiBits\Transactions
 * @subpackage SpotBonuses
 * @since 3.0.0
 */
function cb_ajax_new_spot_bonuses()
{

	$feedback = ['text' => "", 'type' => 'error'];

	if (
		!isset(
			$_POST['recipient_id'],
			$_POST['sender_id'],
			$_POST['date']
		)
	) {
		$feedback['text'] = "Missing sender, recipient, or date.";
		echo json_encode($feedback);
		die();
	}

	if ( empty( $_POST['api_key'] ) ) {
		$feedback['text'] = "Missing Confetti Bits API key. Contact your system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}

	if ( ! cb_core_validate_api_key($_POST['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}


	$recipient_id = intval($_POST['recipient_id']);
	$sender_id = intval($_POST['sender_id']);
	$date = new DateTimeImmutable($_POST['date']);

	if (empty($recipient_id)) {
		$feedback['text'] = 'Recipient ID is required.';
		echo json_encode($feedback);
		die();
	}

	if (empty($sender_id)) {
		$feedback['text'] = 'Sender ID is required.';
		echo json_encode($feedback);
		die();
	}

	$spot_bonus = new CB_Transactions_Spot_Bonus();
	$spot_bonus->recipient_id = $recipient_id;
	$spot_bonus->sender_id = $sender_id;
	$spot_bonus->spot_bonus_date = $date->format('Y-m-d H:i:s');

	$save = $spot_bonus->save();



	if (false === is_int($save)) {
		$feedback['text'] = 'Spot bonus failed to save.';
	} else {
		$recipient_name = cb_core_get_user_display_name($recipient_id);
		$feedback['text'] = "{$recipient_name}'s spot bonus was successfully scheduled for {$date->format('m/d/Y')}.";
		$feedback['type'] = 'success';
	}

	echo json_encode($feedback);
	die();

}

/**
 * Updates an existing spot bonus and saves it to the database.
 *
 * @package ConfettiBits\Transactions
 * @subpackage SpotBonuses
 * @since 3.0.0
 */
function cb_ajax_update_spot_bonuses() {

	if ( ! cb_is_patch_request() ) {
		return;
	}

	$_PATCH = cb_get_patch_data();

	if (!isset(
		$_PATCH['recipient_id'],
		$_PATCH['sender_id'],
		$_PATCH['spot_bonus_date'],
		$_PATCH['spot_bonus_id']
	)) {
		return;
	}

	$feedback = [ 'text' => "", 'type' => 'error'];

	if ( empty( $_PATCH['api_key'] ) ) {
		$feedback['text'] = "Missing Confetti Bits API key. Contact your system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}

	if ( ! cb_core_validate_api_key($_PATCH['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}

	if ( empty( $_PATCH['spot_bonus_id'] ) ) {
		$feedback['text'] = 'Spot bonus ID required.';
		echo json_encode($feedback);
		die();
	}

	$spot_bonus_id = intval($_PATCH['spot_bonus_id']);
	$recipient_id = intval($_PATCH['recipient_id']);
	$sender_id = intval($_PATCH['sender_id']);
	$timezone = new DateTimeZone('America/New_York');
	$date = new DateTime($_PATCH['spot_bonus_date'], $timezone);
	$spot_bonus_date = $date->format('Y-m-d H:i:s');

	$spot_bonus = new CB_Transactions_Spot_Bonus($spot_bonus_id);

	$update_args = [
		'recipient_id' => $recipient_id,
		'sender_id' => $sender_id,
		'spot_bonus_date' => $spot_bonus_date,
	];

	$where_args = ['id' => $spot_bonus_id];

	$updated = $spot_bonus->update($update_args, $where_args);

	if ($updated) {
		$feedback['text'] = 'Spot bonus updated successfully.';
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = 'Spot bonus failed to update.';
	}

	echo json_encode($feedback);
	die();

}

/**
 * Deletes an existing spot bonus from the database.
 *
 * @package ConfettiBits\Transactions
 * @subpackage SpotBonuses
 * @since 3.0.0
 */
function cb_ajax_delete_spot_bonuses()
{

	if ( ! cb_is_delete_request() ) {
		return;
	}

	$_DELETE = cb_get_delete_data();

	if (empty($_DELETE['spot_bonus_id']) || empty( $_DELETE['api_key'])) {
		return;
	}

	$feedback = [ 'text' => "", 'type' => 'error' ];

	if ( !cb_core_validate_api_key( $_DELETE['api_key'] ) ) {
		$feedback['text'] = "Invalid Confetti Bits API key. Contact your system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}

	$spot_bonus_id = intval($_DELETE['spot_bonus_id']);

	$spot_bonus = new CB_Transactions_Spot_Bonus($spot_bonus_id);

	$deleted = $spot_bonus->delete(['id' => $spot_bonus_id]);

	if ($deleted) {
		$feedback['text'] = 'Spot bonus deleted successfully.';
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = 'Spot bonus failed to delete.';
	}

	echo json_encode($feedback);
	die();

}

/**
 * Retrieves spot bonuses from the database.
 *
 * @package ConfettiBits\Transactions
 * @subpackage SpotBonuses
 * @since 3.0.0
 */
function cb_ajax_get_spot_bonuses() {

	if ( !cb_is_get_request() ) {
		return;
	}

	$spot_bonus = new CB_Transactions_Spot_Bonus();
	$get_args = [];
	$feedback = ['text' => "", 'type' => 'error'];

	if ( empty( $_GET['api_key'] ) ) {
		$feedback['text'] = "Missing or invalid Confetti Bits API key. Contact system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}

	if ( cb_core_validate_api_key( $_GET['api_key'] ) === false ) {
		$feedback['text'] = "Missing or invalid Confetti Bits API key. Contact system administrator to generate a new key.";
		echo json_encode($feedback);
		die();
	}

	if ( !empty($_GET['count'] ) ) {
		$get_args['select'] = 'COUNT(id) AS total_count';
	} else {
		$get_args = [
			'select' => ! empty( $_GET['select'] ) ? trim( $_GET['select'] ) : '*',
			'orderby' => ['column' => 'id','order' => 'DESC']
		];
	}

	if ( !empty($_GET['page'] ) ) {
		$get_args['pagination']['page'] = intval($_GET['page']);
	}

	if ( !empty( $_GET['per_page'] ) ) {
		$get_args['pagination']['per_page'] = intval($_GET['per_page']);
	}

	if ( !empty( $_GET['spot_bonus_id'] ) ) {
		$get_args['where']['id'] = intval( $_GET['spot_bonus_id'] );
	}

	if ( !empty( $_GET['date_query'] ) ) {
		$get_args['where']['date_query'] = $_GET['date_query'];
	}

	if ( !empty( $_GET['sender_id'] ) ) {
		$get_args['where']['sender_id'] = intval( $_GET['sender_id']);
	}

	if ( !empty( $_GET['recipient_id'] ) ) {
		$get_args['where']['recipient_id'] = intval( $_GET['recipient_id']);
	}

	if ( !empty( $_GET['or'] ) ) {
		$get_args['where']['or'] = true;
	}

	$get = $spot_bonus->get_spot_bonuses($get_args);
	$feedback['text'] = $get;
	echo json_encode($feedback);
	die();

	if ( $get ) {
		$feedback['text'] = json_encode($get);
		$feedback['type'] = 'success';
	} else {
		$feedback['text'] = false;
	}

	echo json_encode($feedback);
	die();

}