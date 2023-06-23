<?php 
/**
 * Confetti Bits Core Components
 * 
 * This is where we house all of our component-related functions.
 * These functions are typically used to determine what component
 * a user is interacting with, which resources are being requested, 
 * and determining access levels for certain features within the app.
 */

/*
function cb_core_admin_get_components( $type = 'all' ) {

	$components = cb_core_get_components( $type );

	return apply_filters( 'cb_core_admin_get_components', $components, $type );

}
*/

/**
 * Sets a global based on the current URI.
 * 
 * The goal here at one point was to have each component
 * dynamically registered through the URI and accessible 
 * via a stacking path, like this: 
 * 
 * https://{{domain}}/{{component}}/{{action}}/{{item}}
 * 
 * This is no easy feat. It would also require some 
 * significant shenanigans on our end to make that happen.
 * So we've opted to using REST API endpoints, and just
 * checking whether $cb->current_component exists, and
 * is indeed === 'confetti-bits'.
 * 
 * @package ConfettiBits\Core
 * @since 1.0.0
 */
function cb_core_set_uri_globals() {

	$cb = Confetti_Bits();

	$path = strtok( esc_url( $_SERVER["REQUEST_URI"] ), "?" );
	$parts = explode( "/", $path );
	foreach ( (array) $parts as $key => $uri_chunk ) {
		if ( empty( $parts[$key] ) ) {
			unset( $parts[$key] );
		}
	}

	$parts = array_merge( array(), $parts );
	if ( isset($parts[0] ) ) {
		$cb->current_component = $parts[0] === 'confetti-bits' ? $parts[0] : "";	
	}

}

/*
function cb_core_get_components( $type = 'all' ) {

	$required_components = array(
		'transactions' => array(
			'title'       => __( 'Confetti Bits Transactions', 'confetti-bits' ),
			'description' => __( 'Allow members to send and receive Confetti Bits.', 'confetti-bits' ),
			'default'     => true,
		),
	);

	$optional_components = array(
		'downloads' => array(
			'title'       => __( 'Confetti Bits Downloads', 'confetti-bits' ),
			'description' => __( 'Record sitewide file download activity.', 'confetti-bits' ),
			'default'     => true,
		),
	);

	$default_components = array();

	foreach( array_merge( $required_components, $optional_components ) as $key => $component ) {
		if ( isset( $component['default'] ) && true === $component['default'] ) {
			$default_components[ $key ] = $component;
		}
	}

	switch ( $type ) {
		case 'required' :
			$components = $required_components;
			break;
		case 'optional' :
			$components = $optional_components;
			break;
		case 'default' :
			$components = $default_components;
			break;
		case 'all' :
		default :
			$components = array_merge( $required_components, $optional_components );
			break;
	}

	return apply_filters( 'cb_core_get_components', $components, $type );

}


function cb_current_component() {

	$cb                = Confetti_Bits();
	$current_component = !empty( $cb->current_component )
		? $cb->current_component
		: false;

	return apply_filters( 'cb_current_component', $current_component );

}

function cb_is_active( $component = '' ) {

	$retval = false;

	if ( empty( $component ) ) {
		$component = cb_current_component();
	}

	if ( isset( Confetti_Bits()->active_components[ $component ] ) 
		|| isset( Confetti_Bits()->required_components[ $component ] ) ) {

		$retval = true;

	}

	$retval = apply_filters( "cb_is_{$component}_active", $retval );

	return apply_filters( 'cb_is_active', $retval, $component );
}
*/

/**
 * Does an entire song and dance to see if this is the component you want.
 * 
 * 1. Checks to see if the current component is the same as the supplied
 * component. 
 * 2. Checks to see if the supplied component has a slug that matches.
 * 3. Checks to see if the supplied component is in the array of active 
 * components.
 * 4. Checks one last time to see if any of the active components has a
 * slug that matches the supplied component.
 * 
 * @param string $component The component to check for.
 * 
 * @return bool True if it's one of ours, false in any other scenario.
 * 
 * @package ConfettiBits\Core
 * @since 1.0.0
 */
function cb_is_current_component( $component = '' ) {

	if ( empty( $component ) ) {
		return false;
	}

	$cb = Confetti_Bits();

	if ( empty( $cb->current_component ) ) {
		return false;
	}

	if ( $cb->current_component == $component ) {
		return true;
	} 

	if ( isset( $cb->{$component}->slug ) && $cb->{$component}->slug == $cb->current_component ) {
		return true;
	}

	if ( $key = array_search( $component, $cb->active_components ) ) {
		if ( strstr( $cb->current_component, $key ) ) {
			return true;
		}

	}

	foreach ( $cb->active_components as $id ) {

		if ( empty( $cb->{$id}->slug ) || $cb->{$id}->slug != $cb->current_component ) {
			continue;
		}

		if ( $id == $component ) {
			return true;
		}
	}
	
	return false;

}

/**
 * Ask the wizard if we could have some porridge.
 * 
 * @return bool Whether we may have some porridge.
 * 
 * @package ConfettiBits\Core
 * @since 1.0.0
 */
function cb_is_confetti_bits_component() {
	return cb_is_current_component( 'confetti-bits' );
}

/**
 * Checks whether there is a currently logged-in user.
 * 
 * @see is_user_logged_in()
 * @link https://developer.wordpress.org/reference/functions/is_user_logged_in/
 * 
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
function cb_is_user() {
	return is_user_logged_in();
}

/**
 * Checks to see if we're in the land of wonder.
 * 
 * @return bool Whether we're in the land of wonder.
 * 
 * @package ConfettiBits\Core
 * @since 1.0.0
 */
function cb_is_user_confetti_bits() {
	return (bool) ( cb_is_user() && cb_is_confetti_bits_component() );
}

/**
 * Gives us an array of all active components.
 * 
 * @return array Active components.
 * 
 * @package ConfettiBits\Core
 * @since 3.0.0
 */
function cb_core_get_active_components() {
	return Confetti_Bits()->active_components;
}