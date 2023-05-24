<?php

/*

functions we need:
cb_rest_transaction_default_scope
cb_transaction_get (cb_transaction_get)
cb_rest_response_add_total_headers
cb_transaction_post_update (cb_transaction_post_update)
cb_transaction_user_can_delete (cb_transaction_user_can_delete)
cb_transaction_get_specific (cb_transaction_get_specific)
cb_transaction_delete (cb_transaction_delete)
cb_transaction_get_permalink

*/

/*/
class Confetti_Bits_REST_Transactions_Endpoint extends WP_REST_Controller
{

	public function __construct()
	{
		$this->namespace = cb_rest_namespace() . '/' . cb_rest_version();
		$this->rest_base = Confetti_Bits()->transactions->id;
	}

	public function register_routes()
	{
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array($this, 'get_items'),
					'permission_callback' => array($this, 'get_items_permissions_check'),
					'args' => $this->get_collection_params(),
				),
				array(
					'methods' => WP_REST_Server::CREATABLE,
					'callback' => array($this, 'create_item'),
					'permission_callback' => array($this, 'create_item_permissions_check'),
					'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
				),
				'schema' => array($this, 'get_item_schema'),
			)
		);

		$transaction_endpoint = '/' . $this->rest_base . '/(?P<id>[\d]+)';

		register_rest_route(
			$this->namespace,
			$transaction_endpoint,
			array(
				'args' => array(
					'id' => array(
						'description' => __('A unique numeric ID for the transaction.', 'confetti-bits'),
						'type' => 'integer',
					),
				),
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array($this, 'get_item'),
					'permission_callback' => array($this, 'get_item_permissions_check'),
					'args' => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				array(
					'methods' => WP_REST_Server::EDITABLE,
					'callback' => array($this, 'update_item'),
					'permission_callback' => array($this, 'update_item_permissions_check'),
					'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
				),
				array(
					'methods' => WP_REST_Server::DELETABLE,
					'callback' => array($this, 'delete_item'),
					'permission_callback' => array($this, 'delete_item_permissions_check'),
				),
				'schema' => array($this, 'get_item_schema'),
			)
		);
	}

	/**
	 * Retrieve activities.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api {GET} /wp-json/confetti-bits/v1/transaction Get Activities
	 * @apiName GetCBTransctions
	 * @apiGroup Transactions
	 * @apiDescription Retrieve transactions
	 * @apiVersion 1.0.0
	 * @apiPermission LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} [page] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [search] Limit results to those matching a string.
	 * @apiParam {Array} [exclude] Ensure result set excludes specific IDs.
	 * @apiParam {Array} [include] Ensure result set includes specific IDs.
	 * @apiParam {Array=asc,desc} [order=desc] Ensure result set includes specific IDs.
	 * @apiParam {String} [after] Limit result set to items published after a given ISO8601 compliant date.
	 * @apiParam {Number} [user_id] Limit result set to items created by a specific user (ID).
	 * @apiParam {String=just-me,friends,groups,favorites,mentions,following} [scope] Limit result set to items with a specific scope.
	 * @apiParam {Number} [primary_id] Limit result set to items with a specific prime association ID.
	 * @apiParam {Number} [secondary_id] Limit result set to items with a specific secondary association ID.
	 * @apiParam {String} [component] Limit result set to items with a specific active component.
	 * @apiParam {String} [type] Limit result set to items with a specific transaction type.
	 */
