<?php
/**
 * Confetti Bits Transaction Loader.
 * A component that allows leaders to send bits to users and for users to send bits to each other.
 * @package Confetti_Bits 
 * @since Confetti Bits 2.0.0  */

defined( 'ABSPATH' ) || exit;

class Confetti_Bits_Transactions_Transaction {

	public static $last_inserted_id; public $id; public $item_id;

	public $secondary_item_id;

	public $user_id;

	public $sender_id;

	public $sender_name;

	public $recipient_id;

	public $recipients;

	public $recipient_name;

	public $identifier;

	public $date_sent;

	public $log_entry;

	public $component_name;

	public $component_action;

	public $amount;

	public $total_count;

	public $total_pages;

	public $error;

	public $error_type = 'bool';

	public static $columns = array(
		'id',
		'item_id',
		'secondary_item_id',
		'user_id',
		'sender_id',
		'sender_name',
		'recipient_id',
		'recipient_name',
		'identifier',
		'date_sent',
		'log_entry',
		'component_name',
		'component_action',
		'amount',
	);

	public function __construct( $id = 0 ) {

		$this->errors = new WP_Error();

		if ( ! empty ( $id ) ) {

			$this->id = (int) $id;

			$this->populate( $id );

		}

		$this->current_date = current_time( 'Y-m-d H:i:s', false );

		$this->current_cycle_end = bp_get_option( 'cb_reset_date' );

		$this->current_cycle_start = date( 
			'Y-m-d H:i:s', 
			strtotime( bp_get_option( 'cb_reset_date' ) . ' - 1 year' )
		);

		$this->previous_cycle_end = date( 
			'Y-m-d H:i:s', 
			strtotime( bp_get_option( 'cb_reset_date' ) . ' - 1 year' )
		);

		$this->previous_cycle_start = date( 
			'Y-m-d H:i:s', 
			strtotime( bp_get_option( 'cb_reset_date' ) . ' - 2 years' )
		);

		$this->current_spending_cycle_start = date( 
			'Y-m-d H:i:s', 
			strtotime( bp_get_option( 'cb_reset_date' ) . ' - 1 year + 1 month' ) 
		);

		$this->current_spending_cycle_end = date( 
			'Y-m-d H:i:s', 
			strtotime( bp_get_option( 'cb_reset_date' ) . ' + 1 month' )
		);

		$this->previous_spending_cycle_start = date( 
			'Y-m-d H:i:s', 
			strtotime( bp_get_option( 'cb_reset_date' ) . ' - 2 years + 1 month' ) 
		);

		$this->previous_spending_cycle_end = date( 
			'Y-m-d H:i:s', 
			strtotime( bp_get_option( 'cb_reset_date' ) . ' - 1 year + 1 month' )
		);

	}
	public function send_bits() {

		$retval = false;
		do_action_ref_array( 'cb_transactions_before_send', array( &$this ) );
		$data = array (
			'item_id' => $this->item_id,
			'secondary_item_id' => $this->secondary_item_id,
			'user_id' => $this->user_id,
			'sender_id' => $this->sender_id,
			'sender_name' => $this->sender_name,
			'recipient_id' => $this->recipient_id,
			'recipient_name' => $this->recipient_name,
			'identifier' => $this->identifier,
			'date_sent' => $this->date_sent,
			'log_entry' => $this->log_entry,
			'component_name' => $this->component_name,
			'component_action' => $this->component_action,
			'amount' => $this->amount,
		);

		$data_format = array( '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%d', );
		
		$result = self::_insert( $data, $data_format );

		if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
			global $wpdb;

			if ( empty( $this->id ) ) {
				$this->id = $wpdb->insert_id;
			}

			do_action( 'cb_transactions_after_send', $data );

			$retval = $this->id;
		}
		
