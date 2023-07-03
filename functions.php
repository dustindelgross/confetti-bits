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
 * Enqueues all of our scripts in a clean fashion.
 * 
 * From each according to their capability, to each
 * according to their need.
 * Scripts are enqueued and localized using the following
 * nested array structure: [ 
 *     $unique_name_for_script => [ 
 *         $name_of_api_component_to_use => [ $http_method1, $http_method2, ... ],
 *         'dependencies' => [ $dependency1, $dependency2, ... ]
 *     ]
 * ]
 * This structure is then picked apart to dynamically 
 * enqueue and localize scripts. This gives us granular
 * control over who gets access to what API endpoints,
 * and can perform which actions, based on capability.
 * 
 * The scripts will be enqueued as: "cb_{$unique_name_for_script}", 
 * and will load a corresponding file, with the underscores
 * replaced with dashes, like so: "cb-{$unique-name-for-script}.js".
 * This will also load any dependencies found in the
 * 'dependencies' array.
 * 
 * Scripts will then be localized using the same
 * "cb_{$unique_name_for_script}" identifier, which will 
 * become the global name that is usable within the file.
 * All API endpoints are localized as: 
 * "{$endpoint}_{$name_of_api_component}". 
 * 
 * So, for example:
 *     - "cb_participation.get_participation" will return the 
 *       API endpoint for getting participation entries ONLY when used
 * 		 within the cb-participation.js file.
 *     - "cb_core_admin.new_transactions" will return the API endpoint 
 *       for creating a new transaction ONLY when used within the 
 * 		 cb-core-admin.js file.
 * 
 * To access the API key, use "{$unique_name_for_script}.api_key"
 * in the {$unique-name-for-script}.js file.
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 *//*
function cb_core_enqueue_scripts() {

	if ( function_exists( 'cb_is_confetti_bits_component' ) ) {
		if ( cb_is_confetti_bits_component() ) {

			$cache_bust = 'v1.1';
			$user_id = intval(get_current_user_id());
			$api_key_safe_name = get_option( 'cb_core_api_key_safe_name' );

			$components = [
				'participation' => [ 
					'participation' => ['get', 'new', 'update'],
					'dependencies' => ['jquery'],
				],
			];
			
			if ( cb_is_user_admin() ) {
				$components['core_admin'] = [ 
					'participation' => ['get', 'update'], 
					'transactions' => ['get'],
					'dependencies' => ['jquery'],
				];
			}

			if ( cb_is_user_participation_admin() ) {
				$components['participation_admin'] = [ 
					'participation' => ['get', 'update'], 
					'transactions' => ['get'],
					'dependencies' => ['jquery'],
				];
			}

			if ( cb_is_user_requests_admin() ) {
				
			}
			
			if ( cb_is_user_site_admin() ) {

			}

			foreach( $components as $component => $params ) {

				$localize_params = [];
				$with_dashes = str_replace( '_', '-', $component );

				wp_enqueue_script( 
					"cb_{$component}", 
					CONFETTI_BITS_PLUGIN_URL . "assets/js/cb-{$with_dashes}.js", 
					$params['dependencies'],
					$cache_bust,
					true
				);

				unset( $params['dependencies'] );

				foreach ( $params as $api => $endpoints ) {
					$api_with_dashes = str_replace( '_', '-', $api );
					foreach ( $endpoints as $endpoint ) {
						$localize_params["{$endpoint}_{$api}"] = home_url("wp-json/cb-ajax/v1/{$api_with_dashes}/{$endpoint}");
					}

					$localize_params['api_key'] = $api_key_safe_name;
					$localize_params['user_id'] = $user_id;

				}

				wp_localize_script( "cb_{$component}", "cb_{$component}", $localize_params );

			}
		}
	}
}
//add_action( 'cb_enqueue_scripts', 'cb_core_enqueue_scripts'	);
*/

function cb_user_birthday_anniversary_fields( $user ) {
	if( !current_user_can('add_users') ) {
		return false;
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
	$cb->earn_start = $date->modify('-1 year')->format('Y-m-d H:i:s');
	$cb->earn_end = $reset_date;
	$cb->spend_start = $date->modify('-1 year + 1 month')->format('Y-m-d H:i:s');
	$cb->spend_end = $date->modify('+ 1 month')->format('Y-m-d H:i:s');
	$cb->prev_earn_start = $date->modify('-2 years')->format('Y-m-d H:i:s');
	$cb->prev_spend_start = $date->modify('-2 years + 1 month')->format('Y-m-d H:i:s');

}
add_action( 'cb_setup_globals', 'cb_core_set_reset_date_globals' );

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
function cb_core_current_date( $offset = true, $format = "Y-m-d H:i:s" ) {

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
    $reset_date = DateTimeImmutable::createFromFormat('Y-m-d', $cb->earn_end);
    $interval = $current_date->diff($reset_date);
	
    return $interval->days;
	
}