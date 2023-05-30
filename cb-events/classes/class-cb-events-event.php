<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * CB Events Event
 *
 * Handles the creation and management of events.
 *
 * @package ConfettiBits
 * @subpackage Events
 * @since 2.3.0
 */
class CB_Events_Event {

	/**
	 * The ID of the event entry.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * The title of the event.
	 *
	 * @var string
	 */
	private $event_title;

	/**
	 * The description of the event.
	 *
	 * @var string
	 */
	private $event_desc;

	/**
	 * The date and time that the event starts.
	 *
	 * @var datetime
	 */
	private $event_date_start;

	/**
	 * The date and time that the event ends.
	 *
	 * @var datetime
	 */
	private $event_date_end;

	/**
	 * The amount that the user will receive for participating in the event.
	 *
	 * @var int
	 */
	private $participation_amount;

	/**
	 * The ID of the user who created the event.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * The date the event was created.
	 *
	 * @var string
	 */
	private $date_created;

	/**
	 * The date of the last time the entry was modified.
	 *
	 * @var string
	 */
	private $date_modified;

	/**
	 * Constructor
	 *
	 * If an ID is supplied, populate information about that event.
	 */
	public function __construct($id = 0)
	{

		$this->errors = new WP_Error();

		if (!empty($id)) {
			$this->id = (int) $id;
			$this->populate($id);
		}

	}

	/**
	 * Populate
	 *
	 * Get information for specific event.
	 * @uses Confetti_Bits_Events_Event::get_event()
	 */
	public function populate($id = 0)
	{

		$event = $this->get_event(
			array(
				'where' => array(
					'id' => $id
				)
			)
		);

		$fetched_event = !empty($event) ? current($event) : array();

		if (!empty($fetched_event) && is_array($fetched_event)) {
			$this->event_title = $fetched_event['event_title'];
			$this->event_desc = $fetched_event['event_desc'];
			$this->event_date_start = $fetched_event['event_date_start'];
			$this->event_date_end = $fetched_event['event_date_end'];
			$this->participation_amount = $fetched_event['participation_amount'];
			$this->user_id = $fetched_event['user_id'];
			$this->date_created = $fetched_event['date_created'];
			$this->date_modified = $fetched_event['date_modified'];
		}
	}

	/**
	 * Save
	 * Save the event to the database.
	 *
	 * @return int|WP_Error The ID of the event if successful, WP_Error otherwise.
	 * @since 1.0.0
	 * @access public
	 * @uses Confetti_Bits_Events_Event::_insert()
	 */
	public function save()
	{

		$retval = false;

		$data = array(
			'event_title' => $this->event_title,
			'event_desc' => $this->event_desc,
			'event_date_start' => $this->event_date_start,
			'event_date_end' => $this->event_date_end,
			'participation_amount' => $this->participation_amount,
			'user_id' => $this->user_id,
			'date_created' => $this->date_created,
			'date_modified' => $this->date_modified,
		);

		$data_format = array('%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s');
		$result = self::_insert($data, $data_format);
		if (!empty($result) && !is_wp_error($result)) {

			global $wpdb;

			if (empty($this->id)) {
				$this->id = $wpdb->insert_id;
			}

			do_action('cb_events_after_save', $data);

			$retval = $this->id;

		} else if (is_wp_error($result)) {
			$retval = $result;
		}

		return $retval;

	}

	/**
	 * _insert
	 * Insert event entry into the database.
	 * @param array $data Array of event data to insert, passed to
	 * 						  {@link wpdb::insert()}. Accepts any property of a
	 * 						  Confetti_Bits_Event_Event object.
	 * @param array $data_format  See {@link wpdb::insert()}. Default array( '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' ).
	 * @return int|WP_Error The ID of the event if successful, WP_Error otherwise.
	 * @since 1.0.0
	 * @access protected
	 * @uses wpdb::insert()
	 */
	protected static function _insert($data = array(), $data_format = array())
	{
		global $wpdb;
		$cb = Confetti_Bits();
		return $wpdb->insert($cb->events->table_name, $data, $data_format);
	}

