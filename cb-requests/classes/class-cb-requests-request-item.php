<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * A component that allows users to submit requests to cash in
 * their points.
 * 
 * @package ConfettiBits\Requests
 * @since 2.3.0
 */
class CB_Requests_Request_Item
{

	/**
	 * The ID of the requests entry.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The name of the item.
	 * 
	 * @var string
	 */
	public $item_name;

	/**
	 * The description of the item.
	 * 
	 * @var string
	 */
	public $item_desc;

	/**
	 * The date the entry was created.
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
	 * The value of the request item.
	 * 
	 * @var int
	 */
	public $amount;

	/**
	 * The columns available in the database. Used to help 
	 * build our orderby clause.
	 * 
	 * @var array
	 */
	public static $columns = [
		'id',
		'item_name',
		'item_desc',
		'date_created',
		'date_modified',
		'amount',
	];

	/**
	 * Constructor.
	 */
	public function __construct($id = 0)
	{

		if (!empty($id)) {
			$this->id = (int) $id;
			$this->populate($id);
		}

	}

	/**
	 * Populate
	 * 
	 * Populates object data associated with the given ID.
	 * 
	 * @param int $id The requests ID.
	 */
	public function populate($id = 0)
	{

		$items = $this->get_request_items(
			array(
				'where' => array(
					'id' => $id
				)
			)
		);

		$fetched_items = !empty($items) ? current($items) : array();

		if (!empty($fetched_items) && is_array($fetched_items)) {
			$this->item_name = $fetched_items['item_name'];
			$this->item_desc = $fetched_items['item_desc'];
			$this->amount = $fetched_items['amount'];
			$this->date_created = $fetched_items['date_created'];
			$this->date_modified = $fetched_items['date_modified'];
		}
	}


