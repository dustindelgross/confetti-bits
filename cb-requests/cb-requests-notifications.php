<?php 
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * CB Bits Request Sender Email Notification
 * 
 * This function sends an email notification to the request sender
 * 
 * @param array $args The arguments for the email notification.
 * 
 * @var int $recipient_id The ID of the recipient.
 * @var int $sender_id The ID of the sender.
 * @var int $amount The amount of Confetti Bits being sent.
 * @var string $request_item The item being requested.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.0.0
 */
function cb_bits_request_sender_email_notification($args = array()) {

	$r = wp_parse_args(
		$args,
		array(
			'recipient_id' => 0,
			'sender_id' => 0,
			'amount' => 0,
			'request_item' => '',
		)
	);

	$request_fulfillment_name = bp_core_get_user_displayname($r['sender_id']);

	if ('no' != bp_get_user_meta($r['recipient_id'], 'cb_bits_request', true)) {

		$unsubscribe_args = array(
			'user_id' => $r['recipient_id'],
			'notification_type' => 'cb-send-bits-request-email',
		);

		$email_args = array(
			'tokens' => array(
				'request_fulfillment.name' => $request_fulfillment_name,
				'request_sender.item' => $r['request_item'],
				'request.amount' => abs($r['amount']),
				'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
			),
		);

		// the address that gets this email is going to be for the person that sends the request
		bp_send_email('cb-send-bits-request-email', $r['recipient_id'], $email_args);
	}

	do_action('cb_transactions_sent_request_email_notification', $args);

}

/**
 * Sends out notifications whenever someone submits a new request.
 * 
 * @param array $args { 
 *     An associative array of arguments passed from the
 *     CB_Requests_Request::save() method.
 * 
 *     @see CB_Requests_Request::save()
 * }
 * 
 * @package ConfettiBits\Requests
 * @subpackage Notifications
 * @since 3.0.0
 */
function cb_requests_new_request_notifications($args = []) {

	$r = wp_parse_args( $args, [
		'applicant_id' => 0,
		'request_item_id'
	]);

	if ( empty($r['applicant_id']) || empty($r['request_item_id']) ) {
		return;
	}

	$request_item = new CB_Requests_Request_Item($r['request_item_id']);
	$applicant_name = cb_core_get_user_display_name($r['applicant_id']);
	$applicant_email = cb_core_get_user_email($r['applicant_id']);
	$requests_admins = get_users(['role' => 'cb_requests_admin']);
	$site_admins = get_users(['role' => 'administrator']);

	if ('no' != get_user_meta( $r['applicant_id'], 'cb_requests_new_request', true ) ) {

		$unsubscribe_args = [
			'user_id' => $r['applicant_id'],
			'notification_type' => 'cb-requests-new-request-email',
		];

		$email_args = [ 'tokens' => [
			'applicant.id' => $r['applicant_id'],
			'applicant.name' => $applicant_name,
			'request.item' => $request_item->name,
			'request.amount' => $request_item->amount,
			'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
		]];

		bp_notifications_add_notification([
			'user_id' => $r['applicant_id'],
			'item_id' => $r['applicant_id'],
			'secondary_item_id' => $r['request_item_id'],
			'component_name' => 'confetti_bits',
			'component_action' => $r['component_action'],
			'date_notified' => cb_core_current_date(),
			'is_new' => 1,
			'allow_duplicate' => true,
		]);

		bp_send_email('cb-requests-new-request-email', $applicant_email, $email_args);

	}

}
add_action( 'cb_requests_after_save', 'cb_requests_new_request_notifications' );

/**
 * Sends notifications to request admins after a new request is sent in.
 * 
 * @param array $args { 
 *     An associative array of arguments passed from the
 *     CB_Requests_Request::save() method.
 * 
 *     @see CB_Requests_Request::save()
 * }
 * 
 * @package ConfettiBits\Requests
 * @subpackage Notifications
 * @since 3.0.0
 */
function cb_requests_admin_new_request_notifications( $args = []) {

	$r = wp_parse_args( $args, [
		'applicant_id' => 0,
		'request_item_id'
	]);

	if ( empty($r['applicant_id']) || empty($r['request_item_id']) ) {
		return;
	}

	$request_item = new CB_Requests_Request_Item($r['request_item_id']);
	$applicant_name = cb_core_get_user_display_name($r['applicant_id']);
	$requests_admins = get_users(['role' => 'cb_requests_admin']);

	if ( empty( $requests_admins ) ) {
		return;
	}
	
	foreach ( $requests_admins as $requests_admin ) {

		if ('no' != get_user_meta( $requests_admin->ID, 'cb_requests_admin_new_request', true ) ) {

			$unsubscribe_args = [
				'user_id' => $r['applicant_id'],
				'notification_type' => 'cb-requests-new-request-email',
			];

			$email_args = [ 'tokens' => [
				'applicant.id' => $r['applicant_id'],
				'applicant.name' => $applicant_name,
				'request.item' => $request_item->name,
				'request.amount' => $request_item->amount,
				'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
			]];

			bp_notifications_add_notification([
				'user_id' => $requests_admin->ID,
				'item_id' => $r['applicant_id'],
				'secondary_item_id' => $r['request_item_id'],
				'component_name' => 'confetti_bits',
				'component_action' => $r['component_action'],
				'date_notified' => cb_core_current_date(),
				'is_new' => 1,
				'allow_duplicate' => true,
			]);

			bp_send_email('cb-requests-admin-new-request-email', $requests_admin->user_email, $email_args);
			
		}
	}
}
add_action( 'cb_requests_after_save', 'cb_requests_admin_new_request_notifications' );