	/**
	 * Update
	 * Update the event in the database.
	 *
	 * @param array $update_args Associative array of fields to update,
	 *                           and the values to update them to. Of the format
	 *                           array( 'applicant_id' => 4, 'component_action' => 'cb_event', ).
	 * @param array $where_args  Associative array of columns/values, to
	 *                           determine which rows should be updated. Of the format
	 *                           array( 'item_id' => 7, 'component_action' => 'cb_event', ).
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
	 * Update event entry.
	 *
	 * @see wpdb::update() for further description of paramater formats.
	 *
	 * @param array $data  Array of event data to update, passed to
	 *                            {@link wpdb::update()}. Accepts any property of a
	 *                            Confetti_Bits_Events_Event object.
	 * @param array $where  The WHERE params as passed to wpdb::update().
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
			Confetti_Bits()->events->table_name,
			$data,
			$where,
			$data_format,
			$where_format
		);

		do_action('cb_events_after_update', $data);
		return $retval;
	}

	/**
	 * Delete
	 * Delete the event from the database.
	 *
	 * @param array $where_args  Associative array of columns/values, to
	 *                           determine which rows should be updated. Of the format
	 *                           array( 'item_id' => 7, 'component_action' => 'cb_event', ).
	 * @return int|false Number of rows updated on success, false on failure.
	 */
	public static function delete($where_args = array())
	{
		$where = self::get_query_clauses($where_args);

		return self::_delete(
			$where['data'],
			$where['format']
		);
	}

