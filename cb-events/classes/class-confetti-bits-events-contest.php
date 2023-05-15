<?php
/**
 * Confetti Bits Events Contest Class
 * Handles the creation and management of contests.
 *
 * @package Confetti Bits
 * @subpackage Events
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Confetti_Bits_Events_Contest
{

	/**
	 * The ID of the contest entry.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * The ID of the event that the contest is for.
	 *
	 * @var int
	 */
	private $event_id;

	/**
	 * The placement of the user in the contest.
	 *
	 * @var int
	 */
	private $placement;

	/**
	 * The amount that the user will receive for the given placement in the contest.
	 *
	 * @var int
	 */
	private $amount;

	/**
	 * The ID of the user that will receive the amount for the given placement in the contest.
	 *
	 * @var int
	 */
	private $recipient_id;

	/**
	 * The date the contest was created.
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

	public function __construct($id = 0)
	{

		$this->errors = new WP_Error();

		if (!empty($id)) {
			$this->id = (int) $id;
			$this->populate($id);
		}

	}

	public function populate($id = 0)
	{

		$contest = $this->get_contest(
			array(
				'where' => array(
					'id' => $id
				)
			)
		);

		$fetched_contest = !empty($contest) ? current($contest) : array();

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
	 * @return int|WP_Error The ID of the contest if successful, WP_Error otherwise.
	 * @since 1.0.0
	 * @access public
	 * @uses Confetti_Bits_Contests_Contest::_insert()
	 */
	public function save()
	{

		$retval = false;

		$data = array(
			'event_id' => $this->event_id,
			'placement' => $this->placement,
			'amount' => $this->amount,
			'recipient_id' => $this->recipient_id,
			'date_created' => $this->date_created,
			'date_modified' => $this->date_modified,
		);

		$data_format = array('%d', '%d', '%d', '%d', '%s', '%s');
		$result = self::_insert($data, $data_format);
		if (!empty($result) && !is_wp_error($result)) {

			global $wpdb;

			if (empty($this->id)) {
				$this->id = $wpdb->insert_id;
			}

			do_action('cb_contests_after_save', $data);

			$retval = $this->id;

		} else if (is_wp_error($result)) {
			$retval = $result;
		}

		return $retval;

	}

	/**
	 * _insert
	 * Insert contest entry into the database.
	 * @param array $data Array of contest data to insert, passed to
	 * 						  {@link wpdb::insert()}. Accepts any property of a
	 * 						  Confetti_Bits_Events_Contest object.
	 * @param array $data_format  See {@link wpdb::insert()}. Default array( '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' ).
	 * @return int|WP_Error The ID of the contest if successful, WP_Error otherwise.
	 * @since 1.0.0
	 * @access protected
	 * @uses wpdb::insert()
	 */
	protected static function _insert($data = array(), $data_format = array())
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
	 * 						  Typically consists of array( 'ID' => $id ) to specify the
	 * 						  contest entry ID to update.
	 * @param array $data_format  See {@link wpdb::update()}. Default array( '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' ).
	 * @param array $where_format  See {@link wpdb::update()}. Default is array( '%d' ).
	 * @uses Confetti_Bits_Events_Contest::_update()
	 * @return int|WP_Error The number of rows updated, or WP_Error otherwise.
	 * @since 1.0.0
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
	 * Update contest entry.
	 *
	 * @see wpdb::update() for further description of paramater formats.
	 *
	 * @param array $data  Array of contest data to update, passed to
	 *                            {@link wpdb::update()}. Accepts any property of a
	 *                            Confetti_Bits_Events_Contest object.
	 * @param array $where  The WHERE params as passed to wpdb::update().
	 *                            Typically consists of array( 'ID' => $id )
	 * 							  to specify the ID of the item being updated.
	 * 							  See {@link wpdb::update()}.
	 * @param array $data_format  See {@link wpdb::update()}.
	 * @param array $where_format See {@link wpdb::update()}.
	 * @return int|false The number of rows updated, or false on error.
	 */
	protected static function _update($data = array(), $where = array(), $data_format = array(), $where_format = array())
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
	 *                           array( 'item_id' => 7, 'component_action' => 'cb_contest', ).
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
	 * Delete contest entry.
	 * @param array $where  The WHERE params as passed to wpdb::delete().
	 * 						  Typically consists of array( 'ID' => $id ) to specify the ID
	 * 						  of the item being deleted. See {@link wpdb::delete()}.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 * @since 1.0.0
	 * @access protected
	 * @uses wpdb::delete()
	 * @uses Confetti_Bits_Contest_Contest::get_where_sql()
	 *
	 */
	protected static function _delete($where = array(), $where_format = array())
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

		if (!empty($args['event_id'])) {
			$where_clauses['data']['event_id'] = $args['event_id'];
			$where_clauses['format'][] = '%d';
		}

		if ( !empty($args['placement'])) {
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
	 * @param array $args
	 * @return array
	 * @since 1.0.0
	 * @access public
	 * @uses Confetti_Bits_Contest_Contest::get_where_sql()
	 * @uses Confetti_Bits_Contest_Contest::get_paged_sql()
	 *
	 */
	public function get_contest($args = array())
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
		$from_sql = "FROM {$wpdb->prefix}confetti_bits_contests ";
		$where_sql = self::get_where_sql($r['where']);
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
		$columns = array('date_created', 'date_modified', 'contest_date');
		$column = !empty($date_query['column']) && in_array($date_query['column'], $columns) ?
			$date_query['column'] : 'contest_date';

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
	 * @return	string					WHERE SQL.
	 * @since	1.0.0
	 * @access	protected
	 * @static
	 *
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