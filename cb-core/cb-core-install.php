<?php 
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * CB Core Install Events
 * 
 * Installs our events table on the database.
 * 
 * @link https://developer.wordpress.org/reference/functions/dbdelta/ Uses dbDelta()
 * @link https://developer.wordpress.org/reference/classes/wpdb/ Also uses $wpdb
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_core_install_events() {

	global $wpdb;
	$prefix = $wpdb->prefix;
	$charset_collate = $wpdb->get_charset_collate();
	$sql = array();

	$sql[] = "CREATE TABLE {$prefix}confetti_bits_events (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				event_title varchar(75) NOT NULL,
				event_desc varchar(500) NULL,
				event_location varchar(100) NULL,
				date_created datetime NOT NULL,
				date_modified datetime NOT NULL,
				participation_amount int(8) DEFAULT 0,
				event_start datetime NOT NULL,
				event_end datetime NOT NULL,
				user_id int(20) NOT NULL,
				location_id int(20) NULL,
				KEY event_title (event_title),
				KEY event_desc (event_desc),
				KEY event_location (event_location),
				KEY date_created (date_created),
				KEY date_modified (date_modified),
				KEY participation_amount (participation_amount),
				KEY event_start (event_start),
				KEY event_end (event_end),
				KEY user_id (user_id),
				KEY location_id (location_id)
			) {$charset_collate};";

	dbDelta( $sql );

}

/**
 * Installs our spot bonus table on the database.
 * 
 * @link https://developer.wordpress.org/reference/functions/dbdelta/ Uses dbDelta()
 * @link https://developer.wordpress.org/reference/classes/wpdb/ Also uses $wpdb
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_core_install_spot_bonuses() {

	global $wpdb;
	$prefix = $wpdb->prefix;
	$charset_collate = $wpdb->get_charset_collate();
	$sql = [];

	$sql[] = "CREATE TABLE {$prefix}confetti_bits_spot_bonuses (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				sender_id int(20) NOT NULL,
				recipient_id int(20) NOT NULL,
				spot_bonus_date datetime NOT NULL,
				transaction_id bigint(20) NULL,
				KEY sender_id (sender_id),
				KEY recipient_id (recipient_id),
				KEY spot_bonus_date (spot_bonus_date),
				KEY transaction_id (transaction_id)
			) {$charset_collate};";

	dbDelta( $sql );

}

/**
 * CB Core Install Transactions
 * 
 * Installs our transactions table on the database.
 * 
 * @link https://developer.wordpress.org/reference/functions/dbdelta/ Uses dbDelta()
 * @link https://developer.wordpress.org/reference/classes/wpdb/ Also uses $wpdb
 * 
 * @package ConfettiBits\Core
 * @since 1.0.0
 */
function cb_core_install_transactions() {

	global $wpdb;
	$sql = array();
	$prefix = $wpdb->prefix;
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$prefix}confetti_bits_transactions (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				item_id bigint(20) NOT NULL,
				secondary_item_id bigint(20) NOT NULL,
				user_id bigint(20) NULL,
				sender_id bigint(20) NOT NULL,
				sender_name varchar(75) NULL,
				recipient_id bigint(20) NOT NULL,
				recipient_name varchar(75) NULL,
				identifier varchar(75) NULL,
				date_sent datetime NOT NULL,
				log_entry longtext NOT NULL,
				component_name varchar(75) NOT NULL,
				component_action varchar(75) NOT NULL,
				amount bigint(20) NOT NULL,
				event_id bigint(20) NULL,
				KEY item_id (item_id),
				KEY secondary_item_id (secondary_item_id),
				KEY user_id (user_id),
				KEY sender_id (sender_id),
				KEY sender_name (sender_name),
				KEY recipient_id (recipient_id),
				KEY recipient_name (recipient_name),
				KEY identifier (identifier),
				KEY date_sent (date_sent),
				KEY component_name (component_name),
				KEY component_action (component_action),
				KEY amount (amount),
				KEY event_id (event_id)
			) {$charset_collate};";

	dbDelta( $sql );

}

/**
 * CB Core Install Participation
 * 
 * Installs our transactions table on the database.
 * 
 * @link https://developer.wordpress.org/reference/functions/dbdelta/ Uses dbDelta()
 * @link https://developer.wordpress.org/reference/classes/wpdb/ Also uses $wpdb
 * 
 * @package ConfettiBits\Core
 * @since 2.1.0
 */
