<?php
/**
 * Registers all Confetti Bits Notifications
 * 
 * There are a few methods in here that are inherited 
 * from the parent class, where we can't really include
 * doc blocks without potentially breaking something 
 * by overriding those methods. 
 * 
 * So we're going to document what you need for those here.
 * See BuddyBoss docs for more info. ({@link https://www.buddyboss.com/resources/dev-docs/web-development/migrating-custom-notifications-to-modern-notifications-api/})
 * 
 * 
 * 
 * For $this::register_notification_group():
 * 
 * @param string $group_key         Group key.
 * @param string $group_label       Group label.
 * @param string $group_admin_label Group admin label.
 * @param int    $priority          Priority of the group. Optional.
 * 
 * 
 * For $this::register_notification_type():
 * 
 * @param string $notification_type        Notification Type key.
 * @param string $notification_label       Notification label.
 * @param string $notification_admin_label Notification admin label.
 * @param string $notification_group       Notification group.
 * @param bool   $default                  Default status for enabled/disabled. Optional.
 * 
 * For $this::register_notification():
 * 
 * @param string $component         Component name.
 * @param string $component_action  Component action.
 * @param string $notification_type Notification Type key.
 * @param string $icon_class        Notification Small Icon.

 * For $this::register_email_type():
 * 
 * @param string $email_type        Type of email being sent.
 * @param array  $args              Email arguments.
 * @param string $notification_type Notification Type key.
 */
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Core_Notification_Abstract' ) ) {
	return;
}

/**
 * Formats Confetti Bits Notifications.
 * 
 * We use this in tandem with BuddyBoss Platform to give us 
 * email, web, and push notifications. An absolute godsend,
 * this makes it a million times easier to do that stuff, so 
 * we can focus on the actual functionality.
 * 
 * @package ConfettiBits\Notifications
 * @since 2.0.0
 */
class CB_Notifications_Component extends BP_Core_Notification_Abstract {

	/**
	 * Set up singleton instance.
	 */
	private static $instance = null;

	/**
	 * Return the instance kiddos.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor function.
	 * 
	 * Start your engines. We're gonna be so notified.
	 * 
	 * @see CB_Notifications_Component::start()
	 */
	public function __construct() {
		$this->start();
	}

