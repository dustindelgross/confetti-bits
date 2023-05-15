<?php 
defined('ABSPATH') || exit;
function cb_log() {

	if ( !cb_is_confetti_bits_component() ) {
		return false;
	}

	$cb_log_url			= trailingslashit(bp_loggedin_user_domain() . cb_get_transactions_slug());
	$current_log_page	= ( !empty($_GET['cb_log_page'] ) ? $_GET['cb_log_page'] : 1 );
	$transactions	 	= new CB_Transactions_Transaction();
	$paged_transactions = $transactions->get_paged_transactions_for_user(
		get_current_user_id(),
		array(
			'page'		=> $current_log_page,
			'per_page'	=> 5,
		)
	);
	$page_total_cap 	= $transactions->total_pages;

	cb_log_pagination( $current_log_page, $page_total_cap, $cb_log_url );

	cb_log_header();
	cb_log_entries( $paged_transactions );

}

function cb_log_pagination( $current_log_page, $page_total_cap, $cb_log_url ) {

	$pagination_links = cb_log_get_page_urls( $current_log_page, $page_total_cap, $cb_log_url, $page_range = 5 );
	$pagination_list_items = array();

	foreach ( $pagination_links as $pagination_link ) {

		if ( $pagination_link['enabled'] ) {
			$pagination_list_items[] = '<li><a href="' .
				$pagination_link['url'] . '">' .
				$pagination_link['text'] .
				'</a></li>';
		} else {
			$pagination_list_items[] = '<li class="cb-log-link-disabled">' .
				$pagination_link['text'] .
				'</li>';
		}
	}

	$args = $pagination_list_items;
	$string_tags_repeater = trim( str_repeat( "%s ", count( $args ) ) );
	echo '<ul class="cb-log-pagination">' . vsprintf( $string_tags_repeater, $args ) . '</ul>';

}

function cb_log_get_page_urls( $current_log_page, $page_total_cap, $cb_log_url, $page_range = 5 ) {

	$pagination_links		= array();
	$link_text 				= array();
	$previous_page_number	= $current_log_page - 1;
	$next_page_number 		= $current_log_page + 1;
	$page_start 			= $current_log_page;



	if ( $page_total_cap == 0 ) {

		$page_range = 5;
		$page_range_cap = $page_start + $page_range - 1;

		$pagination_links[]	= array(
			'url'		=> '',
			'text'		=> '«',
			'enabled'	=> false,
		);

		$pagination_links[]	= array(
			'url'		=> '',
			'text'		=> '‹',
			'enabled'	=> false,
		);

		for ( $i = $page_start; $i <= $page_range_cap; $i++ ) {

			$pagination_links[] = array(
				'url'		=> '',
				'text'		=> $i,
				'enabled'	=> false,
			);
		}

		$pagination_links[] = array(
			'url'		=> '',
			'text'		=> '›',
			'enabled'	=> false,
		);

		$pagination_links[] = array(
			'url'		=> '',
			'text'		=> '»',
			'enabled'	=> false,
		);

	} else {

		$page_range 			= ( $page_range > $page_total_cap ? $page_total_cap : $page_range );
		$page_range_cap 		= $page_start + $page_range - 1;

		if ( $current_log_page >= ( $page_total_cap - $page_range + 1 ) ) {

			$page_start = $page_total_cap - $page_range + 1;
			$page_range_cap = $page_total_cap;

		}

		if ( $current_log_page <= 1 ) {

			$pagination_links[]	= array(
				'url'		=> '',
				'text'		=> '«',
				'enabled'	=> false,
			);

		} else {

			$pagination_links[]	= array(
				'url'		=> esc_url( add_query_arg( array( 'cb_log_page' => 1, ), $cb_log_url ) ),
				'text'		=>  '«',
				'enabled'	=> true,
			);
		}

		if ( $previous_page_number < 1 ) {

			$pagination_links[]	= array(
				'url'		=> '',
				'text'		=> '‹',
				'enabled'	=> false,
			);

		} else {

			$pagination_links[]		= array(
				'url'		=> esc_url(add_query_arg(array('cb_log_page' => $previous_page_number,), $cb_log_url)),
				'text'		=> '‹',
				'enabled'	=> true,
			);
		}

		for ( $i = $page_start; $i <= $page_range_cap; $i++ ) {

			$pagination_links[] = array(
				'url'		=> esc_url( add_query_arg( array( 'cb_log_page' => $i, ), $cb_log_url ) ),
				'text'		=> $i,
				'enabled'	=> true,
			);
		}

		if ( $next_page_number >= $page_total_cap ) {

			$pagination_links[] = array(
				'url'		=> '',
				'text'		=> '›',
				'enabled'	=> false,
			);
		} else {

			$pagination_links[] = array(
				'url'		=> add_query_arg(array('cb_log_page' => $next_page_number,), $cb_log_url),
				'text'		=> '›',
				'enabled'	=> true,
			);
		}

		if ( $current_log_page >= $page_total_cap ) {

			$pagination_links[] = array(
				'url'		=> '',
				'text'		=> '»',
				'enabled'	=> false,
			);
		} else {

			$pagination_links[] = array(
				'url'		=> add_query_arg(array('cb_log_page' => $page_total_cap,), $cb_log_url),
				'text'		=> '»',
				'enabled'	=> true,
			);
		}
	}

	return $pagination_links;
}

