<?php 
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * CB Import Functions
 *
 * This is going to allow an admin user to bulk import
 * transactions, birthdays, and anniversaries from CSV files.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.0.0
 */

/**
 * CB Import Bits
 *
 * This is going to allow an admin user to bulk import
 * Confetti Bits transactions from a CSV file.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.0.0
 */
function cb_import_bits($args = [] ) {

	$r = wp_parse_args( $args, [
		'item_id' => 0,
		'secondary_item_id' => 0,
		'sender_id' => 0,
		'recipient_id' => 0,
		'log_entry' => '',
		'component_name' => '',
		'component_action' => '',
		'date_sent' => cb_core_current_date(),
		'amount' => 0,
	]);

	$feedback = [ 'type' => 'error', 'text' => ''];

	if (empty($r['sender_id']) || empty($r['log_entry']) || empty($r['recipient_id']) || empty( $r['amount'] ) ) {
		$feedback['text'] = "Your transaction was not sent. Missing one of the following: sender, recipient, amount, or log entry.";
		return $feedback;
	}
	
	$sender_id = intval( $r['sender_id'] );
	$recipient_id = intval( $r['recipient_id'] );
	$amount = intval($r['amount']);
	$log_entry = trim( $r['log_entry'] );
	$date = new DateTimeImmutable($r['date_sent']);
	$date_sent = $date->format('Y-m-d H:i:s');

	if (abs($r['amount']) > cb_transactions_get_request_balance($r['recipient_id']) && ($r['amount'] < 0)) {
		$feedback['text'] = "Sorry, it looks like you don't have enough bits for that.";
	}

	$transaction = new CB_Transactions_Transaction();
	$transaction->item_id = $sender_id;
	$transaction->secondary_item_id = $recipient_id;
	$transaction->sender_id = $sender_id;
	$transaction->recipient_id = $recipient_id;
	$transaction->date_sent = $date_sent;
	$transaction->log_entry = $log_entry;
	$transaction->component_name = $r['component_name'];
	$transaction->component_action = 'cb_transactions_import_bits';
	$transaction->amount = $amount;

	$send = $transaction->send_bits();

	if (false === is_int($send)) {
		$feedback['text'] = "Transaction failed. Contact system administrator.";
		return $feedback;
	}

	return $transaction->id;
	
}

/**
 * Confetti Bits Importer
 *
 * This is going to allow an admin user to bulk import a whole list of Confetti Bits
 * transactions using PHP's built-in csv parser. We've included a lot of sanitization and
 * error-handling here, but it could always be improved upon.
 *
 * Some explanatory comments throughout, but the gist is:
 * 		Make sure it's a post request, on the confetti bits page, from the import panel
 * 		Get the wp importer
 * 		Set the redirect path so we can P-R-G after it's finished,
 * 		Set some variables to display after the import
 * 		Set where the files go
 * 		Open the filestream
 * 		Start the import process
 * 		Read each line, validate each field
 * 		Make sure nothing insane is going into our database
 * 		Compile the feedback and shove it into a session token
 * 		Redirect
 * 		Get the messages, kick back and enjoy
 * 
 * @package ConfettiBits\Transactions
 * @since 1.0.0
 */