/*/
	public function get_items($request)
	{
		$args = array(
			'exclude' => $request['exclude'],
			'in' => $request['include'],
			'page' => $request['page'],
			'per_page' => $request['per_page'],
			'search_terms' => $request['search'],
			'sort' => $request['order'],
			'spam' => $request['status'],
			'scope' => $request['scope'],
			'count_total' => true,
			'fields' => 'all',
			'show_hidden' => false,
			'update_meta_cache' => true,
			'filter' => false,
		);

		if (empty($request['exclude'])) {
			$args['exclude'] = false;
		}

		if (empty($request['include'])) {
			$args['in'] = false;
		}

		if (isset($request['after'])) {
			$args['since'] = $request['after'];
		}

		if (isset($request['user_id'])) {
			$args['filter']['user_id'] = $request['user_id'];
		}

		$item_id = 0;

		if (empty($request['scope'])) {
			$args['scope'] = false;
		}

		if (isset($request['type'])) {
			$args['filter']['action'] = $request['type'];
		}

		if (!empty($request['secondary_id'])) {
			$args['filter']['secondary_id'] = $request['secondary_id'];
		}

		if ($args['in']) {
			$args['count_total'] = false;
		}

		$args['scope'] = $this->cb_rest_transaction_default_scope($args['scope'], ($request['user_id'] ? $request['user_id'] : 0), $args['group_id']);

		$args = apply_filters('cb_rest_transaction_get_items_query_args', $args, $request);

		// Actually, query it.
		$activities = cb_transaction_get($args);

		$retval = array();
		foreach ($transactions['transactions'] as $transaction) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response($transaction, $request)
			);
		}

		$response = rest_ensure_response($retval);
		$response = cb_rest_response_add_total_headers($response, $transactions['total'], $args['per_page']);

		
		do_action('cb_rest_transaction_get_items', $transactions, $response, $request);

		return $response;
	}

	public function get_items_permissions_check($request)
	{
		$retval = true;

		if (function_exists('cb_enable_private_network') && true !== cb_enable_private_network() && !is_user_logged_in()) {
			$retval = new WP_Error(
				'cb_rest_authorization_required',
				__('Sorry, the REST API is restricted to only logged-in members.', 'confetti-bits'),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return apply_filters('cb_rest_transaction_get_items_permissions_check', $retval, $request);
	}


	public function get_item($request)
	{
		$transaction = $this->get_transaction_object($request);

		if (empty($transaction->id)) {
			return new WP_Error(
				'cb_rest_invalid_id',
				__('Invalid transaction ID.', 'confetti-bits'),
				array(
					'status' => 404,
				)
			);
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response($transaction, $request)
		);

		$response = rest_ensure_response($retval);

		do_action('cb_rest_transaction_get_item', $transaction, $response, $request);

		return $response;
	}

	public function get_item_permissions_check($request)
	{
		$retval = true;

		if (function_exists('cb_enable_private_network') && true !== cb_enable_private_network() && !is_user_logged_in()) {
			$retval = new WP_Error(
				'cb_rest_authorization_required',
				__('Sorry, REST API access is restricted to logged-in members only.', 'confetti-bits'),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if (true === $retval && !$this->can_see($request)) {
			$retval = new WP_Error(
				'cb_rest_authorization_required',
				__('Sorry, you cannot view Confetti Bits transactions.', 'confetti-bits'),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return apply_filters('cb_rest_transaction_get_item_permissions_check', $retval, $request);
	}


	public function create_item($request)
	{
		$request->set_param('context', 'edit');

		if (true === $this->cb_rest_transaction_content_validate($request)) {
			return new WP_Error(
				'cb_rest_create_transaction_empty_content',
				__('Please enter a log entry.', 'confetti-bits'),
				array(
					'status' => 400,
				)
			);
		}

		$prepared_transaction = $this->prepare_item_for_database($request);

		if (!isset($request['hidden']) && isset($prepared_transaction->hide_sitewide)) {
			$request['hidden'] = $prepared_transaction->hide_sitewide;
		}

		$type = 'transaction_update';
		if (!empty($request['type'])) {
			$type = $request['type'];
		}

		$prime = $request['primary_item_id'];
		$transaction_id = 0;

		$transaction_id = cb_transaction_create($prepared_transaction);

		if (!is_numeric($transaction_id)) {
			return new WP_Error(
				'cb_rest_user_cannot_create_transaction',
				__('Cannot create new transaction.', 'confetti-bits'),
				array(
					'status' => 500,
				)
			);
		}

		$transaction = cb_transaction_get_specific(
			array(
				'transaction_ids' => array($transaction_id),
			)
		);

		$transaction = current($transaction['transactions']);
		$fields_update = $this->update_additional_fields_for_object($transaction, $request);

		if (is_wp_error($fields_update)) {
			return $fields_update;
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response($transaction, $request)
		);

		$response = rest_ensure_response($retval);

		do_action('cb_rest_transaction_create_item', $transaction, $response, $request);

		return $response;
	}

	public function create_item_permissions_check($request)
	{
		$retval = true;

		if (!is_user_logged_in()) {
			$retval = new WP_Error(
				'cb_rest_authorization_required',
				__('Sorry, you are not allowed to create transactions.', 'confetti-bits'),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return apply_filters('cb_rest_transaction_create_item_permissions_check', $retval, $request);
	}

	/**
	 * Update a transaction.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api {PATCH} /wp-json/confetti-bits/v1/transaction/:id Update transaction
	 * @apiName UpdateCBTransaction
	 * @apiGroup Transactions
	 * @apiDescription Update single transaction
	 * @apiVersion 1.0.0
	 * @apiPermission LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the transaction.
	 * @apiParam {Number} [primary_item_id] The ID of some other object primarily associated with this one.
	 * @apiParam {Number} [secondary_item_id] The ID of some other object also associated with this one.
	 * @apiParam {Number} [user_id] The ID for the author of the transaction.
	 * @apiParam {string} [link] The permalink to this transaction on the site.
	 * @apiParam {String=settings,notifications,groups,forums,transaction,media,messages,friends,invites,search,members,xprofile,blogs} [component] The active component the transaction relates to.
	 * @apiParam {String=new_member,new_avatar,updated_profile,transaction_update,created_group,joined_group,group_details_updated,bcb_topic_create,bcb_reply_create,transaction_comment,friendship_accepted,friendship_created,new_blog_post,new_blog_comment} [type] The transaction type of the transaction.
	 * @apiParam {String} [content] Allowed HTML content for the transaction.
	 * @apiParam {String} [date] The date the transaction was published, in the site's timezone.
	 * @apiParam {Boolean=true,false} [hidden] Whether the transaction object should be sitewide hidden or not.
	 * @apiParam {string=public,loggedin,onlyme,friends,media} [privacy] Privacy of the transaction.
	 * @apiParam {Array} [cb_media_ids] Media specific IDs when Media component is enable.
	 * @apiParam {Array} [media_gif] Save gif data into transaction when Media component is enable. param(url,mp4)
	 */
