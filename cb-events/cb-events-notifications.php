<?php 
// Exit if accessed directly.
defined('ABSPATH') || exit;

function cb_events_format_notifications( $component_action = '', $args = [] ) {

	if ( !isset( $args['title'], $args['text'], $args['item_id'], $component_action ) ) {
		return;
	}

	$event_id = intval($args['item_id']);
	$event = new CB_Events_Event($event_id);
	return [
		'title' => sprintf( $args['title'], $event->event_title ),
		'text' => sprintf( $args['text'], $event->event_title ),
		'link' => home_url('confetti-bits'),
	];

}

function cb_events_notifications( $args = [] ) {

	if ( !isset( $args['event_id'], $args['recipient_id'], $args['component_action'] ) ) {
		return;
	}

	$event_id = intval($args['event_id']);
	$recipient_id = intval($args['recipient_id']);
	$event = new CB_Events_Event($event_id);
	$email_type = str_replace('_', '-', $args['component_action']);
	$unsubscribe_args = [
		'user_id' => $recipient_id,
		'notification_type' => $email_type,
	];

	$email_args = [
		'tokens' => [
			'event.event_title' => $event->event_title,
			'event.participation_amount' => $event->participation_amount,
			'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
		],
	];

	$notification_args = [
		'user_id' => $recipient_id,
		'item_id' => $args['item_id'],
		'secondary_item_id' => $args['secondary_item_id'],
		'component_name' => $args['component_name'],
		'component_action' => $args['component_action'],
		'date_notified' => cb_core_current_date(),
		'is_new' => 1,
		'allow_duplicate' => true,
	];

	if ( $args['component_action'] === 'cb_events_contest_new_transactions' ) {
		$contest = new CB_Events_Contest();
		$placement = $contest->get_contests(['where' => ['event_id' => $event_id, 'recipient_id' => $recipient_id]]);
		$email_args['tokens']['contest.pretty_placement'] = cb_core_ordinal_suffix($placement[0]['placement']);
		$email_args['tokens']['contest.amount'] = cb_core_ordinal_suffix($placement[0]['amount']);
	}

	bp_send_email($email_type, $recipient_id, $email_args);
	bp_notifications_add_notification($notification_args);

}