	/**
	 * Registers notification formats.
	 * 
	 * What's the difference between this and start(), you may ask?
	 * Don't. Don't ask me, at least. Best guess? Start() calls load(),
	 * and load() actually registers notifications.
	 * 
	 * @UPDATE: I was mostly right. $this::start() calls $this::load(), 
	 * 			and also adds a bunch of filter hooks that dynamically
	 * 			update notification configurations based on the
	 * 			what is supplied to the inherited class methods.
	 * 			$this::load() is an abstract method that just calls
	 * 			whatever is inside of it.
	 * 
	 * Regardless, here we are. It works, doesn't it?
	 * 
	 * To be a little clearer: 
	 * 
	 *     1. The *notification group* is the one true group for all our
	 * 		  notifications. Everything we have will be registered under
	 * 		  the confetti_bits group (unless I change my mind later 
	 * 		  lmao).
	 * 
	 *     2. Notification *types* are going to be for our different 
	 * 		  components. So, cb_transactions, cb_requests, 
	 * 		  cb_participation, are all going to get their own types.
	 * 		  The type created by $this->register_notification_type()
	 * 		  is used to add a settings field for a user to control
	 * 		  their notification options. This is a little different
	 * 		  from how BuddyBoss does things, because their notification 
	 * 		  *groups* are based on their components, whereas we're 
	 * 		  putting all our components into the main 'confetti_bits' 
	 * 		  group. Ipso facto, our notification *types* are going to be 
	 * 		  component-based. It'll make sense eventually. Maybe.
	 * 
	 *     3. The notifications created by $this->register_notification() 
	 * 		  are going to distinguish which notification
	 * 		  should be sent, based on a user's action. So this would
	 * 		  be where the component_action comes in, like 
	 * 		  cb_send_bits, cb_anniversary_bits, cb_import_bits, etc.
	 * 		  The notifications are sent out in a function that's
	 * 		  typically defined in a cb-{$component}-notifications.php
	 * 		  file. If that file doesn't exist... why does it not?
	 * 		  Make one. That file needs to exist.
	 * 
	 *     4. Notification content is formatted in 
	 * 		  $this->format_notification(). The tokens are usually 
	 * 		  added in the same function talked about in part 3.
	 * 
	 */
	public function load() {

		$this->register_notification_group(
			'confetti_bits',
			esc_html__( 'Confetti Bits Notifications', 'confetti-bits' ), 
			esc_html__( 'Confetti Bits Notifications', 'confetti-bits' ),
		);

		// Register transactions notifications.
		$this->register_cb_transactions_notifications();

		// Register notifications for leadership bits.
		//		$this->register_confetti_bits_send_notifications();

		// Register notifications for transfers.
		//		$this->register_confetti_bits_transfer_notifications();

		// Register notifications for when ya boi imports a bunch.
		//		$this->register_confetti_bits_import_notifications();

		// @TODO: Redo this, because the structure for this is different.
		//		$this->register_confetti_bits_request_fulfillment_notifications();

		// @TODO: This was never actually implemented, so let's do that.
		//		$this->register_confetti_bits_request_sender_notifications();

		// Register notifications for requests.
		$this->register_cb_requests_notifications();

		// Register notifications for activity posts.
		$this->register_cb_activity_notifications();

		// Register notifications for group posts.
		$this->register_group_activity_notifications();

		// Register notifications for participation updates.
		$this->register_participation_notifications();

		// Register notifications for birthday bits.
		$this->register_birthday_notifications();

		// Register notifications for anniversary bits.
		$this->register_anniversary_notifications();

	}

