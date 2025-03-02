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
 * See BuddyBoss docs for more info. 
 * ({@link https://www.buddyboss.com/resources/dev-docs/web-development/migrating-custom-notifications-to-modern-notifications-api/})
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

		// Register events notifications.
		$this->register_cb_events_notifications();

		// Register notifications for requests.
		$this->register_cb_requests_notifications();

		// Register notifications for activity posts.
		$this->register_cb_activity_notifications();

		// Register notifications for group posts.
//		$this->register_group_activity_notifications();

		// Register notifications for participation updates.
		$this->register_participation_notifications();

		// Register notifications for birthday bits.
		$this->register_birthday_notifications();

		// Register notifications for anniversary bits.
		$this->register_anniversary_notifications();

		// Register transactions notifications.
		$this->register_cb_spot_bonus_notifications();

		// Register volunteer hours notifications.
		$this->register_cb_volunteers_notifications();

	}

	/**
	 * Registers all our notifications for the transactions component.
	 */
	public function register_cb_spot_bonus_notifications() {

		// Register the leadership transactions notification type.
		$this->register_notification_type(
			'cb_transactions_spot_bonus',
			esc_html__( 'You get spot bonus bits', 'confetti-bits' ),
			esc_html__( 'You get spot bonus bits', 'confetti-bits' ),
			'confetti_bits',
			true
		);

		// Register the leadership transactions notification.
		$this->register_notification( 'transactions', 'cb_transactions_spot_bonus', 'cb_transactions_spot_bonus' );

		// Registers the email schema for when a leader sends someone Confetti Bits.
		$this->register_email_type( 'cb-transactions-spot-bonus', [
			'email_title'         => "You're on the Spot!",
			'email_content'       => "<h4>You just got a spot bonus!</h4><p>We went ahead and sent you {{transaction.amount}} Confetti Bits to celebrate. :)</p>",
			'email_plain_content' => "You just got a spot bonus! We went ahead and sent you {{transaction.amount}} Confetti Bits to celebrate. :)",
			'situation_label'     => __( 'You receive a spot bonus', 'confetti-bits' ),
			'unsubscribe_text'    => __( 'You will no longer receive emails when you receive a spot bonus.', 'confetti-bits' ),
		], 'cb_transactions_spot_bonus' );

	}
	
	/**
	 * Registers all our notifications for the transactions component.
	 */
	public function register_cb_volunteers_notifications() {

		// Register the leadership transactions notification type.
		$this->register_notification_type(
			'cb_transactions_volunteer_bits',
			esc_html__( 'You get bits for volunteering', 'confetti-bits' ),
			esc_html__( 'You get bits for volunteering', 'confetti-bits' ),
			'confetti_bits',
			true
		);

		// Register the leadership transactions notification.
		$this->register_notification( 'transactions', 'cb_transactions_volunteer_bits', 'cb_transactions_volunteer_bits' );

		// Registers the email schema for when a leader sends someone Confetti Bits.
		$this->register_email_type( 'cb-transactions-volunteer-bits', [
			'email_title'         => "You're a Rockstar!",
			'email_content'       => "<h4>Thanks for volunteering for {{event.event_title}}!</h4><p>Thank you SO much for taking the time to help out. It means the world to us! We went ahead and sent you {{amount}} Confetti Bits to celebrate. :)</p>",
			'email_plain_content' => "Thanks for volunteering for {{event.event_title}}! Thank you SO much for taking the time to help out - it means the world to us! We went ahead and sent you {{amount}} Confetti Bits to celebrate. :)",
			'situation_label'     => __( 'You receive bits for volunteering', 'confetti-bits' ),
			'unsubscribe_text'    => __( 'You will no longer receive emails when you receive volunteer bits.', 'confetti-bits' ),
		], 'cb_transactions_volunteer_bits' );

	}

	/**
	 * Registers all our notifications for the transactions component.
	 */
	public function register_cb_events_notifications() {

		
		// Register the leadership transactions notification type.
		$this->register_notification_type(
			'cb_events_new_transactions',
			esc_html__( 'You get participation bits', 'confetti-bits' ),
			esc_html__( 'You get participation bits', 'confetti-bits' ),
			'confetti_bits',
			true
		);

		// Register the leadership transactions notification.
		$this->register_notification( 'events', 'cb_events_new_transactions', 'cb_events_new_transactions' );

		// Register the transfer transactions notification type.
		$this->register_notification_type(
			'cb_events_contest_new_transactions',
			esc_html__( 'You claim a contest placement', 'confetti-bits' ),
			esc_html__( 'You claim a contest placement', 'confetti-bits' ),
			'confetti_bits',
			true
		);

		$this->register_notification('events', 'cb_events_contest_new_transactions', 'cb_events_contest_new_transactions');

		// Registers the email schema for when a leader sends someone Confetti Bits.
		$this->register_email_type( 'cb-events-new-transactions', [
			'email_title'         => "{{event.event_title}} Participation",
			'email_content'       => "<h4>Thank you for participating in {{event.event_title}}!<h4><p>We went ahead and sent you {{event.participation_amount}} Confetti Bits for participating. We hope to see you at the next event!</p>",
			'email_plain_content' => "Thank you for participating in {{event.event_title}}! We went ahead and sent you {{event.participation_amount}} Confetti Bits for participating. We hope to see you at the next one!",
			'situation_label'     => __( 'You participate in an event', 'confetti-bits' ),
			'unsubscribe_text'    => __( 'You will no longer receive emails when you receive participation Confetti Bits.', 'confetti-bits' ),
		], 'cb_events_new_transactions' );

		// Registers the email schema for when a team member sends someone Confetti Bits.
		$this->register_email_type( 'cb-events-contest-new-transactions', [
			'email_title'         => "{{contest.pretty_placement}} Place Winner in {{event.event_title}}!",
			'email_content'       => "<h4>Congratulations on winning {{contest.pretty_placement}} place!<h4><p>We just sent you {{contest.amount}} Confetti Bits. We're excited to see you bring it at the next competition - keep crushing it!</p>",
			'email_plain_content' => "Congratulations on winning {{contest.pretty_placement}} place! We just sent you {{contest.amount}} Confetti Bits. We're excited to see you bring it at the next competition - keep crushing it!",
			'situation_label'     => __( 'You claim a contest placement', 'confetti-bits' ),
			'unsubscribe_text'    => __( 'You will no longer receive emails when you claim a contest placement', 'confetti-bits' ),
		], 'cb_events_contest_new_transactions');

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
		$this->register_notification( 'transactions', 'cb_send_bits', 'cb_transactions_send_bits' );

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
			'transactions',
			'cb_transactions_import_bits',
			'cb_transactions_import_bits'
		);

		// Register the transfer transactions notification.
		$this->register_notification( 'transactions', 'cb_send_bits', 'cb_transactions_send_bits' );

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
			'activity',
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
			'requests',
			'cb_requests_new_request',
			'cb_requests_new_request'
		);

		$this->register_notification(
			'requests',
			'cb_requests_new_request',
			'cb_requests_admin_new_request'
		);

		$this->register_notification(
			'requests',
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
			'participation',
			'cb_participation_status_update',
			'cb_participation_status_update'
		);

		$this->register_notification(
			'participation',
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
			[ 'cb_groups_activity_post' ],
			50
		);

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
			'transactions',
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
			'transactions',
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

		$notification_map = [
			'requests' => [
				'cb_requests_new_request' => ['title' => "New Confetti Bits Request!", 'text' => 'Your request has been submitted.'],
				'cb_requests_admin_new_request' => ['title' => "New Confetti Bits Request!", 'text' => '%s just sent in a new Confetti Bits Request!', 'item_id' => $item_id],
				'cb_requests_update_request' => ['title' => "Request Update", 'text' => '%s just updated one of your requests!', 'item_id' => $item_id],
			],
			'transactions' => [
				'cb_send_bits' => ['title' => "Someone just sent Confetti Bits!", 'text' => '%s just sent you bits!', 'item_id' => $item_id ],
				'cb_transfer_bits' => ['title' => "Someone just sent Confetti Bits!", 'text' => '%s just sent you bits!', 'item_id' => $item_id ],
				'cb_transactions_import_bits' => ['title' => "Someone just imported Confetti Bits!", 'text' => '%s just imported bits!', 'item_id' => $item_id],
				'cb_birthday_bits' => ['title' => "Happy Birthday!", 'text' => "We sent you a few Confetti Bits to celebrate!"],
				'cb_anniversary_bits' => ['title' => "Happy Anniversary!", 'text' => "We sent you a few Confetti Bits to celebrate!"],
				'cb_activity_bits' => ['title' => "You just got Confetti Bits!", 'text' => 'You just got %s Confetti Bit%s for posting!', 'item_id' => $item_id],
				'cb_transactions_spot_bonus' => ['title' => "You're on the spot!", 'text' => "We sent you %s Confetti Bits to celebrate!", 'item_id' => $item_id],
				'cb_transactions_volunteer_bits' => ['title' => "Thank you so much for volunteering", 'text' => "We sent you %s Confetti Bits to celebrate!", 'event_id' => $item_id, 'recipient_id' => $secondary_item_id ]
			],
			'participation' => [
				'cb_participation_status_update' => ['title' => "Participation Update", 'text' => '%s just updated your participation status.', 'item_id' => $item_id ],
			],
			'events' => [ 
				'cb_events_new_transactions' => ['title' => "%s Participation", 'text' => 'Thanks for participating in %s!', 'item_id' => $item_id],
				'cb_events_contest_new_transactions' => ['title' => "%s Contest Winner", 'text' => 'Congratulations on winning the %s contest!', 'item_id' => $item_id],
			],
			'groups' => ['activity_update' => ['title' => "Activity update in %s", 'text' => '%s just posted in the group %s', 'item_id' => $item_id, 'secondary_item_id' => $secondary_item_id]],
		];

		if ( !isset( $notification_map[$component_name] ) ) {
			return;
		}

		if ( !isset( $notification_map[$component_name][$component_action_name] ) ) {
			return;
		}

		return call_user_func("cb_{$component_name}_format_notifications", $component_action_name, $notification_map[$component_name][$component_action_name]);



	}

}
