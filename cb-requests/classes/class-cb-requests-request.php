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
class CB_Requests_Request
{

	/**
	 * The ID of the requests entry.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The ID of the main item or user associated with the requests entry.
	 * We usually use the applicant_id for this.
	 * Used for BuddyBoss's Notifications API.
	 *
	 * @var int
	 */
	public $item_id;

	/**
	 * The ID of the secondary item associated with the requests entry.
	 * We usually use the admin_id for this.
	 * Used for BuddyBoss's Notifications API.
	 *
	 * @var int
	 */
	public $secondary_item_id;

	/**
	 * The ID of the user associated with the entry.
	 *
	 * @var int
	 */
	public $applicant_id;

	/**
	 * The ID of the admin associated with the entry.
	 *
	 * @var int
	 */
	public $admin_id;

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
	 * The component associated with the entry.
	 * Used for BuddyBoss's Notifications API.
	 *
	 * @var string
	 */
	public $component_name;

	/**
	 * The component action associated with the entry.
	 * Used for BuddyBoss's Notifications API.
	 *
	 * @var string
	 */
	public $component_action;

	/**
	 * The last updated status of the entry.
	 *
	 * @var string
	 */
	public $status;

	/**
	 * The request_item_id assigned to the the entry.
	 * Updated after a request has been created, to allow
	 * the request to be tied to a specific request item.
	 *
	 * @var int
	 */
	public $request_item_id;

	/**
	 * The columns available in the database. Used to help 
	 * build our orderby clause.
	 * 
	 * @var array
	 */
	public static $columns = [
		'id',
		'item_id',
		'secondary_item_id',
		'applicant_id',
		'admin_id',
		'date_created',
		'date_modified',
		'component_name',
		'component_action',
		'status',
		'request_item_id'
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

		$requests = $this->get_requests(
			array(
				'where' => array(
					'id' => $id
				)
			)
		);

		$fetched_requests = !empty($requests) ? current($requests) : array();

		if (!empty($fetched_requests) && is_array($fetched_requests)) {
			$this->item_id = $fetched_requests['item_id'];
			$this->secondary_item_id = $fetched_requests['secondary_item_id'];
			$this->applicant_id = $fetched_requests['applicant_id'];
			$this->admin_id = $fetched_requests['admin_id'];
			$this->date_created = $fetched_requests['date_created'];
			$this->date_modified = $fetched_requests['date_modified'];
			$this->component_name = $fetched_requests['component_name'];
			$this->component_action = $fetched_requests['component_action'];
			$this->status = $fetched_requests['status'];
			$this->request_item_id = $fetched_requests['request_item_id'];
		}
	}


