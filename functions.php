<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CB Is Get Request
 * 
 * Checks if the current request is a GET request
 * 
 * @since 1.0.0
 * @return bool True if GET request, false otherwise
 * 
 */
function cb_is_get_request() {
	return (bool) ( 'GET' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
}

/**
 * CB Is Post Request
 * 
 * Checks if the current request is a POST request
 * 
 * @since 1.0.0
 * @return bool True if POST request, false otherwise
 */
function cb_is_post_request() {
	return (bool) ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
}

/**
 * CB Is Patch Request
 * 
 * Checks if the current request is a PATCH request
 * 
 * @return bool True if PATCH request, false otherwise
 * 
 * @package ConfettiBits
 * @since 2.3.0
 */
function cb_is_patch_request() {
	return (bool) ( 'PATCH' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
}

/**
 * CB Is Delete Request
 * 
 * Checks if the current request is a DELETE request
 * 
 * @return bool True if DELETE request, false otherwise
 * 
 * @package ConfettiBits
 * @since 2.3.0
 */
function cb_is_delete_request() {
	return (bool) ( 'DELETE' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
}

if ( ! function_exists( 'confetti_bits_admin_enqueue_script' ) ) {
	function confetti_bits_admin_enqueue_script() {
		wp_enqueue_style( 'confetti-bits-admin-css', plugin_dir_url( __FILE__ ) . 'style.css' );
	}

	add_action( 'admin_enqueue_scripts', 'confetti_bits_admin_enqueue_script' );
}

/**
 * Initializes our notifications class.
 * 
 * So we can get those sweet, sweet noties.
 * 
 * @package ConfettiBits\Core
 * @since 1.3.0
 */
function cb_core_notifications_init() {
	if ( class_exists( 'CB_Notifications_Component' ) ) {
		CB_Notifications_Component::instance();
	}
}
add_action( 'cb_init', 'cb_core_notifications_init', 10 );

/**
 * Returns form markup that allows privileged users to manually
 * input the birthday and anniversary dates for other users.
 * 
 * @param object WP_User The current instance of WP_User
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
function cb_user_birthday_anniversary_fields( $user ) {
	if( !current_user_can('add_users') ) {
		return;
	}

?>
<h3>Birthday &amp; Work Anniversary</h3>
<table class="form-table">
	<tr>
		<th><label for="cb_birthday">Birthday</label></th>
		<td>
			<input type="date" class="regular-text" name="cb_birthday" required value="<?php echo esc_attr( xprofile_get_field_data( 51, $user->ID, 'comma' ) ); ?>" id="cb_birthday" /><br />
			<span class="description"></span>
		</td>
	</tr>
	<tr>
		<th><label for="cb_anniversary">Work Anniversary</label></th>
		<td>
			<input type="date" class="regular-text" name="cb_anniversary" required value="<?php echo esc_attr( xprofile_get_field_data( 52, $user->ID, 'comma' ) ); ?>" id="cb_anniversary" /><br />
			<span class="description"></span>
		</td>
	</tr>
</table>
<?php
}
add_action( 'user_new_form', 'cb_user_birthday_anniversary_fields' );

function cb_save_user_birthday_anniversary_fields($user_id, $notify) {

	if( !current_user_can('add_users')
	  ) {
		return false;
	}

	$c = !empty( $_POST['cb_birthday'] ) ? date( 'Y-m-d H:i:s', strtotime( $_POST['cb_birthday'] ) ) : null;
	$d = !empty( $_POST['cb_anniversary']) ? date( 'Y-m-d H:i:s', strtotime( $_POST['cb_anniversary'] ) ) : null;

	xprofile_set_field_data( 51, $user_id, $c );
	xprofile_set_field_data( 52, $user_id, $d );

}
add_action( 'edit_user_created_user', 'cb_save_user_birthday_anniversary_fields', 10, 2 );

/**
 * CB Core Set Reset Date Globals
 * 
 * Sets a few internal globals using the DateTimeImmutable class 
 * so that we can reference these spending/earning cycles throughout 
 * the app without running these calculations all the time.
 * 
 * A breakdown of what this does:
 * 
 * - There are two cycles: an earning cycle and a spending cycle
 * 
 * - The reset date refers to the earning cycle. That is when 
 * users start over with a zero'd out bank of confetti bits. 
 * So the earning cycle "ends" on the reset date.
 * 
 * - The earning cycle "starts" one year prior to that, on the
 * same date. Please don't set it to February 29th, I did not
 * account for that when I built this forsaken system.
 * 
 * - The spending cycle is offset by 1 month after the earning
 * cycle. So the spending cycle "ends" 1 month after the earning
 * cycle does. That means that the spending cycle "starts" one month 
 * after the earning cycle does as well.
 * 
 * - There are situations where a user may want to look back
 * at a previous cycle, so we account for those here as well.
 * We only need the dates that those cycles started, because 
 * they ended when the current cycles started.
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 * 
 */
function cb_core_set_reset_date_globals() {

	$cb = Confetti_Bits();
	$reset_date = get_option('cb_reset_date');

	if ( !$reset_date ) {
		return;
	}

	$date = new DateTimeImmutable($reset_date);
	$today = new DateTimeImmutable();

	if ( $today > $date ) {
		$date = cb_core_auto_reset();
	}

	$cb->earn_start = $date->modify('-1 year')->format('Y-m-d H:i:s');
	$cb->earn_end = $date->format('Y-m-d H:i:s');
	$cb->spend_start = $date->modify('-1 year + 1 month')->format('Y-m-d H:i:s');
	$cb->spend_end = $date->modify('+ 1 month')->format('Y-m-d H:i:s');
	$cb->prev_earn_start = $date->modify('-2 years')->format('Y-m-d H:i:s');
	$cb->prev_spend_start = $date->modify('-2 years + 1 month')->format('Y-m-d H:i:s');

}
add_action( 'cb_setup_globals', 'cb_core_set_reset_date_globals' );


/**
 * Sets the amount for spot bonuses in our core class for easy access.
 * 
 * Make sure to set this value in the DB via admin settings, or else 
 * we may never experience the bliss of automation.
 * 
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
function cb_core_set_spot_bonus_global() {

	$cb = Confetti_Bits();
	$spot_bonus_amount = get_option('cb_core_spot_bonus_amount');

	if ( !$spot_bonus_amount ) {
		return;
	}

	$cb->spot_bonus_amount = intval($spot_bonus_amount);

}
add_action( 'cb_setup_globals', 'cb_core_set_spot_bonus_global' );

/**
 * Automatically increments the reset date by 1 year.
 * 
 * @returns DateTimeImmutable The DateTimeImmutable object, with the new reset
 * 							  date already locked and loaded.
 * 
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
function cb_core_auto_reset() {

	$reset_date = get_option('cb_reset_date');
	$date = new DateTimeImmutable($reset_date);
	$new_reset_date = $date->modify('+1 year')->format('Y-m-d');
	update_option( 'cb_reset_date', $new_reset_date );

	return new DateTimeImmutable($new_reset_date);

}

/**
 * CB Core Current Date
 * 
 * Returns the current date and time in the given
 * format. Defaults to MySQL format in the site's
 * timezone.
 * 
 * 
 * @param bool $offset Whether to use the site's
 *   UTC offset setting. Default true.
 * 
 * @param string $format The desired datetime format.
 *   Default MySQL - 'Y-m-d H:i:s'
 * 
 * @return string The formatted datetime.
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_core_current_date( $offset = false, $format = "Y-m-d H:i:s" ) {

	$tz = $offset ? wp_timezone() : null;
	$date = new DateTimeImmutable("now", $tz);

	return $date->format($format);

}


if (!function_exists('str_starts_with')) {
	/**
	 * Str Starts With
	 * 
	 * PHP 8 Polyfill for str_starts_with
	 * 
	 * @package ConfettiBits
	 * @since 2.3.0
	 * 
	 * @param string $haystack The string to search.
	 * @param string $needle The substring to search for at the beginning.
	 * 
	 * @return bool Whether the string starts with the given substring.
	 */
	function str_starts_with($haystack = '', $needle = '') {
		return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
	}
}
if (!function_exists('str_ends_with')) {
	/**
	 * Str Ends With
	 * 
	 * PHP 8 Polyfill for str_ends_with
	 * 
	 * @package ConfettiBits
	 * @since 2.3.0
	 * 
	 * @param string $haystack The string to search.
	 * @param string $needle The substring to search for at the end.
	 * 
	 * @return bool Whether the string ends with the given substring.
	 */
	function str_ends_with($haystack, $needle) {
		return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;
	}
}

if (!function_exists('str_contains')) {
	/**
	 * Str Contains
	 * 
	 * PHP 8 Polyfill for str_contains
	 * 
	 * @package ConfettiBits
	 * @since 2.3.0
	 * 
	 * @param string $haystack The string to search.
	 * @param string $needle The substring to search for.
	 * 
	 * @return bool Whether the string contains the given substring.
	 */
	function str_contains($haystack, $needle) {
		return $needle !== '' && mb_strpos($haystack, $needle) !== false;
	}
}

/**
 * CB Flush Rewrite Rules
 * 
 * Flushes the rewrite rules after we update a
 * plugin or theme, so our pages stop disappearing.
 * 
 * @package ConfettiBits
 * @since 2.3.0
 */
function cb_flush_rewrite_rules() {
	flush_rewrite_rules();
}
add_action( 'after_plugin_or_theme_update', 'cb_flush_rewrite_rules' );

/**
 * Gets PATCH data from an HTTP PATCH request.
 * 
 * Retrieves data from a PATCH request and returns it as an
 * associative array.
 * 
 * @return array The PATCH request body as an associative array.
 * 
 * @package ConfettiBits
 * @since 2.3.0
 */
function cb_get_patch_data() {

	if ( !cb_is_patch_request() ) {
		return;
	}

	return json_decode(file_get_contents('php://input'), true);

}

/**
 * Gets DELETE data from an HTTP DELETE request.
 * 
 * Retrieves data from a DELETE request and returns it as an
 * associative array.
 * 
 * @return array The DELETE request body as an associative array.
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_get_delete_data() {

	if ( !cb_is_delete_request() ) {
		return;
	}

	return json_decode(file_get_contents('php://input'), true);

}

/**
 * Gets a list of transactions for nonexistent users.
 * 
 * Get a list of users that are present in the 
 * confetti_bits_transactions table, but not in the 
 * wp_users table. Returns an array of transactions where
 * the sender or recipient isn't on the platform anymore.
 * 
 * @return array An associative array of transaction data.
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_core_get_missing_users() {

	global $wpdb;
	$cb = Confetti_Bits();
	$users = "{$wpdb->prefix}users";
	$select = 'SELECT t.*';
	$from = "FROM {$cb->transactions->table_name} t";
	$left_join1 = "LEFT JOIN {$users} u1 ON t.sender_id = u1.id";
	$left_join2 = "LEFT JOIN {$users} u2 ON t.recipient_id = u2.id";
	$where = "WHERE u1.id IS NULL OR u2.id IS NULL";

	$sql = "{$select} {$from} {$left_join1} {$left_join2} {$where}";

	return $wpdb->get_results( $sql, "ARRAY_A" );

}

/**
 * Gets the user display name.
 * 
 * Attempts to get a display_name for the given user_id. If that
 * comes up empty, searches for the first_name. If that also
 * comes up empty, searches for the nickname. If there's no
 * display name to be found, it gives us a lovely bunch of
 * abject nothingness. 
 * 
 * @param int $user_id The ID for the user whose name we want.
 * 					   Default current user_id.
 * 
 * @return string The display_name on success, empty on failure.
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_core_get_user_display_name( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$display_name = get_the_author_meta('display_name', $user_id );

	if ( empty( $display_name ) ) {
		$display_name = get_the_author_meta( 'first_name', $user_id );
	}

	if ( empty( $display_name ) ) {
		$display_name = get_the_author_meta( 'nickname', $user_id );
	}

	if ( empty( $display_name ) ) {
		$display_name = "Unknown Member";
	}

	return $display_name;

}

/**
 * Returns the email address for the given user.
 * 
 * @param int $user_id The ID of the user. Default current user.
 * 
 * @return string The user's email address, if one exists.
 * 
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
function cb_core_get_user_email( $user_id = 0 ) {

	if ( $user_id === 0 ) {
		$user_id = get_current_user_id();
	}

	$user = get_userdata($user_id);

	return $user->user_email;

}

/**
 * Checks if the parameter is a multi-dimensional array.
 * 
 * @param array $arr The array to check.
 * 
 * @return bool Whether the array is multi-dimensional.
 * 
 * @package ConfettiBits\Core
 * @since 1.3.0
 */
function cb_core_is_multi_array(array $arr) {
	rsort($arr);
	return (isset($arr[0]) && is_array($arr[0]));
}

/**
 * Sends out a sitewide notice.
 * 
 * Use this to send out non-critical updates that are 
 * intended to be informative or nice to know, such as
 * an upcoming or recent update, new feature, etc.
 * 
 * @package ConfettiBits\Core
 * @since 1.2.0
 */
function cb_core_send_sitewide_notice() {

	if (
		!cb_is_confetti_bits_component() ||
		!cb_is_post_request() || 
		empty( $_POST['cb_sitewide_notice_heading'] ) || 
		empty( $_POST['cb_sitewide_notice_body'] )
	) {
		return;
	}

	$redirect_to = Confetti_Bits()->page;
	$feedback = ['type' => 'error', 'text' => ''];

	$username = cb_core_get_user_display_name(intval($_POST['cb_sitewide_notice_user_id']));
	$subject = trim($_POST['cb_sitewide_notice_heading']);
	$message = trim($_POST['cb_sitewide_notice_body']) . " - {$username}";

	$notice = messages_send_notice($subject, $message);

	if ($notice) {
		$feedback['type'] = 'success';
		$feedback['text'] = 'Sitewide notice was successfully posted.';
	} else {
		$feedback['text'] = 'Failed to send sitewide notice.';
	}

	bp_core_add_message($feedback['text'], $feedback['type']);
	bp_core_redirect($redirect_to);

}
add_action('cb_actions', 'cb_core_send_sitewide_notice');

/**
 * Returns the number of days remaining until the reset date.
 * 
 * @return int The number of days remaining before the reset.
 * 
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
function cb_core_get_doomsday_clock() {

	$cb = Confetti_Bits();
	$current_date = new DateTimeImmutable();
	$reset_date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $cb->earn_end);
	$interval = $current_date->diff($reset_date);

	return $interval->days;

}

/**
 * Washes away the sins of bad actors.
 * 
 * Use this to aggressively scrub input strings.
 * I doubt that there are going to be any
 * elite hackers playing injecting nonsense
 * into this app, but this is good practice
 * for other kinds of sanitization that we might
 * want to do later.
 * 
 * @param string $input An input string
 * @return string A kinder, gentler string.
 * 
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
function cb_core_sanitize_string( $input = '' ) {

	$input = trim($input);
	$input = htmlentities($input, ENT_QUOTES, 'UTF-8');
	$input = addslashes($input);
	$input = preg_replace(
		[ '/\\\\/', '/\0/', '/\n/', '/\r/', '/\'/', '/"/', '/\x1a/' ], 
		[ '\\\\\\\\', '\\\\0', '\\\\n', '\\\\r', "\\'", '\\"', '\\\\Z' ], 
		$input
	);

	return $input;

}

/**
 * Injects our own config settings into PHPMailer.
 * 
 * @param PHPMailer $phpmailer A PHPMailer object.
 * 
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
function cb_core_smtp_init( $phpmailer ) {
	$phpmailer->Host = SMTP_HOST;
	$phpmailer->SMTPAuth = SMTP_AUTH;
	$phpmailer->Port = SMTP_PORT;
	$phpmailer->SMTPSecure = SMTP_SECURE;
	$phpmailer->Username = SMTP_USER;
	$phpmailer->Password = SMTP_PASS;
	$phpmailer->From = SMTP_FROM;
	$phpmailer->FromName = SMTP_NAME;
	$phpmailer->isSMTP();
}
add_action( 'phpmailer_init', 'cb_core_smtp_init' );


/**
 * Adds a menu item for our settings.
 * 
 * @package ConfettiBits\Core
 * @subpackage Templates
 * @since 3.0.0
 */
function cb_core_admin_menu() {
	add_options_page(
		'Confetti Bits',    // Page title
		'Confetti Bits',    // Menu title
		'manage_options',       // Capability required to access the page
		'cb-core-admin-settings',    // Menu slug
		'cb_core_admin_page'// Callback function to display the page content
	);
}
add_action('admin_menu', 'cb_core_admin_menu');

/**
 * Formats markup for our admin settings page.
 * 
 * @TODO: Add a form here, friend.
 * 
 * @return string The formatted page markup.
 * 
 * @package ConfettiBits\Templates
 * @since 3.0.0
 */
function cb_templates_get_admin_page() {

	$heading = cb_templates_get_heading('Confetti Bits Settings', 1);

	return cb_templates_container([
		'classes' => ['wrap'],
		'output' => $heading
	]);

}

/**
 * Outputs markup for our admin settings page.
 * 
 * @see cb_templates_get_admin_page()
 * 
 * @package ConfettiBits\Templates
 * @since 3.0.0
 */
function cb_core_admin_page() {

	echo '<div class="wrap">';
	cb_templates_heading('Confetti Bits Settings', 1);
	echo '<form method="POST" action="options.php">';
	settings_fields('cb_core_admin_settings');
	do_settings_sections('cb-core-admin-settings'); 
	submit_button();
	echo '</form>';
	echo '</div>';

}

function cb_core_admin_settings_init() {

	// Register the settings
	register_setting(
		'cb_core_admin_settings', // Option group (used in settings_fields)
		'cb_reset_date',       // Option name (used in the database)
		'cb_core_admin_settings_sanitize'// Sanitization callback function
	);

	register_setting(
		'cb_core_admin_settings', // Option group (used in settings_fields)
		'cb_core_volunteer_amount',       // Option name (used in the database)
		'cb_core_admin_settings_sanitize'// Sanitization callback function
	);

	register_setting(
		'cb_core_admin_settings', // Option group (used in settings_fields)
		'cb_core_spot_bonus_amount',       // Option name (used in the database)
		'cb_core_admin_settings_sanitize'// Sanitization callback function
	);

	// Add a section and fields for your settings
	add_settings_section(
		'cb_core_admin_settings_section',
		'Confetti Bits Core Settings',
		'cb_core_admin_settings_section_callback',
		'cb-core-admin-settings'
	);

	add_settings_field(
		'cb_reset_date',
		'Cycle Reset Date',
		'cb_core_admin_reset_date_setting',
		'cb-core-admin-settings',
		'cb_core_admin_settings_section'
	);

	add_settings_field(
		'cb_core_volunteer_amount',
		'Amount for Volunteer Hours',
		'cb_core_admin_volunteer_setting',
		'cb-core-admin-settings',
		'cb_core_admin_settings_section'
	);

	add_settings_field(
		'cb_core_spot_bonus_amount',
		'Amount for Spot Bonuses',
		'cb_core_admin_spot_bonus_setting',
		'cb-core-admin-settings',
		'cb_core_admin_settings_section'
	);

}
add_action('admin_init', 'cb_core_admin_settings_init');

/**
 * Will eventually be used to sanitize user input in the
 * admin menu.
 * 
 * @param mixed $input The input passed via post.
 * @return string $input The "sanitized" input.
 * 
 * @package ConfettiBits\Core
 * @subpackage Templates
 * @since 3.0.0
 */
function cb_core_admin_settings_sanitize($input) {

	return $input;

}
add_action('admin_init', 'cb_core_admin_settings_sanitize');

/**
 * Returns the content for our main settings section.
 * 
 * @return string Content.
 * 
 * @package ConfettiBits\Core
 * @subpackage Templates
 * @since 3.0.0
 */
function cb_core_admin_settings_section_callback() {
	// You can add section description here if needed
	echo "
	<p>Here is where we house all of our global settings used throughout the app.</p>
	<p>This will include things such as the yearly reset date, the amounts for certain actions, and other
	settings as we add them.</p>
	";
}

/**
 * Outputs the setting field for the reset date.
 * 
 * @package Core
 * @since 3.0.0
 */
function cb_core_admin_reset_date_setting() {

	$option = get_option('cb_reset_date');
	$value = !empty($option) ? date('Y-m-d', strtotime($option) ) : '';
	echo '<input type="date" name="cb_reset_date" value="' . esc_attr($value) . '" />';

}

/**
 * Outputs the setting field for the volunteer amount per hour.
 * 
 * @package ConfettiBits\Core
 * @subpackage Templates
 * @since 3.0.0
 */
function cb_core_admin_volunteer_setting() {
	$option = get_option('cb_core_volunteer_amount');
	$value = isset($option) ? intval($option) : '';
	echo '<input type="text" name="cb_core_volunteer_amount" value="' . esc_attr($value) . '" />';
}

/**
 * Outputs the setting field for the volunteer amount per hour.
 * 
 * @package ConfettiBits\Core
 * @subpackage Templates
 * @since 3.0.0
 */
function cb_core_admin_spot_bonus_setting() {

	$option = get_option('cb_core_spot_bonus_amount');
	$value = isset($option) ? intval($option) : '';
	echo '<input type="text" name="cb_core_spot_bonus_amount" value="' . esc_attr($value) . '" />';
}

/**
 * Replaces our default script tags with modules.
 */
function cb_core_convert_scripts_to_modules($tag, $handle, $src) {

	$modules = ['cb_core', 'cb_staffing_admin', 'cb_events_admin', 'cb_events', 'cb_core_modules', 'cb_volunteers', 'cb_settings'];

	if ( in_array( $handle, $modules ) ) {
		$src = esc_url($src);
		$tag = "<script type='module' src='{$src}' defer></script>";
	}

	return $tag;
}
add_filter('script_loader_tag', 'cb_core_convert_scripts_to_modules', 10, 3);


/**
 * Deletes all Confetti Bits data associated with deleted user.
 * 
 * @param int $id The ID of the user that's being deleted.
 * @param int $reassign An optional user ID to reassign items to.
 * @param WP_User $user The instance of WP_User associated with the deleted user.
 * 
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
function cb_core_delete_user_data( $id, $reassign, $user ) {

	global $wpdb;
	$cb = Confetti_Bits();

	if ( ! is_numeric( $id ) ) {
		return false;
	}

	$id   = (int) $id;
	$user = new WP_User( $id );

	if ( ! $user->exists() ) {
		return false;
	}

	if ( null !== $reassign ) {
		$reassign = (int) $reassign;
	}

	if ( null === $reassign ) {
		$wpdb->delete($cb->participation->table_name, ['applicant_id' => $id ], ['%d']);
		$wpdb->delete($cb->transactions->table_name, ['recipient_id' => $id ], ['%d']);
		$wpdb->delete($cb->transactions->table_name, ['sender_id' => $id ], ['%d']);
		$wpdb->delete($cb->requests->table_name, ['applicant_id' => $id ], ['%d']);
		$wpdb->delete($cb->requests->table_name, ['admin_id' => $id ], ['%d']);
	}

}
add_action('delete_user', 'cb_core_delete_user_data', 10, 3);

function cb_core_do_spot_bonuses() {

	$spot_bonus = new CB_Transactions_Spot_Bonus();
	$transaction = new CB_Transactions_Transaction();
	$date = new DateTime('now', new DateTimeZone('UTC'));
	$recipient_list = [];

	$spot_bonus_get_args = [
		'select' => '*',
		'where' => [
			'date_query' => [
				'column' => 'spot_bonus_date',
				'before' => [
					'day' => $date->format('d'),
					'month' => $date->format('m'),
					'year' => $date->format('Y'),
				],
				'after' => [
					'day' => $date->format('d'),
					'month' => $date->format('m'),
					'year' => $date->format('Y') - 1,
				],
				'inclusive' => true,
			],
			'transaction_id' => null,
		]
	];

	$bonuses_for_today = $spot_bonus->get_spot_bonuses($spot_bonus_get_args);

	if ( ! empty( $bonuses_for_today ) && is_array( $bonuses_for_today ) ) {

		foreach ( $bonuses_for_today as $bonus ) {

			if ( !is_null( $bonus['transaction_id'] ) ) {
				continue;
			}


			$recipient_id = intval($bonus['recipient_id']);
			$sender_id = intval($bonus['sender_id']);
			array_push($recipient_list, cb_core_get_user_display_name($recipient_id));
			$bonus_date = new DateTime($bonus['spot_bonus_date'], new DateTimeZone('UTC'));
			$transaction->item_id = $sender_id;
			$transaction->secondary_item_id = $recipient_id;
			$transaction->sender_id = $sender_id;
			$transaction->recipient_id = $recipient_id;
			$transaction->amount = get_option('cb_core_spot_bonus_amount');
			$transaction->log_entry = "Spot Bonus winner {$bonus_date->format('m/d/Y')}";
			$transaction->date_sent = $date->format('Y-m-d H:i:s');
			$transaction->component_name = "transactions";
			$transaction->component_action = "cb_transactions_spot_bonus";

			$save = $transaction->send_bits();

			$spot_bonus->update(['transaction_id' => $save], ['id' => $bonus['id']]);
		}

	}

	$message = !empty($recipient_list) ? 
		'Here\'s a list of people who got their bits: <ul>' . implode('', array_map(cb_core_listify($recipient_list) ) ) . '</ul>' 
		: "<p>We didn't find anyone waiting on their bits.</p> <p><b>Hooray!</b></p>";

	wp_mail('dustin@celebrationtitlegroup.com', 'Cron Job Report', "<h4>Spot bonuses were sent out!</h4> {$message}", ['Content-Type: text/html; charset=UTF-8']);

}
add_action('cb_core_run_spot_bonuses', 'cb_core_do_spot_bonuses');

function cb_core_listify( $string ) {
	return "<li>{$string}</li>";
}

function cb_core_schedule_events() {

	if (! wp_next_scheduled ( 'cb_core_run_spot_bonuses' )) {
		wp_schedule_event(time(), 'daily', 'cb_core_run_spot_bonuses');
	}

}
add_action('cb_actions', 'cb_core_schedule_events');

add_filter( 'cron_schedules', function ( $schedules ) {
	$schedules['per_minute'] = array(
		'interval' => 60,
		'display' => __( 'One Minute' )
	);
	return $schedules;
} );

function cb_core_utc_to_local( $date_string = '' ) {

	$site_tz = wp_timezone();
	$date = new DateTime($date_string, new DateTimeZone('UTC'));
	$date->setTimezone($site_tz);

	return $date;
}

/**
 * Adds an ordinal suffix to a given integer.
 * 
 * @param int|string $int An integer.
 * 
 * @return string The same integer, but with a little spice.
 * 
 * @package Core
 * @since 3.0.0
 */
function cb_core_ordinal_suffix( $int ) {

	$int = intval($int);

	if ($int >= 11 && $int <= 13) {
		return "{$int}th";
	}

	if ( $int % 10 === 1)  {
		return "{$int}st";
	}

	if ( $int % 10 === 2 )  {
		return "{$int}nd";
	}

	if ( $int % 10 === 3 )  {
		return "{$int}rd";
	}

	return "{$int}th";

}