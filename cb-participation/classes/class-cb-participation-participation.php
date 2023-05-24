<?php
/**
 * Confetti Bits Participation Loader.
 * A component that allows leaders to send bits to users and for users to send bits to each other.
 * @package Confetti_Bits 
 * @since Confetti Bits 2.0.0 
 */

defined( 'ABSPATH' ) || exit;

class CB_Participation_Participation {

	/**
	 * The ID of the participation entry.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The ID of the main item or user associated with the participation entry.
	 * We usually use the applicant_id for this.
	 * Used for BuddyBoss's Notifications API.
	 *
	 * @var int
	 */
	public $item_id;

	/**
	 * The ID of the secondary item associated with the participation entry.
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
	 * The type of event associated with the entry.
	 *
	 * @var string
	 */
	public $event_type;

	/**
	 * The date of the event associated with the entry.
	 *
	 * @var datetime
	 */
	public $event_date;

	/**
	 * The date of the event associated with the entry.
	 *
	 * @var string
	 */
	public $event_note;

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
	 * The filepath to the media associated with the entry.
	 *
	 * @var array
	 */
	public $media_filepath;

	/**
	 * The transaction_id assigned to the the entry.
	 * Updated after a transaction has been created, to allow
	 * the transaction to be tied to a specific participation
	 * event.
	 *
	 * @var int
	 */
	public $transaction_id;

	public function __construct( $id = 0 ) {

		$this->errors	= new WP_Error();
		$this->month	= date_format( date_create(), 'm' );
		$this->year		= date_format( date_create(), 'Y' );

		if ( ! empty( $id ) ) {
			$this->id = (int) $id;
			$this->populate( $id );
		}

	}

	public function populate( $id = 0 ) {

		$participation = $this->get_participation(
			array(
				'where' 	=> array(
					'id'	=> $id
				)
			)
		);

		$fetched_participation = ! empty( $participation ) ? current( $participation ) : array();

		if ( ! empty( $fetched_participation ) && is_array( $fetched_participation ) ) {	
			$this->item_id				= $fetched_participation['item_id'];
			$this->secondary_item_id	= $fetched_participation['secondary_item_id'];
			$this->applicant_id			= $fetched_participation['applicant_id'];
			$this->admin_id				= $fetched_participation['admin_id'];
			$this->date_created			= $fetched_participation['date_created'];
			$this->date_modified		= $fetched_participation['date_modified'];
			$this->event_type			= $fetched_participation['event_type'];
			$this->event_date			= $fetched_participation['event_date'];
			$this->event_note			= $fetched_participation['event_note'];
			$this->component_name		= $fetched_participation['component_name'];
			$this->component_action		= $fetched_participation['component_action'];
			$this->status				= $fetched_participation['status'];
			$this->media_filepath		= $fetched_participation['media_filepath'];
			$this->transaction_id		= $fetched_participation['transaction_id'];
		}
	}


	public function save() {

		$retval = false;

		$data = array (
			'item_id'			=> $this->item_id,
			'secondary_item_id'	=> $this->secondary_item_id,
			'applicant_id'		=> $this->applicant_id,
			'admin_id'			=> $this->admin_id,
			'date_created'		=> $this->date_created,
			'date_modified'		=> $this->date_modified,
			'event_type'		=> $this->event_type,
			'event_date'		=> $this->event_date,
			'event_note'		=> $this->event_note,
			'component_name'	=> $this->component_name,
			'component_action'	=> $this->component_action,
			'status'			=> $this->status,
			'media_filepath'	=> $this->media_filepath,
			'transaction_id'	=> $this->transaction_id
		);

		$data_format = array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' );
		$result = self::_insert( $data, $data_format );
		if ( ! empty( $result ) && ! is_wp_error( $result ) ) {

			global $wpdb;

			if ( empty( $this->id ) ) {
				$this->id = $wpdb->insert_id;
			}

			do_action( 'cb_participation_after_save', $data );

			$retval = $this->id;

		} else if ( is_wp_error( $result ) ) {
			$retval = $result;
		}

		return $retval;
	}



