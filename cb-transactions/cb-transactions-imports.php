<?php 
/**
 * CB Import Functions
 *
 * This is going to allow an admin user to bulk import
 * transactions, birthdays, and anniversaries from CSV files.
 */
// Exit if accessed directly
defined('ABSPATH') || exit;
/**
 * CB Import Bits
 *
 * This is going to allow an admin user to bulk import
 * Confetti Bits transactions from a CSV file.
 */
function cb_import_bits($args = '')
{

	$r = wp_parse_args(
		$args,
		array(
			'item_id' => 0,
			'secondary_item_id' => 0,
			'user_id' => 0,
			'sender_id' => 0,
			'sender_name' => '',
			'recipient_id' => 0,
			'recipient_name' => '',
			'identifier' => 0,
			'log_entry' => '',
			'component_name' => '',
			'component_action' => '',
			'date_sent' => current_time('mysql'),
			'amount' => 0,
			'error_type' => 'bool',
		)
	);

	if (empty($r['sender_id']) || empty($r['log_entry'])) {
		if ('wp_error' === $r['error_type']) {
			if (empty($r['sender_id'])) {
				$error_code = 'transactions_empty_sender_id';
				$feedback = __('Your transaction was not sent. We couldn\'t find a sender.', 'confetti-bits');
			} else {
				$error_code = 'transactions_empty_log_entry';
				$feedback = __('Your transaction was not sent. Please add log entries.', 'confetti-bits');
			}

			return new WP_Error($error_code, $feedback);
		} else {

			return false;
		}
	}

	if (empty($r['recipient_id']) || empty($r['recipient_name'])) {
		if ('wp_error' === $r['error_type']) {
			if (empty($r['recipient_name'])) {
				$error_code = 'transactions_empty_recipient_name';
				$feedback = __('Your bits were not sent. We couldn\'t find the recipients.', 'confetti-bits');
			} else {
				$error_code = 'transactions_empty_recipient_id';
				$feedback = __('Your bits were not sent. We couldn\'t find the recipients.', 'confetti-bits');
			}

			return new WP_Error($error_code, $feedback);
		} else {
			return false;
		}
	}

	if (empty($r['amount'])) {
		if ('wp_error' === $r['error_type']) {

			$error_code = 'transactions_empty_amount';
			$feedback = __('Your bits were not sent. Please enter a valid amount.', 'confetti-bits');

			return new WP_Error($error_code, $feedback);
		} else {
			return false;
		}
	}

	if (abs($r['amount']) > cb_get_total_bits($r['recipient_id']) && ($r['amount'] < 0)) {
		if ('wp_error' === $r['error_type']) {

			$error_code = 'transactions_not_enough_bits';
			$feedback = __('Sorry, it looks like you don\'t have enough bits for that.', 'confetti-bits');

			return new WP_Error($error_code, $feedback);
		} else {
			return false;
		}
	}

	$transaction = new Confetti_Bits_Transactions_Transaction();
	$transaction->item_id = $r['item_id'];
	$transaction->secondary_item_id = $r['secondary_item_id'];
	$transaction->user_id = $r['user_id'];
	$transaction->sender_id = $r['sender_id'];
	$transaction->sender_name = $r['sender_name'];
	$transaction->recipient_id = $r['recipient_id'];
	$transaction->recipient_name = $r['recipient_name'];
	$transaction->identifier = $r['identifier'];
	$transaction->date_sent = $r['date_sent'];
	$transaction->log_entry = $r['log_entry'];
	$transaction->component_name = $r['component_name'];
	$transaction->component_action = $r['component_action'];
	$transaction->amount = $r['amount'];

	$send = $transaction->send_bits();


	if (false === is_int($send)) {
		if ('wp_error' === $r['error_type']) {
			if (is_wp_error($send)) {
				return $send;
			} else {
				return new WP_Error(
					'transaction_generic_error',
					__(
						'Bits were not sent. Please try again.',
						'confetti-bits'
					)
				);
			}
		}

		return false;
	}

	do_action('cb_import_bits', $r);

	return $transaction->id;
}