		return $retval;
	}

	public function populate( $id ) {
		$transaction = $this->get_transactions(
			array(
				'where' 	=> array( 'id' => $id ),
			)
		);

		global $wpdb;
<<<<<<< HEAD
		$bp = buddypress();
		$cb = Confetti_Bits();
=======

>>>>>>> 4bd4bbb (The Big Commit of April 2023)
		$fetched_transaction = ( ! empty( $transaction[0] ) ? current( $transaction ) : array() );
		if ( ! empty( $fetched_transaction ) && ! is_wp_error( $fetched_transaction ) ) {
			$this->item_id           = (int) $fetched_transaction['item_id'];
			$this->secondary_item_id = (int) $fetched_transaction['secondary_item_id'];
			$this->user_id           = (int) $fetched_transaction['user_id'];
			$this->sender_id		 = (int) $fetched_transaction['sender_id'];
			$this->sender_name		 = $fetched_transaction['sender_name'];
			$this->recipient_id		 = (int) $fetched_transaction['recipient_id'];
			$this->recipient_name	 = $fetched_transaction['recipient_name'];
			$this->identifier		 = (int) $fetched_transaction['identifier'];
			$this->date_sent		 = $fetched_transaction['date_sent'];
			$this->log_entry		 = $fetched_transaction['log_entry'];
			$this->component_name    = $fetched_transaction['component_name'];
			$this->component_action  = $fetched_transaction['component_action'];
			$this->amount			 = (int) $fetched_transaction['amount'];
		}
	}

	protected static function _insert( $data = array(), $data_format = array() ) {
		global $wpdb;
		return $wpdb->insert( Confetti_Bits()->transactions->table_name, $data, $data_format );
	}

	public function get_users_balance( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		$total_standard	= $this->get_users_earning_cycle( $user_id );
		$total_requests	= $this->get_users_request_cycle( $user_id );
		$earned		= ( ! empty ( $total_standard ) ) ? $total_standard[0]['amount'] : 0;
		$requests	= ( ! empty ( $total_requests ) ) ? $total_requests[0]['amount'] : 0;

		return $earned + $requests;

	}

	/*/
	 * The goal here is to dynamically populate whichever balance is necessary for the
	 * module being used. So for the requests module, we need to check today's date against
	 * the cycle reset dates.
	 * 
	 * If the new cycle started but our spending cycle hasn't reset yet, we'll need to 
	 * use our previous cycle's balance. If we're clear of the spending cycle start date,
	 * we'll use our current cycle's balance.
	/*/
	public function get_users_request_balance( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		if ( $this->current_date < $this->current_spending_cycle_start ) {
			$total_standard	= $this->get_users_earnings_from_previous_cycle( $user_id );
			$total_requests	= $this->get_users_requests_from_previous_cycle( $user_id );
		} else if ( $this->current_date > $this->current_spending_cycle_start ) {
			$total_standard	= $this->get_users_earnings_from_current_cycle( $user_id );
			$total_requests	= $this->get_users_requests_from_current_cycle( $user_id );
		}

		$earned		= ( ! empty ( $total_standard ) ) ? $total_standard[0]['amount'] : 0;
		$requests	= ( ! empty ( $total_requests ) ) ? $total_requests[0]['amount'] : 0;


		return $earned + $requests;

	}

	public function get_users_transfer_balance( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		$earned_transactions	= $this->get_users_earnings_from_current_cycle( $user_id );
		$request_transactions	= $this->get_users_requests_from_current_cycle( $user_id );

		$earned		= ( ! empty( $earned_transactions ) ) ? $earned_transactions[0]['amount'] : 0;
		$requests	= ( ! empty( $request_transactions ) ) ? $request_transactions[0]['amount'] : 0;
		return $earned + $requests;

	}

	/** 
	 * Get all transactions from whichever cycle is currently in place, 
	 * except for transfers/requests. If the date is earlier 
	 * than the spending cycle reset, we're going to use all 
	 * the transactions from the previous cycle as our earnings. 
	 * If we're after the spending reset, we'll use 
	 * all the transactions from the current cycle as our earnings.
	 */
	public function get_users_earning_cycle( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		if ( $this->current_date < $this->current_spending_cycle_start ) {
			$before = $this->previous_cycle_end;
			$after = $this->previous_cycle_start;
		} else {
			$before = $this->current_cycle_end;
			$after = $this->current_cycle_start;
		}

		global $wpdb;
		$cb = Confetti_Bits();

		$select_sql = "SELECT identifier, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'recipient_id'		=> $user_id,
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'compare'		=> 'BETWEEN',
				'relation'		=> 'AND',
				'before'		=> $before,
				'after'			=> $after,
				'inclusive'		=> true,
			),
			'component_name'	=> 'confetti_bits',
			'excluded_action'	=> array( 'cb_bits_request' ),
		), $select_sql, $from_sql );

		$group_sql = "GROUP BY identifier";

		$pagination_sql = "LIMIT 0, 1";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	/** 
	 * Get all cb_bits_request transactions from whichever cycle is currently in place.
	 * 
	 * If today's date is earlier than the spending cycle reset, 
	 * we're going to use all the cb_bits_request transactions
	 * from the previous cycle as the basis for our calculations.
	 * If we're after the spending cycle reset, we'll use 
	 * all the transactions from the current spending cycle as our request pool.
	 */
	public function get_users_request_cycle( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		if ( $this->current_date < $this->current_spending_cycle_start  ) {
			$before = $this->previous_spending_cycle_end;
			$after = $this->previous_spending_cycle_start;
		} else {
			$before = $this->current_spending_cycle_end;
			$after = $this->current_spending_cycle_start;
		}

		global $wpdb;
		$cb = Confetti_Bits();

		$select_sql = "SELECT id, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'recipient_id'		=> $user_id,
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'compare'		=> 'BETWEEN',
				'relation'		=> 'AND',
				'before'		=> $before,
				'after'			=> $after,
				'inclusive'		=> true,
			),
			'component_name'	=> 'confetti_bits',
			'component_action'	=> array( 'cb_bits_request' ),
		), $select_sql, $from_sql );

		$group_sql = "GROUP BY identifier";

		$pagination_sql = "LIMIT 0, 1";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	/** 
	 * Get all cb_transfer_bits transactions from whichever cycle is currently in place.
	 * 
	 * If today's date is earlier than the spending cycle reset, 
	 * we're going to use all the cb_transfer_bits transactions
	 * from the previous cycle as the basis for our calculations.
	 * If we're after the spending cycle reset, we'll use 
	 * all the transfers from the current spending cycle.
	 * 
	 * Both positive and negative amounts are all based on the recipient_id, 
	 * whereas all negative amounts are based on sender_id.
	 */

	public function get_users_transfer_cycle( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		if ( $this->current_date < $this->current_spending_cycle_start ) {
			$before = $this->previous_cycle_end;
			$after = $this->previous_cycle_start;
		} else {
			$before = $this->current_cycle_end;
			$after = $this->current_cycle_start;
		}

		global $wpdb;
		$cb = Confetti_Bits();

		$select_sql = "SELECT id, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'recipient_id'		=> $user_id,
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'compare'		=> 'BETWEEN',
				'relation'		=> 'AND',
				'before'		=> $before,
				'after'			=> $after,
				'inclusive'		=> true,
			),
			'component_name'	=> 'confetti_bits',
			'component_action'	=> array( 'cb_transfer_bits' ),
		), $select_sql, $from_sql );

		$group_sql = "GROUP BY identifier";

		$pagination_sql = "LIMIT 0, 1";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_users_earnings_from_previous_cycle( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		global $wpdb;
		$cb = Confetti_Bits();

		$select_sql = "SELECT identifier, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'recipient_id'		=> $user_id,
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'compare'		=> 'BETWEEN',
				'relation'		=> 'AND',
				'before'		=> $this->previous_cycle_end,
				'after'			=> $this->previous_cycle_start,
				'inclusive'		=> true,
			),
			'component_name'	=> 'confetti_bits',
			'excluded_action'	=> array( 'cb_bits_request' ),
		), $select_sql, $from_sql );

		$group_sql = "GROUP BY identifier";

		$pagination_sql = "LIMIT 0, 1";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_transactions( $args = array() ) {

		global $wpdb;
		$cb = Confetti_Bits();

		$r = wp_parse_args( 
			$args, 
			array(
				'select'		=> '*',
				'where'			=> array(
					'date_query'		=> array(
						'column'		=> 'date_sent',
						'compare'		=> 'BETWEEN',
						'relation'		=> 'AND',
						'before'		=> $this->current_cycle_end,
						'after'			=> $this->current_cycle_start,
						'inclusive'		=> true,
					),
					'component_name'	=> 'confetti_bits',
					'component_action'	=> '',
<<<<<<< HEAD
				),
				'pagination'	=> array(),
				'group'			=> '',
=======
					'or'				=> false
				),
				'pagination'	=> array(),
				'group'			=> '',
				'order'		=> array()
>>>>>>> 4bd4bbb (The Big Commit of April 2023)
			)
		);

		$select = ( is_array( $r['select'] ) ) ? implode( ', ', $r['select'] ) : $r['select'];
		$select_sql = "SELECT {$select}";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( $r['where'], $select_sql, $from_sql );
		$group_sql = ( ! empty( $r['group'] ) ) ? "GROUP BY {$r['group']}" : '';
		$pagination = ( ! empty( $r['pagination'] ) ) ? implode( ',', wp_parse_id_list( $r['pagination'] ) ) : '';
