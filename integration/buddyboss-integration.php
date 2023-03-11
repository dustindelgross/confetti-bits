<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class Confetti_Bits_BuddyBoss_Integration extends BP_Integration {

	public function __construct() {
		$this->start(
			'confetti-bits',
			__( 'Confetti Bits', 'confetti-bits' ),
			'confetti-bits',
			array(
				'required_plugin' => array(),
			)
		);

		add_filter( 'plugin_action_links',               array( $this, 'action_links' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'action_links' ), 10, 2 );
	}

	public function setup_admin_integration_tab() {

		require_once 'buddyboss-addon-integration-tab.php';

		new Confetti_Bits_BuddyBoss_Admin_Integration_Tab(
			"bp-{$this->id}",
			$this->name,
			array(
				'root_path'       => CONFETTI_BITS_PLUGIN_PATH . '/integration',
				'root_url'        => CONFETTI_BITS_PLUGIN_URL . '/integration',
				'required_plugin' => $this->required_plugin,
			)
		);
	}

	public function action_links( $links, $file ) {

		// Return normal links if not BuddyPress.
		if ( CONFETTI_BITS_PLUGIN_BASENAME != $file ) {
			return $links;
		}

		// Add a few links to the existing links array.
		return array_merge(
			$links,
			array(
				'settings' => '<a href="' . esc_url( bp_get_admin_url( 'admin.php?page=bp-integrations&tab=bp-confetti-bits' ) ) . '">' . __( 'Settings', 'confetti-bits' ) . '</a>',
			)
		);
	}
}