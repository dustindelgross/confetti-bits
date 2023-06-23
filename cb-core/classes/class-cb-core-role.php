<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CB Core Roles Component
 *
 * Helps manage roles and capabilities within the application.
 * 
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
class CB_Core_Role {

	/** Variables ***************************************************************/

	/**
	 * The role's id.
	 * 
	 * @var string
	 */
	public $id;

	/**
	 * The role's label.
	 * 
	 * @var string
	 */
	public $label = '';

	/**
	 * The capabilities of the role.
	 * 
	 * @var array
	 */
	public $caps = [];

	/** Methods ***************************************************************/		

	/**
	 * Construct a role.
	 * 
	 * Attempts to populate data when supplied with an id.
	 * 
	 * @param string $id A text-based identifier for the role.
	 * @param string $label A label for the role.
	 * @param array $caps An array of key-value pairs of capabilities.
	 */
	public function __construct( $id = '', $label = '', $caps = [] ) {

		$this->id = $id;
		$this->label = $label;
		$this->caps = $caps;
		
		$this->check_for_updates();
		
	}
	
	/**
	 * Adds an action to setup global values for roles.
	 */
	public function setup_actions() {
		add_action( 'cb_setup_globals', [ $this, 'setup_globals' ] , 10 );
	}
	
	/**
	 * Maybe setup globals here?
	 */
	public function setup_globals() {}
	
	/**
	 * Checks against WP roles to see if they need to be changed.
	 */
	public function check_for_updates() {
		
		$role = get_role("cb_{$this->id}");
		
		if ( $role !== null ) {
			
			$remove = array_diff_assoc( $role->capabilities, $this->caps );
			$add = array_diff_assoc( $this->caps, $role->capabilities );
			
			if ( !empty( $remove ) ) {
				$this->remove_caps( $remove );
			}
			
			if ( !empty( $add ) ) {
				$this->add_caps( $add );
			}
						
		} else {
			add_role("cb_{$this->id}", $this->label, $this->caps );
		}
		
	}
	
	/**
	 * Adds the given capabilities to the WP role.
	 * 
	 * @param WP_Role $role An instance of WP_Role.
	 * @param array $caps An array of key-value pairs of capabilities to add.
	 */
	public function add_caps( $caps = [] ) {
		
		$role = get_role("cb_{$this->id}");
		
		foreach( $caps as $key => $value ) {
			$role->add_cap($key, $value);
		}
		
	}
	
	/**
	 * Removes the given capabilities from the WP role.
	 * 
	 * @param WP_Role $role An instance of WP_Role.
	 * @param array $caps An array of key-value pairs of capabilities to remove.
	 */
	public function remove_caps( $caps = [] ) {
		
		$role = get_role("cb_{$this->id}");
		
		foreach ( $caps as $key => $value ) {
			$role->remove_cap($key);
		}
	}
}