<<<<<<< HEAD
		$orderby = ( !empty( $r['order'] ) && is_array($r['order'] ) ) ?? "ORDER BY {$r['order'][0]} {$r['order'][1]}";
		$pagination_sql = ( ! empty( $r['pagination'] ) ) ? "LIMIT {$pagination}" : '';

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$pagination_sql}";
=======
		$orderby = ( !empty( $r['order'] ) && is_array($r['order'] ) ) ? "ORDER BY {$r['order'][0]} {$r['order'][1]}" : "";
		$pagination_sql = ( ! empty( $r['pagination'] ) ) ? "LIMIT {$pagination}" : '';

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$orderby} {$pagination_sql}";
>>>>>>> 4bd4bbb (The Big Commit of April 2023)

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_users_requests_from_previous_cycle( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		global $wpdb;
		$cb = Confetti_Bits();

		$select_sql = "SELECT id, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'recipient_id'		=> $user_id,
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'compare'		=> 'BETWEEN',
				'relation'		=> 'AND',
				'before'		=> $this->previous_spending_cycle_end,
				'after'			=> $this->previous_spending_cycle_start,
				'inclusive'		=> true,
			),
			'component_name'	=> 'confetti_bits',
			'component_action'	=> array( 'cb_bits_request' ),
		), $select_sql, $from_sql );

		$group_sql = "GROUP BY identifier";

		$pagination_sql = "LIMIT 0, 1";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_users_transfers_from_previous_cycle( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		global $wpdb;
		$cb = Confetti_Bits();

		$select_sql = "SELECT id, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'recipient_id'		=> $user_id,
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'compare'		=> 'BETWEEN',
				'relation'		=> 'AND',
				'before'		=> $this->previous_cycle_end,
				'after'			=> $this->previous_cycle_start,
				'inclusive'		=> true,
			),
			'component_name'	=> 'confetti_bits',
			'component_action'	=> array( 'cb_transfer_bits' ),
		), $select_sql, $from_sql );

		$group_sql = "GROUP BY identifier";

		$pagination_sql = "LIMIT 0, 1";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_users_earnings_from_current_cycle( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		global $wpdb;
		$cb = Confetti_Bits();

		$select_sql = "SELECT id, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'recipient_id'		=> $user_id,
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'compare'		=> 'BETWEEN',
				'relation'		=> 'AND',
				'before'		=> $this->current_cycle_end,
				'after'			=> $this->current_cycle_start,
				'inclusive'		=> true,
			),
			'component_name'	=> 'confetti_bits',
			'excluded_action'	=> array( 'cb_bits_request' ),
		), $select_sql, $from_sql );

		$group_sql = "GROUP BY identifier";

		$pagination_sql = "LIMIT 0, 1";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_users_requests_from_current_cycle( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		global $wpdb;
		$cb = Confetti_Bits();

		$select_sql = "SELECT id, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'recipient_id'		=> $user_id,
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'compare'		=> 'BETWEEN',
				'relation'		=> 'AND',
				'before'		=> $this->current_spending_cycle_end,
				'after'			=> $this->current_spending_cycle_start,
				'inclusive'		=> true,
			),
			'component_name'	=> 'confetti_bits',
			'component_action'	=> array( 'cb_bits_request' ),
		), $select_sql, $from_sql );

		$group_sql = "GROUP BY identifier";

		$pagination_sql = "LIMIT 0, 1";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_users_transfers_from_current_cycle( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		global $wpdb;
		$cb = Confetti_Bits();

		$select_sql = "SELECT id, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'recipient_id'		=> $user_id,
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'compare'		=> 'BETWEEN',
				'relation'		=> 'AND',
				'before'		=> $this->current_cycle_end,
				'after'			=> $this->current_cycle_start,
				'inclusive'		=> true,
			),
			'component_name'	=> 'confetti_bits',
			'component_action'	=> array( 'cb_transfer_bits' ),
		), $select_sql, $from_sql );

		$group_sql = "GROUP BY identifier";

		$pagination_sql = "LIMIT 0, 1";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_users_total_from_current_cycle( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		$earnings	= $this->get_users_earnings_from_current_cycle( $user_id );
		$requests	= $this->get_users_requests_from_current_cycle( $user_id );

		$total = $earnings[0]['amount'] + $requests[0]['amount'];

		return $total;

	}

	public function get_leaderboard_totals_groupedby_identifier_from_current_cycle() {

		global $wpdb;

		$cb = Confetti_Bits();

		$select_sql = "SELECT identifier, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'component_name'	=> 'confetti_bits',
			'excluded_action'	=> 'cb_bits_request',
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'before'		=> $this->current_cycle_end,
				'after'			=> $this->current_cycle_start,
				'inclusive'		=> true,
			),
		), $select_sql, $from_sql );
		$group_sql = "GROUP BY identifier";
		$order_sql = "ORDER BY amount DESC";
		$pagination_sql = "LIMIT 0, 15";
		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$order_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );

	}

	public function get_leaderboard_requests_groupedby_identifier_from_current_cycle() {

		global $wpdb;

		$cb = Confetti_Bits();

		$select_sql = "SELECT identifier, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_bits_request',
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'before'		=> $this->current_spending_cycle_end,
				'after'			=> $this->current_spending_cycle_start,
				'inclusive'		=> true,
			),
		), $select_sql, $from_sql );
		$group_sql = "GROUP BY identifier";
		$order_sql = "ORDER BY amount DESC";
		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$order_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );

	}

	public function get_leaderboard_requests_groupedby_identifier_from_previous_cycle() {

		global $wpdb;

		$cb = Confetti_Bits();

		$select_sql = "SELECT identifier, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_bits_request',
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'before'		=> $this->previous_spending_cycle_end,
				'after'			=> $this->previous_spending_cycle_start,
				'inclusive'		=> true,
			),
		), $select_sql, $from_sql );
		$group_sql = "GROUP BY identifier";
		$order_sql = "ORDER BY amount DESC";
		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$order_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );

	}

	public function get_leaderboard_totals_groupedby_identifier_from_previous_cycle() {

		global $wpdb;

		$cb = Confetti_Bits();
		$select_sql = "SELECT identifier, recipient_name, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'component_name'	=> 'confetti_bits',
			'excluded_action'	=> 'cb_bits_request',
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'compare'		=> 'BETWEEN',
				'relation'		=> 'AND',
				'before'		=> date( 
					'Y-m-d H:i:s', 
					strtotime( bp_get_option('cb_reset_date') . ' - 1 year' ) ),
				'after'			=> date( 
					'Y-m-d H:i:s', 
					strtotime( bp_get_option( 'cb_reset_date' ) . ' - 2 years' ) 
				),
				'inclusive'		=> true,
			),
		), $select_sql, $from_sql );
		$group_sql = "GROUP BY identifier";
		$order_sql = "ORDER BY amount DESC";
		$pagination_sql = "LIMIT 0, 15";
		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$order_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );

	}

	public function get_totals_groupedby_identifier_from_current_cycle() {

		global $wpdb;

		$cb = Confetti_Bits();

		$select_sql = "SELECT identifier, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'component_name'	=> 'confetti_bits',
			'excluded_action'	=> 'cb_bits_request',
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'before'		=> $this->current_cycle_end,
				'after'			=> $this->current_cycle_start,
				'inclusive'		=> true,
			),
		), $select_sql, $from_sql );
		$group_sql = "GROUP BY identifier";
		$order_sql = "ORDER BY amount DESC";
		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$order_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );

	}

	public function get_requests_groupedby_identifier_from_current_cycle() {

		global $wpdb;

		$cb = Confetti_Bits();

		$select_sql = "SELECT identifier, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_bits_request',
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'before'		=> $this->current_spending_cycle_end,
				'after'			=> $this->current_spending_cycle_start,
				'inclusive'		=> true,
			),
		), $select_sql, $from_sql );
		$group_sql = "GROUP BY identifier";
		$order_sql = "ORDER BY amount DESC";
		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$order_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );

	}



	public function get_totals_groupedby_identifier_from_previous_cycle() {

		global $wpdb;

		$cb = Confetti_Bits();
		$select_sql = "SELECT identifier, recipient_name, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'component_name'	=> 'confetti_bits',
			'excluded_action'	=> 'cb_bits_request',
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'compare'		=> 'BETWEEN',
				'relation'		=> 'AND',
				'before'		=> $this->previous_cycle_end,
				'after'			=> $this->previous_cycle_start,
				'inclusive'		=> true,
			),
		), $select_sql, $from_sql );
		$group_sql = "GROUP BY identifier";
		$order_sql = "ORDER BY amount DESC";
		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$order_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );

	}

	public function get_requests_groupedby_identifier_from_previous_cycle() {

		global $wpdb;

		$cb = Confetti_Bits();

		$select_sql = "SELECT identifier, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_bits_request',
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'before'		=> $this->previous_spending_cycle_end,
				'after'			=> $this->previous_spending_cycle_start,
				'inclusive'		=> true,
			),
		), $select_sql, $from_sql );
		$group_sql = "GROUP BY identifier";
		$order_sql = "ORDER BY amount DESC";
		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$order_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );

	}

	public function get_activity_bits_transactions_for_today( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		global $wpdb;

		$bp = buddypress();
		$cb = Confetti_Bits();

		$select_sql = "SELECT user_id, date_sent, component_name, component_action, COUNT(user_id) as total_count";

		$from_sql = "FROM {$cb->transactions->table_name} n ";

		$where_sql = self::get_where_sql( array(
			'user_id'			=> $user_id,
			'date_query'		=> array (
				'column'		=> 'date_sent',
				'compare'		=> 'IN',
				'relation'		=> 'AND',
				'day'			=> bp_core_current_time(false, 'd'),
				'month'			=> bp_core_current_time(false, 'm'),
				'year'			=> bp_core_current_time(false, 'Y'),
			),
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_activity_bits',
		), $select_sql, $from_sql );

		$order_sql = "ORDER BY date_sent desc";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$order_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_activity_bits_transactions_from_current_cycle( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		global $wpdb;

		$bp = buddypress();
		$cb = Confetti_Bits();

		$select_sql = "SELECT user_id, date_sent, component_name, component_action";

		$from_sql = "FROM {$cb->transactions->table_name} n ";

		$where_sql = self::get_where_sql( array(
			'user_id'			=> $user_id,
			'date_query'		=> array (
				'column'		=> 'date_sent',
				'compare'		=> 'IN',
				'relation'		=> 'AND',
				'before'		=> $this->current_cycle_end,
				'after'			=> $this->current_cycle_start,
				'inclusive'		=> true,
			),
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_activity_bits',
		), $select_sql, $from_sql );

		$order_sql = "ORDER BY date_sent desc";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$order_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_activity_posts_for_user( $user_id = 0 ) {

		if ( $user_id === 0 ) {
			return;
		}

		global $wpdb;

		$bp = buddypress();
		$cb = Confetti_Bits();

		$select_sql = "SELECT user_id, date_recorded, component, type";

		$from_sql = "FROM {$bp->activity->table_name} n ";

		$where_sql = self::get_where_sql( array(
			'user_id'			=> $user_id,
			'date_query'		=> array(
				'column'		=> 'date_recorded',
				'compare'		=> 'IN',
				'relation'		=> 'AND',
				'before'		=> $this->current_cycle_end,
				'after'			=> $this->current_cycle_start,
				'inclusive'		=> true,
			),
			'component'			=> 'activity',
			'type'				=> 'activity_update',
			'item_id'			=> 0,
			'secondary_item_id'	=> 0,
		), $select_sql, $from_sql );

		$order_sql = "ORDER BY date_recorded DESC";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$order_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_send_bits_transactions_for_today( $user_id ) {

		global $wpdb;

		$bp = buddypress();
		$cb = Confetti_Bits();

		$select_sql = "SELECT id, SUM(amount) as amount";

		$from_sql = "FROM {$cb->transactions->table_name} n ";

		$where_sql = self::get_where_sql( array(
			'sender_id'			=> $user_id,
			'date_query'		=> array (
				'column'		=> 'date_sent',
				'compare'		=> 'IN',
				'relation'		=> 'AND',
				'day'			=> bp_core_current_time(false, 'd'),
				'month'			=> bp_core_current_time(false, 'm'),
				'year'			=> bp_core_current_time(false, 'Y'),
			),
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_send_bits',
		), $select_sql, $from_sql );

		$pagination_sql = "LIMIT 0, 1";

		$order_sql = "ORDER BY date_sent desc";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$order_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_transfer_bits_transactions_for_today( $user_id ) {

		global $wpdb;

		$bp = buddypress();
		$cb = Confetti_Bits();

		$select_sql = "SELECT id, SUM(amount) as amount";

		$from_sql = "FROM {$cb->transactions->table_name} n ";

		$where_sql = self::get_where_sql( array(
			'sender_id'			=> $user_id,
			'recipient_id'		=> $user_id,
			'date_query'		=> array (
				'column'		=> 'date_sent',
				'compare'		=> 'IN',
				'relation'		=> 'AND',
				'day'			=> bp_core_current_time(false, 'd'),
				'month'			=> bp_core_current_time(false, 'm'),
				'year'			=> bp_core_current_time(false, 'Y'),
			),
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_transfer_bits',
		), $select_sql, $from_sql );

		$pagination_sql = "LIMIT 0, 1";

		$order_sql = "ORDER BY date_sent desc";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$order_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_send_bits_transactions_for_recipient( $user_id ) {

		global $wpdb;

		$cb = Confetti_Bits();

		$select_sql = "SELECT id, item_id, secondary_item_id, user_id, sender_id, sender_name, recipient_id, recipient_name, identifier, date_sent, log_entry, component_name, component_action,  amount";

		$from_sql = "FROM {$cb->transactions->table_name} n ";

		$where_sql = self::get_where_sql( array(
			'recipient_id'		=> $user_id,
			'component_name'	=> 'confetti_bits',
			'component_action'	=> 'cb_send_bits',
		), $select_sql, $from_sql );

		$order_sql = "ORDER BY date_sent desc";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$order_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_users_total_earnings_from_previous_cycle( $user_id ) {

		if ( $user_id === 0 ) {
			return;
		}

		global $wpdb;
		$cb = Confetti_Bits();

		$select_sql = "SELECT id, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'recipient_id'		=> $user_id,
			'date_query'		=> array(
				'column'		=> 'date_sent',
				'compare'		=> 'BETWEEN',
				'relation'		=> 'AND',
				'before'		=> $this->current_cycle_end,
				'after'			=> $this->current_cycle_start,
				'inclusive'		=> true,
			),
			'amount_comparison'	=> '>',
			'amount'			=> 0,
			'component_name'	=> 'confetti_bits',
			'excluded_action'	=> array( 'cb_transfer_bits' ),
		), $select_sql, $from_sql );

		$group_sql = "GROUP BY identifier";

		$pagination_sql = "LIMIT 0, 1";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );

	}

	public function get_paged_transactions_for_user( $user_id, $args = array() ) {
		global $wpdb;
		$bp = buddypress();
		$cb = Confetti_Bits();
		$defaults = array (
			'page'		=> 1,
			'per_page'	=> 7,
		);
		$r = bp_parse_args( $args, $defaults, 'cb_transactions_get_transactions_for_user' );
		$select_sql = "SELECT id, date_sent, log_entry, amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'recipient_id'		=> $user_id,
			'component_name'	=> 'confetti_bits',
		), $select_sql, $from_sql );

		$prefetch_select_sql = "SELECT id, COUNT(id) AS total_rows";

		$prefetch_sql = "{$prefetch_select_sql} {$from_sql} {$where_sql}";

		$wpdb_prefetch_total = $wpdb->get_results( $prefetch_sql, 'ARRAY_A');

		$this->total_pages = ceil($wpdb_prefetch_total[0]['total_rows']/$r['per_page']);

		$page_val = ( $r['page'] - 1 ) * $r['per_page'];

		/* * * * * * * * * * * v v v page v v v , v v # of rows v v * * */
		$pagination_sql 	= "LIMIT {$page_val}, {$r['per_page']}";

		$order_sql = "ORDER BY date_sent DESC";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$order_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );

	}

	public function get_transactions_for_user( $args = array() ) {

		global $wpdb;
		$cb = Confetti_Bits();
		$defaults = array (
			'recipient_id'		=> get_current_user_id(),
			'component_name'	=> 'confetti_bits',
		);
		$r = bp_parse_args( $args, $defaults, 'cb_transactions_get_some_transactions_for_user' );
		$select_sql = "SELECT id, recipient_name, date_sent, log_entry, amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'recipient_id'		=> get_current_user_id(),
			'component_name'	=> 'confetti_bits',
		), $select_sql, $from_sql );

		$order_sql = "ORDER BY date_sent DESC";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$order_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );

	}

	public function get_leadership_transactions() {

		global $wpdb;
		$cb = Confetti_Bits();
		$select_sql = "SELECT id, component_action, sender_name, recipient_name, date_sent, log_entry, amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'component_name'	=> 'confetti_bits',
			'component_action'	=> array('cb_send_bits', 'cb_import_bits'),
			'search_terms'		=> 'from',
			'exclude_terms'		=> 'Kevin Doherty',
		), $select_sql, $from_sql );

		$order_sql = "ORDER BY date_sent DESC";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$order_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );

	}

	public static function get_date_query_sql( $date_query = array() ) {
		$sql = '';

		if ( ! empty( $date_query ) && is_array( $date_query ) ) {
			if ( ! empty( $date_query['column'] ) && 'date_recorded' === $date_query['column'] ) {
				$date_query = new BP_Date_Query( $date_query, 'date_recorded' );	
			} else {
				$date_query = new BP_Date_Query( $date_query, 'date_sent' );	
			}
			$sql        = preg_replace( '/^\sAND/', '', $date_query->get_sql() );
		}

		return $sql;
	}

	protected static function get_where_sql( $args = array(), $select_sql = '', $from_sql = '', $join_sql = '', $meta_query_sql = '' ) {
		global $wpdb;
		$where_conditions = array();
		$where            = '';

		if ( ! empty( $args['id'] ) ) {
			$id_in                  = implode( ',', wp_parse_id_list( $args['id'] ) );
			$where_conditions['id'] = "id IN ({$id_in})";
		}

		if ( ! empty( $args['user_id'] ) ) {
			$user_id_in                  = implode( ',', wp_parse_id_list( $args['user_id'] ) );
			$where_conditions['user_id'] = "user_id IN ({$user_id_in})";
		}

		if ( ! empty( $args['sender_id'] ) ) {
			$sender_id_in                  = implode( ',', wp_parse_id_list( $args['sender_id'] ) );
			$where_conditions['sender_id'] = "sender_id IN ({$sender_id_in})";
		}

		if ( ! empty( $args['item_id'] ) ) {
			$item_id_in                  = implode( ',', wp_parse_id_list( $args['item_id'] ) );
			$where_conditions['item_id'] = "item_id IN ({$item_id_in})";
		}

		if ( ! empty( $args['secondary_item_id'] ) ) {
			$secondary_item_id_in                  = implode( 
				',', 
				wp_parse_id_list( 
					$args['secondary_item_id'] 
				) 
			);
			$where_conditions['secondary_item_id'] = "secondary_item_id IN ({$secondary_item_id_in})";
		}

		if ( ! empty( $args['recipient_id'] ) ) {
			$recipient_id_in                  = implode( ',', wp_parse_id_list( $args['recipient_id'] ) );
			$where_conditions['recipient_id'] = "recipient_id IN ({$recipient_id_in})";
		}

		if ( ! empty( $args['identifier'] ) ) {
			$identifier_in                  = implode( ',', wp_parse_id_list( $args['identifier'] ) );
			$where_conditions['identifier'] = "identifier IN ({$identifier_in})";
		}

		if ( ! empty( $args['log_entry'] ) ) {
			$log_entries	= explode( ',', $args['log_entry'] );

			$log_entry_clean = array();
			foreach ( $log_entries as $log_entry ) {
				$log_entry_clean[] = $wpdb->prepare( '%s', $log_entry );
			}

			$log_entry_in = implode( ',', $log_entry_clean );

			$where_conditions['log_entry'] = "log_entry LIKE ({$log_entry_in})";
		}

		if ( ! empty( $args['component_name'] ) ) {
			if ( ! is_array( $args['component_name'] ) ) {
				$component_names = explode( ',', $args['component_name'] );
			} else {
				$component_names = $args['component_name'];
			}
			$cn_clean = array();
			foreach ( $component_names as $cn ) {
				$cn_clean[] = $wpdb->prepare( '%s', $cn );
			}
			$cn_in                              = implode( ',', $cn_clean );
			$where_conditions['component_name'] = "component_name IN ({$cn_in})";
		}

		if ( ! empty( $args['component'] ) ) {
			if ( ! is_array( $args['component'] ) ) {
				$components = explode( ',', $args['component'] );
			} else {
				$components = $args['component'];
			}
			$c_clean = array();
			foreach ( $components as $c ) {
				$c_clean[] = $wpdb->prepare( '%s', $c );
			}
			$c_in                              = implode( ',', $c_clean );
			$where_conditions['component'] = "component IN ({$c_in})";
		}

		if ( ! empty( $args['type'] ) ) {
			if ( ! is_array( $args['type'] ) ) {
				$types = explode( ',', $args['type'] );
			} else {
				$types = $args['type'];
			}
			$t_clean = array();
			foreach ( $types as $t ) {
				$t_clean[] = $wpdb->prepare( '%s', $t );
			}
			$t_in                              = implode( ',', $t_clean );
			$where_conditions['type'] = "type IN ({$t_in})";
		}

		if ( ! empty( $args['component_action'] ) ) {
			if ( 'leadership' === $args['component_action'] || 'all' === $args['component_action'] ) {

				$where_conditions['component_action'] = array(
					'cb_send_bits',
					'cb_import_bits',
					'cb_activity_bits',
					'cb_bits_request',
				);

			}

			if ( 'transfers' === $args['component_action'] ) {

				$where_conditions['component_action'] = array('cb_transfer_bits',);

			}

			if ( ! is_array( $args['component_action'] ) ) {
				$component_actions = explode( ',', $args['component_action'] );
			} else {
				$component_actions = $args['component_action'];
			}

			$ca_clean = array();
			foreach ( $component_actions as $ca ) {
				$ca_clean[] = $wpdb->prepare( '%s', $ca );
			}

			$ca_in                                = implode( ',', $ca_clean );
			$where_conditions['component_action'] = "component_action IN ({$ca_in})";
		}

		if ( ! empty( $args['excluded_action'] ) ) {
			if ( ! is_array( $args['excluded_action'] ) ) {
				$excluded_action = explode( ',', $args['excluded_action'] );
			} else {
				$excluded_action = $args['excluded_action'];
			}
			$ca_clean = array();
			foreach ( $excluded_action as $ca ) {
				$ca_clean[] = $wpdb->prepare( '%s', $ca );
			}
			$ca_not_in                           = implode( ',', $ca_clean );
			$where_conditions['excluded_action'] = "component_action NOT IN ({$ca_not_in})";
		}

		if ( ! empty( $args['search_terms'] ) ) {
			$search_terms_like                = '%' . bp_esc_like( $args['search_terms'] ) . '%';
			$where_conditions['search_terms'] = $wpdb->prepare( '( component_name LIKE %s OR component_action LIKE %s OR log_entry LIKE %s )', $search_terms_like, $search_terms_like, $search_terms_like );
		}

		if ( ! empty( $args['exclude_terms'] ) ) {
			$search_terms_not_like                = '%' . bp_esc_like( $args['exclude_terms'] ) . '%';
			$where_conditions['exclude_terms'] = $wpdb->prepare( '( log_entry NOT LIKE %s )', $search_terms_not_like );
		}

		if ( ! empty( $args['date_query'] ) ) {
			$where_conditions['date_query'] = self::get_date_query_sql( $args['date_query'] );
		}
		if ( ! empty( $meta_query_sql['where'] ) ) {
			$where_conditions['meta_query'] = $meta_query_sql['where'];
		}

		if ( ! empty( $args['amount'] ) && ! empty( $args['amount_comparison'] ) ) {
			$where_conditions['amount'] = "amount " . $args['amount_comparison'] . " " . $args['amount'];
		}

		$where_conditions = apply_filters( 'cb_transactions_get_where_conditions', $where_conditions, $args, $select_sql, $from_sql, $join_sql, $meta_query_sql );

		if ( ! empty( $where_conditions ) ) {
<<<<<<< HEAD
			$where = 'WHERE ' . implode( ' AND ', $where_conditions );
=======
			if ( isset( $args['or'] ) ) {
				if ( $args['or'] === true ) {
					$where = 'WHERE ' . implode( ' OR ', $where_conditions );
				} else {
					$where = 'WHERE ' . implode( ' AND ', $where_conditions );
				}
			} else {
				$where = 'WHERE ' . implode( ' AND ', $where_conditions );	
			}
			
>>>>>>> 4bd4bbb (The Big Commit of April 2023)
		}

		return $where;

	}

	public function get_recipients( $item_id = 0 ) {

		if ( empty( $item_id ) ) {
			$item_id = $this->item_id;
		}

		$item_id = (int) $item_id;

		$recipients = wp_cache_get( 'transaction_recipients_' . $item_id, 'confetti_bits_transactions_recipients' );

		if ( false === $recipients ) {

			$recipients = array();

			$results = self::get(
				array(
					'per_page'		=> - 1,
					'transactions' 	=> array( $item_id ),
				)
			);

			if ( ! empty( $results['recipients'] ) ) {
				foreach ( (array) $results['recipients'] as $recipient ) {
					$recipients[ $recipient->user_id ] = $recipient;
				}

				wp_cache_set( 'transaction_recipients_' . $item_id, $recipients, 'cb_transactions' );
			}
		}

		// Cast all items from the messages DB table as integers.
		foreach ( (array) $recipients as $key => $data ) {
			$recipients[ $key ] = (object) array_map( 'intval', (array) $data );
		}
	}

	protected static function convert_orderby_to_order_by_term( $orderby ) {
		$order_by_term = '';

		switch ( $orderby ) {
			case 'id':
				$order_by_term = 'm.id';
				break;
			case 'sender_id':
			case 'user_id':
				$order_by_term = 'm.sender_id';
				break;
			case 'amount' :
				$order_by_term = 'SUM( m.amount ) AS amount';
				break;
			case 'date_sent':
			default:
				$order_by_term = 'm.date_sent';
				break;
		}

		return $order_by_term;
	}

	protected static function strip_leading_and( $s ) {
		return preg_replace( '/^\s*AND\s*/', '', $s );
	}

	public static function get( $args = array() ) {

		global $wpdb;
		$bp = buddypress();
		$cb = Confetti_Bits();
		$defaults = array(
			'orderby'           => 'date_sent',
			'order'             => 'DESC',
			'per_page'          => 20,
			'page'              => 1,
			'user_id'           => 0,
			'date_query'        => false,
			'transactions'		=> array(),
			'include'           => false,
			'exclude'           => false,
			'fields'            => 'all',
			'group_by'          => '',
			'log_entry'         => '',
			'count_total'       => false,
		);
		$r = bp_parse_args( $args, $defaults, 'confetti_bits_transactions_transaction_get' );
		$sql = array(
			'select'     => 'SELECT DISTINCT m.id',
			'from'       => "{$cb->transactions->table_name} m",
			'where'      => '',
			'orderby'    => '',
			'pagination' => '',
			'date_query' => '',
		);
		if ( 'sender_ids' === $r['fields'] ) {
			$sql['select'] = 'SELECT DISTINCT m.sender_id';
		}
		if ( 'recipient_ids' === $r['fields'] ) {
			$sql['select'] = 'SELECT DISTINCT m.recipient_id';
		}
		$where_conditions = array();
		$date_query_sql = self::get_date_query_sql( $r['date_query'] );
		if ( ! empty( $date_query_sql ) ) {
			$where_conditions['date'] = $date_query_sql;
		}
		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions['user'] = $wpdb->prepare( 'm.sender_id = %d', $r['user_id'] );
		}
		if ( ! empty( $r['include'] ) ) {
			$include                     = implode( ',', wp_parse_id_list( $r['include'] ) );
			$where_conditions['include'] = "m.id IN ({$include})";
		}
		if ( ! empty( $r['exclude'] ) ) {
			$exclude                     = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$where_conditions['exclude'] = "m.id NOT IN ({$exclude})";
		}
		if ( ! empty( $r['log_entry'] ) ) {
			$where_conditions['log_entry'] = $wpdb->prepare( 'm.log_entry != %s', $r['log_entry'] );
		}
		$order   = $r['order'];
		$orderby = $r['orderby'];

		$order = bp_esc_sql_order( $order );

		$orderby = apply_filters( 
			'confetti_bits_transactions_transaction_get_orderby', 
			self::convert_orderby_to_order_by_term( $orderby ), $orderby );

		$sql['orderby'] = "ORDER BY {$orderby} {$order}";

		if ( ! empty( $r['per_page'] ) && ! empty( $r['page'] ) && - 1 !== $r['per_page'] ) {
			$sql['pagination'] = $wpdb->prepare( 
				'LIMIT %d, %d', 
				intval( ( $r['page'] - 1 ) * $r['per_page'] ), 
				intval( $r['per_page'] ) 
			);
		}
		$where_conditions = apply_filters( 
			'confetti_bits_transactions_transaction_get_where_conditions', 
			$where_conditions, 
			$r 
		);

		$where = '';

		if ( ! empty( $where_conditions ) ) {
			$sql['where'] = implode( ' AND ', $where_conditions );

			$where        = "WHERE {$sql['where']}";
		}

		$sql['from'] = apply_filters( 
			'confetti_bits_transactions_transaction_get_join_sql', 
			$sql['from'], $r 
		);

		$paged_transactions_sql = "{$sql['select']} FROM {$sql['from']} {$where} {$sql['orderby']} {$sql['pagination']}";
		$paged_transactions_sql = apply_filters( 
			'confetti_bits_transactions_transaction_get_paged_sql', 
			$paged_transactions_sql, 
			$sql, $r 
		);

		$cached = bp_core_get_incremented_cache( $paged_transactions_sql, 'confetti_bits_transactions' );

		if ( false === $cached ) {

			$paged_transaction_ids = $wpdb->get_col( $paged_transactions_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

			bp_core_set_incremented_cache( $paged_transactions_sql, 'confetti_bits_transactions', $paged_transaction_ids );

		} else {

			$paged_transaction_ids = $cached;

		}

		$paged_transactions = array();

		if ( 'ids' === $r['fields'] || 'sender_ids' === $r['fields'] || 'recipients' === $r['fields'] ) {

			$paged_transactions = array_map( 'intval', $paged_transaction_ids );

		} elseif ( ! empty( $paged_transaction_ids ) ) {

			$transaction_ids_sql             = implode( ',', array_map( 'intval', $paged_transaction_ids ) );

			$transaction_data_objects_sql    = "SELECT m.* FROM {$cb->transactions->table_name} m WHERE m.id IN ({$transaction_ids_sql})";
			$transaction_data_objects_cached = bp_core_get_incremented_cache( $transaction_data_objects_sql, 'confetti_bits_transactions' );

			if ( false === $transaction_data_objects_cached ) {
				$transaction_data_objects = $wpdb->get_results( $transaction_data_objects_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				bp_core_set_incremented_cache( $transaction_data_objects_sql, 'confetti_bits_transactions', $transaction_data_objects );
			} else {
				$transaction_data_objects = $transaction_data_objects_cached;
			}

			foreach ( (array) $transaction_data_objects as $tdata ) {
				$transaction_data_objects[ $tdata->id ] = $tdata;
			}
			foreach ( $paged_transaction_ids as $paged_transaction_id ) {
				$paged_transactions[] = $transaction_data_objects[ $paged_transaction_id ];
			}
		}

		$retval = array(
			'transactions' => $paged_transactions,
			'total'    => 0,
		);

		if ( ! empty( $r['count_total'] ) ) {

			$total_transactions_sql = "SELECT COUNT(DISTINCT m.id) FROM {$sql['from']} $where";

			$total_transactions_sql = apply_filters( 'confetti_bits_transactions_transaction_get_total_sql', $total_transactions_sql, $sql, $r );

			$total_transactions_sql_cached = bp_core_get_incremented_cache( $total_transactions_sql, 'confetti_bits_transactions' );

			if ( false === $total_transactions_sql_cached ) {
				$total_transactions  = (int) $wpdb->get_var( $total_transactions_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				bp_core_set_incremented_cache( $total_transactions_sql, 'confetti_bits_transactions', $total_transactions );
			} else {
				$total_transactions = $total_transactions_sql_cached;
			}

			$retval['total'] = $total_transactions;
		}

		return $retval;
	}

	/*/ we use this little guy so we don't disturb the others until we're ready for an update
	public function debuggification( $user_id, $args = array() ) {
		global $wpdb;
		$bp = buddypress();
		$cb = Confetti_Bits();
		$defaults = array (
			'page'		=> 1,
			'per_page'	=> 7,
		);
		//, recipient_name, date_sent, log_entry, amount
		$r = bp_parse_args( $args, $defaults, 'cb_transactions_get_transactions_for_user' );
		$select_sql = "SELECT id";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'recipient_id'		=> $user_id,
			'component_name'	=> 'confetti_bits',
		), $select_sql, $from_sql );

		//		$prefetch_select_sql = "SELECT id, COUNT(id) AS total_rows";

		//		$prefetch_sql = "{$prefetch_select_sql} {$from_sql} {$where_sql}";

		//		$wpdb_prefetch_total = $wpdb->get_results( $prefetch_sql, 'ARRAY_A');

		//		$this->total_pages = ceil($wpdb_prefetch_total[0]['total_rows']/$r['per_page']);

		//		$page_val = ( $r['page'] - 1 ) * $r['per_page'];

		$pagination_sql 	= "LIMIT {$r['page']}, {$r['per_page']}";

		$order_sql = "ORDER BY date_sent DESC";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$order_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );

	}
/*/

	/*/
	public static function get_totals_groupedby_recipient_name() {

		global $wpdb;

		$cb = Confetti_Bits();
		$select_sql = "SELECT identifier, recipient_name, SUM(amount) as amount";
		$from_sql = "FROM {$cb->transactions->table_name} n ";
		$where_sql = self::get_where_sql( array(
			'component_name'	=> 'confetti_bits',
		), $select_sql, $from_sql );
		$group_sql = "GROUP BY identifier";
		$order_sql = "ORDER BY recipient_name ASC";
		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$order_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );

	}
/*/

}
