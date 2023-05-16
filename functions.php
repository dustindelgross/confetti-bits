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

//				wp_enqueue_script( 'cb_dropzone_js', 'https://unpkg.com/dropzone@5/dist/min/dropzone.min.js' );
				wp_enqueue_script( 'cb_participation', CONFETTI_BITS_PLUGIN_URL . 'assets/js/cb-participation.js', array('jquery') );

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
					'transactions'=> admin_url( 'admin-ajax.php?action=cb_transactions_get_transactions' ),
					'user_id'	=> $user_id,
				);

				$cb_participation_params = array(
					'upload'		=> admin_url( 'admin-ajax.php?action=cb_upload_media' ),
					'delete'		=> admin_url( 'admin-ajax.php?action=cb_delete_media' ),
					'total'			=> admin_url( 'admin-ajax.php?action=cb_participation_get_total_participation' ),
					'paged'			=> admin_url( 'admin-ajax.php?action=cb_participation_get_paged_participation' ),
					'create'		=> admin_url( 'admin-ajax.php?action=cb_participation_create_participation' ),
					'update'		=> admin_url( 'admin-ajax.php?action=cb_participation_update_participation' ),
					'transactions'	=> admin_url( 'admin-ajax.php?action=cb_participation_get_transactions' ),
					'total_transactions'	=> admin_url( 'admin-ajax.php?action=cb_participation_get_total_transactions' ),
					'nonce'			=> wp_create_nonce( 'cb_participation_post' ),
				);

				$cb_transactions_params = array(
					'send'		=> admin_url( 'admin-ajax.php?action=cb_send_bits' )
				);

				wp_localize_script( 
					'cb_participation', 
					'cb_participation', 
					$cb_participation_params
				);

				wp_localize_script( 
					'cb_core', 
					'cb_core', 
					$cb_core_params
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
 * @since Confetti_Bits 2.3.0
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
 * @since Confetti_Bits 2.3.0
 * 
 * @param bool $offset Whether to use the site's
 *   UTC offset setting. Default true.
 * 
 * @param string $format The desired datetime format.
 *   Default MySQL - 'Y-m-d H:i:s'
 * 
 * @return string The formatted datetime.
 */
function cb_core_current_date( $offset = true, $format = "Y-m-d H:i:s" ) {
	
	$tz = $offset ? wp_timezone() : null;
	$date = new DateTimeImmutable("now", $tz);
	
	return $date->format($format);

}