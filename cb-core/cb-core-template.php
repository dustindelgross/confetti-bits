<?php 
/** 
 * Confetti Bits Template Functions
 */

/**
 * CB Member Locate Template Part
 * 
 * Attempts to locate the specified template in the TeamCTG 
 * Child Theme, located at '/members/single/confetti-bits-hub/cb-{$template}.php'.
 * 
 * @param string $template The template to look for.
 * 
 * @return string The template, if found.
 * 
 */
function cb_member_locate_template_part ( $template = '' ) {

	if ( ! $template  ) {
		return '';
	}

	$cb_template_parts = array(
		'members/single/confetti-bits-hub/cb-%s.php',
	);

	$templates = array();

	foreach ( $cb_template_parts as $cb_template_part ) {
		$templates[] = sprintf( $cb_template_part, $template );
	}

	return locate_template( $templates, true, true );
}

/**
 * CB Member Get Template Part
 * 
 * Loads a template part based on the template
 * that gets passed in.
 * 
 * @see cb_member_locate_template_part()
 * 
 * @return An array of the active templates.
 */
function cb_member_get_template_part( $template = '' ) {

	$located = cb_member_locate_template_part( $template );

	if ( false !== $located ) {
		$slug = str_replace( '.php', '', $located );
		$name = null;

		do_action( 'get_template_part_' . $slug, $slug, $name );

		load_template( $located, true );
	}

	return $located;
}

/**
 * Confetti Bits Get Active Templates
 * 
 * Sets up the templates to show users based on permissions.
 * 
 * @return An array of the active templates.
 */
function cb_get_active_templates() {

	$debug = isset( $_GET['cb_debug'] ) ? $_GET['cb_debug'] : false;
	$templates = array();

	switch ( true ) {

		case ( cb_is_confetti_bits_component() && cb_is_user_admin() && ! cb_is_user_site_admin() && ! cb_is_user_executive()  && ! cb_is_user_participation_admin() ) :
			$templates = array (
				'Dashboard Header'	=> 'dashboard-header',
				'Dashboard'			=> 'dashboard',
				'Participation'		=> 'participation',
				'My Transactions'	=> 'transactions',
			);
			break;

		case ( cb_is_confetti_bits_component() && cb_is_user_site_admin() ) :
			$templates = array (
				'Dashboard Header'		=> 'dashboard-header',
				'Dashboard'				=> 'dashboard',
				'Culture Admin'			=> 'participation-admin',
				'Participation'			=> 'participation',
				'My Transactions'	=> 'transactions',
			);
			break;

		case ( cb_is_confetti_bits_component() && cb_is_user_executive() && ! cb_is_user_site_admin() ) :
			$templates = array (
				'Dashboard Header'	=> 'dashboard-header',
				'Dashboard'			=> 'dashboard',
				'Culture Admin'		=> 'participation-admin',
				'Participation'		=> 'participation',
				'My Transactions'	=> 'transactions',
			);
			break;

		case ( cb_is_confetti_bits_component() && cb_is_user_participation_admin() && ! cb_is_user_site_admin() && ! cb_is_user_executive() ) :
			$templates = array (
				'Dashboard Header'	=> 'dashboard-header',
				'Dashboard'			=> 'dashboard',
				'Culture Admin'		=> 'participation-admin',
				'Participation'		=> 'participation',
				'My Transactions'	=> 'transactions',
			);
			break;

		case ( cb_is_confetti_bits_component() ) :
		default :
			$templates = array(
				'Dashboard Header'	=> 'dashboard-header',
				'Dashboard'			=> 'dashboard',
				'Participation'		=> 'participation',
				'My Transactions'	=> 'transactions',
			);
			break;
	}

	if ( 1 == $debug ) {
		$templates['Debug'] = 'debug';
	}

	if ( cb_is_user_site_admin() ) {
		$templates['Events'] = 'events';
	}

	return $templates;

}


/**
 * CB Member Template Part
 * 
 * Renders the member template part appropriate for the current page
 * 
 * @since 1.0.0
 */
function cb_member_template_part() {
	
	$templates = array_values( cb_get_active_templates() );

	foreach ( $templates as $template ) {
		cb_member_get_template_part( $template );
	}

	do_action( 'cb_after_member_body' );
}

/**
 * CB Container
 *
 * A function that lets us wrap content dynamically
 * with the supplied arguments, so we can have more
 * fun and less pain.
 *
 * @param array $args An associative array of the following: {
 *     @var string $container The HTML tag to use for the container. Default 'div'.
 *     @var string $container_id The ID to use for the container. Default empty.
 *     @var array $container_classes An array of classes to use for the container.
 *     Default empty.
 *     @var string $output The content to wrap in the container. Default empty.
 * }
 * @return string The HTML markup to output.
 * @since 2.3.0
 */