function cb_importer() {

	if ( !cb_is_post_request() || !cb_is_confetti_bits_component() || !cb_is_user_site_admin() ) {
		return;
	}

	if ( empty( $_FILES['cb_transactions_import'] ) ) {
		return;
	}

	global $wpdb;

	require(ABSPATH . 'wp-admin/includes/import.php');

	if (!class_exists('WP_Importer')) {
		$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
		if (file_exists($class_wp_importer))
			require $class_wp_importer;
	}

	if (!function_exists('wp_handle_upload')) {
		require_once(ABSPATH . 'wp-admin/includes/file.php');
	}

	// Redirect variables
	$redirect_to = trailingslashit('confetti-bits');

	// Loop variables
	$row_list = array();
	$imported = 0;
	$skipped = 0;
	$row_number = 2;
	$skip_list = array();
	$skipped_users = '';
	$success = false;

	// File handling variables
	$max_upload_size = apply_filters('import_upload_size_limit', wp_max_upload_size());
	$max_upload_display_text = size_format($max_upload_size);
	$upload_dir = wp_upload_dir();

	$_FILES['import'] = $_FILES['cb_transactions_import']; 

	// start the actual business
	$file = wp_import_handle_upload();

	// pretty much everything hinges on there not being a fundamental problem with the file upload
	if (!empty($file['error']) || empty($file['id'])) {
		$feedback = $file['error'];
	}

	if (!cb_is_user_site_admin()) {
		$feedback = __('Sorry, you don\'t have permission to import Confetti Bits. Call Dustin!', 'confetti-bits');
	} else {

		$attached_file = get_attached_file($file['id']);

		if (!is_file($attached_file)) {
			$success = false;
			$feedback = __('The file does not exist or could not be read.', 'confetti-bits');
		}

		$file_stream = fopen($attached_file, "r");
		$each_row = fgetcsv($file_stream, 0, ",");
		$no_of_columns = sizeof($each_row);

		if ($no_of_columns != 4 && $no_of_columns != 5) {

			$success = false;
			$feedback = __('Invalid CSV file. Make sure there are only 4 or 5 columns containing first name, last name, amount of bits, a log entry, and date sent (optional)', 'confetti-bits');
		}

		$row_loop = 0;
		$ran = false;

		while (($each_row = fgetcsv($file_stream, 0, ",")) !== false) {

			$fname = '';
			$lname = '';
			$amount = 0;
			$log_entry = '';
			$date_sent = '';
			$row_error = false;

			if ($no_of_columns = 4) {
				list($fname, $lname, $amount, $log_entry) = $each_row;
			}

			if ($no_of_columns = 5) {
				list($fname, $lname, $amount, $log_entry, $date_sent) = $each_row;
			}

			$r = array(
				'type' => 'alphabetical',
				'search_terms' => trim($fname) . ' ' . trim($lname),
				'search_wildcard' => 'both',
				'per_page' => 2,
				'error_type' => 'wp_error',
			);

			if (empty($r['search_terms']) || $fname == '' || $lname == '') {
				$skip_list[] = 'No first or last name in row ' . $row_number . '.';
				$row_error = true;
				$skipped++;
				$row_number++;
				continue;
			}

			$new_user_query = new BP_User_Query($r);
			$new_user_query->__construct();
			$query_results = $new_user_query->results;

			$recipient_id = 0;

			if (empty($query_results) || !$new_user_query) {

				$skip_list[] = 'The name "' . trim($fname . ' ' . $lname) . '"' .
					' in row ' . $row_number .
					' didn\'t show up in the member search.';
				$skipped++;
				$row_number++;
				$row_error = true;
				continue;
			}

			if (count($query_results) > 1 || count($query_results) === 2) {
				$skip_list[] = '"' . $fname . ' ' . $lname . '"' . ' in row ' . $row_number . ' returned multiple members.';
				$skipped++;
				$row_number++;
				$row_error = true;
				continue;
			}

			if (count($query_results) === 1) {
				foreach ($query_results as $query_result) {
					$recipient_id = $query_result->ID;
				}
			}

			if ($recipient_id == false || $recipient_id == 0) {

				$skip_list[] = 'The name "' . trim($fname . ' ' . $lname) . '"' .
					' in row ' . $row_number .
					' didn\'t show up in the member search.';
				$skipped++;
				$row_number++;
				$row_error = true;
				continue;
			}

			if ($amount < 0 && abs($amount) > cb_transactions_get_request_balance($recipient_id)) {

				$skip_list[] = $fname . ' ' . $lname .
					' in row ' . $row_number .
					' didn\'t have enough Confetti Bits to buy something.';
				$skipped++;
				$row_number++;
				$row_error = true;
				continue;
			}

			if (!is_numeric($amount) || empty($amount)) {

				if (!is_numeric($amount)) {
					$skip_list[] = '"' . $amount . '" is not a number in row ' . $row_number . '.';
				} else if (empty($amount)) {
					$skip_list[] = 'Amount is empty in row ' . $row_number . '.';
				} else {
					$skip_list[] = 'Invalid amount entered in row ' . $row_number . '.';
				}

				$row_error = true;
				$skipped++;
				$row_number++;
				continue;
			}

			if (empty($log_entry)) {

				$skip_list[] = 'No log entry in row ' . $row_number . '.';
				$skipped++;
				$row_number++;
				$row_error = true;
				continue;
			}

			if (!empty($date_sent) && '' !== $date_sent) {

				$format_check = preg_match(
					'%(\d{4}(-|/)\d{1,2}(-|/)\d{1,2})|(\d{1,2}(-|/)\d{1,2}(-|/)\d{4})%',
					$date_sent
				);

				if ($format_check == 1) {
					$date_sent = date('Y-m-d H:i:s', strtotime($date_sent));
				} else {
					$skip_list[] = 'Invalid date in row ' . $row_number . '.';
					$skipped++;
					$row_number++;
					$row_error = true;
					continue;
				}
			} else {
				$date_sent = cb_core_current_date();
			}

			$sender_id = get_current_user_id();
			$row_error = false;

			if (!$row_error && !empty($log_entry) && !empty($recipient_id) && !empty($amount)) {

				$send = cb_import_bits([
					'item_id' => $recipient_id,
					'secondary_item_id' => $sender_id,
					'sender_id' => $sender_id,
					'recipient_id' => $recipient_id,
					'date_sent' => $date_sent,
					'log_entry' => $log_entry,
					'component_name' => 'confetti_bits',
					'component_action' => 'cb_transactions_import_bits',
					'amount' => $amount
				]);
				
				if ( !is_int( $send ) ) {
					$skip_list[] = "Transaction failed to process in row {$row_number}. Error: {$send['text']}";
					$skipped++;
					$row_number++;
					$row_error = true;
					continue;
				}
			}

			$row_loop++;
			$imported++;
			$row_number++;
		}

		fclose($file_stream);
		$file = '';

		$success = true;
		$ran = true;
		$feedback = $imported === 1 ? 
			__('Not a problem in sight, we successfully imported ' . $imported . ' row!.', 'confetti-bits')
			: __('Not a problem in sight, we successfully imported ' . $imported . ' rows!.', 'confetti-bits');

	}

	if (!empty($skip_list) && $ran === true) {
		$type = 'success';
		$feedback = '
		<span>Successfully imported: ' . $imported . '. These oopsies came up: </span>
				<strong>' . implode(' <br> ', $skip_list ) . '</strong>';
	}

	if (!empty($feedback)) {

		$type = (true === $success) ? 'success' : 'error';
		bp_core_add_message($feedback, $type);

	}

	if (!empty($redirect_to)) {
		bp_core_redirect(
			add_query_arg(
				array(
					'results' => $type,
				),
				$redirect_to
			)
		);
	}
}
add_action('cb_actions', 'cb_importer');

