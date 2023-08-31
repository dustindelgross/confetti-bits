<?php 
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;
/**
 * Houses all of our transaction notification functions.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Notifications
 * @since 1.1.0
 */

/**
 * Sends a notification to members of a group when someone posts.
 * 
 * @param string $content The notification content.
 * @param int $user_id The ID of the user who is posting in the group.
 * @param int $group_id The group ID.
 * @param int $activity_id The ID of the activity post.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Notifications
 * @since 1.3.0
 */
function cb_groups_activity_notifications($content, $user_id, $group_id, $activity_id) {

	$group = bp_groups_get_activity_group($group_id);
	$user_ids = BP_Groups_Member::get_group_member_ids($group_id);

	foreach ((array) $user_ids as $notified_user_id) {

		if ('no' === bp_get_user_meta($notified_user_id, 'cb_groups_activity_post', true)) {
			continue;
		}

		$unsubscribe_args = array(
			'user_id' => $notified_user_id,
			'notification_type' => 'cb-groups-activity-post',
		);

		$args = array(
			'tokens' => array(
				'group_member.name' => bp_core_get_user_displayname($user_id),
				'group.name' => $group->name,
				'group.id' => $group_id,
				'group.url' => esc_url(bp_get_group_permalink($group)),
				'group_activity.content' => esc_html($content),
				'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
			),
		);
		
		bp_notifications_add_notification(
			array(
				'user_id' => $notified_user_id,
				'item_id' => $group_id,
				'secondary_item_id' => $user_id,
				'component_name' => 'groups',
				'component_action' => 'activity_update',
				'allow_duplicate' => false,
			)
		);
		bp_send_email('cb-groups-activity-post', (int) $notified_user_id, $args);
	}
}
add_action('bp_groups_posted_update', 'cb_groups_activity_notifications', 10, 4);

/**
 * Sends transaction notifications based on component action.
 * 
 * @param array $data { 
 *     An associative array of data received from the 
 *     CB_Transactions_Transaction::save() method.
 * 
 *     @see CB_Transactions_Transaction::save().
 * 
 * }
 * 
 * @return int|bool Notification ID on success, false on failure.
 * 
 * @package ConfettiBits\Transactions
 * @subpackage Notifications
 * @since 1.1.0
 */
function cb_transactions_notifications( $data = [] ) {

	$r = wp_parse_args( $data, [
		'item_id' => 0,
		'secondary_item_id' => 0,
		'sender_id' => 0,
		'recipient_id' => 0,
		'date_sent' => '',
		'log_entry' => '',
		'component_name' => '',
		'component_action' => '',
		'amount' => 0,
		'event_id' => 0,
	]);

	if (
		empty($data) ||
		empty($r['sender_id']) ||
		empty($r['recipient_id']) ||
		empty($r['component_action'])
	) {
		return;
	}
	
	$recipient_id = intval( $r['recipient_id'] );
	$sender_id = intval( $r['sender_id'] );

	$notification_args = [
		'user_id' => $recipient_id,
		'item_id' => $sender_id,
		'secondary_item_id' => $recipient_id,
		'component_name' => 'confetti_bits',
		'component_action' => $r['component_action'],
		'date_notified' => cb_core_current_date(),
		'is_new' => 1,
	];

	$unsubscribe_args = [
		'user_id' => $recipient_id
	];

	$email_args = ['tokens' => [
		'user.first_name' => xprofile_get_field_data(1, $recipient_id),
		'user.cb_url' => Confetti_Bits()->page,
		'transaction.amount' => intval($r['amount']),
		'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
	]];

	if ( 
		$r['component_action'] === 'cb_send_bits' || 
		$r['component_action'] === 'cb_transfer_bits' 
	) {
		$notification_args['allow_duplicate'] = true;
	}

	if ( $r['component_action'] === 'cb_birthday_bits' ) {

		$notification_args['allow_duplicate'] = true;
		$unsubscribe_args['notification_type'] = 'cb-birthday-bits';

		bp_send_email('cb-birthday-bits', $recipient_id, $email_args);

	}

	if ( $r['component_action'] === 'cb_anniversary_bits' ) {

		$unsubscribe_args['notification_type'] = 'cb-anniversary-bits';

		bp_send_email('cb-anniversary-bits', $recipient_id, $email_args);

	}
	
	if ( $r['component_action'] === 'cb_requests_status_update' ) {
		return;
	}

	bp_notifications_add_notification($notification_args);

}
add_action('cb_transactions_after_send', 'cb_transactions_notifications');