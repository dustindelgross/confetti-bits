<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * A component that allows certain users to schedule spot bonuses.
 * 
 * @package ConfettiBits\Transactions
 * @since 1.0.0
 */
class CB_Transactions_Spot_Bonus {


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
	 * The date of the spot bonus.
	 * 
	 * @var datetime
	 */
	public $spot_bonus_date;
	
	/**
	 * The ID of the associated transaction.
	 * 
	 * @var int
	 */
	public $transaction_id;

	public static $columns = array(
		'id',
		'sender_id',
		'recipient_id',
		'spot_bonus_date',
		'transaction_id',
	);

	public function __construct( $id = 0 ) {

		$this->errors = new WP_Error();

		if ( ! empty ( $id ) ) {

			$this->id = (int) $id;
			$this->populate( $id );

		}

	}

	/**
	 * Saves a spot bonus entry to the database.
	 */
	public function save() {

		$retval = false;
		$data = [
			'sender_id' => $this->sender_id,
			'recipient_id' => $this->recipient_id,
			'spot_bonus_date' => $this->spot_bonus_date,
			'transaction_id' => $this->transaction_id,
		];

		$data_format = ['%d', '%d', '%s', '%d'];

		$result = self::_insert( $data, $data_format );

		if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
			global $wpdb;

			if ( empty( $this->id ) ) {
				$this->id = $wpdb->insert_id;
			}

			$retval = $this->id;
			
		}