/*/
	public function update_item($request)
	{
		$request->set_param('context', 'edit');
		$transaction_object = $this->prepare_item_for_database($request);

		if (
			(empty($transaction_object->content)
				//&& empty(cb_transaction_get_meta($transaction_object->id, 'cb_media_ids', true))
				//&& empty(cb_transaction_get_meta($transaction_object->id, '_gif_data', true))
			) && true === $this->cb_rest_transaction_content_validate($request)
		) {
			return new WP_Error(
				'cb_rest_update_transaction_empty_content',
				__('Please, enter a log entry.', 'confetti-bits'),
				array(
					'status' => 400,
				)
			);
		}

		$transaction_id = cb_transaction_create($transaction_object);

		if (!is_numeric($transaction_id)) {
			return new WP_Error(
				'cb_rest_user_cannot_update_transaction',
				__('Cannot update existing transaction.', 'confetti-bits'),
				array(
					'status' => 500,
				)
			);
		}

		$transaction = $this->get_transaction_object($transaction_id);
		$fields_update = $this->update_additional_fields_for_object($transaction, $request);

		if (is_wp_error($fields_update)) {
			return $fields_update;
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response($transaction, $request)
		);

		$response = rest_ensure_response($retval);

		do_action('cb_rest_transaction_update_item', $transaction, $response, $request);

		return $response;
	}

	public function update_item_permissions_check($request)
	{
		$retval = true;

		if (!is_user_logged_in()) {
			$retval = new WP_Error(
				'cb_rest_authorization_required',
				__('Sorry, you are not allowed to update this transaction.', 'confetti-bits'),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$transaction = $this->get_transaction_object($request);

		if (true === $retval && empty($transaction->id)) {
			$retval = new WP_Error(
				'cb_rest_invalid_id',
				__('Invalid transaction ID.', 'confetti-bits'),
				array(
					'status' => 404,
				)
			);
		}

		if (true === $retval && !cb_transaction_user_can_delete($transaction)) {
			$retval = new WP_Error(
				'cb_rest_authorization_required',
				__('Sorry, you are not allowed to update this transaction.', 'confetti-bits'),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return apply_filters('cb_rest_transaction_update_item_permissions_check', $retval, $request);
	}

	public function delete_item($request)
	{
		$request->set_param('context', 'edit');

		$transaction = $this->get_transaction_object($request);
		$previous = $this->prepare_item_for_response($transaction, $request);

		$retval = cb_transaction_delete(
			array(
				'id' => $transaction->id,
			)
		);

		if (!$retval) {
			return new WP_Error(
				'cb_rest_transaction_cannot_delete',
				__('Could not delete the transaction.', 'confetti-bits'),
				array(
					'status' => 500,
				)
			);
		}

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted' => true,
				'previous' => $previous->get_data(),
			)
		);

		do_action('cb_rest_transaction_delete_item', $transaction, $response, $request);

		return $response;
	}

	public function delete_item_permissions_check($request)
	{
		$retval = true;

		if (!is_user_logged_in()) {
			$retval = new WP_Error(
				'cb_rest_authorization_required',
				__('Sorry, you are not allowed to delete this transaction.', 'confetti-bits'),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$transaction = $this->get_transaction_object($request);

		if (true === $retval && empty($transaction->id)) {
			$retval = new WP_Error(
				'cb_rest_invalid_id',
				__('Invalid transaction ID.', 'confetti-bits'),
				array(
					'status' => 404,
				)
			);
		}

		if (true === $retval && !cb_transaction_user_can_delete($transaction)) {
			$retval = new WP_Error(
				'cb_rest_authorization_required',
				__('Sorry, you are not allowed to delete this transaction.', 'confetti-bits'),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return apply_filters('cb_rest_transaction_delete_item_permissions_check', $retval, $request);
	}

	public function render_item($transaction)
	{
		$rendered = '';

		if (empty($transaction->content)) {
			return $rendered;
		}

		// Do not truncate activities.
		add_filter('cb_transaction_maybe_truncate_entry', '__return_false');
		$transactions_template = null;

		if (isset($GLOBALS['transactions_template'])) {
			$transactions_template = $GLOBALS['transactions_template'];
		}

		$GLOBALS['transactions_template'] = new stdClass();
		$GLOBALS['transactions_template']->transaction = $transaction;

		// cb_transaction_embed();

		$rendered = apply_filters_ref_array(
			'cb_get_transaction_content_body',
			array(
				$transaction->content,
				&$transaction,
			)
		);

		$GLOBALS['transactions_template'] = $transactions_template;

		// Restore the filter to truncate activities.
		remove_filter('cb_transaction_maybe_truncate_entry', '__return_false');

		return $rendered;
	}

	public function prepare_item_for_response($transaction, $request)
	{
		global $transactions_template;
		$transactions_template = new \stdClass();
		$transactions_template->disable_blogforum_replies = (bool) cb_core_get_root_option('cb-disable-blogforum-comments');
		$transactions_template->transaction = $transaction;

		$data = array(
			'user_id' => $transaction->user_id,
			'name' => cb_core_get_user_displayname($transaction->user_id),
			'component' => $transaction->component,
			'content' => array(
				'raw' => $transaction->content,
				'rendered' => $this->render_item($transaction),
			),
			'date' => cb_rest_prepare_date_response($transaction->date_recorded),
			'id' => $transaction->id,
			'link' => cb_transaction_get_permalink($transaction->id),
			'primary_item_id' => $transaction->item_id,
			'secondary_item_id' => $transaction->secondary_item_id,
			'title' => $transaction->action,
			'type' => $transaction->type,

			'can_delete' => cb_transaction_user_can_delete($transaction),
			'content_stripped' => html_entity_decode(wp_strip_all_tags($transaction->content)),
		);

		// Get item schema.
		$schema = $this->get_item_schema();

		if (!empty($schema['properties']['user_avatar'])) {
			$data['user_avatar'] = array(
				'full' => cb_core_fetch_avatar(
					array(
						'item_id' => $transaction->user_id,
						'html' => false,
						'type' => 'full',
					)
				),

				'thumb' => cb_core_fetch_avatar(
					array(
						'item_id' => $transaction->user_id,
						'html' => false,
					)
				),
			);
		}

		$context = !empty($request['context']) ? $request['context'] : 'view';
		$data = $this->add_additional_fields_to_object($data, $request);
		$data = $this->filter_response_by_context($data, $context);

		$response = rest_ensure_response($data);
		$response->add_links($this->prepare_links($transaction));

		return apply_filters('cb_rest_transaction_prepare_value', $response, $request, $transaction);
	}


	/**
	 * Prepare an transaction for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return stdClass|WP_Error Object or WP_Error.
	 * @since 0.1.0
	 */

