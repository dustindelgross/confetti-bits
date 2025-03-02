<?php

/**
 * Utility function to determine whether a blackout period is active.
 * 
 * @return bool Whether a blackout period is active.
 * @package Settings
 * @since 3.1.0
 */
function cb_settings_get_blackout_status() {

	$blackout_start_date = new DateTimeImmutable(get_option('cb_transactions_blackout_start'));
	$blackout_end_date = new DateTimeImmutable(get_option('cb_transactions_blackout_end'));
	$today = new DateTimeImmutable('now');
	$blackout_active = get_option('cb_transactions_blackout_active');
	
	if ( $today >= $blackout_start_date && $today <= $blackout_end_date ) {
		return true;	
	}
	
	return $blackout_active;

}