function cb_log_header() {

	echo '<div class="cb-log-header">
	<span class="cb-log-header-item">Transaction Date</span>
	<span class="cb-log-header-item">Amount Exchanged</span>
	<span class="cb-log-header-item">Log Entry</span>
	</div>';
}

function cb_log_entries( $paged_transactions ) {

	foreach ( $paged_transactions as $paged_transaction ) {

		$transaction_date = date("M d, Y | g:iA", strtotime( $paged_transaction['date_sent'] ) );
		$amount_entry = '';

		switch ( true ) {

			case ( intval( $paged_transaction['amount'] ) == -1):
				$amount_entry = 'spent ' . str_replace('-', '', $paged_transaction['amount']) . ' Confetti Bit';
				break;
			case ( intval( $paged_transaction['amount'] ) < -1):
				$amount_entry = 'spent ' . str_replace('-', '', $paged_transaction['amount']) . ' Confetti Bits';
				break;
			case ( intval( $paged_transaction['amount'] ) > 1 ):
				$amount_entry = 'received ' . $paged_transaction['amount'] . ' Confetti Bits';
				break;
			case ( intval( $paged_transaction['amount'] ) == 1 ):
				$amount_entry = 'received ' . $paged_transaction['amount'] . ' Confetti Bit';
				break;
		}

		echo sprintf(
			'<div class="cb-log-row">
	<span class="cb-log-row-item cb-log-date">%s</span>
	<span class="cb-log-row-item cb-log-bits-sent">%s</span>
	<span class="cb-log-row-item cb-log-entry">%s</span>
	</div>',
			$transaction_date,
			$amount_entry,
			$paged_transaction['log_entry'],
		);
	}
}

function cb_ajax_get_transactions_by_id() {
	if ( !isset( $_GET['user_id'] ) ) {
		http_response_code(400);
		die();
	}
	$page = 1;
	$per_page = 15;

	if ( isset( $_GET['page'] ) ) {
		$page = intval( $_GET['page'] );
	}

	if ( isset( $_GET['per_page'] ) ) {
		$per_page = intval( $_GET['per_page'] );
	}

	$recipient_id = intval( $_GET['user_id'] );

	$args = array(
		'where' => array(
			'recipient_id'	=> $recipient_id,
			'sender_id'		=> $recipient_id,
			'or'			=> true
		),
		'order' => array( 'id', 'DESC' ),
		'pagination' => array( ($page) * $per_page, $per_page )
	);

	$transaction = new Confetti_Bits_Transactions_Transaction();
	$transactions = $transaction->get_transactions($args);

	echo json_encode($transactions);
	die();

}
add_action('wp_ajax_cb_participation_get_transactions', 'cb_ajax_get_transactions_by_id');

function cb_ajax_get_total_transactions() {
	if ( !isset( $_GET['user_id'] ) ) {
		http_response_code(400);
		die();
	}

	$recipient_id = intval( $_GET['user_id'] );
	$args = array(
		'select'	=> 'COUNT(id) as total_count',
		'where' => array(
			'recipient_id'	=> $recipient_id,
			'sender_id'		=> $recipient_id,
			'or'			=> true
		),
	);
	$transaction = new Confetti_Bits_Transactions_Transaction();
	$count = $transaction->get_transactions($args);

	echo json_encode($count);
	die();

}
add_action( 'wp_ajax_cb_participation_get_total_transactions', 'cb_ajax_get_total_transactions' );