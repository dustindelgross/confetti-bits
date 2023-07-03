<?php
/**
 * Manages the creation and management of contests within the Events component.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * CB_Events_Contest class.
 *
 * @package ConfettiBits\Events
 * @since 3.0.0
 */
class CB_Events_Contest
{

	/**
	 * The ID of the contest entry.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The ID of the event that the contest is for.
	 *
	 * @var int
	 */
	public $event_id;

	/**
	 * The placement of the user in the contest.
	 *
	 * @var int
	 */
	public $placement;

	/**
	 * The amount that the user will receive for the given placement in the contest.
	 *
	 * @var int
	 */
	public $amount;

	/**
	 * The ID of the user that will receive the amount for the given placement in the contest.
	 *
	 * @var int
	 */
	public $recipient_id;

	/**
	 * The date the contest was created.
	 *
	 * @var string
	 */
	public $date_created;

	/**
	 * The date of the last time the entry was modified.
	 *
	 * @var string
	 */
	public $date_modified;

	/**
	 * The column names for the contest entry table.
	 * @var array
	 */
	public $columns = [
		'id',
		'event_id',
		'placement',
		'amount',
		'recipient_id',
		'date_created',
		'date_modified',
	];

	/**
	 * Constructor
	 *
	 * @param int $id Optional. The ID of the contest entry to populate.
	 * @since 3.0.0
	 * @access public
	 */
	public function __construct($id = 0)
	{

		if (!empty($id)) {
			$this->id = (int) $id;
			$this->populate($id);
		}

	}

	/**
	 * Populates a contest object's properties from the database, based on contest ID.
	 * @param int $id The ID of the contest entry to populate.
	 * @since 3.0.0
	 * @see CB_Contests_Contest::get_contest()
	 */
	public function populate($id = 0)
	{

		$contest = $this->get_contest(['where' => ['id' => $id]]);

		$fetched_contest = !empty($contest) ? current($contest) : [];

		if (!empty($fetched_contest) && is_array($fetched_contest)) {
			$this->event_id = $fetched_contest['event_id'];
			$this->placement = $fetched_contest['placement'];
			$this->amount = $fetched_contest['amount'];
			$this->recipient_id = $fetched_contest['recipient_id'];
			$this->date_created = $fetched_contest['date_created'];
			$this->date_modified = $fetched_contest['date_modified'];
		}
	}

	/**
	 * Save
	 * Save the contest to the database.
	 *
	 * @return int|bool The ID of the contest if successful, false otherwise.
	 * @since 3.0.0
	 * @access public
	 * @see CB_Contests_Contest::_insert()
	 */
	public function save()
	{

		$retval = false;

		$data = [
			'event_id' => $this->event_id,
			'placement' => $this->placement,
			'amount' => $this->amount,
			'recipient_id' => $this->recipient_id,
			'date_created' => $this->date_created,
			'date_modified' => $this->date_modified,
		];

		$data_format = ['%d', '%d', '%d', '%d', '%s', '%s'];
		$result = self::_insert($data, $data_format);

		if (!empty($result)) {

			global $wpdb;

			if (empty($this->id)) {
				$this->id = $wpdb->insert_id;
			}

			do_action('cb_contests_after_save', $data);

			$retval = $this->id;

		} else if ($result === false) {
			$retval = $result;
		}

		return $retval;

	}

	/**
	 * _insert
	 * Insert contest entry into the database.
	 *
	 * @param array $data {
	 *     Associative array of contest data to insert, passed to
	 * 	   wpdb::insert(). Accepts any key-value pair of a
	 *     Confetti_Bits_Events_Contest object.
	 * }
	 * @param array $data_format  {
	 *     See {@link wpdb::insert()}.
	 *     Default [ '%d', '%d', '%d', '%d', '%s', '%s' ].
	 * }
	 * @return int|bool The ID of the contest if successful, false otherwise.
	 * @since 3.0.0
	 * @access protected
	 * @see wpdb::insert()
	 */
	protected static function _insert($data = [], $data_format = [])
	{
		global $wpdb;
		$cb = Confetti_Bits();
		return $wpdb->insert(
			"{$wpdb->prefix}confetti_bits_contests",
			$data,
			$data_format
		);
	}