	/**
	 * Delete event entry.
	 * @param array $where  The WHERE params as passed to wpdb::delete().
	 * 						  Typically consists of array( 'ID' => $id ) to specify the ID
	 * 						  of the item being deleted. See {@link wpdb::delete()}.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 * @since 1.0.0
	 * @access protected
	 * @uses wpdb::delete()
	 * @uses Confetti_Bits_Event_Event::get_where_sql()
	 *
	 */
	protected static function _delete($where = array(), $where_format = array())
	{

		global $wpdb;
		$cb = Confetti_Bits();

		return $wpdb->delete($cb->events->table_name, $where, $where_format);

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
	 *         'user_id' => 4,
	 *         'component_name' => 'groups',
	 *     );
	 *
	 * This will be converted to:
	 *
	 *     array(
	 *         'data' => array(
	 *             'user_id' => 4,
	 *             'component_name' => 'groups',
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
			$where_clauses['data']['id'] = absint($args['id']);
			$where_clauses['format'][] = '%d';
		}

		if (!empty($args['event_title'])) {
			$where_clauses['data']['event_title'] = $args['event_title'];
			$where_clauses['format'][] = '%s';
		}

		if (!empty($args['event_desc'])) {
			$where_clauses['data']['event_desc'] = $args['event_desc'];
			$where_clauses['format'][] = '%s';
		}

		if (!empty($args['participation_amount'])) {
			$where_clauses['data']['participation_amount'] = $args['participation_amount'];
			$where_clauses['format'][] = '%d';
		}

		if (!empty($args['event_date_start'])) {
			$where_clauses['data']['event_date_start'] = $args['event_date_start'];
			$where_clauses['format'][] = '%s';
		}

		if (!empty($args['event_date_end'])) {
			$where_clauses['data']['event_date_end'] = $args['event_date_end'];
			$where_clauses['format'][] = '%s';
		}

		if (!empty($args['user_id'])) {
			$where_clauses['data']['user_id'] = absint($args['user_id']);
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
	 * Get Event
	 * @param array $args
	 * @return array
	 * @since 1.0.0
	 * @access public
	 * @uses Confetti_Bits_Event_Event::get_where_sql()
	 * @uses Confetti_Bits_Event_Event::get_paged_sql()
	 *
	 */
	public function get_event($args = array())
	{

		global $wpdb;
		$cb = Confetti_Bits();

		$r = wp_parse_args(
			$args,
			array(
				'select' => '*',
				'where' => array(),
				'orderby' => '',
				'pagination' => array(),
				'group' => '',
			)
		);

		$select = (is_array($r['select'])) ? implode(', ', $r['select']) : $r['select'];
		$select_sql = "SELECT {$select}";
		$from_sql = "FROM {$cb->event->table_name} n ";
		$where_sql = self::get_where_sql($r['where'], $select_sql, $from_sql);
		$orderby_sql = (!empty($r['orderby'])) ? "ORDER BY {$r['orderby']} DESC" : '';
		$group_sql = (!empty($r['group'])) ? "GROUP BY {$r['group']}" : '';
		$pagination_sql = self::get_paged_sql($r['pagination']);

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$orderby_sql} {$pagination_sql}";

		return $wpdb->get_results($sql, 'ARRAY_A');
	}

	/**
	 * Get Date Query SQL.
	 *
	 * @param	array	$date_query	Date query arguments.
	 * @return	string				Date query SQL.
	 * @since	1.0.0
	 * @access	public
	 * @static
	 * @todo	Refactor to use Confetti_Bits_Core_Date_Query.
	 *
	 */
	public static function get_date_query_sql($date_query = array())
	{

		$sql = '';
		$columns = array('date_created', 'date_modified', 'event_date_start', 'event_date_end');
		$column = !empty($date_query['column']) && in_array($date_query['column'], $columns) ?
			$date_query['column'] : 'event_date_start';

		$date_query = new Confetti_Bits_Core_Date_Query($date_query, $column);
		$sql = preg_replace('/^\sAND/', '', $date_query->get_sql());

		return $sql;

	}

	/**
	 * Assemble the LIMIT clause of a get() SQL statement.
	 *
	 * Used to create a LIMIT clause.
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
	 * Get WHERE SQL.
	 *
	 * @param	array	$args			Arguments.
	 * @param	string	$select_sql		Select SQL.
	 * @param	string	$from_sql		From SQL.
	 * @param	string	$join_sql		Join SQL.
	 * @param	string	$meta_query_sql	Meta query SQL.
	 * @return	string					WHERE SQL.
	 * @since	1.0.0
	 * @access	protected
	 * @static
	 *
	 */
	protected static function get_where_sql($args = array(), $select_sql = '', $from_sql = '', $join_sql = '', $meta_query_sql = '')
	{
		global $wpdb;
		$where_conditions = array();
		$where = '';

		if (!empty($args['id'])) {
			$id_in = implode(',', wp_parse_id_list($args['id']));
			$where_conditions['id'] = "id IN ({$id_in})";
		}

		if (!empty($args['user_id'])) {
			$user_id_in = implode(',', wp_parse_id_list($args['user_id']));
			$where_conditions['user_id'] = "user_id IN ({$user_id_in})";
		}

		if (!empty($args['event_title'])) {
			$event_titles = explode(',', $args['event_title']);

			$event_title_clean = array();
			foreach ($event_titles as $event_title) {
				$event_title_clean[] = $wpdb->prepare('%s', $event_title);
			}

			$event_title_like = implode(',', $event_title_clean);

			$where_conditions['event_title'] = "event_title LIKE %{$event_title_like}%";
		}

		if (!empty($args['event_desc'])) {
			$event_descs = explode(',', $args['event_desc']);

			$event_desc_clean = array();
			foreach ($event_descs as $event_desc) {
				$event_desc_clean[] = $wpdb->prepare('%s', $event_desc);
			}

			$event_desc_like = implode(',', $event_desc_clean);

			$where_conditions['event_desc'] = "event_desc LIKE %{$event_desc_like}%";
		}

		if (!empty($args['date_query'])) {
			$where_conditions['date_query'] = self::get_date_query_sql($args['date_query']);
		}

		if (!empty($meta_query_sql['where'])) {
			$where_conditions['meta_query'] = $meta_query_sql['where'];
		}

		if (!empty($where_conditions)) {
			$where = $args['or'] ? 'WHERE ' . implode(' OR ', $where_conditions) : 'WHERE ' . implode(' AND ', $where_conditions);
		}

		return $where;

	}

}