		return $retval;
	}

	/**
	 * Populates a spot bonus object with data when given an ID.
	 * 
	 * @param int $id The ID of the spot bonus to fetch.
	 */
	public function populate( $id ) {
		
		global $wpdb;
		$spot_bonus = $this->get_spot_bonuses([ 'where' => ['id' => $id ] ]);

		$fetched_spot_bonus = ( ! empty( $spot_bonus[0] ) ? current( $spot_bonus ) : [] );
		if ( ! empty( $fetched_spot_bonus ) && ! is_wp_error( $fetched_spot_bonus ) ) {
			$this->sender_id		 = (int) $fetched_spot_bonus['sender_id'];
			$this->recipient_id		 = (int) $fetched_spot_bonus['recipient_id'];
			$this->spot_bonus_date		 = $fetched_spot_bonus['spot_bonus_date'];
			$this->transaction_id = $fetched_spot_bonus['transaction_id'];
		}
	}

	/**
	 * Inserts a spot bonus into the database.
	 */
	protected static function _insert( $data = [], $data_format = [] ) {
		global $wpdb;
		return $wpdb->insert( Confetti_Bits()->transactions->table_name_spot_bonuses, $data, $data_format );
	}

	/**
	 * Deletes a spot bonus entry from the database.
	 * 
	 * @param array $args An associative array of arguments that gets passed
	 * 					  to self::get_query_clauses for formatting. Accepts
	 * 					  any property of a CB_Transactions_Transaction object.
	 * 					  For example: ['recipient_id' => 4, 'sender_id' => 16]
	 * @since 2.3.0
	 */
	public function delete( $args = [] ) {
		$where = self::get_query_clauses($args);
		return self::_delete( $where['data'], $where['format'] );
	}

	/**
	 * Deletes a spot bonus entry.
	 *
	 * @see wpdb::delete() for further description of paramater formats.
	 *
	 * @param array $where        Array of WHERE clauses to filter by, passed to
	 *                            {@link wpdb::delete()}. Accepts any property of a
	 *                            CB_Transactions_Transaction object.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 * 
	 * @since 2.3.0
	 */
	protected static function _delete( $where = [], $where_format = [] ) {

		global $wpdb;

		// $where_sql = self::get_where_sql( $where );
		// $participation = $wpdb->get_results( "SELECT * FROM {$cb->transactions->table_name_spot_bonuses} {$where_sql}" );

		return $wpdb->delete( Confetti_Bits()->transactions->table_name_spot_bonuses, $where, $where_format );

	}

	/**
	 * Gets spot bonuses from the database.
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
	 * 						  method. @see CB_Transactions_Spot_Bonus::get_where_sql()
	 *     @type array $orderby Array of specific key => value pairs that determine
	 * 							the ORDER BY clause. 
	 * 							@see CB_Transactions_Spot_Bonus::get_orderby_sql()
	 *     @type string $groupby A string that determines whether the query should be
	 * 							 grouped by a specific column.
	 *     @type array $pagination An array of specific key => value pairs that 
	 * 							   determine the LIMIT clause.
	 * }
	 * 
	 * @return array An associative array of spot bonus data.
	 */
	public function get_spot_bonuses( $args = [] ) {

		global $wpdb;
		$cb = Confetti_Bits();
		$r = wp_parse_args( $args, [
			'select' => '*',
			'where' => [],
			'orderby' => [],
			'groupby' => '',
			'pagination' => []
		]);

		$select = ( is_array( $r['select'] ) ) ? implode( ', ', $r['select'] ) : $r['select'];
		$select_sql = "SELECT {$select}";
		$from_sql = "FROM {$cb->transactions->table_name_spot_bonuses}";
		$where_sql = self::get_where_sql( $r['where'] );
		$limit_sql = self::get_paged_sql($r['pagination']);
		$orderby_sql = self::get_orderby_sql($r['orderby']);
		$groupby_sql = ! empty( $r['groupby'] ) ? "GROUP BY {$r['groupby']}" : '';

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$groupby_sql} {$orderby_sql} {$limit_sql}";

		return $wpdb->get_results( $sql, "ARRAY_A" );

	}
	
	/**
	 * Update spot bonus entry in the database.
	 * @param array $data Array of spot bonus data to update, passed to
	 * 						  {@link wpdb::update()}. Accepts any property of a
	 * 						  CB_Transactions_Spot_Bonus object.
	 * @param array $where  The WHERE params as passed to wpdb::update().
	 * 						  Typically consists of [ 'ID' => $id ) to specify the
	 * 						  contest entry ID to update.
	 * @param array $data_format  See {@link wpdb::update()}. Default [ '%d', '%d', '%d', '%d' ].
	 * @param array $where_format  See {@link wpdb::update()}. Default is [ '%d' ].
	 * @see CB_Transactions_Spot_Bonus::_update()
	 * @return int|WP_Error The number of rows updated, or WP_Error otherwise.
	 * @since 3.0.0
	 */
	public static function update($update_args = [], $where_args = [])
	{
		$update = self::get_query_clauses($update_args);
		$where = self::get_query_clauses($where_args);

		return self::_update(
			$update['data'],
			$where['data'],
			$update['format'],
			$where['format']
		);
	}

	/**
	 * Update spot bonus entry.
	 *
	 * @see wpdb::update() for further description of paramater formats.
	 *
	 * @param array $data  Array of contest data to update, passed to
	 *                            {@link wpdb::update()}. Accepts any property of a
	 *                            CB_Transactions_Spot_Bonus object.
	 * @param array $where  The WHERE params as passed to wpdb::update().
	 *                            Typically consists of [ 'ID' => $id )
	 * 							  to specify the ID of the item being updated.
	 * 							  See {@link wpdb::update()}.
	 * @param array $data_format  See {@link wpdb::update()}.
	 * @param array $where_format See {@link wpdb::update()}.
	 * @return int|false The number of rows updated, or false on error.
	 */
	protected static function _update($data = [], $where = [], $data_format = [], $where_format = [])
	{
		global $wpdb;

		$retval = $wpdb->update(
			Confetti_Bits()->transactions->table_name_spot_bonuses,
			$data,
			$where,
			$data_format,
			$where_format
		);

		do_action('cb_transactions_spot_bonuses_after_update', $data);

		return $retval;
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
	 * Used by CB_Transactions_Spot_Bonus::get_spot_bonuses() to create its LIMIT clause.
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

	/**
	 * Assembles a date query clause for an SQL WHERE statement.
	 * 
	 * @see CB_Core_Date_Query()
	 * 
	 * @package ConfettiBits\Transactions
	 * @since 2.3.0
	 */
	public static function get_date_query_sql( $date_query = array() ) {

		$sql = '';

		if ( ! empty( $date_query ) && is_array( $date_query ) ) {
			if ( ! empty( $date_query['column'] ) && 'date_recorded' === $date_query['column'] ) {
				$date_query = new CB_Core_Date_Query( $date_query, 'date_recorded' );
			} else {
				$date_query = new CB_Core_Date_Query( $date_query, 'spot_bonus_date' );
			}
			$sql = preg_replace( '/^\sAND/', '', $date_query->get_sql() );
		}
		return $sql;
	}

	/**
	 * Assembles the SQL WHERE clause.
	 * 
	 * @param array $args { 
	 *     An associative array of arguments. All optional.
	 * 
	 *     @type int $id One or more spot bonus IDs.
	 *     @type int $sender_id One or more sender IDs.
	 *     @type string $date_query A date query to send to CB_Transactions_Spot_Bonus::get_date_query_sql
	 * }
	 * 
	 * @return string The WHERE clause.
	 * 
	 * @package ConfettiBits\Transactions
	 * @since 1.0.0
	 */
	protected static function get_where_sql( $args = [] ) {
		global $wpdb;
		$where_conditions = array();
		$where = '';

		if ( ! empty( $args['id'] ) ) {
			$id_in                  = implode( ',', wp_parse_id_list( $args['id'] ) );
			$where_conditions['id'] = "id IN ({$id_in})";
		}

		if ( ! empty( $args['sender_id'] ) ) {
			$sender_id_in                  = implode( ',', wp_parse_id_list( $args['sender_id'] ) );
			$where_conditions['sender_id'] = "sender_id IN ({$sender_id_in})";
		}

		if ( ! empty( $args['recipient_id'] ) ) {
			$recipient_id_in                  = implode( ',', wp_parse_id_list( $args['recipient_id'] ) );
			$where_conditions['recipient_id'] = "recipient_id IN ({$recipient_id_in})";
		}
		
		if ( ! empty( $args['transaction_id'] ) ) {
			$transaction_id_in = implode(',', wp_parse_id_list( $args['transaction_id'] ) );
			$where_conditions['transaction_id'] = "transaction_id IN ({$transaction_id_in})";
		}

		if ( ! empty( $args['date_query'] ) ) {
			$where_conditions['date_query'] = self::get_date_query_sql( $args['date_query'] );
		}

		if ( ! empty( $where_conditions ) ) {
			$where = !empty( $args['or'] ) ? 
				'WHERE ' . implode( ' OR ', $where_conditions ) 
				: 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		return $where;

	}

	/**
	 * Assemble query clauses, based on arguments, to pass to $wpdb methods.
	 *
	 * The insert(), update(), and delete() methods of {@link wpdb} expect
	 * arguments of the following forms:
	 *
	 * - associative arrays whose key/value pairs are column => value, to
	 *   be used in WHERE, SET, or VALUES clauses.
	 * - arrays of "formats", which tell $wpdb->prepare() which type of
	 *   value to expect when sanitizing (eg, array( '%s', '%d' ))
	 *
	 * This utility method can be used to assemble both kinds of params,
	 * out of a single set of associative array arguments, such as:
	 *
	 *     $args = array(
	 *         'applicant_id' => 4,
	 * 		   'component_action' => 'cb_participation_new'
	 *     );
	 *
	 * This will be converted to:
	 *
	 *     array(
	 *         'data' => array(
	 *             'applicant_id' => 4,
	 *             'component_action' => 'cb_participation_new',
	 *         ),
	 *         'format' => array(
	 *             '%d',
	 *             '%s',
	 *         ),
	 *     )
	 *
	 * which can easily be passed as arguments to the $wpdb methods.
	 *
	 *
	 * @param array $args Associative array of filter arguments.
	 *                    
	 * @return array Associative array of 'data' and 'format' args.
	 */
	protected static function get_query_clauses( $args = [] ) {
		$where_clauses = [
			'data'   => [],
			'format' => [],
		];

		if ( ! empty( $args['id'] ) ) {
			$where_clauses['data']['id'] = absint( $args['id'] );
			$where_clauses['format'][]   = '%d';
		}

		if ( ! empty( $args['sender_id'] ) ) {
			$where_clauses['data']['sender_id'] = absint( $args['sender_id'] );
			$where_clauses['format'][]        = '%d';
		}

		if ( ! empty( $args['recipient_id'] ) ) {
			$where_clauses['data']['recipient_id'] = absint( $args['recipient_id'] );
			$where_clauses['format'][]        = '%d';
		}

		if ( ! empty( $args['spot_bonus_date'] ) ) {
			$where_clauses['data']['spot_bonus_date'] = $args['spot_bonus_date'];
			$where_clauses['format'][]               = '%s';
		}
		
		if ( ! empty( $args['transaction_id'] ) ) {
			$where_clauses['data']['transaction_id'] = $args['transaction_id'];
			$where_clauses['format'][] = '%d';
		}

		return $where_clauses;
	}

}
