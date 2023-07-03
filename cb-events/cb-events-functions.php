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
 * CB Events New Notifications
 *
 * Sends out notifications when a new event is created.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
function cb_events_new_notifications($data = array())
{

	$r = wp_parse_args(
		$data,
		array(
			'applicant_id' => 0,
			'admin_id' => 0,
			'component_action' => '',
			'event_note' => '',
			'status' => ''
		)
	);

	if (
		empty($data) ||
		empty($r['applicant_id']) ||
		empty($r['admin_id']) ||
		empty($r['status']) ||
		empty($r['component_action'])
	) {
		return;
	}

	$item_id = 0;
	$secondary_item_id = 0;

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
function cb_events_update_notifications($data = array())
{

	$r = wp_parse_args(
		$data,
		array(
			'applicant_id' => 0,
			'admin_id' => 0,
			'component_action' => '',
			'event_note' => '',
			'status' => '',
			'event_type' => ''
		)
	);

	if (
		empty($data) ||
		empty($r['applicant_id']) ||
		empty($r['admin_id']) ||
		empty($r['status']) ||
		empty($r['component_action'])
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