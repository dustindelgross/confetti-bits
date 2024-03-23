<?php 
// Exit if accessed directly.
defined('ABSPATH') || exit;

function cb_participation_format_notifications( $component_action = '', $args = [] ) {

	if ( !isset( $args['title'], $args['text'], $args['item_id'], $component_action ) ) {
		return;
	}
	
	$retval = [
		'title' => $args['title'],
		'link' => home_url('confetti-bits'),
	];
	
	$retval['text'] = sprintf( $args['text'], cb_core_get_user_display_name($args['item_id']) );
	return $retval;

}