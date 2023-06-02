<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Core_Notification_Abstract' ) ) {
	return;
}

/**
 * CB Notifications Component
 *
 * Establishes the Confetti Bits Notifications component.
 * 
 * We use this in tandem with BuddyBoss platform to give us 
 * email, web, and push notifications. An absolute godsend,
 * this makes it a million times easier to do that stuff, so 
 * we can focus on the actual functionality.
 * 
 * @package Confetti_Bits
 * @subpackage Notifications
 * @since 2.0.0
 */
class CB_Notifications_Component extends BP_Core_Notification_Abstract {

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		$this->start();
	}

	public function load() {

		/**
		 * Register Notification Group.
		 *
		 * @param string $group_key         Group key.
		 * @param string $group_label       Group label.
		 * @param string $group_admin_label Group admin label.
		 * @param int    $priority          Priority of the group.
		 */
		$this->register_notification_group(
			'confetti_bits',

			esc_html__( 'Confetti Bits Notifications', 'confetti-bits' ), 
			esc_html__( 'Confetti Bits Notifications Admin', 'confetti-bits' ),
		);

		$this->register_confetti_bits_send_notifications();

		$this->register_confetti_bits_transfer_notifications();

		$this->register_confetti_bits_import_notifications();

		$this->register_confetti_bits_request_fulfillment_notifications();

		$this->register_confetti_bits_request_sender_notifications();

		$this->register_confetti_bits_activity_notifications();

		$this->register_group_activity_notifications();

		$this->register_participation_notifications();

		$this->register_birthday_notifications();

		$this->register_anniversary_notifications();

		/**
		 * Register Notification Filter.
		 *
		 * @param string $notification_label    Notification label.
		 * @param array  $notification_types    Notification types.
		 * @param int    $notification_position Notification position.
		 */

	}



	public function register_confetti_bits_send_notifications() {

		/**
		 * Register Notification Type.
		 *
		 * @param string $notification_type        Notification Type key.
		 * @param string $notification_label       Notification label.
		 * @param string $notification_admin_label Notification admin label.
		 * @param string $notification_group       Notification group.
		 * @param bool   $default                  Default status for enabled/disabled.
		 */
		$this->register_notification_type(
			'cb_transactions_send_bits',
			esc_html__( 'Someone sends you Confetti Bits', 'confetti-bits' ),
			esc_html__( 'Someone sends you Confetti Bits', 'confetti-bits' ),
			'confetti_bits',
		);



		/**
		 * Register notification.
		 *
		 * @param string $component         Component name.
		 * @param string $component_action  Component action.
		 * @param string $notification_type Notification Type key.
		 * @param string $icon_class        Notification Small Icon.
		 */
		$this->register_notification(
			'confetti_bits',
			'cb_send_bits',
			'cb_transactions_send_bits'
		);

		/**
		 * Add email schema.
		 *
		 * @param string $email_type        Type of email being sent.
		 * @param array  $args              Email arguments.
		 * @param string $notification_type Notification Type key.
		 */
		$this->register_email_type(
			'cb-send-bits-email',
			array(
				'email_title'         => __( 'Someone Sent Confetti Bits!', 'confetti-bits' ),
				'email_content'       => __( 'Someone just sent you Confetti Bits!', 'confetti-bits' ),
				'email_plain_content' => __( 'Someone just sent you Confetti Bits!', 'confetti-bits' ),
				'situation_label'     => __( 'Someone sends Confetti Bits!', 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone sends you Confetti Bits.', 'confetti-bits' ),
			),
			'cb_transactions_send_bits'
		);

		$this->register_notification_filter(
			__( 'Leadership Confetti Bits Notifications', 'confetti-bitts' ),
			array( 'cb_transactions_send_bits' ),
			5
		);


		//		add_filter( 'cb_transactions_send_bits', array( $this, 'format_notification' ), 10, 7 );	

	}

	public function register_confetti_bits_transfer_notifications() {

		/**
		 * Register Notification Type.
		 *
		 * @param string $notification_type        Notification Type key.
		 * @param string $notification_label       Notification label.
		 * @param string $notification_admin_label Notification admin label.
		 * @param string $notification_group       Notification group.
		 * @param bool   $default                  Default status for enabled/disabled.
		 */
		$this->register_notification_type(
			'cb_transactions_transfer_bits',
			esc_html__( 'Someone sends you Confetti Bits', 'confetti-bits' ),
			esc_html__( 'Someone sends you Confetti Bits', 'confetti-bits' ),
			'confetti_bits',
		);

		/**
		 * Register notification.
		 *
		 * @param string $component         Component name.
		 * @param string $component_action  Component action.
		 * @param string $notification_type Notification Type key.
		 * @param string $icon_class        Notification Small Icon.
		 */
		$this->register_notification(
			'confetti_bits',
			'cb_transfer_bits',
			'cb_transactions_transfer_bits'
		);

		/**
		 * Add email schema.
		 *
		 * @param string $email_type        Type of email being sent.
		 * @param array  $args              Email arguments.
		 * @param string $notification_type Notification Type key.
		 */
		$this->register_email_type(
			'cb-transfer-bits-email',
			array(
				'email_title'         => __( 'Someone Sent Confetti Bits!', 'confetti-bits' ),
				'email_content'       => __( 'Someone just sent you Confetti Bits!', 'confetti-bits' ),
				'email_plain_content' => __( 'Someone just sent you Confetti Bits!', 'confetti-bits' ),
				'situation_label'     => __( 'Someone sends Confetti Bits', 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone sends you Confetti Bits.', 'confetti-bits' ),
			),
			'cb_transactions_send_bits'
		);

		$this->register_notification_filter(
			__( 'Confetti Bits Transfer Notifications', 'confetti-bitts' ),
			array( 'cb_transactions_transfer_bits' ),
			5
		);


		//		add_filter( 'cb_transactions_send_bits', array( $this, 'format_notification' ), 10, 7 );	

	}

	public function register_confetti_bits_activity_notifications() {

		/**
		 * Register Notification Type.
		 *
		 * @param string $notification_type        Notification Type key.
		 * @param string $notification_label       Notification label.
		 * @param string $notification_admin_label Notification admin label.
		 * @param string $notification_group       Notification group.
		 * @param bool   $default                  Default status for enabled/disabled.
		 */
		$this->register_notification_type(
			'cb_transactions_activity_bits',
			esc_html__( 'You get Confetti Bits for posting', 'confetti-bits' ),
			esc_html__( 'You get Confetti Bits for posting', 'confetti-bits' ),
			'confetti_bits',
		);

		/**
		 * Register notification.
		 *
		 * @param string $component         Component name.
		 * @param string $component_action  Component action.
		 * @param string $notification_type Notification Type key.
		 * @param string $icon_class        Notification Small Icon.
		 */
		$this->register_notification(
			'confetti_bits',
			'cb_activity_bits',
			'cb_transactions_activity_bits'
		);

		/**
		 * Add email schema.
		 *
		 * @param string $email_type        Type of email being sent.
		 * @param array  $args              Email arguments.
		 * @param string $notification_type Notification Type key.
		 */
		$this->register_email_type(
			'cb-send-bits-email',
			array(
				'email_title'         => __( 'Nice!', 'confetti-bits' ),
				'email_content'       => __( 'You just got Confetti Bits for posting on TeamCTG!', 'confetti-bits' ),
				'email_plain_content' => __( 'You just got Confetti Bits for posting on TeamCTG!', 'confetti-bits' ),
				'situation_label'     => __( 'You get Confetti Bits for posting', 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when you receive Confetti Bits for posting.', 'confetti-bits' ),
			),
			'cb_transactions_activity_bits'
		);

		$this->register_notification_filter(
			__( 'Confetti Bits Activity Notifications', 'confetti-bitts' ),
			array( 'cb_transactions_activity_bits' ),
			5
		);


		//		add_filter( 'cb_transactions_send_bits', array( $this, 'format_notification' ), 10, 7 );	

	}

	public function register_confetti_bits_import_notifications() {

		$this->register_notification_type(
			'cb_transactions_import_bits',
			esc_html__( 'Someone performs a Confetti Bits import', 'confetti-bits' ),
			esc_html__( 'Someone performs a Confetti Bits import', 'confetti-bits' ),
			'confetti_bits'
		);

		$this->register_notification(
			'confetti_bits',
			'cb_import_bits',
			'cb_transactions_import_bits'
		);

		$this->register_email_type(
			'cb-import-bits-email',
			array(
				'email_title'         => __( 'Confetti Bits Imported', 'confetti-bits' ),
				'email_content'       => __( "Confetti Bits were just imported!", 'confetti-bits' ),
				'email_plain_content' => __( "Confetti Bits were just imported!", 'confetti-bits' ),
				'situation_label'     => __( "Confetti Bits are imported.", 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when Confetti Bits are imported.', 'confetti-bits' ),
			),
			'cb_transactions_import_bits'
		);
		$this->register_notification_filter(
			__( 'Confetti Bits Import Notifications', 'confetti-bits' ),
			array( 'cb_transactions_import_bits' ),
			5
		);
		//		add_filter( 'cb_transactions_import_bits', array( $this, 'format_notification' ), 10, 7 );

	}


	public function register_confetti_bits_request_fulfillment_notifications() {

		$this->register_notification_type(
			'cb_transactions_bits_request',
			esc_html__( 'Someone sends in a Confetti Bits request', 'confetti-bits' ),
			esc_html__( 'Someone sends in a Confetti Bits request', 'confetti-bits' ),
			'confetti_bits'
		);

		$this->register_notification(
			'confetti_bits',
			'cb_bits_request',
			'cb_transactions_bits_request'
		);

		$this->register_email_type(
			'cb-bits-request-email',
			array(
				'email_title'         => __( 'New Confetti Bits Request from {{request_sender.name}}', 'confetti-bits' ),
				'email_content'       => __( "A new Confetti Bits Request came in! {{request_sender.name}} requested: {{request_sender.item}}.", 'confetti-bits' ),
				'email_plain_content' => __( "A new Confetti Bits Request came in! {{request_sender.name}} requested: {{request_sender.item}}.", 'confetti-bits' ),
				'situation_label'     => __( "A new Confetti Bits Request comes in", 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when Confetti Bits requests are sent.', 'confetti-bits' ),
			),
			'cb_transactions_bits_request'
		);

		$this->register_notification_filter(
			__( 'Confetti Bits Request Notifications', 'confetti-bits' ),
			array( 'cb_transactions_bits_request' ),
			5
		);

		//		add_filter( 'cb_transactions_request_bits', array( $this, 'format_notification' ), 10, 7 );

	}

	public function register_confetti_bits_request_sender_notifications() {

		$this->register_notification_type(
			'cb_transactions_send_bits_request',
			esc_html__( 'You send in a Confetti Bits request', 'confetti-bits' ),
			esc_html__( 'You send in a Confetti Bits request', 'confetti-bits' ),
			'confetti_bits'
		);

		$this->register_notification(
			'confetti_bits',
			'cb_bits_request',
			'cb_transactions_send_bits_request'
		);

		$this->register_email_type(
			'cb-send-bits-request-email',
			array(
				'email_title'         => __( 'Your Confetti Bits Request Receipt', 'confetti-bits' ),
				'email_content'       => __( '<h4>You requested {{request_sender.item}} for {{request.amount}} Confetti Bits</h4><p>Please allow 4-6 weeks for your request to be fulfilled. {{request_fulfillment.name}} will reach out to you within that time frame and discuss further steps to fulfill your request!</p><span style="font-size:10px;"><b>Also please note that {{request.amount}} Confetti Bits have been deducted from your total balance, and will not count toward additional request items or other company-sponsored rewards based on Confetti Bits balance.</b></span>', 'confetti-bits' ),
				'email_plain_content' => __( "You successfully requested {{request_sender.item}} for {{request.amount}} Confetti Bits. Please allow 4-6 weeks for your request to be fulfilled. {{request_fulfillment.name}} will reach out to you within that time frame and discuss further steps to fulfill your request! Also please note that {{request.amount}} Confetti Bits have been deducted from your total balance, and will not count toward additional request items or other company-sponsored rewards based on Confetti Bits balance.", 'confetti-bits' ),
				'situation_label'     => __( "You put in a Confetti Bits Request", 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when you send in Confetti Bits Requests', 'confetti-bits' ),
			),
			'cb_transactions_send_bits_request'
		);

		$this->register_notification_filter(
			__( 'Confetti Bits Request Receipt Notifications', 'confetti-bits' ),
			array( 'cb_transactions_send_bits_request' ),
			5
		);

		//		add_filter( 'cb_transactions_request_bits', array( $this, 'format_notification' ), 10, 7 );

	}

	public function register_participation_notifications() {

		/**
		 * Register Notification Type.
		 *
		 * @param string $notification_type        Notification Type key.
		 * @param string $notification_label       Notification label.
		 * @param string $notification_admin_label Notification admin label.
		 * @param string $notification_group       Notification group.
		 * @param bool   $default                  Default status for enabled/disabled.
		 */
		$this->register_notification_type(
			'cb_participation_status_update',
			esc_html__( 'Someone updates your participation status', 'confetti-bits' ),
			esc_html__( 'Someone updates your participation status', 'confetti-bits' ),
			'confetti_bits'
		);

		$this->register_notification_type(
			'cb_participation_new',
			esc_html__( 'Someone submits participation for approval', 'confetti-bits' ),
			esc_html__( 'Someone submits participation for approval', 'confetti-bits' ),
			'confetti_bits'
		);

		/**
		 * Register notification.
		 *
		 * @param string $component         Component name.
		 * @param string $component_action  Component action.
		 * @param string $notification_type Notification Type key.
		 * @param string $icon_class        Notification Small Icon.
		 */
		$this->register_notification(
			'confetti_bits',
			'cb_participation_status_update',
			'cb_participation_status_update'
		);

		$this->register_notification(
			'confetti_bits',
			'cb_participation_new',
			'cb_participation_new'
		);

		/**
		 * Add email schema.
		 *
		 * @param string $email_type        Type of email being sent.
		 * @param array  $args              Email arguments.
		 * @param string $notification_type Notification Type key.
		 */
		$this->register_email_type(
			'cb-participation-status-update',
			array(
				'email_title'         => __( '{{participation.note}} | Participation Update', 'confetti-bits' ),
				'email_content'       => "<h4>The status of one of your Confetti Bits participation submissions has been changed to \"{{participation.status}}\" by {{admin.name}}.</h4><h5>Notes:</h5><p>\"{{participation.note}}\"</p>",
				'email_plain_content' => __( "The status of one of your Confetti Bits participation submissions has been changed to \"{{participation.status}}\" by {{admin.name}}. Notes: \"{{participation.note}}\"", 'confetti-bits' ),
				'situation_label'     => __( "Someone updates the status of a participation submission", 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone updates your participation.', 'confetti-bits' ),
			),
			'cb_participation_status_update'
		);

		$this->register_email_type(
			'cb-participation-status-denied',
			array(
				'email_title'         => __( '{{participation.type}}: {{participation.status}} | Participation Update', 'confetti-bits' ),
				'email_content'       => "<h4>The status of your Confetti Bits participation submission for \"{{participation.type}}\" has been changed to \"{{participation.status}}\" by {{admin.name}}.</h4><h5>Reason Given:</h5><p>{{participation.note}}</p>",
				'email_plain_content' => __( "The status of your Confetti Bits participation submission for \"{{participation.type}}\" has been changed to \"{{participation.status}}\" by {{admin.name}}. Reason Given: {{participation.note}}", 'confetti-bits' ),
				'situation_label'     => __( "Someone denies a participation submission", 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone denies a participation submission.', 'confetti-bits' ),
			),
			'cb_participation_status_update'
		);

		$this->register_email_type(
			'cb-participation-new',
			array(
				'email_title'         => __( 'New Participation Submission from {{applicant.name}}', 'confetti-bits' ),
				'email_content'       => "<h4>{{applicant.name}} just submitted participation for '{{participation.note}}'.</h4><p>Update the status by visiting your Confetti Bits admin panel.</p>",
				'email_plain_content' => __( "{{applicant.name}} just submitted participation for '{{participation.note}}'. Update the status by visiting your Confetti Bits admin panel.", 'confetti-bits' ),
				'situation_label'     => __( "Someone submits their participation", 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone submits their participation.', 'confetti-bits' ),
			),
			'cb_participation_new'
		);

		/**
		 * Register Notification Filter.
		 *
		 * @param string $notification_label    Notification label.
		 * @param array  $notification_types    Notification types.
		 * @param int    $notification_position Notification position.
		 */
		$this->register_notification_filter(
			__( 'Participation Notifications', 'confetti-bits' ),
			array( 'cb_participation_status_update', 'cb_participation_new' ),
			5
		);

		//		add_filter( 'cb_transactions_request_bits', array( $this, 'format_notification' ), 10, 7 );

	}

	public function register_group_activity_notifications() {

		/**
		 * Register Notification Type.
		 *
		 * @param string $notification_type        Notification Type key.
		 * @param string $notification_label       Notification label.
		 * @param string $notification_admin_label Notification admin label.
		 * @param string $notification_group       Notification group.
		 * @param bool   $default                  Default status for enabled/disabled.
		 */

		$this->register_notification_type(
			'cb_groups_activity_post',
			esc_html__( 'Someone posts in a group', 'confetti-bits' ),
			esc_html__( 'Someone posts in a group', 'confetti-bits' ),
			'groups'
		);

		/**
		 * Register notification.
		 *
		 * @param string $component         Component name.
		 * @param string $component_action  Component action.
		 * @param string $notification_type Notification Type key.
		 * @param string $icon_class        Notification Small Icon.
		 */
		$this->register_notification(
			'groups',
			'activity_update',
			'cb_groups_activity_post'
		);

		/**
		 * Add email schema.
		 *
		 * @param string $email_type        Type of email being sent.
		 * @param array  $args              Email arguments.
		 * @param string $notification_type Notification Type key.
		 */
		$this->register_email_type(
			'cb-groups-activity-post',
			array(
				'email_title'         => __( '{{group_member.name}} just posted in {{group.name}}', 'confetti-bits' ),
				'email_content'       => "<h4>{{group_member.name}} posted in the group <a href='{{group.url}}'>{{group.name}}</a>:</h4> {{group_activity.content}}",
				'email_plain_content' => __( "{{group_member.name}} posted: {{group_activity.content}}.", 'confetti-bits' ),
				'situation_label'     => __( "Someone posts in a group", 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone posts in a group.', 'confetti-bits' ),
			),
			'cb_groups_activity_post'
		);

		/**
		 * Register Notification Filter.
		 *
		 * @param string $notification_label    Notification label.
		 * @param array  $notification_types    Notification types.
		 * @param int    $notification_position Notification position.
		 */
		$this->register_notification_filter(
			__( 'Group Activity Posts', 'confetti-bits' ),
			array( 'cb_groups_activity_post' ),
			5
		);

		//		add_filter( 'cb_transactions_request_bits', array( $this, 'format_notification' ), 10, 7 );

	}

	public function register_birthday_notifications() {

		/**
		 * Register Notification Type.
		 *
		 * @param string $notification_type        Notification Type key.
		 * @param string $notification_label       Notification label.
		 * @param string $notification_admin_label Notification admin label.
		 * @param string $notification_group       Notification group.
		 * @param bool   $default                  Default status for enabled/disabled.
		 */

		$this->register_notification_type(
			'cb_transactions_birthday_bits',
			esc_html__( "Birthday Notifications", 'confetti-bits' ),
			esc_html__( "Birthday Notifications", 'confetti-bits' ),
			'confetti_bits'
		);

		/**
		 * Register notification.
		 *
		 * @param string $component         Component name.
		 * @param string $component_action  Component action.
		 * @param string $notification_type Notification Type key.
		 * @param string $icon_class        Notification Small Icon.
		 */
		$this->register_notification(
			'confetti_bits',
			'cb_birthday_bits',
			'cb_transactions_birthday_bits'
		);

		/**
		 * Add email schema.
		 *
		 * @param string $email_type        Type of email being sent.
		 * @param array  $args              Email arguments.
		 * @param string $notification_type Notification Type key.
		 */
		$this->register_email_type(
			'cb-birthday-bits',
			array(
				'email_title'         => __( 'Happy Birthday, {{user.first_name}}!', 'confetti-bits' ),
				'email_content'       => __( "<h4>Happy Birthday, {{user.first_name}}!</h4><p>We hope you have a spectacular day full of celebrations today! We sent you <a href='{{user.cb_url}}'>{{transaction.amount}} Confetti Bits</a> to help celebrate!</p>", 'confetti-bits' ),
				'email_plain_content' => __( "Happy Birthday, {{user.first_name}}! We hope you have a spectacular day full of celebrations today! We sent you {{transaction.amount}} Confetti Bits to help celebrate!", 'confetti-bits' ),
				'situation_label'     => __( "Birthday Notifications", 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails on your birthday.', 'confetti-bits' ),
			),
			'cb_transactions_birthday_bits'
		);

		/**
		 * Register Notification Filter.
		 *
		 * @param string $notification_label    Notification label.
		 * @param array  $notification_types    Notification types.
		 * @param int    $notification_position Notification position.
		 */
		$this->register_notification_filter(
			__( 'Birthday Notifications', 'confetti-bits' ),
			array( 'cb_transactions_birthday_bits' ),
			5
		);

	}

	public function register_anniversary_notifications() {

		/**
		 * Register Notification Type.
		 *
		 * @param string $notification_type        Notification Type key.
		 * @param string $notification_label       Notification label.
		 * @param string $notification_admin_label Notification admin label.
		 * @param string $notification_group       Notification group.
		 * @param bool   $default                  Default status for enabled/disabled.
		 */

		$this->register_notification_type(
			'cb_transactions_anniversary_bits',
			esc_html__( "Work Anniversary Notifications", 'confetti-bits' ),
			esc_html__( "Work Anniversary Notifications", 'confetti-bits' ),
			'confetti_bits'
		);

		/**
		 * Register notification.
		 *
		 * @param string $component         Component name.
		 * @param string $component_action  Component action.
		 * @param string $notification_type Notification Type key.
		 * @param string $icon_class        Notification Small Icon.
		 */
		$this->register_notification(
			'confetti_bits',
			'cb_anniversary_bits',
			'cb_transactions_anniversary_bits'
		);

		/**
		 * Add email schema.
		 *
		 * @param string $email_type        Type of email being sent.
		 * @param array  $args              Email arguments.
		 * @param string $notification_type Notification Type key.
		 */
		$this->register_email_type(
			'cb-anniversary-bits',
			array(
				'email_title'         => __( 'Happy Anniversary, {{user.first_name}}!', 'confetti-bits' ),
				'email_content'       => __( "<h4>Happy Anniversary, {{user.first_name}}!</h4><p>We hope you have a spectacular day full of celebrations today! We sent you <a href='{{user.cb_url}}'>{{transaction.amount}} Confetti Bits</a> to help celebrate!</p>", 'confetti-bits' ),
				'email_plain_content' => __( "Happy Anniversary, {{user.first_name}}! We hope you have a spectacular day full of celebrations today! We sent you {{transaction.amount}} Confetti Bits to help celebrate!", 'confetti-bits' ),
				'situation_label'     => __( "Work Anniversary Notifications", 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails on your work anniversary.', 'confetti-bits' ),
			),
			'cb_transactions_anniversary_bits'
		);

		/**
		 * Register Notification Filter.
		 *
		 * @param string $notification_label    Notification label.
		 * @param array  $notification_types    Notification types.
		 * @param int    $notification_position Notification position.
		 */
		$this->register_notification_filter(
			__( 'Work Anniversary Notifications', 'confetti-bits' ),
			array( 'cb_transactions_anniversary_bits' ),
			5
		);

	}

	public function format_notification( $content, $item_id, $secondary_item_id, $action_item_count, $component_action_name, $component_name, $notification_id, $screen ) {

		$text = '';
		$link = bp_loggedin_user_domain() . "confetti-bits";

		if ( ( 'confetti_bits' === $component_name && 'cb_send_bits' === $component_action_name ) 
			|| ( 'confetti_bits' === $component_name && 'cb_transfer_bits' === $component_action_name ) ) {

			$text = esc_html__( bp_core_get_user_displayname( $item_id ) . ' just sent you bits!', 'confetti-bits' );

			$content = array(
				'title' => "Someone just sent Confetti Bits!", 
				'text' => $text,
				'link' => $link,
			);
		}

		if ( 'confetti_bits' === $component_name && 'cb_activity_bits' === $component_action_name ) {

			if ( $item_id === 1 ) {

				$text = esc_html__( 'You just got ' . $item_id . ' Confetti Bit for posting!', 'confetti-bits' );

			} else {

				$text = esc_html__( 'You just got ' . $item_id . ' Confetti Bits for posting!', 'confetti-bits' );

			}

			$content = array(
				'title' => "You just got Confetti Bits!", 
				'text' => $text,
				'link' => $link,
			);
		}

		if ( 'confetti_bits' === $component_name && 'cb_import_bits' === $component_action_name ) {

			$text = esc_html__( bp_core_get_user_displayname( $item_id ) . ' just imported bits!', 'confetti-bits' );

			$content = array(
				'title' => "Someone just imported Confetti Bits!", 
				'text' => $text,
				'link' => $link,
			);
		}

		if ( 'confetti_bits' === $component_name && 'cb_bits_request' === $component_action_name ) {

			$text = bp_core_get_user_displayname( $secondary_item_id ) . ' just sent in a new Confetti Bits Request!';

			$content = array(
				'title' => "New Confetti Bits Request!", 
				'text' => $text,
				'link' => $link,
			);
		}

		if ( ( 'groups' === $component_name && 'activity_update' === $component_action_name ) ) {

			$group = groups_get_group( $item_id );
			$text = bp_core_get_user_displayname( $secondary_item_id ) . 
				' just posted in the group ' . $group->name;
			$link = esc_url( bp_get_group_permalink( $group ) );
			$content = array(
				'title' => "Activity update in " . $group->name, 
				'text' => $text,
				'link' => $link,
			);
		}

		if ( 'confetti_bits' === $component_name && 'cb_participation_status_update' === $component_action_name ) {

			$applicant_name = bp_core_get_user_displayname( $item_id );
			$admin_name = bp_core_get_user_displayname( $secondary_item_id );
			$text = esc_html__( "{$admin_name} just updated your participation status.", 'confetti-bits' );
			$link = bp_core_get_user_domain( $item_id ) . 'confetti-bits/';
			$content = array(
				'title' => "Participation Update", 
				'text' => $text,
				'link' => $link,
			);
		}

		if ( 'confetti_bits' === $component_name && 'cb_birthday_bits' === $component_action_name ) {

			$user_name = bp_core_get_user_displayname( $item_id );
			$text = esc_html__( "Happy Birthday!", 'confetti-bits' );
			$link = bp_core_get_user_domain( $item_id ) . 'confetti-bits/';
			$content = array(
				'title' => "Happy Birthday!", 
				'text' => $text,
				'link' => $link,
			);
		}

		if ( 'confetti_bits' === $component_name && 'cb_anniversary_bits' === $component_action_name ) {

			$user_name = bp_core_get_user_displayname( $item_id );
			$text = esc_html__( "Happy Anniversary!", 'confetti-bits' );
			$link = bp_core_get_user_domain( $item_id ) . 'confetti-bits/';
			$content = array(
				'title' => "Happy Anniversary!", 
				'text' => $text,
				'link' => $link,
			);
		}

		return $content;

	}

}
