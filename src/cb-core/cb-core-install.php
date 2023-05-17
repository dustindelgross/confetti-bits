<?php 
function cb_core_prepare_install() {

}


function cb_core_install_transactions() {

	global $wpdb;
	$sql = array();
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql[] = "CREATE TABLE {$wpdb->prefix}confetti_bits_transactions (
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
				CONSTRAINT fk_event_id FOREIGN KEY (event_id) REFERENCES {$wpdb->prefix}confetti_bits_events(id)
			) {$charset_collate};";

	dbDelta( $sql );

}

function cb_core_install_participation() {

	global $wpdb;
	$sql = array();
	$charset_collate = $wpdb->get_charset_collate();

	$sql[] = "CREATE TABLE {$wpdb->prefix}confetti_bits_participation (
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
				media_filepath varchar(150) NULL,
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
				KEY media_filepath (media_filepath),
				KEY transaction_id (transaction_id),
				CONSTRAINT fk_event_id FOREIGN KEY (event_id) REFERENCES {$wpdb->prefix}confetti_bits_events(id)
			) {$charset_collate};";

	dbDelta( $sql );

}

function cb_core_install_events() {

	global $wpdb;
	$prefix = $wpdb->prefix;
	$charset_collate = $wpdb->get_charset_collate();
	$sql = array();

	$sql[] = "CREATE TABLE {$prefix}confetti_bits_events (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				event_title varchar(75) NOT NULL,
				event_desc varchar(500) NULL,
				date_created datetime NOT NULL,
				date_modified datetime NOT NULL,
				participation_amount int(8) DEFAULT 0,
				event_date_start datetime NOT NULL,
				event_date_end datetime NOT NULL,
				user_id int(20) NOT NULL,
				KEY event_title (event_title),
				KEY event_desc (event_desc),
				KEY date_created (date_created),
				KEY date_modified (date_modified),
				KEY participation_amount (participation_amount),
				KEY event_date_start (event_date_start),
				KEY event_date_end (event_date_end),
				KEY user_id (user_id)
			) {$charset_collate};";

	dbDelta( $sql );

}

function cb_core_install_contests() {

	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$sql = array();


	$sql[] = "CREATE TABLE {$wpdb->prefix}confetti_bits_contests (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				amount int(20) NOT NULL,
				placement tinyint(20) NOT NULL,
				recipient_id int(20) NOT NULL,
				date_created datetime NOT NULL,
				date_modified datetime NOT NULL,
				event_id bigint(20) NOT NULL,
				KEY amount (amount),
				KEY placement (placement),
				KEY recipient_id (recipient_id),
				KEY date_created (date_created),
				KEY date_modified (date_modified),
				CONSTRAINT fk_event_id FOREIGN KEY (event_id) REFERENCES {$wpdb->prefix}confetti_bits_events(id)
			) {$charset_collate};";

	dbDelta( $sql );

}

function cb_core_install_download_logs() {

	$sql = array();

	$bp_prefix      = bp_core_get_table_prefix();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();

	$sql[] = "CREATE TABLE {$bp_prefix}confetti_bits_downloads (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				item_id bigint(20) NOT NULL,
				secondary_item_id bigint(20) NOT NULL,
				user_id bigint(20) NOT NULL,
				user_name varchar(75) NOT NULL,
				download_date datetime NOT NULL,
				component_name varchar(75) NOT NULL,
				component_action varchar(75) NOT NULL,
				KEY item_id (item_id),
				KEY secondary_item_id (secondary_item_id),
				KEY user_id (user_id),
				KEY user_name (user_name),
				KEY download_date (download_date),
				KEY component_name (component_name),
				KEY component_action (component_action),
			) {$charset_collate};";

	dbDelta( $sql );

}

function cb_core_install( $active_components = array() ) {

	cb_core_prepare_install();

	if ( empty( $active_components ) ) {
		$active_components = bp_get_option( 'cb_active_components' );	
	}

	cb_core_install_events();

	if ( ! empty ( $active_components['transactions'] ) ) {
		cb_core_install_transactions();		
	}

	if ( ! empty ( $active_components['downloads'] ) ) {
		cb_core_install_download_logs();
	}



	cb_core_install_participation();

	do_action('cb_core_install');

	// Needs to flush all cache when component activate/deactivate.
	wp_cache_flush();

	// Reset the permalink to fix the 404 on some pages.
	flush_rewrite_rules();

}