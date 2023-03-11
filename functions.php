<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

function cb_participation_survey() {
	if( is_page('participation-survey') ) {
		echo '<script type="text/javascript" src="https://form.jotform.com/jsform/223134225267147"></script><style>.entry-title {display:none;}</style>';
	}
}
add_action( 'buddyboss_theme__template_parts_content_top', 'cb_participation_survey');

if ( ! function_exists( 'confetti_bits_admin_enqueue_script' ) ) {
	function confetti_bits_admin_enqueue_script() {
		wp_enqueue_style( 'confetti-bits-admin-css', plugin_dir_url( __FILE__ ) . 'style.css' );
	}

	add_action( 'admin_enqueue_scripts', 'confetti_bits_admin_enqueue_script' );
}

add_action(
	'bp_init',
	function () {
		if ( class_exists( 'Confetti_Bits_Notifications_Component' ) ) {
			Confetti_Bits_Notifications_Component::instance();
		}
	}
);

add_action( 
	'wp_enqueue_scripts', 
	function () {
		if ( function_exists( 'cb_is_user_confetti_bits' ) ) {
			if ( cb_is_user_confetti_bits() ) {

				wp_enqueue_script( 'cb_dropzone_js', 'https://unpkg.com/dropzone@5/dist/min/dropzone.min.js' );
				wp_enqueue_script( 'cb_file_uploads', CONFETTI_BITS_PLUGIN_URL . 'assets/js/cb-participation-uploads.js', 'jquery' );
				if ( cb_is_user_participation_admin() ) {
					wp_enqueue_script( 'cb_hub_admin', CONFETTI_BITS_PLUGIN_URL . 'assets/js/cb-hub-admin.js', 'jquery');
				}

				$cb_upload_param = array(
					'upload'	=> admin_url( 'admin-ajax.php?action=cb_upload_media' ),
					'delete'	=> admin_url( 'admin-ajax.php?action=cb_delete_media' ),
					'total'		=> admin_url( 'admin-ajax.php?action=cb_participation_get_participation_total' ),
					'paged'		=> admin_url( 'admin-ajax.php?action=cb_participation_get_paged_participation' ),
					'create'	=> admin_url( 'admin-ajax.php?action=cb_participation_create_participation' ),
					'update'	=> admin_url( 'admin-ajax.php?action=cb_participation_update_participation' ),
					'nonce'		=> wp_create_nonce( 'cb_participation_post' )
				);

				wp_localize_script( 
					'cb_file_uploads', 
					'cb_upload', 
					$cb_upload_param 
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

function cb_save_user_birthday_anniversary_fields($user_id, $notify){

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