function cb_container( $args = array() ) {

	$r = wp_parse_args( $args, array(
		'container' => 'div',
		'id' => '',
		'classes' => array(),
		'output' => '',
	));

	$valid_containers = array(
		'div'  => true,
		'ul'   => true,
		'ol'   => true,
		'span' => true,
		'p'    => true,
	);

	$default_classes        = array();
	$r['classes'] = array_merge( $r['classes'], $default_classes );

	if ( empty( $r['container'] ) || ! isset( $valid_containers[ $r['container'] ] ) || empty( $r['output'] ) ) {
		return;
	}

	$container = $r['container'];
	$id = '';
	$classes = '';
	$output = trim($r['output']);

	if ( ! empty( $r['id'] ) ) {
		$id = ' id="' . esc_attr( $r['id'] ) . '"';
	}

	if ( ! empty( $r['classes'] ) && is_array( $r['classes'] ) ) {
		$classes = ' class="' . join( ' ', array_map( 'sanitize_html_class', $r['classes'] ) ) . '"';
	}

	// Print the wrapper and its content.
	return sprintf('<%1$s%2$s%3$s>%4$s</%1$s>', $container, $id, $classes, $output);
}

/**
 * CB Get Button
 * 
 * Formats a button element with the given arguments
 * 
 * @param array $args {
 *     @var string $id The ID of the button
 *     @var string $content The content of the button
 *     @var string $type The type of the button (button, reset, submit). Default 'button'
 *     @var array $classes An array of classes to add to the button.
 *     Default array('cb-button')
 * }
 * @return string The formatted button element
 * @since 2.3.0
 */
function cb_get_button( $args = array() ) {

	$r = wp_parse_args( $args, array(
		'classes' => array(),
		'id' => '',
		'content' => '',
		'type' => 'button',
		'custom_attr' => array()
	));

	$valid_types = array( 'button', 'reset', 'submit' );

	if ( !in_array( $r['type'], $valid_types ) ) {
		return;
	}

	$id = '';
	$classes = '';
	$custom_attrs = '';
	$default_classes = array( 'cb-button' );
	$content = trim( $r['content'] );
	$type = ' type="' . trim($r['type']) . '"';

	$r['classes'] = array_merge( $r['classes'], $default_classes );

	if ( !empty( $r['id'] ) ) {
		$id = ' id="' . esc_attr( $r['id'] ) . '"';
	}

	if ( ! empty( $r['classes'] ) && is_array( $r['classes'] ) ) {
		$classes = ' class="' . join( ' ', array_map( 'sanitize_html_class', $r['classes'] ) ) . '"';
	}

	if ( !empty( $r['custom_attr'] ) && is_array( $r['custom_attr'] ) ) {
		foreach ( $r['custom_attr'] as $key => $val) {
			$custom_attrs .= ' data-' . esc_attr($key) . '="' . esc_attr($val) . '"';
		}
	}

	return "<button{$type}{$id}{$classes}{$custom_attrs}>{$content}</button>";

}

/**
 * CB AJAX Table
 * 
 * Returns a table for the specified component. Typically
 * used for displaying items from the database that are
 * fetched via AJAX. Optionally, a pagination bar can be
 * displayed above and below the table.
 * 
 * @param array $args {
 *     @var string $component The component to display the table for.
 *     @var bool $paginated Whether or not to display a pagination bar.
 * }
 * @return string|void The HTML for the table or nothing if the component is empty.
 * @since 2.3.0
 */
function cb_ajax_table( $component = '', $paginated = true ) {

	if ( empty( $component ) ) {
		return;
	}

	$pagination = '';
	$component = trim( $component );

	if ( $paginated ) {
		$pagination = cb_container(array(
			'classes' => array("cb-{$component}-pagination-container"),
			'output'  => cb_get_pagination($component)
		));
	}

	$table = cb_container(array(
		"classes" => array("cb-data-table-container"),
		"output" => sprintf( '%1$s<table class="cb-data-table" id="cb_%2$s_table"></table>', $pagination, $component )
	));

	return printf("%s", $table);

}

/**
 * CB Get Pagination
 * 
 * Returns a pagination bar for the specified component.
 * Typically used when displaying items from the database
 * that are fetched via AJAX.
 * 
 * @param string $component The component to display the pagination bar for.
 * @return string|void The HTML for the pagination bar or nothing 
 * if the component is empty.
 * @since 2.3.0
 */
function cb_get_pagination( $component = '' ) {

	if ( empty( $component ) ) {
		return;
	}

	$pagination_buttons = '';
	$button_args = array(
		'first' => '«',
		'previous' => '‹',
		'next' => '›',
		'last' => '»'
	);

	foreach ( $button_args as $placement => $content ) {
		$attr_val = $placement === 'first' ? 1 : '';
		$pagination_buttons .= cb_get_button(array(
			'classes' => array( "cb-{$component}-pagination-{$placement}", "cb-{$component}-pagination-button" ),
			'content' => $content,
			'custom_attr' => array("cb-{$component}-page" => $attr_val )
		));
	}

	$pagination = cb_container(array(
		'classes' => array("cb-{$component}-pagination"),
		'output'            => $pagination_buttons,
	));

	return $pagination;

}