/**
 * Sends notifications to leadership after a new request is sent in.
 * 
 * @param array $args { 
 *     An associative array of arguments passed from the
 *     CB_Requests_Request::save() method.
 * 
 *     @see CB_Requests_Request::save()
 * }
 * 
 * @package ConfettiBits\Requests
 * @subpackage Notifications
 * @since 3.0.0
 */
function cb_requests_leadership_new_request_notifications( $args = []) {

	$r = wp_parse_args( $args, [
		'applicant_id' => 0,
		'request_item_id'
	]);

	if ( empty($r['applicant_id']) || empty($r['request_item_id']) ) {
		return;
	}

	$request_item = new CB_Requests_Request_Item($r['request_item_id']);
	$applicant_name = cb_core_get_user_display_name($r['applicant_id']);
	$leaders = get_users(['role' => 'cb_leadership']);
	
	if ( empty( $leaders ) ) {
		return;
	}

	foreach ( $leaders as $leader ) {

		if ('no' != get_user_meta( $leader->ID, 'cb_requests_admin_new_request', true ) ) {

			$unsubscribe_args = [
				'user_id' => $r['applicant_id'],
				'notification_type' => 'cb-requests-new-request-email',
			];

			$email_args = [ 'tokens' => [
				'applicant.id' => $r['applicant_id'],
				'applicant.name' => $applicant_name,
				'request.item' => $request_item->name,
				'request.amount' => $request_item->amount,
				'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
			]];

			bp_notifications_add_notification([
				'user_id' => $leader->ID,
				'item_id' => $r['applicant_id'],
				'secondary_item_id' => $r['request_item_id'],
				'component_name' => 'confetti_bits',
				'component_action' => $r['component_action'],
				'date_notified' => cb_core_current_date(),
				'is_new' => 1,
				'allow_duplicate' => true,
			]);

			bp_send_email('cb-requests-admin-new-request-email', $leader->user_email, $email_args);
			
		}
	}
}
add_action( 'cb_requests_after_save', 'cb_requests_leadership_new_request_notifications' );

/**
 * Sends notifications to site admins after a new request is sent in.
 * 
 * @param array $args { 
 *     An associative array of arguments passed from the
 *     CB_Requests_Request::save() method.
 * 
 *     @see CB_Requests_Request::save()
 * }
 * 
 * @package ConfettiBits\Requests
 * @subpackage Notifications
 * @since 3.0.0
 */
function cb_requests_site_admins_new_request_notifications( $args = []) {

	$r = wp_parse_args( $args, [
		'applicant_id' => 0,
		'request_item_id'
	]);

	if ( empty($r['applicant_id']) || empty($r['request_item_id']) ) {
		return;
	}

	$request_item = new CB_Requests_Request_Item($r['request_item_id']);
	$applicant_name = cb_core_get_user_display_name($r['applicant_id']);
	$site_admins = get_users(['role' => 'administrator']);
	
	if ( empty( $site_admins ) ) {
		return;
	}

	foreach ( $site_admins as $site_admin ) {

		if ('no' != get_user_meta( $site_admin->ID, 'cb_requests_admin_new_request', true ) ) {

			$unsubscribe_args = [
				'user_id' => $r['applicant_id'],
				'notification_type' => 'cb-requests-new-request-email',
			];

			$email_args = [ 'tokens' => [
				'applicant.id' => $r['applicant_id'],
				'applicant.name' => $applicant_name,
				'request.item' => $request_item->name,
				'request.amount' => $request_item->amount,
				'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
			]];

			bp_notifications_add_notification([
				'user_id' => $site_admin->ID,
				'item_id' => $r['applicant_id'],
				'secondary_item_id' => $r['request_item_id'],
				'component_name' => 'confetti_bits',
				'component_action' => $r['component_action'],
				'date_notified' => cb_core_current_date(),
				'is_new' => 1,
				'allow_duplicate' => true,
			]);

			bp_send_email('cb-requests-admin-new-request-email', $site_admin->user_email, $email_args);
			
		}
	}
}
add_action( 'cb_requests_after_save', 'cb_requests_site_admins_new_request_notifications' );


// // @TODO: Move to participation notifications file.
/*
	case ('cb_participation_status_update'):

	bp_notifications_add_notification(
		array(
			'user_id' => $r['recipient_id'],
			'item_id' => $r['recipient_id'],
			'secondary_item_id' => $r['sender_id'],
			'component_name' => 'confetti_bits',
			'component_action' => $r['component_action'],
			'date_notified' => current_time('mysql', true),
			'is_new' => 1,

		)
	);
	break;
	*/