function cb_core_install_participation() {

	global $wpdb;
	$sql = array();
	$prefix = $wpdb->prefix;
	$table_name = "{$prefix}confetti_bits_participation";
	$charset_collate = $wpdb->get_charset_collate();

	$sql[] = "CREATE TABLE {$table_name} (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				item_id bigint(20) NOT NULL,
				secondary_item_id bigint(20) NOT NULL,
				applicant_id bigint(20) NOT NULL,
				admin_id bigint(20) NOT NULL,
				date_created datetime NOT NULL,
				date_modified datetime NOT NULL,
				event_type varchar(75) NOT NULL,
				event_date datetime NOT NULL,
				event_note longtext NOT NULL,
				component_name varchar(75) NOT NULL,
				component_action varchar(75) NOT NULL,
				status varchar(75) NOT NULL,
				transaction_id bigint(20) NULL,
				event_id bigint(20) NULL,
				KEY item_id (item_id),
				KEY secondary_item_id (secondary_item_id),
				KEY applicant_id (applicant_id),
				KEY admin_id (admin_id),
				KEY date_created (date_created),
				KEY date_modified (date_modified),
				KEY event_type (event_type),
				KEY event_date (event_date),
				KEY event_note (event_note),
				KEY component_name (component_name),
				KEY component_action (component_action),
				KEY status (status),
				KEY transaction_id (transaction_id),
				KEY event_id (event_id)
			) {$charset_collate};";

	dbDelta( $sql );
	
}

/**
 * CB Core Install Contests
 * 
 * Installs our contests table on the database.
 * 
 * @link https://developer.wordpress.org/reference/functions/dbdelta/ Uses dbDelta()
 * @link https://developer.wordpress.org/reference/classes/wpdb/ Also uses $wpdb
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_core_install_contests() {

	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$prefix = $wpdb->prefix;
	$sql = array();


	$sql[] = "CREATE TABLE {$prefix}confetti_bits_contests (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				amount int(20) NOT NULL,
				placement int(20) NOT NULL,
				recipient_id int(20) NULL,
				event_id bigint(20) NOT NULL,
				KEY amount (amount),
				KEY placement (placement),
				KEY recipient_id (recipient_id),
				KEY event_id (event_id)
			) {$charset_collate};";

	dbDelta( $sql );

}

/**
 * CB Core Install Request Items
 * 
 * Installs our request items table on the database.
 * 
 * @link https://developer.wordpress.org/reference/functions/dbdelta/ Uses dbDelta()
 * @link https://developer.wordpress.org/reference/classes/wpdb/ Also uses $wpdb
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_core_install_request_items() {

	global $wpdb;
	$sql = array();
	$prefix = $wpdb->prefix;
	$charset_collate = $wpdb->get_charset_collate();

	$sql[] = "CREATE TABLE {$prefix}confetti_bits_request_items (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				item_name varchar(75) NOT NULL,
				item_desc varchar(500) NULL,
				date_created datetime NOT NULL,
				date_modified datetime NOT NULL,
				amount int(20) NOT NULL,
				KEY item_name (item_name),
				KEY item_desc (item_desc),
				KEY date_created (date_created),
				KEY date_modified (date_modified),
				KEY amount (amount)
			) {$charset_collate};";

	dbDelta( $sql );

}

/**
 * CB Core Install Requests
 * 
 * Installs our requests table on the database.
 * 
 * @link https://developer.wordpress.org/reference/functions/dbdelta/ Uses dbDelta()
 * @link https://developer.wordpress.org/reference/classes/wpdb/ Also uses $wpdb
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_core_install_requests() {

	global $wpdb;
	$sql = array();
	$prefix = $wpdb->prefix;
	$charset_collate = $wpdb->get_charset_collate();

	$sql[] = "CREATE TABLE {$prefix}confetti_bits_requests (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				item_id bigint(20) NOT NULL,
				secondary_item_id bigint(20) NOT NULL,
				applicant_id bigint(20) NOT NULL,
				admin_id bigint(20) NOT NULL,
				date_created datetime NOT NULL,
				date_modified datetime NOT NULL,
				component_name varchar(75) NOT NULL,
				component_action varchar(75) NOT NULL,
				status varchar(75) NOT NULL,
				request_item_id bigint(20) NOT NULL,
				KEY item_id (item_id),
				KEY secondary_item_id (secondary_item_id),
				KEY applicant_id (applicant_id),
				KEY admin_id (admin_id),
				KEY date_created (date_created),
				KEY date_modified (date_modified),
				KEY component_name (component_name),
				KEY component_action (component_action),
				KEY status (status),
				KEY request_item_id (request_item_id)
			) {$charset_collate};";

	dbDelta( $sql );

}

/**
 * Put stuff here that you want to run before installation.
 * 
 * @package ConfettiBits\Core
 * @since 1.0.0
 */
function cb_core_prepare_install() {}

/**
 * Installs all our tables on the database.
 * 
 * Also flushes the WordPress cache and rewrite rules
 * so that our pages still show up after plugins get updated.
 * 
 * @see cb_core_install_transactions()
 * @see cb_core_install_participation()
 * @see cb_core_install_events()
 * @see cb_core_install_contests()
 * 
 * @package ConfettiBits\Core
 * @since 1.0.0
 */
function cb_core_install( $active_components = array() ) {

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	cb_core_prepare_install();
	cb_core_install_spot_bonuses();
	cb_core_install_events();
	cb_core_install_contests();
	cb_core_install_transactions();
	cb_core_install_participation();
	cb_core_install_request_items();
	cb_core_install_requests();
	do_action('cb_core_install');
	wp_cache_flush();
	flush_rewrite_rules();

}