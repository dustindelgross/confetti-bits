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
class CB_Core_Role_Manager {

	/** Variables ***************************************************************/

	/**
	 * It's a secret.
	 */
	private $data;

	/** Methods ***************************************************************/		

	/**
	 * Construct the role manager.
	 */
	public function __construct() {}

	/**
	 * Magic method for getting ConfettiBits variables.
	 *
	 * @param string $key Key to return the value for.
	 *
	 * @return mixed
	 * 
	 * @package ConfettiBits\Core
	 * @since 2.3.0
	 */
	public function __get( $key ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
	}

	/**
	 * Magic method for setting ConfettiBits variables.
	 *
	 * @param string $key   Key to set a value for.
	 * @param mixed  $value Value to set.
	 * 
	 * @package ConfettiBits\Core
	 * @since 3.0.0
	 */
	public function __set( $key, $value ) {
		$this->data[ $key ] = $value; 
	}

	/**
	 * Adds an action to setup global values for roles.
	 */
	public function setup_actions() {
		add_action( 'cb_setup_globals', [ $this, 'setup_globals' ] , 10 );
	}

	/**
	 * Sets up global values based on provided data.
	 */
	public function setup_globals() {
		Confetti_Bits()->roles->{$this->id} = $this;
	}
	
}