/*
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
 * */
function cb_importer()
{

	if (!cb_is_post_request() || !cb_is_confetti_bits_component() || !isset($_POST['cb_bits_imported'])) {
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
	$redirect_to = trailingslashit(bp_loggedin_user_domain() . cb_get_transactions_slug());

	// Loop variables
	$row_list = array();
	$imported = 0;
	$skipped = 0;
	$row_number = 2;
	$skip_list = array();
	$skipped_users = '';


	// File handling variables
	$max_upload_size = apply_filters('import_upload_size_limit', wp_max_upload_size());
	$max_upload_display_text = size_format($max_upload_size);
	$upload_dir = wp_upload_dir();

	// start the actual business
	$file = wp_import_handle_upload();

	// pretty much everything hinges on there not being a fundamental problem with the file upload
	if (!empty($file['error']) || empty($file['id'])) {

		$success = false;
		$feedback = $file['error'];
	}
	if (!cb_is_user_site_admin()) {

		$success = false;
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

		$ran = false;
		$row_loop = 0;

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

			if ($amount < 0 && abs($amount) > cb_get_users_request_balance($recipient_id)) {

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

				if ($format_check = 1) {
					$date_sent = date('Y-m-d H:i:s', strtotime($date_sent));
				} else {
					$skip_list[] = 'Invalid date in row ' . $row_number . '.';
					$skipped++;
					$row_number++;
					$row_error = true;
					continue;
				}
			} else {
				$date_sent = bp_core_current_time(false);
			}

			$sender_id = get_current_user_id();
			$sender_name = bp_get_loggedin_user_fullname();
			$row_error = false;

			if (!$row_error && !empty($log_entry) && !empty($recipient_id) && !empty($amount)) {

				$send = cb_import_bits(
					$args = array(
						'item_id' => $recipient_id,
						'secondary_item_id' => $amount,
						'user_id' => $sender_id,
						'sender_id' => $sender_id,
						'sender_name' => $sender_name,
						'recipient_id' => $recipient_id,
						'recipient_name' => bp_xprofile_get_member_display_name($recipient_id),
						'identifier' => $recipient_id,
						'date_sent' => $date_sent,
						'log_entry' => $log_entry,
						'component_name' => 'confetti_bits',
						'component_action' => 'cb_import_bits',
						'amount' => $amount,
						'error_type' => 'wp_error',
					)
				);
			}

			$row_loop++;
			$imported++;
			$row_number++;
		}


		$ran = true;

		fclose($file_stream);
		$file = '';

		$success = true;

		if ($imported === 1) {

			$feedback = __('Not a problem in sight, we successfully imported ' . $imported . ' row!.', 'confetti-bits');
		} else {

			$feedback = __('Not a problem in sight, we successfully imported ' . $imported . ' rows!.', 'confetti-bits');
		}
	}

	if (!empty($skip_list) && $ran = true) {

		$type = 'success';
		$feedback = '
		<span>Successfully imported: ' . $imported . '. These oopsies came up: </span>
				<strong>' . implode(
			' ',
			$skip_list
		) . '</strong>';
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
add_action('bp_actions', 'cb_importer');

/**
 * CB Import BDA
 *
 * This is going to allow an admin user to bulk import
 * birthdays and anniversaries from a CSV file.
 */
function cb_import_bda($args = '')
{

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

/*
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
 * */
function cb_bda_importer()
{

	if (!bp_is_post_request() || !cb_is_confetti_bits_component() || !isset($_POST['cb_bda_import'])) {
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
	$redirect_to = trailingslashit(bp_loggedin_user_domain() . cb_get_transactions_slug());

	// Loop variables
	$imported = 0;
	$skipped = 0;
	$row_number = 2;
	$skip_list = array();

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
add_action('bp_actions', 'cb_bda_importer');