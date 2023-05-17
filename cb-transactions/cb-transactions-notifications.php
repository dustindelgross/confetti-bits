<?php 

function cb_groups_activity_notifications($content, $user_id, $group_id, $activity_id)
{

	$group = bp_groups_get_activity_group($group_id);
	$user_ids = BP_Groups_Member::get_group_member_ids($group_id);

	foreach ((array) $user_ids as $notified_user_id) {

		if ('no' === bp_get_user_meta($notified_user_id, 'cb_group_activity', true)) {
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