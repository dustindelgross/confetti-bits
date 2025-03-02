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

	

	if ( $r['component_action'] === "cb_transactions_spot_bonus" ) {
		return cb_transactions_spot_bonus_notifications($r);
	}

	if ( $r['component_action'] === 'cb_transactions_volunteer_bits' ) {
		return cb_transactions_volunteer_bits_notifications($r);
	}
	
	if ( $r['component_name'] === 'events' || !empty($r['event_id'] ) ) {
		return cb_events_notifications($r);
	}

	$recipient_id = intval( $r['recipient_id'] );
	$sender_id = intval( $r['sender_id'] );
	$allow_duplicates = [ 'cb_birthday_bits', 'cb_send_bits', 'cb_transfer_bits', 'cb_events_new_transactions', 'cb_events_contest_new_transactions' ];
	$notification_args = [
		'user_id' => $recipient_id,
		'item_id' => $r['item_id'],
		'secondary_item_id' => $r['secondary_item_id'],
		'component_name' => $r['component_name'],
		'component_action' => $r['component_action'],
		'date_notified' => cb_core_current_date(),
		'is_new' => 1,
	];

	$unsubscribe_args = [
		'user_id' => $recipient_id
	];

	if ( in_array( $r['component_action'], $allow_duplicates ) ) {
		$notification_args['allow_duplicate'] = true;
	}

	$email_args = ['tokens' => [
		'user.first_name' => xprofile_get_field_data(1, $recipient_id),
		'user.cb_url' => Confetti_Bits()->page,
		'transaction.amount' => intval($r['amount']),
		'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
	]];

	if ( $r['component_action'] === 'cb_birthday_bits' ) {
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

	return bp_notifications_add_notification($notification_args);

}
add_action('cb_transactions_after_send', 'cb_transactions_notifications');


function cb_transactions_format_notifications( $component_action = '', $args = [] ) {

	if ( !isset( $args['title'], $args['text'], $component_action ) ) {
		return;
	}

	$display_name_notifications = ['cb_send_bits', 'cb_transfer_bits', 'cb_transactions_import_bits'];
	$retval = [
		'title' => $args['title'],
		'link' => home_url("confetti-bits"),
	];

	if ( in_array( $component_action, $display_name_notifications ) ) {
		$retval['text'] = sprintf($args['text'], cb_core_get_user_display_name($args['item_id']));
		return $retval;
	}

	if ( $component_action === 'cb_activity_bits' ) {
		$retval['text'] = sprintf( $args['text'], $args['item_id'], ( $args['item_id'] === 1 ? '' : 's' ) );
		return $retval;
	}

	if ( $component_action === 'cb_transactions_volunteer_bits' ) {
		$transaction_obj = new CB_Transactions_Transaction();
		$transaction = $transaction_obj->get_transactions([
			'where' => [
				'event_id' => intval($args['event_id']), 
				'recipient_id' => intval($args['recipient_id']), 
				'component_action' => $component_action
			],
			'orderby' => [
				'column' => 'id',
				'order' => 'DESC'
			]
		]);
		$retval['text'] = sprintf($args['text'], $transaction[0]['amount']);
		return $retval;
	}

	$retval['text'] = $args['text'];
	return $retval;

}

function cb_groups_format_notifications( $component_action, $args = [] ) {

	if ( !isset( $args['title'], $args['text'], $args['item_id'], $args['secondary_item_id'], $component_action ) ) {
		return;
	}

	$retval = [];

	$group = groups_get_group( $item_id );
	$user_name = cb_core_get_user_display_name( $secondary_item_id );

	$retval['title'] = "Activity update in {$group->name}";
	$retval['text'] =  "{$user_name} just posted in the group {$group->name}";
	$retval['link'] = esc_url( bp_get_group_permalink( $group ) );

	return $retval;

}

function cb_transactions_spot_bonus_notifications( $args = [] ) {

	$recipient_id = intval( $args['recipient_id'] );
	$sender_id = intval( $args['sender_id'] );
	$email_type = str_replace( '_', '-', $args['component_action']);
	$notification_args = [
		'user_id' => $recipient_id,
		'item_id' => $args['item_id'],
		'secondary_item_id' => $args['secondary_item_id'],
		'component_name' => $args['component_name'],
		'component_action' => $args['component_action'],
		'date_notified' => cb_core_current_date(),
		'is_new' => 1,
	];

	$unsubscribe_args = [
		'user_id' => $recipient_id,
		'notification_type' => $email_type,
	];

	$email_args = ['tokens' => [
		'transaction.amount' => $args['amount'],
		'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
	]];

	bp_send_email($email_type, $recipient_id, $email_args);

	return bp_notifications_add_notification($notification_args);

}

function cb_transactions_volunteer_bits_notifications( $args = [] ) {

	$recipient_id = intval( $args['recipient_id'] );
	$sender_id = intval( $args['sender_id'] );
	$event_id = intval($args['event_id']);
	$email_type = str_replace( '_', '-', $args['component_action']);
	$notification_args = [
		'user_id' => $recipient_id,
		'item_id' => $args['item_id'],
		'secondary_item_id' => $args['secondary_item_id'],
		'component_name' => $args['component_name'],
		'component_action' => $args['component_action'],
		'date_notified' => cb_core_current_date(),
		'is_new' => 1,
	];
	$event = new CB_Events_Event($args['event_id']);
	

	$unsubscribe_args = [
		'user_id' => $recipient_id,
		'notification_type' => $email_type,
	];

	$email_args = [
		'tokens' => [
		'event.event_title' => $event->event_title,
		'amount' => $args['amount'],
		'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
	]];

	bp_send_email($email_type, $recipient_id, $email_args);

	return bp_notifications_add_notification($notification_args);

}