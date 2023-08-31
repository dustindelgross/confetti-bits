<?php
/**
 * CB Events Functions
 *
 * These are going to be all of our CRUD functions for
 * the events component.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Saves a new event to the database.
 * 
 * @param array $args { 
 *     An associative array of arguments.
 *     Accepts any parameters of a 
 *     CB_Events_Event object.
 * }
 */
function cb_events_new_event( $args = [] ) {

	$r = wp_parse_args( $args, [
		'user_id' => get_current_user_id(),
		'event_title' => '',
		'event_desc' => '',
		'participation_amount' => 5,
	]);
	
	$feedback = ['type' => 'error', 'text' => ''];
	$start_date = new DateTimeImmutable($r['event_start_date']);

	if ( 
		empty( $r['user_id'] ) || 
		empty( $r['event_title'] ) || 
		empty( $r['participation_amount'] ) || 
		empty( $r['event_start_date'] ) || 
		empty( $r['event_end_date'] ) 
	) {
		$feedback["text"] = "Event creation failed. Missing one of the following parameters: user ID, event title, participation amount, start date, or end date.";
		return $feedback;
	}
	
	$event = new CB_Events_Event();
	$event->user_id = intval($r['user_id']);
	$event->event_title = cb_core_sanitize_string($r['event_title']);
	$event->event_desc = cb_core_sanitize_string($r['event_desc']);
	$event->participation_amount = intval($r['participation_amount']);
	$event->date_created = cb_core_current_date();
	$event->date_modified = cb_core_current_date();

}

/**
 * Sends out notifications when a new event is created.
 * 
 * @param array $data { 
 *     An associative array of key => value pairs from
 *     a CB_Events_Event object.
 * 
 *     @see CB_Events_Event::save()
 * }
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_events_new_notifications($data = [])
{

	$r = wp_parse_args( $data, [
		'event_title' => '',
		'event_desc' => '',
		'event_start' => '',
		'event_end' => '',
		'participation_amount' => 0,
		'user_id' => 0,
	]);

	if (
		empty($data) ||
		empty($r['event_title']) ||
		empty($r['user_id']) ||
		empty($r['participation_amount'])
	) {
		return;
	}

	$item_id = 0;
	$secondary_item_id = 0;
	/*
	switch ($r['component_action']) {

		case ('cb_participation_new'):
			$item_id = $r['admin_id'];
			$secondary_item_id = $r['applicant_id'];

			$unsubscribe_args = array(
				'user_id' => $item_id,
				'notification_type' => 'cb-participation-new',
			);

			$email_args = array(
				'tokens' => array(
					'applicant.name' => bp_core_get_user_displayname($secondary_item_id),
					'participation.note' => $r['event_note'],
					'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
				),
			);

			bp_send_email('cb-participation-new', $item_id, $email_args);
			break;

	}
*/
	bp_notifications_add_notification(
		array(
			'user_id' => $item_id,
			'item_id' => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'component_name' => 'confetti_bits',
			'component_action' => $r['component_action'],
			'date_notified' => current_time('mysql', true),
			'is_new' => 1,
			'allow_duplicate' => true,
		)
	);

}

/**
 * CB Events Update Notifications
 *
 * Sends out an update notification when an event is updated.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_events_update_notifications($data = [])
{

	$r = wp_parse_args( $data, [
		'event_title' => 0,
		'event_desc' => 0,
		'participation_amount' => '',
		'event_start' => '',
		'event_end' => '',
		'user_id'
	]);

	if (
		empty($data) ||
		empty($r['event_title']) ||
		empty($r['participation_amount']) ||
		empty($r['user_id'])
	) {
		return;
	}

	$item_id = 0;
	$secondary_item_id = 0;
	$event_type = ucwords(str_replace('_', ' ', $r['event_type']));
	$event_note = '';

	if (!empty($r['event_note'])) {
		$event_note = ucwords(str_replace('_', ' ', $r['event_note']));
	} else {
		$event_note = $event_type;
	}

	switch ($r['component_action']) {

		case ('cb_participation_status_update'):

			$item_id = $r['applicant_id'];
			$secondary_item_id = $r['admin_id'];


			/*
			if ( $r['status'] === 'denied' ) {
			$unsubscribe_args = array(
			'user_id'           => $item_id,
			'notification_type' => 'cb-participation-status-denied',
			);
			$email_args = array(
			'tokens' => array(
			'admin.name' => bp_core_get_user_displayname( $secondary_item_id ),
			'participation.status' => ucfirst( $r['status'] ),
			'participation.type' => $event_type,
			'participation.note' => $event_note,
			'unsubscribe' => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
			),
			);
			bp_send_email( 'cb-participation-status-denied', (int) $item_id, $email_args );
			*/
			//			} else {

			$unsubscribe_args = array(
				'user_id' => $item_id,
				'notification_type' => 'cb-participation-status-update',
			);

			$email_args = array(
				'tokens' => array(
					'admin.name' => bp_core_get_user_displayname($secondary_item_id),
					'participation.status' => ucfirst($r['status']),
					'participation.note' => $event_note,
					'unsubscribe' => esc_url(bp_email_get_unsubscribe_link($unsubscribe_args)),
				),
			);

			bp_send_email('cb-participation-status-update', (int) $item_id, $email_args);

			//			}

			break;
	}

	bp_notifications_add_notification(
		array(
			'user_id' => $item_id,
			'item_id' => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'component_name' => 'confetti_bits',
			'component_action' => $r['component_action'],
			'date_notified' => current_time('mysql', true),
			'is_new' => 1,
			'allow_duplicate' => false,
		)
	);

}