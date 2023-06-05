<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CB Transactions Transaction
 * 
 * A component that allows users to send bits.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.0.0
 */
class CB_Transactions_Transaction {


	/**
	 * Last recorded Transaction ID in the database.
	 * 
	 * @var int
	 */
	public static $last_inserted_id; 

	/**
	 * The Transaction ID.
	 * 
	 * @var int
	 */
	public $id; 

	/**
	 * The item ID of the Transaction. Used with the 
	 * BuddyBoss Notifications API.
	 * 
	 * @var int
	 */
	public $item_id;

	/**
	 * The secondary item ID of the Transaction. Used 
	 * with the BuddyBoss Notifications API.
	 * 
	 * @var int
	 */
	public $secondary_item_id;

	/**
	 * The ID of the user sending the Confetti Bits.
	 * 
	 * @var int
	 */
	public $sender_id;

	/**
	 * The ID of the user receiving the Confetti Bits.
	 * 
	 * @var int
	 */
	public $recipient_id;

	/**
	 * The date of the Transaction.
	 * 
	 * @var int
	 */
	public $date_sent;

	/**
	 * A memo for the Transaction.
	 * 
	 * @var int
	 */
	public $log_entry;

	/**
	 * The component associated with the Transaction.
	 * 
	 * @var int
	 */
	public $component_name;

	/**
	 * The component action associate with the Transaction.
	 * 
	 * @var int
	 */
	public $component_action;

	/**
	 * The amount of Confetti Bits that were sent.
	 * 
	 * @var int
	 */
	public $amount;

	/**
	 * The ID of the event associated with the Transaction.
	 * 
	 * @var int
	 */
	public $event_id;

	public $error;

	public $error_type = 'bool';

	public static $columns = array(
		'id',
		'item_id',
		'secondary_item_id',
		'sender_id',
		'recipient_id',
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

		$reset_date = get_option('cb_reset_date');
		$date = new DateTimeImmutable($reset_date);
		$this->current_date = current_time( 'Y-m-d H:i:s', false );
		$this->current_cycle_end = $reset_date;
		$this->current_cycle_start = $date->modify("- 1 year")->format('Y-m-d H:i:s');
		$this->previous_cycle_end = $date->modify("- 1 year")->format('Y-m-d H:i:s');
		$this->previous_cycle_start = $date->modify("- 2 years")->format('Y-m-d H:i:s');
		$this->current_spending_cycle_start = $date->modify("- 1 year + 1 month")->format('Y-m-d H:i:s');
		$this->current_spending_cycle_end = $date->modify("+ 1 month")->format('Y-m-d H:i:s');
		$this->previous_spending_cycle_start = $date->modify("- 2 years + 1 month")->format('Y-m-d H:i:s');
		$this->previous_spending_cycle_end = $date->modify("- 1 year + 1 month")->format('Y-m-d H:i:s');

	}

	public function send_bits() {

		$retval = false;
		do_action( 'cb_transactions_before_send', array( &$this ) );
		$data = array (
			'item_id' => $this->item_id,
			'secondary_item_id' => $this->secondary_item_id,
			'sender_id' => $this->sender_id,
			'recipient_id' => $this->recipient_id,
			'date_sent' => $this->date_sent,
			'log_entry' => $this->log_entry,
			'component_name' => $this->component_name,
			'component_action' => $this->component_action,
			'amount' => $this->amount,
			'event_id' => $this->event_id
		);

		$data_format = array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d' );

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

		$fetched_transaction = ( ! empty( $transaction[0] ) ? current( $transaction ) : array() );
		if ( ! empty( $fetched_transaction ) && ! is_wp_error( $fetched_transaction ) ) {
			$this->item_id           = (int) $fetched_transaction['item_id'];
			$this->secondary_item_id = (int) $fetched_transaction['secondary_item_id'];
			$this->sender_id		 = (int) $fetched_transaction['sender_id'];
			$this->recipient_id		 = (int) $fetched_transaction['recipient_id'];
			$this->date_sent		 = $fetched_transaction['date_sent'];
			$this->log_entry		 = $fetched_transaction['log_entry'];
			$this->component_name    = $fetched_transaction['component_name'];
			$this->component_action  = $fetched_transaction['component_action'];
			$this->amount			 = (int) $fetched_transaction['amount'];
			$this->event_id			 = (int) $fetched_transaction['event_id'];
		}
	}

	protected static function _insert( $data = array(), $data_format = array() ) {
		global $wpdb;
		return $wpdb->insert( Confetti_Bits()->transactions->table_name, $data, $data_format );
	}

	/**
	 * Gets transactions from the database.
	 * 
	 * Pieces together an SQL query based on the given 
	 * arguments.
	 * 
	 * @global $wpdb The WordPress database global.
	 * 
	 * @param array $args { 
	 *     Optional. An array of arguments that get 
	 *     merged with some defaults.
	 * 
	 *     @type string|array $select Default '*'. Either a comma-separated list or array
	 *                                of the columns to select.
	 *     @type array $where Array of key => value pairs that get passed to an internal
	 * 						  method. @see CB_Transactions_Transaction::get_where_sql()
	 *     @type array $orderby Array of specific key => value pairs that determine
	 * 							the ORDER BY clause. 
	 * 							@see CB_Transactions_Transaction::get_orderby_sql()
	 *     @type string $groupby A string that determines whether the query should be
	 * 							 grouped by a specific column.
	 *     @type array $pagination An array of specific key => value pairs that 
	 * 							   determine the LIMIT clause.
	 * }
	 * 
	 * @return array An associative array of transaction data.
	 */
	public function get_transactions( $args = [] ) {

		global $wpdb;
		$r = wp_parse_args( $args, [
			'select' => '*',
			'where' => [],
			'orderby' => [],
			'groupby' => '',
			'pagination' => []
		]);

		$select = ( is_array( $r['select'] ) ) ? implode( ', ', $r['select'] ) : $r['select'];
		$select_sql = "SELECT {$select}";
		$from_sql = "FROM {$wpdb->prefix}confetti_bits_transactions";
		$where_sql = self::get_where_sql( $r['where'] );
		$limit_sql = self::get_paged_sql($r['pagination']);
		$orderby_sql = self::get_orderby_sql($r['orderby']);
		$groupby_sql = ! empty( $r['groupby'] ) ? "GROUP BY {$r['groupby']}" : '';

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$groupby_sql} {$orderby_sql} {$limit_sql}";