	/**
	 * Save
	 * 
	 * Handles saving data to the database using our static
	 * _insert method.
	 * 
	 * @return bool|int False on failure, request item ID on success.
	 */
	public function save() {

		$retval = false;

		$insert = self::get_query_clauses([
			'item_name' => $this->item_name,
			'item_desc' => $this->item_desc,
			'date_created' => $this->date_created,
			'date_modified' => $this->date_modified,
			'amount' => $this->amount,
		]);

		$result = self::_insert($insert['data'], $insert['format']);

		if (!empty($result)) {

			global $wpdb;

			if (empty($this->id)) {
				$this->id = $wpdb->insert_id;
			}

			do_action('cb_requests_after_save', $insert);

			$retval = $this->id;

		}

		return $retval;
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
	 *         'id' => 4
	 *     );
	 *
	 * This will be converted to:
	 *
	 *     array(
	 *         'data' => array(
	 *             'id' => 4
	 *         ),
	 *         'format' => array(
	 *             '%d'
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
	protected static function get_query_clauses($args = array())
	{
		$where_clauses = array(
			'data' => array(),
			'format' => array(),
		);

		if (!empty($args['id'])) {
			$where_clauses['data']['id'] = $args['id'];
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

		if (isset($args['item_name'])) {
			$where_clauses['data']['item_name'] = $args['item_name'];
			$where_clauses['format'][] = '%s';
		}

		if (isset($args['item_desc'])) {
			$where_clauses['data']['item_desc'] = $args['item_desc'];
			$where_clauses['format'][] = '%s';
		}

		if (isset($args['amount'])) {
			$where_clauses['data']['amount'] = $args['amount'];
			$where_clauses['format'][] = '%d';
		}

		return $where_clauses;
	}

	/**
	 * _insert
	 * 
	 * Handles the actual insertion into the database.
	 * 
	 * @return int|bool The inserted ID on success, false on failure.
	 */
	protected static function _insert($data = array(), $data_format = array())
	{
		global $wpdb;
		$cb = Confetti_Bits();
		return $wpdb->insert($cb->requests->table_name_items, $data, $data_format);
	}

	/**
	 * Update requests entry.
	 *
	 *
	 * @see wpdb::update() for further description of paramater formats.
	 *
	 * @param array $data         Array of requests data to update, passed to
	 *                            {@link wpdb::update()}. Accepts any property of a
	 *                            Confetti_Bits_Requests_Requests object.
	 * @param array $where        The WHERE params as passed to wpdb::update().
	 *                            Typically consists of array( 'ID' => $id ) to specify the ID
	 *                            of the item being updated. See {@link wpdb::update()}.
	 * @param array $data_format  See {@link wpdb::insert()}.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 */
	protected static function _update($data = array(), $where = array(), $data_format = array(), $where_format = array())
	{
		global $wpdb;

		$retval = $wpdb->update(
			Confetti_Bits()->requests->table_name_items,
			$data,
			$where,
			$data_format,
			$where_format
		);

		do_action('cb_requests_after_update', $data);
		return $retval;
	}

	/**
	 * Update status entry.
	 *
	 *
	 * @param array $update_args Associative array of fields to update,
	 *                           and the values to update them to. Of the format
	 *                           array( 'applicant_id' => 4, 'component_action' => 'cb_requests', ).
	 * @param array $where_args  Associative array of columns/values, to
	 *                           determine which rows should be updated. Of the format
	 *                           array( 'item_id' => 7, 'component_action' => 'cb_requests', ).
	 * @return int|false Number of rows updated on success, false on failure.
	 */
	public static function update($update_args = array(), $where_args = array())
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
	 * Delete requests entry.
	 *
	 *
	 * @see wpdb::update() for further description of paramater formats.
	 *
	 * @param array $where        Array of WHERE clauses to filter by, passed to
	 *                            {@link wpdb::delete()}. Accepts any property of a
	 *                            CB_Requests_Requests object.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 */
	protected static function _delete($where = [], $where_format = []) {

		global $wpdb;
		$cb = Confetti_Bits();

		return $wpdb->delete($cb->requests->table_name_items, $where, $where_format);

	}

	/**
	 * Deletes rows from the database. 
	 * 
	 * Careful there, bucko. It's dangerous round these parts.
	 * 
	 * @param array $where_args An array of key-value pairs that gets passed
	 * 							to self::get_query_clauses()
	 * 
	 * @return int|false The number of rows affected, or false on failure.
	 * 
	 * @since 2.3.0
	 */
	public function delete( $where_args = [] ) {

		$where = self::get_query_clauses( $where_args );
		
		return self::_delete( $where['data'], $where['format'] );

	}

	/**
	 * get_requests
	 * 
	 * Handles retrieving data from the database. Nice and clean!
	 * 
	 * @param array $args An array of stuff to get! { 
	 *   @type string $select The database column to get
	 *   @type array $where A selection of key-value pairs that 
	 *         get evaluated by another method. See self::get_where_sql()
	 * 
	 * @TODO: Finish documenting this (sweat emoji)
	 * }
	 */
	public function get_request_items($args = array())
	{

		global $wpdb;
		$cb = Confetti_Bits();

		$r = wp_parse_args(
			$args,
			array(
				'select' => '*',
				'where' => [],
				'orderby' => [],
				'pagination' => [],
				'group' => '',
			)
		);

		$select = (is_array($r['select'])) ? implode(', ', $r['select']) : $r['select'];
		$select_sql = "SELECT {$select}";
		$from_sql = "FROM {$cb->requests->table_name_items}";
		$where_sql = self::get_where_sql($r['where']);
		$orderby_sql = !empty($r['orderby']) ? self::get_orderby_sql($r['orderby']) : '';
		$group_sql = (!empty($r['group'])) ? "GROUP BY {$r['group']}" : '';
		$pagination_sql = self::get_paged_sql($r['pagination']);

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$orderby_sql} {$pagination_sql}";

		return $wpdb->get_results($sql, 'ARRAY_A');
	}

	/**
	 * Assembles a date query into SQL for use in a WHERE clause.
	 * 
	 * @param array $date_query An array of date query clauses.
	 * @return string The SQL WHERE clause for the query.
	 * @see WP_Date_Query
	 * @since 2.3.0
	 */
	public static function get_date_query_sql($date_query = array())
	{

		$sql = '';
		$columns = array('date_created', 'date_modified');
		$column = !empty($date_query['column']) && in_array($date_query['column'], $columns) ?
			$date_query['column'] : 'date_modified';

		$date_query = new CB_Core_Date_Query($date_query, $column);
		$sql = preg_replace('/^\sAND/', '', $date_query->get_sql());

		return $sql;

	}

	/**
	 * Get Orderby SQL
	 * 
	 * Checks against the columns available and order
	 * arguments, then spits out usable SQL if everything
	 * looks okay.
	 * 
	 * @return string The ORDER BY clause of an SQL query.
	 */
	public static function get_orderby_sql($args = [])
	{

		$sql = '';

		if (empty($args)) {
			return $sql;
		}

		$valid_sql = array_merge(self::$columns, ['DESC', 'ASC', 'calculated_total']);

		$r = wp_parse_args($args, [
			'column' => 'id',
			'order' => 'DESC',
		]);

		if (!in_array(strtolower($r['column']), $valid_sql)) {
			return $sql;
		}

		if (!in_array(strtoupper($r['order']), $valid_sql)) {
			return $sql;
		}

		$sql = sprintf("ORDER BY %s %s", $r['column'], $r['order']);

		return $sql;
	}

	/**
	 * Assemble the LIMIT clause of a get() SQL statement.
	 *
	 * Used by CB_Requests_Requests::get_requests() to create its LIMIT clause.
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
	protected static function get_paged_sql($args = array())
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
	 * Used by CB_Requests_Requests::get_requests() to create its WHERE clause.
	 * 
	 * 
	 * @param	array	$args { 
	 *     Optional array of arguments. 
	 *     
	 *     @type	int		$id				One or more request IDs.
	 *     @type	int		$applicant_id	One or more applicant IDs.
	 *     @type	int		$admin_id		One or more admin IDs.
	 *     @type	string	$status			One or more request statuses.
	 *     @type	string $component_name	One or more component names.
	 *     @type	string $component_action	One or more component actions.
	 *     @type    request_item_id	One or more request item IDs.
	 *     @type    item_id	One or more item IDs.
	 *     @type    secondary_item_id	One or more secondary item IDs.
	 *     @type    string $date_query	A date query to restrict the result set by.
	 * }
	 * 
	 * @return string $retval WHERE clause.
	 * 
	 * @since 2.3.0
	 */
	protected static function get_where_sql($args = array())
	{
		global $wpdb;
		$where_conditions = array();
		$where = '';

		if (!empty($args['id'])) {
			$id_in = implode(',', wp_parse_id_list($args['id']));
			$where_conditions['id'] = "id IN ({$id_in})";
		}

		if ( !empty( $args['amount'] ) ) {
			$amt_in = implode( ',', wp_parse_id_list( $args['amount'] ) );
			$where_conditions['amount'] = "amount IN ({$amt_in})";
		}

		if ( !empty( $args['item_name'] ) ) {
			if (!is_array($args['item_name'])) {
				$item_name = explode(',', $args['item_name']);
			} else {
				$item_name = $args['item_name'];
			}
			$in_clean = array();
			foreach ($item_name as $in) {
				$in_clean[] = $wpdb->prepare('%s', $in);
			}
			$in_in = implode(',', $in_clean);
			$where_conditions['item_name'] = "item_name IN ({$in_in})";
		}

		if ( !empty( $args['item_desc'] ) ) {
			$item_desc = $args['item_desc'];
			$i_desc_clean = $wpdb->prepare('%s', $item_desc);
			$where_conditions['item_desc'] = "item_desc LIKE %{$i_desc_clean}%";
		}

		if (!empty($args['date_query'])) {
			$where_conditions['date_query'] = self::get_date_query_sql($args['date_query']);
		}

		if (!empty($where_conditions)) {
			$where = 'WHERE ' . implode(' AND ', $where_conditions);
		}

		return $where;

	}

}