	/**
	 * Update
	 * Update contest entry in the database.
	 * @param array $data Array of contest data to update, passed to
	 * 						  {@link wpdb::update()}. Accepts any property of a
	 * 						  Confetti_Bits_Events_Contest object.
	 * @param array $where  The WHERE params as passed to wpdb::update().
	 * 						  Typically consists of [ 'ID' => $id ) to specify the
	 * 						  contest entry ID to update.
	 * @param array $data_format  See {@link wpdb::update()}. Default [ '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' ).
	 * @param array $where_format  See {@link wpdb::update()}. Default is [ '%d' ).
	 * @see Confetti_Bits_Events_Contest::_update()
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
	 * Update contest entry.
	 *
	 * @see wpdb::update() for further description of paramater formats.
	 *
	 * @param array $data  Array of contest data to update, passed to
	 *                            {@link wpdb::update()}. Accepts any property of a
	 *                            Confetti_Bits_Events_Contest object.
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
			"{$wpdb->prefix}confetti_bits_contests",
			$data,
			$where,
			$data_format,
			$where_format
		);

		do_action('cb_contests_after_update', $data);

		return $retval;
	}

	/**
	 * Delete
	 * Delete the contest from the database.
	 *
	 * @param array $where_args  Associative array of columns/values, to
	 *                           determine which rows should be updated. Of the format
	 *                           [ 'item_id' => 7, 'component_action' => 'cb_contest', ).
	 * @return int|false Number of rows updated on success, false on failure.
	 */
	public static function delete($where_args = [])
	{
		$where = self::get_query_clauses($where_args);

		return self::_delete(
			$where['data'],
			$where['format']
		);
	}