/*/
	protected function prepare_item_for_database($request)
	{
		$prepared_transaction = new stdClass();
		$schema = $this->get_item_schema();
		$transaction = $this->get_transaction_object($request);

		if (!empty($schema['properties']['id']) && !empty($transaction->id)) {
			$prepared_transaction = $transaction;
			$prepared_transaction->id = $transaction->id;
		}

		// transaction user ID.
		if (!empty($schema['properties']['user_id']) && isset($request['user_id'])) {
			$prepared_transaction->user_id = (int) $request['user_id'];
		} else {
			$prepared_transaction->user_id = get_current_user_id();
		}

		// transaction component.
		if (!empty($schema['properties']['component']) && isset($request['component'])) {
			$prepared_transaction->component = $request['component'];
		} else {
			$prepared_transaction->component = Confetti_Bits()->transactions->id;
		}

		// transaction Item ID.
		if (!empty($schema['properties']['primary_item_id']) && isset($request['primary_item_id'])) {
			$item_id = (int) $request['primary_item_id'];
			$prepared_transaction->item_id = $item_id;
		}

		// Secondary Item ID.
		if (!empty($schema['properties']['secondary_item_id']) && isset($request['secondary_item_id'])) {
			$prepared_transaction->secondary_item_id = (int) $request['secondary_item_id'];
		}

		// transaction type.
		if (!empty($schema['properties']['type']) && isset($request['type'])) {
			$prepared_transaction->type = $request['type'];
		}

		// transaction content.
		if (!empty($schema['properties']['content']) && isset($request['content'])) {
			if (is_string($request['content'])) {
				$prepared_transaction->content = $request['content'];
			} elseif (isset($request['content']['raw'])) {
				$prepared_transaction->content = $request['content']['raw'];
			}
		}

		return apply_filters('cb_rest_transaction_pre_insert_value', $prepared_transaction, $request);
	}


	protected function prepare_links($transaction)
	{
		$base = sprintf('/%s/%s/', $this->namespace, $this->rest_base);
		$url = $base . $transaction->id;

		// Entity meta.
		$links = array(
			'self' => array(
				'href' => rest_url($url),
			),
			'collection' => array(
				'href' => rest_url($base),
			),
			'user' => array(
				'href' => rest_url(cb_rest_get_user_url($transaction->user_id)),
				'embeddable' => true,
			),
		);

		
		return apply_filters('cb_rest_transaction_prepare_links', $links, $transaction);
	}

	protected function can_see($request)
	{
		return cb_transaction_user_can_read(
			$this->get_transaction_object($request),
			bp_loggedin_user_id()
		);
	}

	public function get_transaction_object($request)
	{
		$transaction_id = is_numeric($request) ? $request : (int) $request['id'];

		$transaction = cb_transaction_get_specific(
			array(
				'transaction_ids' => array($transaction_id),
			)
		);

		if (is_array($transaction) && !empty($transaction['transactions'][0])) {
			return $transaction['transactions'][0];
		}

		return '';
	}

	public function get_endpoint_args_for_item_schema($method = WP_REST_Server::CREATABLE)
	{
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema($method);
		$key = 'get_item';

		if (WP_REST_Server::CREATABLE === $method || WP_REST_Server::EDITABLE === $method) {
			$key = 'create_item';
			$args['content']['type'] = 'string';
			unset($args['content']['properties']);

			if (WP_REST_Server::EDITABLE === $method) {
				$key = 'update_item';
			}
		} elseif (WP_REST_Server::DELETABLE === $method) {
			$key = 'delete_item';
		}
		return apply_filters("cb_rest_transaction_{$key}_query_arguments", $args, $method);
	}


	public function get_item_schema()
	{
		$schema = array(
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title' => 'cb_transaction',
			'type' => 'object',
			'properties' => array(
				'id' => array(
					'context' => array('embed', 'view', 'edit'),
					'description' => __('A unique numeric ID for the transaction.', 'confetti-bits'),
					'readonly' => true,
					'type' => 'integer',
				),
				'primary_item_id' => array(
					'context' => array('embed', 'view', 'edit'),
					'description' => __('The ID of some other object primarily associated with this one.', 'confetti-bits'),
					'type' => 'integer',
				),
				'secondary_item_id' => array(
					'context' => array('embed', 'view', 'edit'),
					'description' => __('The ID of some other object also associated with this one.', 'confetti-bits'),
					'type' => 'integer',
				),
				'user_id' => array(
					'context' => array('embed', 'view', 'edit'),
					'description' => __('The ID for the author of the transaction.', 'confetti-bits'),
					'type' => 'integer',
				),
				'name' => array(
					'context' => array('embed', 'view', 'edit'),
					'description' => __('User\'s display name for the transaction.', 'confetti-bits'),
					'type' => 'string',
				),
				'link' => array(
					'context' => array('embed', 'view', 'edit'),
					'description' => __('The permalink to this transaction on the site.', 'confetti-bits'),
					'format' => 'uri',
					'type' => 'string',
				),
				'component' => array(
					'context' => array('embed', 'view', 'edit'),
					'description' => __('The active BuddyPress component the transaction relates to.', 'confetti-bits'),
					'type' => 'string',
					'enum' => array_keys(buddypress()->active_components),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'type' => array(
					'context' => array('embed', 'view', 'edit'),
					'description' => __('The transaction type of the transaction.', 'confetti-bits'),
					'type' => 'string',
					'enum' => array_keys(cb_transaction_get_types()),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'title' => array(
					'context' => array('embed', 'view', 'edit'),
					'description' => __('The description of the transaction\'s type (eg: Username posted an update)', 'confetti-bits'),
					'type' => 'string',
					'readonly' => true,
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'content' => array(
					'context' => array('embed', 'view', 'edit'),
					'description' => __('Allowed HTML content for the transaction.', 'confetti-bits'),
					'type' => 'object',
					'arg_options' => array(
						'sanitize_callback' => null,
						// Note: sanitization implemented in self::prepare_item_for_database().
						'validate_callback' => null,
						// Note: validation implemented in self::prepare_item_for_database().
					),
					'properties' => array(
						'raw' => array(
							'description' => __('Content for the transaction, as it exists in the database.', 'confetti-bits'),
							'type' => 'string',
							'context' => array('embed', 'edit'),
						),
						'rendered' => array(
							'description' => __('HTML content for the transaction, transformed for display.', 'confetti-bits'),
							'type' => 'string',
							'context' => array('embed', 'view', 'edit'),
							'readonly' => true,
						),
					),
				),
				'date' => array(
					'context' => array('embed', 'view', 'edit'),
					'description' => __("The date the transaction was published, in the site's timezone.", 'confetti-bits'),
					'type' => 'string',
					'format' => 'date-time',
				),
				'status' => array(
					'context' => array('embed', 'view', 'edit'),
					'description' => __('Whether the transaction has been marked as spam or not.', 'confetti-bits'),
					'type' => 'string',
					'enum' => array('published', 'spam'),
					'readonly' => true,
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'can_delete' => array(
					'context' => array('embed', 'view', 'edit'),
					'description' => __('Whether or not user have the delete access for the transaction object.', 'confetti-bits'),
					'type' => 'boolean',
					'readonly' => true,
				),
				'content_stripped' => array(
					'context' => array('embed', 'view', 'edit'),
					'description' => __('Content for the transaction without HTML tags.', 'confetti-bits'),
					'type' => 'string',
					'readonly' => true,
				),
			),
		);

		// Avatars.
		if (true === buddypress()->avatar->show_avatars) {
			$avatar_properties = array();

			$avatar_properties['full'] = array(
				'context' => array('embed', 'view', 'edit'),

				'description' => sprintf(__('Avatar URL with full image size (%1$d x %2$d pixels).', 'confetti-bits'), number_format_i18n(cb_core_avatar_full_width()), number_format_i18n(cb_core_avatar_full_height())),
				'type' => 'string',
				'format' => 'uri',
			);

			$avatar_properties['thumb'] = array(
				'context' => array('embed', 'view', 'edit'),

				'description' => sprintf(__('Avatar URL with thumb image size (%1$d x %2$d pixels).', 'confetti-bits'), number_format_i18n(cb_core_avatar_thumb_width()), number_format_i18n(cb_core_avatar_thumb_height())),
				'type' => 'string',
				'format' => 'uri',
			);

			$schema['properties']['user_avatar'] = array(
				'context' => array('embed', 'view', 'edit'),
				'description' => __('Avatar URLs for the author of the transaction.', 'confetti-bits'),
				'type' => 'object',
				'readonly' => true,
				'properties' => $avatar_properties,
			);
		}

		return apply_filters('cb_rest_transaction_schema', $this->add_additional_fields_schema($schema));
	}

	public function get_collection_params()
	{
		$params = parent::get_collection_params();
		$params['context']['default'] = 'view';

		$params['exclude'] = array(
			'description' => __('Ensure result set excludes specific IDs.', 'confetti-bits'),
			'default' => array(),
			'type' => 'array',
			'items' => array('type' => 'integer'),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['include'] = array(
			'description' => __('Ensure result set includes specific IDs.', 'confetti-bits'),
			'default' => array(),
			'type' => 'array',
			'items' => array('type' => 'integer'),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order'] = array(
			'description' => __('Order sort attribute ascending or descending.', 'confetti-bits'),
			'default' => 'desc',
			'type' => 'string',
			'enum' => array('asc', 'desc'),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['after'] = array(
			'description' => __('Limit result set to items published after a given ISO8601 compliant date.', 'confetti-bits'),
			'type' => 'string',
			'format' => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description' => __('Limit result set to items created by a specific user (ID).', 'confetti-bits'),
			'default' => 0,
			'type' => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['status'] = array(
			'description' => __('Limit result set to items with a specific status.', 'confetti-bits'),
			'default' => 'ham_only',
			'type' => 'string',
			'enum' => array('ham_only', 'spam_only', 'all'),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['scope'] = array(
			'description' => __('Limit result set to items with a specific scope.', 'confetti-bits'),
			'type' => 'string',
			'enum' => array('just-me', 'friends', 'groups', 'favorites', 'mentions', 'following'),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['primary_id'] = array(
			'description' => __('Limit result set to items with a specific prime association ID.', 'confetti-bits'),
			'default' => 0,
			'type' => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['secondary_id'] = array(
			'description' => __('Limit result set to items with a specific secondary association ID.', 'confetti-bits'),
			'default' => 0,
			'type' => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['component'] = array(
			'description' => __('Limit result set to items with a specific active BuddyPress component.', 'confetti-bits'),
			'type' => 'string',
			'enum' => array_keys(buddypress()->active_components),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['type'] = array(
			'description' => __('Limit result set to items with a specific transaction type.', 'confetti-bits'),
			'type' => 'string',
			'enum' => array_keys(cb_transaction_get_types()),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['privacy'] = array(
			'description' => __('Privacy of the transaction.', 'confetti-bits'),
			'type' => 'array',
			'items' => array(
				'type' => 'string',
				'enum' => array('public', 'loggedin', 'onlyme', 'friends', 'media'),
			),
			'sanitize_callback' => 'cb_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return apply_filters('cb_rest_transaction_collection_params', $params);
	}

	public function get_transaction_favorite_count($transaction_id)
	{

		if (empty($transaction_id)) {
			return;
		}

		$fav_count = cb_transaction_get_meta($transaction_id, 'favorite_count', true);

		return (!empty($fav_count) ? $fav_count : 0);
	}

	public function cb_rest_transaction_content_validate($request)
	{
		$toolbar_option = false;

		if (!empty($request['content'])) {
			return false;
		}

		return $toolbar_option;
	}

	public function cb_rest_transaction_default_scope($scope = 'all', $user_id = 0)
	{
		$new_scope = array();

		if (bp_loggedin_user_id() && ('all' === $scope || empty($scope))) {

			$new_scope[] = 'public';

			$new_scope[] = 'just-me';

			if (empty($user_id)) {
				$new_scope[] = 'public';
			}
		} elseif (!bp_loggedin_user_id() && ('all' === $scope || empty($scope))) {
			$new_scope[] = 'public';
		}

		$new_scope = array_unique($new_scope);

		if (empty($new_scope)) {
			$new_scope = (array) $scope;
		}

		$new_scope = apply_filters('cb_rest_transaction_default_scope', $new_scope);

		return implode(',', $new_scope);
	}
}
/*/