/**
 * CB Import BDA
 *
 * This is going to allow an admin user to bulk import
 * birthdays and anniversaries from a CSV file.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.0.0
 */
function cb_import_bda($args = '') {

	$r = wp_parse_args(
		$args,
		array(
			'user_id' => 0,
			'birthdate' => '',
			'anniversary' => '',
		)
	);

	if (empty($r['birthay']) || empty($r['anniversary']) || empty($r['user_id'])) {
		return;
	}

	$bd_update	= xprofile_set_field_data(51, $r['user_id'], $r['birthdate']);
	$a_update	= xprofile_set_field_data(52, $r['user_id'], $r['anniversary']);

	return array( 'birthdate' => $bd_update, 'anniversary' => $a_update );

}

/**
 * CB BDA Importer
 *
 * This is going to allow an admin user to bulk import a whole list of birthdays and anniversaries
 * using PHP's built-in csv parser.
 *
 * Some explanatory comments throughout, but the gist is:
 * 		Make sure it's a post request, on the confetti bits page, from the import panel
 * 		Get the wp importer
 * 		Set the redirect path so we can P-R-G after it's finished,
 * 		Set some variables to display after the import
 * 		Set where the files go
 * 		Open the filestream
 * 		Start the import process
 * 		Read each line, validate each field
 * 		Make sure nothing insane is going into our database
 * 		Compile the feedback and shove it into a session token
 * 		Redirect
 * 		Get the messages, kick back and enjoy
 * 
 * @package ConfettiBits\Transactions
 * @since 1.0.0
 */