	/**
	 * Delete contest entry.
	 * @param array $where  The WHERE params as passed to wpdb::delete().
	 * 						  Typically consists of [ 'ID' => $id ) to specify the ID
	 * 						  of the item being deleted. See {@link wpdb::delete()}.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 * @since 3.0.0
	 * @access protected
	 * @see wpdb::delete()
	 * @see CB_Contest_Contest::get_where_sql()
	 *
	 */
	protected static function _delete($where = [], $where_format = [])
	{

		global $wpdb;
		$cb = Confetti_Bits();

		return $wpdb->delete(
			"{$wpdb->prefix}confetti_bits_contests",
			$where,
			$where_format
		);

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
	 *   value to expect when sanitizing (eg, [ '%s', '%d' ))
	 *
	 * This utility method can be used to assemble both kinds of params,
	 * out of a single set of associative array arguments, such as:
	 *
	 *     $args = [
	 *         'user_id' => 4,
	 *         'component_name' => 'groups',
	 *     );
	 *
	 * This will be converted to:
	 *
	 *     [
	 *         'data' => [
	 *             'recipient_id' => 4,
	 *             'date_modified' => '2023-01-01 00:00:00',
	 *         ),
	 *         'format' => [
	 *             '%d',
	 *             '%s',
	 *         ),
	 *     )
	 *
	 * which can easily be passed as arguments to the $wpdb methods.
	 *
	 * @param array $args Associative array of filter arguments.
	 *
	 * @return array Associative array of 'data' and 'format' args.
	 */
	protected static function get_query_clauses($args = [])
	{
		$where_clauses = [
			'data' => [],
			'format' => [],
		];

		if (!empty($args['id'])) {
			$where_clauses['data']['id'] = absint($args['id']);
			$where_clauses['format'][] = '%d';
		}

		if (!empty($args['event_id'])) {
			$where_clauses['data']['event_id'] = $args['event_id'];
			$where_clauses['format'][] = '%d';
		}

		if (!empty($args['placement'])) {
			$where_clauses['data']['placement'] = $args['placement'];
			$where_clauses['format'][] = '%d';
		}

		if (!empty($args['amount'])) {
			$where_clauses['data']['amount'] = $args['amount'];
			$where_clauses['format'][] = '%d';
		}

		if (!empty($args['recipient_id'])) {
			$where_clauses['data']['recipient_id'] = absint($args['recipient_id']);
			$where_clauses['format'][] = '%d';
		}

		if (!empty($args['date_created'])) {
			$where_clauses['data']['date_created'] = $args['date_created'];
			$where_clauses['format'][] = '%s';
		}

		if (!empty($args['date_modified'])) {
			$where_clauses['data']['date_modified'] = $args['date_modified'];
			$where_clauses['format'][] = '%s';
		}

		return $where_clauses;
	}

	/**
	 * Get Contest
	 * @param array $args {
	 * 		@type string|array $select The columns to select. Default: '*'
	 * 		@type array $where {
	 * 			@type int $id The ID of the contest.
	 * 			@type int $event_id The ID of the event.
	 * 			@type int $placement The placement of the contest.
	 * 			@type int $amount The amount of the contest.
	 * 			@type int $recipient_id The ID of the recipient.
	 * 			@type string $date_created The date the contest was created.
	 * 			@type string $date_modified The date the contest was modified.
	 * 		}
	 * 		@type array $orderby {
	 * 			@type string $column The column to order by.
	 * 			@type string $order The order to sort by. Default: 'ASC'
	 * 		}
	 * 		@type array $pagination {
	 * 			@type int $page The page number.
	 * 			@type int $per_page The number of items per page.
	 * 		}
	 * 		@type string $group The column to group by.
	 * }
	 * @return array The contest(s), if found, as an associative array of objects.
	 * @since 3.0.0
	 * @access public
	 * @see CB_Events_Contest::get_where_sql()
	 * @see CB_Events_Contest::get_paged_sql()
	 * @see CB_Events_Contest::get_orderby_sql()
	 */
	public function get_contest($args = [])
	{

		global $wpdb;
		$cb = Confetti_Bits();

		$r = wp_parse_args($args, [
			'select' => '*',
			'where' => [],
			'orderby' => [],
			'pagination' => [],
			'groupby' => '',
		]);

		$select = (is_array($r['select'])) ? implode(', ', $r['select']) : $r['select'];
		$select_sql = "SELECT {$select}";
		$from_sql = "FROM {$wpdb->prefix}confetti_bits_contests ";
		$where_sql = self::get_where_sql($r['where']);
		$orderby_sql = (!empty($r['orderby'])) ? self::get_orderby_sql($r['orderby']) : '';
		$group_sql = (!empty($r['groupby'])) ? "GROUP BY {$r['groupby']}" : '';
		$pagination_sql = self::get_paged_sql($r['pagination']);

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$orderby_sql} {$pagination_sql}";

		return $wpdb->get_results($sql, 'ARRAY_A');
	}

	/**
	 * Assembles an ORDER BY clause from an array of fields and directions.
	 * @param array $orderby {
	 * 		@type string $field		Field to order by.
	 * 		@type string $direction	Direction to order by. Accepts 'ASC' or 'DESC'. Default 'ASC'.
	 * }
	 * @return string ORDER BY clause.
	 * @since 3.0.0
	 * @access public
	 */
	public static function get_orderby_sql($orderby = []) {

		if (empty($orderby)) {
			return '';
		}

		$pieces = [];
		foreach ($orderby as $field => $direction) {
			if (!in_array(strtoupper($direction), ['ASC', 'DESC'])) {
				$direction = 'DESC';
			}
			if ( !in_array( $field, self::$columns )) {
				continue;
			}

			$pieces[] = "{$field} {$direction}";

		}

		return 'ORDER BY ' . implode(', ', $pieces);

	}