	/**
	 * Registers all our notifications for the transactions component.
	 */
	public function register_cb_transactions_notifications() {

		// Register the leadership transactions notification type.
		$this->register_notification_type(
			'cb_transactions_send_bits',
			esc_html__( 'A company leader sends you Confetti Bits', 'confetti-bits' ),
			esc_html__( 'A company leader sends you Confetti Bits', 'confetti-bits' ),
			'confetti_bits',
			true
		);
		// Register the leadership transactions notification.
		$this->register_notification( 'confetti_bits', 'cb_send_bits', 'cb_transactions_send_bits' );

		// Register the transfer transactions notification type.
		$this->register_notification_type(
			'cb_transactions_transfer_bits',
			esc_html__( 'A team member sends you Confetti Bits', 'confetti-bits' ),
			esc_html__( 'A team member sends you Confetti Bits', 'confetti-bits' ),
			'confetti_bits',
			true
		);

		$this->register_notification_type(
			'cb_transactions_import_bits',
			esc_html__( 'Someone performs a Confetti Bits import', 'confetti-bits' ),
			esc_html__( 'Someone performs a Confetti Bits import', 'confetti-bits' ),
			'confetti_bits'
		);

		$this->register_notification(
			'confetti_bits',
			'cb_transactions_import_bits',
			'cb_transactions_import_bits'
		);

		// Register the transfer transactions notification.
		$this->register_notification( 'confetti_bits', 'cb_send_bits', 'cb_transactions_send_bits' );

		// Registers the email schema for when a leader sends someone Confetti Bits.
		$this->register_email_type(
			'cb-send-bits-email',
			array(
				'email_title'         => "{{transaction.sender_name}} Sent Confetti Bits!",
				'email_content'       => "<h4>{{transaction.sender_name}} just sent you {{transaction.amount}} Confetti Bits!<h4><p>They said: {{transaction.log_entry}}</p>",
				'email_plain_content' => "{{transaction.sender_name}} just sent you {{transaction.amount}} Confetti Bits! They said: {{transaction.log_entry}}",
				'situation_label'     => __( 'Someone sends Confetti Bits', 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone sends you Confetti Bits.', 'confetti-bits' ),
			),
			'cb_transactions_send_bits'
		);

		// Registers the email schema for when a team member sends someone Confetti Bits.
		$this->register_email_type(
			'cb-transfer-bits-email',
			array(
				'email_title'         => "{{transaction.sender_name}} Sent Confetti Bits!",
				'email_content'       => "<h4>{{transaction.sender_name}} just sent you {{transaction.amount}} Confetti Bits!<h4><p>They said: {{transaction.log_entry}}</p>",
				'email_plain_content' => "{{transaction.sender_name}} just sent you {{transaction.amount}} Confetti Bits! They said: {{transaction.log_entry}}",
				'situation_label'     => __( 'Someone sends Confetti Bits', 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone sends you Confetti Bits.', 'confetti-bits' ),
			),
			'cb_transactions_transfer_bits'
		);

		$this->register_email_type(
			'cb-transactions-import-bits-email',
			array(
				'email_title'         => __( 'Confetti Bits Imported', 'confetti-bits' ),
				'email_content'       => __( "Confetti Bits were just imported!", 'confetti-bits' ),
				'email_plain_content' => __( "Confetti Bits were just imported!", 'confetti-bits' ),
				'situation_label'     => __( "Confetti Bits are imported", 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when Confetti Bits are imported.', 'confetti-bits' ),
			),
			'cb_transactions_import_bits'
		);

	}

	/**
	 * Registers notifications for activity posts.
	 */
	public function register_cb_activity_notifications() {

		$this->register_notification_type(
			'cb_transactions_activity_bits',
			esc_html__( 'You get Confetti Bits for posting', 'confetti-bits' ),
			esc_html__( 'You get Confetti Bits for posting', 'confetti-bits' ),
			'confetti_bits',
		);

		$this->register_notification(
			'confetti_bits',
			'cb_activity_bits',
			'cb_transactions_activity_bits'
		);

		$this->register_email_type(
			'cb-transactions-activity-bits-email',
			array(
				'email_title'         => __( 'Nice!', 'confetti-bits' ),
				'email_content'       => __( 'You just got Confetti Bits for posting on TeamCTG!', 'confetti-bits' ),
				'email_plain_content' => __( 'You just got Confetti Bits for posting on TeamCTG!', 'confetti-bits' ),
				'situation_label'     => __( 'You get Confetti Bits for posting', 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when you receive Confetti Bits for posting.', 'confetti-bits' ),
			),
			'cb_transactions_activity_bits'
		);

	}

	/**
	 * Registers notifications for the requests component.
	 */
	public function register_cb_requests_notifications() {

		$this->register_notification_type(
			'cb_requests_new_request',
			esc_html__( 'You send in a Confetti Bits request', 'confetti-bits' ),
			esc_html__( 'You send in a Confetti Bits request', 'confetti-bits' ),
			'confetti_bits'
		);

		$this->register_notification_type(
			'cb_requests_admin_new_request',
			esc_html__( 'Someone sends in a Confetti Bits request', 'confetti-bits' ),
			esc_html__( 'Someone sends in a Confetti Bits request', 'confetti-bits' ),
			'confetti_bits'
		);

		$this->register_notification_type(
			'cb_requests_update_request',
			esc_html__( 'One of your Confetti Bits requests gets updated', 'confetti-bits' ),
			esc_html__( 'One of your Confetti Bits requests gets updated', 'confetti-bits' ),
			'confetti_bits'
		);

		$this->register_notification(
			'confetti_bits',
			'cb_requests_new_request',
			'cb_requests_new_request'
		);

		$this->register_notification(
			'confetti_bits',
			'cb_requests_new_request',
			'cb_requests_admin_new_request'
		);

		$this->register_notification(
			'confetti_bits',
			'cb_requests_update_request',
			'cb_requests_update_request'
		);

		$this->register_email_type(
			'cb-requests-new-request-email', [
				'email_title'         => __( 'Your Confetti Bits Request', 'confetti-bits' ),
				'email_content'       =>  '<h4>You requested {{request.item}} for {{request.amount}} Confetti Bits</h4><p>Please allow 4-6 weeks for your request to be fulfilled. Someone will reach out to you within that time frame and discuss further steps to fulfill your request!</p><p style="font-size:10px;"><b>Also please note that {{request.amount}} Confetti Bits will be deducted from your total balance once the request is fulfilled, and will not count toward additional request items or other company-sponsored rewards based on Confetti Bits balance.</b></p>',
				'email_plain_content' => "You successfully requested {{request.item}} for {{request.amount}} Confetti Bits. Please allow 4-6 weeks for your request to be fulfilled. Someone will reach out to you within that time frame and discuss further steps to fulfill your request! Also please note that {{request.amount}} Confetti Bits will be deducted from your total balance once the request is fulfilled, and will not count toward additional request items or other company-sponsored rewards based on Confetti Bits balance.",
				'situation_label'     => __( "You put in a Confetti Bits Request", 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when you send in Confetti Bits Requests', 'confetti-bits' ),
			],
			'cb_requests_new_request'
		);

		$this->register_email_type(
			'cb-requests-update-request-email', [
				'email_title'         => __( 'Your Confetti Bits Request for {{request.item}}', 'confetti-bits' ),
				'email_content'       =>  '<h4>The status of your request for "{{request.item}}" has been updated to {{request.status}}</h4><p>{{request.amount}} Confetti Bits will be deducted from your total balance.</p>',
				'email_plain_content' => 'The status of your request for "{{request.item}}" has been updated to {{request.status}}. {{request.amount}} Confetti Bits will be deducted from your total balance.',
				'situation_label'     => __( "Your Confetti Bits request is updated", 'confetti-bits' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when your Confetti Bits Requests get updated', 'confetti-bits' ),
			],
			'cb_requests_update_request'
		);

		$this->register_email_type(
			'cb-requests-admin-new-request-email', [
				'email_title'         => 'New Confetti Bits Request from {{applicant.name}}',
				'email_content'       => "A new Confetti Bits Request came in! {{applicant.name}} requested: {{request.item}}.",
				'email_plain_content' => "A new Confetti Bits Request came in! {{applicant.name}} requested: {{request.item}}.",
				'situation_label'     => "A new Confetti Bits Request comes in",
				'unsubscribe_text'    => 'You will no longer receive emails when Confetti Bits requests are sent.',
			],
			'cb_requests_admin_new_request'
		);

	}

	public function register_participation_notifications() {

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

		$this->register_notification_type(
			'cb_transactions_anniversary_bits',
			esc_html__( "Work Anniversary Notifications", 'confetti-bits' ),
			esc_html__( "Work Anniversary Notifications", 'confetti-bits' ),
			'confetti_bits'
		);

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

	public function format_notification( $content, $item_id, $secondary_item_id, $total_items, $component_action_name, $component_name, $notification_id, $screen ) {

		$text = '';
		$retval = [
			'link' => home_url("confetti-bits"),
		];

		if ( ( 'confetti_bits' === $component_name && 'cb_send_bits' === $component_action_name ) 
			|| ( 'confetti_bits' === $component_name && 'cb_transfer_bits' === $component_action_name ) ) {

			$retval['title'] = "Someone just sent Confetti Bits!";
			$retval['text'] = esc_html__( cb_core_get_user_display_name( $item_id ) . ' just sent you bits!', 'confetti-bits' );
			return $retval;

		}

		if ( 'confetti_bits' === $component_name && 'cb_activity_bits' === $component_action_name ) {

			$retval['title'] = "You just got Confetti Bits!";
			$retval['text'] = $item_id === 1 ? 
				esc_html__( 'You just got ' . $item_id . ' Confetti Bit for posting!', 'confetti-bits' )
				: esc_html__( 'You just got ' . $item_id . ' Confetti Bits for posting!', 'confetti-bits' );
			return $retval;

		}

		if ( 'confetti_bits' === $component_name && 'cb_transactions_import_bits' === $component_action_name ) {

			$retval['text'] = cb_core_get_user_display_name( $item_id ) . ' just imported bits!';
			$retval['title'] = "Someone just imported Confetti Bits!";
			return $retval;

		}

		if ( 'confetti_bits' === $component_name && 'cb_bits_request' === $component_action_name ) {

			$retval['text'] = cb_core_get_user_display_name( $secondary_item_id ) . ' just sent in a new Confetti Bits Request!';
			$retval['title'] = "New Confetti Bits Request!";
			return $retval;

		}

		if ( 'confetti_bits' === $component_name && 'cb_requests_new_request' === $component_action_name ) {

			$retval['text'] = ' Your request has been submitted.';
			$retval['title'] = "New Confetti Bits Request!";
			return $retval;

		}

		if ( 'confetti_bits' === $component_name && 'cb_requests_admin_new_request' === $component_action_name ) {

			$retval['text'] = cb_core_get_user_display_name( $item_id ) . ' just sent in a new Confetti Bits Request!';
			$retval['title'] = "New Confetti Bits Request!";
			return $retval;

		}

		if ( 'confetti_bits' === $component_name && 'cb_requests_update_request' === $component_action_name ) {

			$retval['text'] = cb_core_get_user_display_name( $secondary_item_id ) . ' just updated one of your requests!';
			$retval['title'] = "Request Update";
			return $retval;

		}

		if ( ( 'groups' === $component_name && 'activity_update' === $component_action_name ) ) {

			$group = groups_get_group( $item_id );
			$retval['title'] = "Activity update in " . $group->name;
			$retval['text'] = bp_core_get_user_displayname( $secondary_item_id ) . ' just posted in the group ' . $group->name;
			$retval['link'] = esc_url( bp_get_group_permalink( $group ) );
			return $retval;

		}

		if ( 'confetti_bits' === $component_name && 'cb_participation_status_update' === $component_action_name ) {

			$applicant_name = cb_core_get_user_display_name( $item_id );
			$admin_name = cb_core_get_user_display_name( $secondary_item_id );
			$retval['text'] = "{$admin_name} just updated your participation status.";
			$retval['title'] = "Participation Update";
			return $retval;

		}

		if ( 'confetti_bits' === $component_name && 'cb_birthday_bits' === $component_action_name ) {

			$retval['title'] = "Happy Birthday!";
			$retval['text'] = "We sent you a few Confetti Bits to celebrate!";
			return $retval;

		}

		if ( 'confetti_bits' === $component_name && 'cb_anniversary_bits' === $component_action_name ) {

			$retval['text'] = "We sent you a few Confetti Bits to celebrate!";
			$retval['title'] = "Happy Anniversary!";
			return $retval;

		}

		return $retval;

	}


	/*
	public function register_confetti_bits_send_notifications() {

		$this->register_notification_type(
			'cb_transactions_send_bits',
			esc_html__( 'A company leader sends you Confetti Bits', 'confetti-bits' ),
			esc_html__( 'A company leader sends you Confetti Bits', 'confetti-bits' ),
			'confetti_bits',
		);

		$this->register_notification(
			'confetti_bits',
			'cb_send_bits',
			'cb_transactions_send_bits'
		);

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

	}

	public function register_confetti_bits_transfer_notifications() {

		$this->register_notification_type(
			'cb_transactions_transfer_bits',
			esc_html__( 'Someone sends you Confetti Bits', 'confetti-bits' ),
			esc_html__( 'Someone sends you Confetti Bits', 'confetti-bits' ),
			'confetti_bits',
		);

		$this->register_notification(
			'confetti_bits',
			'cb_transfer_bits',
			'cb_transactions_transfer_bits'
		);

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

	}



	public function register_confetti_bits_import_notifications() {



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
*/

}