function cb_bda_importer() {

	if (!cb_is_post_request() || !cb_is_confetti_bits_component() || !isset($_POST['cb_transactions_bda_import'])) {
		return;
	}

	global $wpdb;

	require(ABSPATH . 'wp-admin/includes/import.php');

	if (!class_exists('WP_Importer')) {
		$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
		if (file_exists($class_wp_importer))
			require $class_wp_importer;
	}

	if (!function_exists('wp_handle_upload')) {
		require_once(ABSPATH . 'wp-admin/includes/file.php');
	}

	// Redirect variables
	$redirect_to = Confetti_Bits()->page;

	// Loop variables
	$imported = 0;
	$skipped = 0;
	$row_number = 2;
	$skip_list = array();
	
	if ( empty( $_FILES['cb_transactions_import_bda'] ) ) {
		return;
	}
	
	$_FILES['import'] = $_FILES['cb_transactions_import_bda'];

	/**
	 *  File handling variables
	 * $max_upload_size = apply_filters('import_upload_size_limit', wp_max_upload_size());
	 * $max_upload_display_text = size_format($max_upload_size);
	 * $upload_dir = wp_upload_dir();
	 */

	// start the actual business
	$file = wp_import_handle_upload();
	$success = false;
	// pretty much everything hinges on there not being a fundamental problem with the file upload
	if (!empty($file['error']) || empty($file['id'])) {
		$feedback = $file['error'];
	}

	if (!cb_is_user_site_admin()) {

		$feedback = __("Sorry, you don't have permission to import Confetti Bits. Call Dustin!", 'confetti-bits');

	} else {

		$attached_file = get_attached_file($file['id']);

		if (!is_file($attached_file)) {

			$feedback = __('The file does not exist or could not be read.', 'confetti-bits');

		} else {
			$file_stream = fopen($attached_file, "r");
			$each_row = fgetcsv($file_stream, 0, ",");
			$no_of_columns = sizeof($each_row);

			if ($no_of_columns != 4 && $no_of_columns != 3) {
				$feedback = __('Invalid CSV file. Make sure there are only 3 or 4 columns containing a user_id, birthdate, and anniversary.', 'confetti-bits');
			}

			while (($each_row = fgetcsv($file_stream, 0, ",")) !== false) {

				$row_error = false;
				$user_id = 0;
				$birthdate = '';
				$anniversary = '';

				list($user_id, $birthdate, $anniversary) = $each_row;

				if (empty($user_id) || empty($birthdate) || empty($anniversary)) {
					$skip_list[] = 'Row ' . $row_number . ' is missing a required field.';
					$skipped++;
					$row_number++;
					$row_error = true;
					continue;
				}

				// Integerize the user_id
				$user_id = intval($user_id);

				// SQLify the dates
				$bd_obj = date_create($birthdate);
				$a_obj = date_create($anniversary);
				if ($bd_obj === false) {
					$skip_list[] = 'Row ' . $row_number . ' has an invalid birthdate.';
					$skipped++;
					$row_number++;
					$row_error = true;
					continue;
				} else {
					$birthdate = date_format($bd_obj, 'Y-m-d H:i:s');
				}

				if ($a_obj === false) {
					$skip_list[] = 'Row ' . $row_number . ' has an invalid anniversary.';
					$skipped++;
					$row_number++;
					$row_error = true;
					continue;
				} else {
					$anniversary = date_format($a_obj, 'Y-m-d H:i:s');
				}

				$row_error = false;

				if (!$row_error) {

					$bd_update	= xprofile_set_field_data(51, $user_id, $birthdate);
					$a_update	= xprofile_set_field_data(52, $user_id, $anniversary);

					if ( !$bd_update ) {
						$feedback .= ' Birthdate in ' . $row_number . ' could not be updated. ';
					}
					if ( !$a_update ) {
						$feedback .= ' Anniversary in ' . $row_number . ' could not be updated. ';
					}

				}

				$imported++;
				$row_number++;
			}

			fclose($file_stream);
			$file = '';

			$success = true;

			if ($imported === 1) {
				$feedback = __('Not a problem in sight, we successfully imported ' . $imported . ' row!', 'confetti-bits');
			} else {
				$feedback = __('Not a problem in sight, we successfully imported ' . $imported . ' rows!', 'confetti-bits');
			}
		}
	}

	if (!empty($skip_list) ) {
		$feedback = sprintf("<span>Successfully imported: %s. These oopsies came up: <strong>%s'</strong>", $imported, implode( ' ', $skip_list ) );
	}

	if (!empty($feedback)) {
		$type = (true === $success)
			? 'success'
			: 'error';

		bp_core_add_message($feedback, $type);
	}

	if (!empty($redirect_to)) {
		bp_core_redirect(
			add_query_arg(
				array(
					'results' => $type,
				),
				$redirect_to
			)
		);
	}
}
add_action('cb_actions', 'cb_bda_importer');