	/**
	 * Get Date Query SQL.
	 *
	 * @param	array $date_query {
	 * 		Array of date query clauses.
	 * 		@type	string	$column		Column to query against. Accepts 'date_created', 'date_modified', or 'contest_date'. Default 'contest_date'.
	 * 		@type	string	$after		Date to retrieve items after. Accepts MySQL datetime format.
	 * 		@type	string	$before		Date to retrieve items before. Accepts MySQL datetime format.
	 * 		@type	string	$year		4 digit year (e.g. 2011).
	 * 		@type	string	$month		2 digit month (e.g. 02).
	 * 		@type	string	$day	   2 digit day (e.g. 02).
	 * 		@type	string	$hour       2 digit hour (e.g. 15).
	 * 		@type	string	$minute		 2 digit minute (e.g. 43).
	 * 		@type	string	$second		2 digit second (e.g. 33).
	 * 		@type	string	$compare  Comparison operator to test.
	 * 										  Accepts '=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE',
	 * 										  'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN',
	 * 										  'EXISTS' (only in WP >= 3.5), and
	 * 										  'NOT EXISTS' (also only in WP >= 3.5). Default '='.
	 * 		@type	array	$inclusive Whether the results should be
	 * 										  inclusive or not. Accepts 'true' or 'false'.
	 * 										  Default 'true'.
	 * 		@type	string	$relation	Relation between date query clauses.
	 * 										  Accepts 'AND' or 'OR'. Default 'AND'.
	 * }
	 * @return	string Date query SQL.
	 * @since	3.0.0
	 * @access	public
	 */
	public static function get_date_query_sql($date_query = [])
	{

		$sql = '';
		$columns = ['date_created', 'date_modified', 'contest_date'];
		$column = !empty($date_query['column']) && in_array($date_query['column'], $columns) ?
			$date_query['column'] : 'contest_date';

		$date_query = new CB_Core_Date_Query($date_query, $column);
		$sql = preg_replace('/^\sAND/', '', $date_query->get_sql());

		return $sql;

	}

	/**
	 * Assemble the LIMIT clause of a get() SQL statement.
	 *
	 * @param	array	$args {
	 *     Array consisting of the page number and items per page.
	 *
	 * 			@type	int		$page		page number
	 * 			@type	int		$per_page	items to return
	 *
	 * }
	 *
	 * @return string $retval LIMIT clause.
	 * @since 3.0.0
	 * @access protected
	 */
	protected static function get_paged_sql($args = [])
	{

		global $wpdb;
		$retval = '';

		if (!empty($args['page']) && !empty($args['per_page'])) {
			$page = absint($args['page']);
			$per_page = absint($args['per_page']);
			$offset = $per_page * ($page - 1);
			$retval = $wpdb->prepare('LIMIT %d, %d', $offset, $per_page);
		}

		return $retval;
	}

	/**
	 * Assemble the WHERE clause of a get() SQL statement.
	 *
	 * @param	array	$args {
	 *     Optional. Array of arguments for generating the WHERE SQL clause for get().
	 *
	 *     @type	int		$id	Contest ID.
	 *     @type	int		$recipient_id Recipient ID.
	 *     @type	int		$event_id Event ID.
	 *     @type	int		$placement Placement.
	 *     @type	int		$amount Amount.
	 *     @type	string	$date_created Date created.
	 *     @type	string	$date_modified Date modified.
	 * }
	 * @return	string $where WHERE clause.
	 * @since 3.0.0
	 * @access protected
	 */
	protected static function get_where_sql($args = [])
	{
		global $wpdb;
		$where_conditions = [];
		$where = '';

		if (!empty($args['id'])) {
			$id_in = implode(',', wp_parse_id_list($args['id']));
			$where_conditions['id'] = "id IN ({$id_in})";
		}

		if (!empty($args['recipient_id'])) {
			$recipient_id_in = implode(',', wp_parse_id_list($args['recipient_id']));
			$where_conditions['user_id'] = "recipient_id IN ({$recipient_id_in})";
		}

		if (!empty($args['event_id'])) {
			$event_id_in = implode(',', wp_parse_id_list($args['event_id']));
			$where_conditions['event_id'] = "event_id IN ({$event_id_in})";
		}

		if (!empty($args['amount'])) {
			$amount_in = implode(',', $args['amount']);
			$where_conditions['amount'] = "amount IN ({$amount_in})";
		}

		if (!empty($args['placement'])) {
			$placement_in = implode(',', wp_parse_id_list($args['placement']));
			$where_conditions['placement'] = "placement IN ({$placement_in})";
		}

		if (!empty($args['date_query'])) {
			$where_conditions['date_query'] = self::get_date_query_sql($args['date_query']);
		}

		if (!empty($where_conditions)) {
			$where = $args['or'] ? 'WHERE ' . implode(' OR ', $where_conditions) : 'WHERE ' . implode(' AND ', $where_conditions);
		}

		return $where;

	}

}