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

add_action(
	'bp_init',
	function () {
		if ( class_exists( 'CB_Notifications_Component' ) ) {
			CB_Notifications_Component::instance();
		}
	},
	10
);

add_action( 
	'cb_enqueue_scripts', 
	function () {
		if ( function_exists( 'cb_is_confetti_bits_component' ) ) {
			if ( cb_is_confetti_bits_component() ) {

				wp_enqueue_script( 
					'cb_participation', 
					CONFETTI_BITS_PLUGIN_URL . 'assets/js/cb-participation.js', 
					array('jquery') 
				);

				wp_enqueue_script( 
					'cb_core', 
					CONFETTI_BITS_PLUGIN_URL . 'assets/js/cb-core.js',
					array('jquery')
				);

				wp_enqueue_script( 
					'cb_transactions', 
					CONFETTI_BITS_PLUGIN_URL . 'assets/js/cb-transactions.js', 
					array('jquery') 
				);

				if ( cb_is_user_participation_admin() ) {
					wp_enqueue_script( 
						'cb_core_admin', 
						CONFETTI_BITS_PLUGIN_URL . 'assets/js/cb-core-admin.js', 
						array('jquery')
					);
					wp_enqueue_script( 
						'cb_participation_admin', 
						CONFETTI_BITS_PLUGIN_URL . 'assets/js/cb-participation-admin.js', 
						array('jquery') 
					);

				}

				if ( cb_is_user_site_admin() ) {
					wp_enqueue_script( 
						'cb_events', 
						CONFETTI_BITS_PLUGIN_URL . 'assets/js/cb-events.js', 
						array('jquery', 'jquery-ui-datepicker')
					);
				}

				// @TODO: Standardize this - find a good naming convention
				// and figure out a way to dynamically set all these

				$params = array(
					'core'		=> array(
						'transactions' => '',
						'user_id' => '',
					),
					'events'	=> array(),
				);

				$cb_events_params = array(

				);

				$user_id = intval(get_current_user_id());

				$cb_core_params = array(
					'get_transactions' => home_url('/wp-json/cb-ajax/v1/transactions/get'),
					'user_id'	=> $user_id,
				);

				$cb_core_admin_params = array(
					'get_participation' => home_url('/wp-json/cb-ajax/v1/participation/get'),
					'get_transactions' => home_url('/wp-json/cb-ajax/v1/transactions/get'),
					'update'		=> home_url('/wp-json/cb-ajax/v1/participation/update'),
					'nonce'			=> wp_create_nonce( 'cb_participation_post' ),
				);

				$cb_participation_params = array(
					'get'			=> home_url('/wp-json/cb-ajax/v1/participation/get'),
					'new'			=> home_url('/wp-json/cb-ajax/v1/participation/new'),
					'update'		=> home_url('/wp-json/cb-ajax/v1/participation/update'),
					'nonce'			=> wp_create_nonce( 'cb_participation_post' ),
				);
				
				$cb_participation_admin_params = [
					'get_participation'	=> home_url('/wp-json/cb-ajax/v1/participation/get'),
					'get_transactions'	=> home_url('/wp-json/cb-ajax/v1/transactions/get'),
					'update'			=> home_url('/wp-json/cb-ajax/v1/participation/update'),
					'api_key'			=> get_option( 'cb_core_api_key_safe_name' ),
				];

				$cb_transactions_params = array(
					'send'		=> admin_url( 'admin-ajax.php?action=cb_send_bits' )
				);

				wp_localize_script( 
					'cb_participation', 
					'cb_participation', 
					$cb_participation_params
				);
				
				wp_localize_script( 
					'cb_participation_admin', 
					'cb_participation_admin', 
					$cb_participation_admin_params
				);

				wp_localize_script( 
					'cb_core', 
					'cb_core', 
					$cb_core_params
				);

				wp_localize_script( 
					'cb_core_admin', 
					'cb_core_admin', 
					$cb_core_admin_params
				);

				wp_localize_script( 
					'cb_transactions', 
					'cb_transactions',
					$cb_transactions_params
				);

				wp_localize_script( 
					'cb_events', 
					'cb_events', 
					$cb_events_params
				);

			}
		}
	}
);

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
	 * @package ConfettiBits\Core
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
	 * @package ConfettiBits\Core
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
	 * @package ConfettiBits\Core
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
 * @package ConfettiBits\Core
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
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_get_patch_data() {

	if ( !cb_is_patch_request() ) {
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