		return $wpdb->get_results( $sql, "ARRAY_A" );

	}

	/**
	 * Get Orderby SQL.
	 * 
	 * Checks against the columns available and order
	 * arguments, then spits out usable SQL if everything
	 * looks okay.
	 * 
	 * @param array $args { 
	 *     Optional. An array of arguments.
	 *     
	 *     @type string $column Default 'id'. The column to order by.
	 *     @type string $order Default 'DESC'. The order of the items.
	 * }
	 * 
	 * @return string The ORDER BY clause of an SQL query, or 
	 * 				  nothing if the args are empty or malformed.
	 */
	public static function get_orderby_sql( $args = [] ) {

		global $wpdb;
		$sql = '';

		if ( empty($args) ) {
			return $sql;
		}

		$valid_sql = array_merge( self::$columns, ['DESC', 'ASC', 'calculated_total'] );

		$r = wp_parse_args( $args, [
			'column' => 'id',
			'order' => 'DESC',
		]);

		if ( !in_array(strtolower($r['column']), $valid_sql ) ) {
			return $sql;
		}

		if ( !in_array( strtoupper($r['order']), $valid_sql ) ) {
			return $sql;
		}

		$sql = sprintf("ORDER BY %s %s", $r['column'], $r['order']);

		return $sql;
	}

	/**
	 * Assemble the LIMIT clause of a get() SQL statement.
	 *
	 * Used by CB_Participation_Participation::get_participation() to create its LIMIT clause.
	 *
	 *
	 * @param	array	$args	Array consisting of 
	 * 							the page number and items per page. { 
	 * 			@type	int		$page		page number
	 * 			@type	int		$per_page	items to return
	 * }
	 * 
	 * @return string $retval LIMIT clause.
	 * 
	 */
	protected static function get_paged_sql( $args = array() ) {

		global $wpdb;
		$retval = '';

		if ( ! empty( $args['page'] ) && ! empty( $args['per_page'] ) ) {
			$page     = absint( $args['page'] );
			$per_page = absint( $args['per_page'] );
			$offset   = $per_page * ( $page - 1 );
			$retval   = $wpdb->prepare( 'LIMIT %d, %d', $offset, $per_page );
		}

		return $retval;
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
	}	 */

	/*

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
*/
	/*
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
			'date_query'		=> array(
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
*/

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
				'before'		=> $cb->earn_end,
				'after'			=> $cb->earn_start,
				'inclusive'		=> true,
			),
			'component'			=> 'activity',
			'type'				=> 'activity_update',
			'item_id'			=> 0,
			'secondary_item_id'	=> 0,
		));

		$order_sql = "ORDER BY date_recorded DESC";

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$order_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	/*
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

	*/

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

	public static function get_date_query_sql( $date_query = array() ) {

		$sql = '';

		if ( ! empty( $date_query ) && is_array( $date_query ) ) {
			if ( ! empty( $date_query['column'] ) && 'date_recorded' === $date_query['column'] ) {
				$date_query = new CB_Core_Date_Query( $date_query, 'date_recorded' );
			} else {
				$date_query = new CB_Core_Date_Query( $date_query, 'date_sent' );
			}
			$sql = preg_replace( '/^\sAND/', '', $date_query->get_sql() );
		}
		return $sql;
	}

	protected static function get_where_sql( $args = [] ) {
		global $wpdb;
		$where_conditions = array();
		$where = '';

		if ( ! empty( $args['id'] ) ) {
			$id_in                  = implode( ',', wp_parse_id_list( $args['id'] ) );
			$where_conditions['id'] = "id IN ({$id_in})";
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

		if ( ! empty( $args['sender_id'] ) ) {
			$sender_id_in                  = implode( ',', wp_parse_id_list( $args['sender_id'] ) );
			$where_conditions['sender_id'] = "sender_id IN ({$sender_id_in})";
		}

		if ( ! empty( $args['recipient_id'] ) ) {
			$recipient_id_in                  = implode( ',', wp_parse_id_list( $args['recipient_id'] ) );
			$where_conditions['recipient_id'] = "recipient_id IN ({$recipient_id_in})";
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

		if ( ! empty( $args['date_query'] ) ) {
			$where_conditions['date_query'] = self::get_date_query_sql( $args['date_query'] );
		}
		if ( ! empty( $meta_query_sql['where'] ) ) {
			$where_conditions['meta_query'] = $meta_query_sql['where'];
		}

		if ( ! empty( $args['amount'] ) && ! empty( $args['amount_comparison'] ) ) {
			$where_conditions['amount'] = "amount " . $args['amount_comparison'] . " " . $args['amount'];
		}

		$where_conditions = apply_filters( 'cb_transactions_get_where_conditions', $where_conditions, $args );

		if ( ! empty( $where_conditions ) ) {
			$where = !empty( $args['or'] ) ? 
				'WHERE ' . implode( ' OR ', $where_conditions ) 
				: 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		return $where;

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

}