	/**
	 * Save
	 * 
	 * Handles saving data to the database using our static
	 * _insert method.
	 * 
	 * @return obj|int WP_Error on failure, requests ID on success.
	 */
	public function save()
	{

		$retval = false;

		$data = [
			'item_id' => $this->item_id,
			'secondary_item_id' => $this->secondary_item_id,
			'applicant_id' => $this->applicant_id,
			'admin_id' => $this->admin_id,
			'date_created' => $this->date_created,
			'date_modified' => $this->date_modified,
			'component_name' => $this->component_name,
			'component_action' => $this->component_action,
			'status' => $this->status,
			'request_item_id' => $this->request_item_id
		];

		$data_format = array('%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d' );
		$result = self::_insert($data, $data_format);
		if (!empty($result)) {

			global $wpdb;

			if (empty($this->id)) {
				$this->id = $wpdb->insert_id;
			}

			do_action('cb_requests_after_save', $data);

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
	 *         'applicant_id' => 4,
	 * 		   'component_action' => 'cb_requests_new'
	 *     );
	 *
	 * This will be converted to:
	 *
	 *     array(
	 *         'data' => array(
	 *             'applicant_id' => 4,
	 *             'component_action' => 'cb_requests_new',
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
	protected static function get_query_clauses($args = array())
	{
		$where_clauses = array(
			'data' => array(),
			'format' => array(),
		);

		if (!empty($args['id'])) {
			$where_clauses['data']['id'] = is_array($args['id']) ? 
				implode(', ', wp_parse_id_list($args['id'])) 
				: intval( $args['id'] );
			$where_clauses['format'][] = '%d';
		}

		if (!empty($args['applicant_id'])) {
			$where_clauses['data']['applicant_id'] = is_array($args['applicant_id']) ?
				implode( ', ', wp_parse_id_list($args['applicant_id'])) 
				: intval($args['applicant_id']);
			$where_clauses['format'][] = '%d';
		}

		if (!empty($args['admin_id'])) {
			$where_clauses['data']['admin_id'] = is_array( $args['admin_id'] ) ? 
				implode( ', ', wp_parse_id_list($args['admin_id']))
				: intval($args['admin_id']);
			$where_clauses['format'][] = '%d';
		}

		if (!empty($args['item_id'])) {
			$where_clauses['data']['item_id'] = is_array( $args['item_id'] ) ? 
				implode( ', ', wp_parse_id_list($args['item_id']))
				: intval($args['item_id']);
			$where_clauses['format'][] = '%d';
		}

		if (!empty($args['secondary_item_id'])) {
			$where_clauses['data']['secondary_item_id'] = is_array($args['secondary_item_id']) ?
				implode( ', ', wp_parse_id_list($args['secondary_item_id']))
				: intval($args['secondary_item_id']);
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

		if (!empty($args['component_name'])) {
			$where_clauses['data']['component_name'] = $args['component_name'];
			$where_clauses['format'][] = '%s';
		}

		if (!empty($args['component_action'])) {
			$where_clauses['data']['component_action'] = $args['component_action'];
			$where_clauses['format'][] = '%s';
		}

		if (isset($args['status'])) {
			$where_clauses['data']['status'] = $args['status'];
			$where_clauses['format'][] = '%s';
		}

		if (!empty($args['request_item_id'])) {
			$where_clauses['data']['request_item_id'] = is_array($args['request_item_id']) ?
				implode(', ', wp_parse_id_list($args['request_item_id']))
				: intval($args['request_item_id']);
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
		return $wpdb->insert($cb->requests->table_name, $data, $data_format);
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
	protected static function _update($data = [], $where = [], $data_format = [], $where_format = [])
	{
		global $wpdb;

		$retval = $wpdb->update(
			Confetti_Bits()->requests->table_name,
			$data,
			$where,
			$data_format,
			$where_format
		);

		do_action('cb_requests_after_update', $data );
		
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
	protected static function _delete($where = array(), $where_format = array())
	{

		global $wpdb;
		$cb = Confetti_Bits();

		$where_sql = self::get_where_sql($where);

		$requests = $wpdb->get_results("SELECT * FROM {$cb->requests->table_name} {$where_sql}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $wpdb->delete($cb->requests->table_name, $where, $where_format);

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
	public function get_requests($args = array())
	{

		global $wpdb;
		$cb = Confetti_Bits();

		$r = wp_parse_args( $args, [
			'select' => '*',
			'where' => [],
			'orderby' => [],
			'pagination' => [],
			'group' => '',
		]);

		$select = (is_array($r['select'])) ? implode(', ', $r['select']) : $r['select'];
		$select_sql = "SELECT {$select}";
		$from_sql = "FROM {$cb->requests->table_name}";
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
			$id_in = is_array($args['id']) ? implode(', ', wp_parse_id_list($args['id'])) : intval( $args['id'] );
			$where_conditions['id'] = "id IN ({$id_in})";
		}

		if (!empty($args['applicant_id'])) {
			$applicant_id_in = implode(',', wp_parse_id_list($args['applicant_id']));
			$where_conditions['applicant_id'] = "applicant_id IN ({$applicant_id_in})";
		}

		if (!empty($args['admin_id'])) {
			$admin_id_in = implode(',', wp_parse_id_list($args['admin_id']));
			$where_conditions['admin_id'] = "admin_id IN ({$admin_id_in})";
		}

		if (!empty($args['item_id'])) {
			$item_id_in = implode(',', wp_parse_id_list($args['item_id']));
			$where_conditions['item_id'] = "item_id IN ({$item_id_in})";
		}

		if (!empty($args['secondary_item_id'])) {
			$secondary_item_id_in = implode(',', wp_parse_id_list($args['secondary_item_id']) );
			$where_conditions['secondary_item_id'] = "secondary_item_id IN ({$secondary_item_id_in})";
		}

		if (!empty($args['component_name'])) {
			if (!is_array($args['component_name'])) {
				$component_names = explode(',', $args['component_name']);
			} else {
				$component_names = $args['component_name'];
			}
			$cn_clean = array();
			foreach ($component_names as $cn) {
				$cn_clean[] = $wpdb->prepare('%s', $cn);
			}
			$cn_in = implode(',', $cn_clean);
			$where_conditions['component_name'] = "component_name IN ({$cn_in})";
		}

		if (!empty($args['component_action'])) {
			if (!is_array($args['component_action'])) {
				$component_actions = explode(',', $args['component_action']);
			} else {
				$component_actions = $args['component_action'];
			}
			$ca_clean = array();
			foreach ($component_actions as $ca) {
				$ca_clean[] = $wpdb->prepare('%s', $ca);
			}
			$ca_in = implode(',', $ca_clean);
			$where_conditions['component_action'] = "component_action IN ({$ca_in})";
		}

		if (!empty($args['status'])) {
			if ('all' == $args['status']) {
				$args['status'] = array('approved', 'denied', 'new');
			}
			if (!is_array($args['status'])) {
				$statuses = explode(',', $args['status']);
			} else {
				$statuses = $args['status'];
			}
			$s_clean = array();
			foreach ($statuses as $s) {
				$s_clean[] = $wpdb->prepare('%s', $s);
			}
			$s_in = implode(',', $s_clean);
			$where_conditions['status'] = "status IN ({$s_in})";
		}

		if ( !empty( $args['request_item_id'] ) ) {
			$request_item_id_in = implode( ',', wp_parse_id_list( $args['request_item_id'] ) );
			$where_conditions['request_item_id'] = "request_item_id IN ({$request_item_id_in})";
		}

		if (!empty($args['excluded_action'])) {
			if (!is_array($args['excluded_action'])) {
				$excluded_action = explode(',', $args['excluded_action']);
			} else {
				$excluded_action = $args['excluded_action'];
			}
			$ca_clean = array();
			foreach ($excluded_action as $ca) {
				$ca_clean[] = $wpdb->prepare('%s', $ca);
			}
			$ca_not_in = implode(',', $ca_clean);
			$where_conditions['excluded_action'] = "component_action NOT IN ({$ca_not_in})";
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