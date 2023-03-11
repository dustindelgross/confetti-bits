<?php 
/**
 * Confetti Bits Core Components
 * 
 * This is where we house all of our component-related functions.
 * These functions are typically used to determine what component
 * a user is interacting with, which resources are being requested, 
 * and determining access levels for certain features within the app.
 */

if ( ! function_exists( 'cb_core_admin_get_components' ) ) {

	function cb_core_admin_get_components( $type = 'all' ) {

		$components = cb_core_get_components( $type );

		return apply_filters( 'cb_core_admin_get_components', $components, $type );

	}
}

if ( ! function_exists( 'cb_core_get_components' ) ) {

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
}

if ( ! function_exists( 'cb_current_component' ) ) {

	function cb_current_component() {

		$cb                = Confetti_Bits();
		$current_component = !empty( $cb->current_component )
			? $cb->current_component
			: false;

		return apply_filters( 'cb_current_component', $current_component );

	}
}

if ( ! function_exists( 'cb_is_active' ) ) {

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
}

if ( ! function_exists( 'cb_is_current_component' ) ) {

	function cb_is_current_component( $component = '' ) {

		$is_current_component = false;

		if ( empty( $component ) ) {
			return false;
		}

		$cb = Confetti_Bits();
		$bp = buddypress();

		if ( ! empty( $bp->current_component ) ) {

			if ( $bp->current_component == $component ) {
				$is_current_component = true;

			} elseif ( isset( $cb->{$component}->root_slug ) && $cb->{$component}->root_slug == $bp->current_component ) {
				$is_current_component = true;

			} elseif ( isset( $cb->{$component}->slug ) && $cb->{$component}->slug == $bp->current_component ) {
				$is_current_component = true;

			} elseif ( $key = array_search( $component, $cb->active_components ) ) {
				if ( strstr( $bp->current_component, $key ) ) {
					$is_current_component = true;
				}

			} else {

				foreach ( $cb->active_components as $id ) {

					if ( empty( $cb->{$id}->slug ) || $cb->{$id}->slug != $bp->current_component ) {
						continue;
					}

					if ( $id == $component ) {
						$is_current_component = true;
						break;
					}
				}
			}
		}

		return apply_filters( 'cb_is_current_component', $is_current_component, $component );

	}
}

if ( ! function_exists( 'cb_is_confetti_bits_component' ) ) {

	function cb_is_confetti_bits_component() {

		return (bool) cb_is_current_component( 'confetti-bits' );

	}
}

if ( ! function_exists( 'cb_is_user_confetti_bits' ) ) {

	function cb_is_user_confetti_bits() {

		return (bool) ( bp_is_user() && cb_is_confetti_bits_component() );

	}
}