	protected static function convert_orderby_to_order_by_term( $orderby ) {
		$order_by_term = '';

		switch ( $orderby ) {
			case 'id':
				$order_by_term = 'id';
				break;
			case 'applicant_id':
				$order_by_term = 'applicant_id';
				break;
			case 'date_created':
			default:
				$order_by_term = 'date_created';
				break;
		}

		return $order_by_term;
	}

	/**
	 * Update participation status. Uses our static _update method.
	 * 
	 * @param	array	$args	The arguments for the update. Accepts all attributes
	 * 							of the Confetti_Bits_Participation_Participation object. {  
	 *   
	 * 		@type	int		$item_id				The item_id of the object. 
	 * 												Usually the admin_id
	 * 		@type	int		$secondary_item_id		The secondary_item_id of the object. 
	 * 												Usually the applicant_id
	 * 		@type	int		$applicant_id			The applicant_id of the object.
	 * 		@type	int		$admin_id				The admin_id of the object.
	 * 		@type	string	$date_created			The datetime the object was put in the database
	 * 		@type	string	$date_modified			The datetime for the most recent modification
	 * 		@type	string	$event_type				The event_type of the object
	 * 		@type	string	$status					The current status of the object
	 * 		@type	string	$media_filepath			The filepath where the media objects are
	 * }
	 * 		
	 * 
	 */
	public function update_participation_request_status() {

		$data = array (
			'admin_id'			=> $this->admin_id,
			'applicant_id'		=> $this->applicant_id,
			'date_modified' 	=> $this->date_modified,
			'component_action'	=> $this->component_action,
			'event_note'		=> $this->event_note,
			'status' 			=> $this->status,
		);

		$where = array(
			'id'				=> $this->id
		);

		$data_format = array( '%d', '%d', '%s', '%s', '%s', '%s' );
		$where_format = array( '%d' );

		return self::_update( 
			$data, 
			$where,
			$data_format,
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
	protected static function get_query_clauses( $args = array() ) {
		$where_clauses = array(
			'data'   => array(),
			'format' => array(),
		);

		if ( ! empty( $args['id'] ) ) {
			$where_clauses['data']['id'] = absint( $args['id'] );
			$where_clauses['format'][]   = '%d';
		}

		if ( ! empty( $args['applicant_id'] ) ) {
			$where_clauses['data']['applicant_id'] = absint( $args['applicant_id'] );
			$where_clauses['format'][]        = '%d';
		}

		if ( ! empty( $args['admin_id'] ) ) {
			$where_clauses['data']['admin_id'] = absint( $args['admin_id'] );
			$where_clauses['format'][]        = '%d';
		}

		if ( ! empty( $args['item_id'] ) ) {
			$where_clauses['data']['item_id'] = absint( $args['item_id'] );
			$where_clauses['format'][]        = '%d';
		}

		if ( ! empty( $args['secondary_item_id'] ) ) {
			$where_clauses['data']['secondary_item_id'] = absint( $args['secondary_item_id'] );
			$where_clauses['format'][]                  = '%d';
		}

		if ( ! empty( $args['event_type'] ) ) {
			$where_clauses['data']['event_type'] = $args['event_type'];
			$where_clauses['format'][]               = '%s';
		}

		if ( ! empty( $args['event_date'] ) ) {
			$where_clauses['data']['event_date'] = $args['event_date'];
			$where_clauses['format'][]               = '%s';
		}

		if ( ! empty( $args['event_note'] ) ) {
			$where_clauses['data']['event_note'] = $args['event_note'];
			$where_clauses['format'][]               = '%s';
		}

		if ( ! empty( $args['date_modified'] ) ) {
			$where_clauses['data']['date_modified'] = $args['date_modified'];
			$where_clauses['format'][]               = '%s';
		}

		if ( ! empty( $args['component_name'] ) ) {
			$where_clauses['data']['component_name'] = $args['component_name'];
			$where_clauses['format'][]               = '%s';
		}

		if ( ! empty( $args['component_action'] ) ) {
			$where_clauses['data']['component_action'] = $args['component_action'];
			$where_clauses['format'][]                 = '%s';
		}

		if ( isset( $args['status'] ) ) {
			$where_clauses['data']['status'] = $args['status'];
			$where_clauses['format'][]       = '%s';
		}

		if ( isset( $args['media_filepath'] ) ) {
			$where_clauses['data']['media_filepath'] = $args['media_filepath'];
			$where_clauses['format'][]       = '%s';
		}

		if ( isset( $args['transaction_id'] ) ) {
			$where_clauses['data']['transaction_id'] = $args['transaction_id'];
			$where_clauses['format'][]       = '%d';
		}

		return $where_clauses;
	}

	protected static function _insert( $data = array(), $data_format = array() ) {
		global $wpdb;
		$cb = Confetti_Bits();
		return $wpdb->insert( $cb->participation->table_name, $data, $data_format );
	}

	/**
	 * Update participation entry.
	 *
	 *
	 * @see wpdb::update() for further description of paramater formats.
	 *
	 * @param array $data         Array of participation data to update, passed to
	 *                            {@link wpdb::update()}. Accepts any property of a
	 *                            Confetti_Bits_Participation_Participation object.
	 * @param array $where        The WHERE params as passed to wpdb::update().
	 *                            Typically consists of array( 'ID' => $id ) to specify the ID
	 *                            of the item being updated. See {@link wpdb::update()}.
	 * @param array $data_format  See {@link wpdb::insert()}.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 */
	protected static function _update( $data = array(), $where = array(), $data_format = array(), $where_format = array() ) {
		global $wpdb;
				
		$retval = $wpdb->update( 
			Confetti_Bits()->participation->table_name, 
			$data, $where, 
			$data_format, $where_format 
		);
		
		do_action( 'cb_participation_after_update', $data );
		return $retval;
	}

	/**
	 * Update status entry.
	 *
	 *
	 * @param array $update_args Associative array of fields to update,
	 *                           and the values to update them to. Of the format
	 *                           array( 'applicant_id' => 4, 'component_action' => 'cb_participation', ).
	 * @param array $where_args  Associative array of columns/values, to
	 *                           determine which rows should be updated. Of the format
	 *                           array( 'item_id' => 7, 'component_action' => 'cb_participation', ).
	 * @return int|false Number of rows updated on success, false on failure.
	 */
	public static function update( $update_args = array(), $where_args = array() ) {
		$update = self::get_query_clauses( $update_args );
		$where  = self::get_query_clauses( $where_args );

		return self::_update(
			$update['data'],
			$where['data'],
			$update['format'],
			$where['format']
		);
	}

	/**
	 * Delete participation entry.
	 *
	 *
	 * @see wpdb::update() for further description of paramater formats.
	 *
	 * @param array $where        Array of WHERE clauses to filter by, passed to
	 *                            {@link wpdb::delete()}. Accepts any property of a
	 *                            BP_Notification_Notification object.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 */
	protected static function _delete( $where = array(), $where_format = array() ) {

		global $wpdb;
		$cb = Confetti_Bits();

		$where_sql = self::get_where_sql( $where );

		$participation = $wpdb->get_results( "SELECT * FROM {$cb->participation->table_name} {$where_sql}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $wpdb->delete( $cb->participation->table_name, $where, $where_format );

	}

	public function get_participation( $args = array() ) {

		global $wpdb;
		$cb = Confetti_Bits();

		$r = wp_parse_args( 
			$args, 
			array(
				'select'		=> '*',
				'where'			=> array(),
				'orderby'		=> '',
				'pagination'	=> array(),
				'group'			=> '',
			)
		);

		$select = ( is_array( $r['select'] ) ) ? implode( ', ', $r['select'] ) : $r['select'];
		$select_sql = "SELECT {$select}";
		$from_sql = "FROM {$cb->participation->table_name} ";
		$where_sql = self::get_where_sql( $r['where'], $select_sql, $from_sql );
		$orderby_sql = ( ! empty( $r['orderby'] ) ) ? "ORDER BY {$r['orderby']} DESC" : '';
		$group_sql = ( ! empty( $r['group'] ) ) ? "GROUP BY {$r['group']}" : '';
		$pagination_sql = self::get_paged_sql( $r['pagination'] );

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$orderby_sql} {$pagination_sql}";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public static function get_date_query_sql( $date_query = array() ) {

		$sql = '';
		$columns = array( 'date_created', 'date_modified', 'event_date' );
		$column = ! empty( $date_query['column'] ) && in_array( $date_query['column'], $columns ) ? 
			$date_query['column'] : 'event_date';

		$date_query = new CB_Core_Date_Query( $date_query, $column );
		$sql        = preg_replace( '/^\sAND/', '', $date_query->get_sql() );

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

	protected static function get_where_sql( $args = array(), $select_sql = '', $from_sql = '', $join_sql = '', $meta_query_sql = '' ) {
		global $wpdb;
		$where_conditions = array();
		$where            = '';

		if ( ! empty( $args['id'] ) ) {
			$id_in                  = implode( ',', wp_parse_id_list( $args['id'] ) );
			$where_conditions['id'] = "id IN ({$id_in})";
		}

		if ( ! empty( $args['applicant_id'] ) ) {
			$applicant_id_in                  = implode( ',', wp_parse_id_list( $args['applicant_id'] ) );
			$where_conditions['applicant_id'] = "applicant_id IN ({$applicant_id_in})";
		}

		if ( ! empty( $args['admin_id'] ) ) {
			$admin_id_in                  = implode( ',', wp_parse_id_list( $args['admin_id'] ) );
			$where_conditions['admin_id'] = "admin_id IN ({$admin_id_in})";
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

		if ( ! empty( $args['event_type'] ) ) {
			$event_types	= explode( ',', $args['event_type'] );

			$event_type_clean = array();
			foreach ( $event_types as $event_type ) {
				$event_type_clean[] = $wpdb->prepare( '%s', $event_type );
			}

			$event_type_in = implode( ',', $event_type_clean );

			$where_conditions['event_type'] = "event_type LIKE ({$event_type_in})";
		}

		if ( ! empty( $args['event_note'] ) ) {
			$event_notes	= explode( ',', $args['event_note'] );

			$event_note_clean = array();
			foreach ( $event_notes as $event_note ) {
				$event_note_clean[] = $wpdb->prepare( '%s', $event_note );
			}

			$event_note_in = implode( ',', $event_note_clean );

			$where_conditions['event_note'] = "event_note LIKE ({$event_note_in})";
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
			if ( ! is_array( $args['component_action'] ) ) {
				$component_actions = explode( ',', $args['component_action'] );
			} else {
				$component_actions = $args['component_action'];
			}
			$ca_clean = array();
			foreach ( $component_actions as $ca ) {
				$ca_clean[] = $wpdb->prepare( '%s', $ca );
			}
			$ca_in = implode( ',', $ca_clean );
			$where_conditions['component_action'] = "component_action IN ({$ca_in})";
		}

		if ( ! empty( $args['status'] ) ) {
			if ( 'all' == $args['status'] ) {
				$args['status'] = array( 'approved', 'denied', 'new' );
			}
			if ( ! is_array( $args['status'] ) ) {
				$statuses = explode( ',', $args['status'] );
			} else {
				$statuses = $args['status'];
			}
			$s_clean = array();
			foreach ( $statuses as $s ) {
				$s_clean[] = $wpdb->prepare( '%s', $s );
			}
			$s_in = implode( ',', $s_clean );
			$where_conditions['status'] = "status IN ({$s_in})";
		}

		if ( ! empty( $args['media_filepath'] ) ) {
			if ( ! is_array( $args['media_filepath'] ) ) {
				$media_filepaths = explode( ',', $args['media_filepath'] );
			} else {
				$media_filepaths = $args['media_filepath'];
			}
			$fp_clean = array();
			foreach ( $media_filepaths as $fp ) {
				$fp_clean[] = $wpdb->prepare( '%s', $fp );
			}
			$fp_in = implode( ',', $fp_clean );
			$where_conditions['media_filepath'] = "media_filepath IN ({$fp_in})";
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

		if ( ! empty( $args['date_query'] ) ) {
			$where_conditions['date_query'] = self::get_date_query_sql( $args['date_query'] );
		}

		if ( ! empty( $meta_query_sql['where'] ) ) {
			$where_conditions['meta_query'] = $meta_query_sql['where'];
		}

		$where_conditions = apply_filters( 'cb_participation_get_where_conditions', $where_conditions, $args, $select_sql, $from_sql, $join_sql, $meta_query_sql );

		if ( ! empty( $where_conditions ) ) {
			$where = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		return $where;

	}

	protected static function strip_leading_and( $s ) {
		return preg_replace( '/^\s*AND\s*/', '', $s );
	}

}