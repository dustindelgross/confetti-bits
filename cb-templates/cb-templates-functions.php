<?php 
/**
 * CB Templates Container
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
function cb_templates_container( $args = array() ) {

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
		'li'   => true,
		'span' => true,
		'p'    => true,
	);

	$default_classes        = array();
	$r['classes'] = array_merge( $r['classes'], $default_classes );

	if ( empty( $r['container'] ) || ! isset( $valid_containers[ $r['container'] ] ) ) {
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
function cb_templates_get_button( $args = array() ) {

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
 * CB Templates Get List Item
 * 
 * Formats a list item element with the given arguments
 * 
 * @param array $args {
 *     @var string $id The ID of the item
 *     @var string $content The content of the item
 *     @var array $classes An array of classes to add to the button.
 *     Default array('cb-button')
 * }
 * @return string The formatted button element
 * @since 2.3.0
 */
function cb_templates_get_list_item( $args = array() ) {

	$r = wp_parse_args( $args, array(
		'classes' => array(),
		'id' => '',
		'content' => '',
		'custom_attr' => array(),
	));

	$id = '';
	$classes = '';
	$custom_attrs = '';
	$default_classes = array( 'cb-button' );
	$content = trim( $r['content'] );

	$r['classes'] = array_merge( $r['classes'], $default_classes );

	if ( !empty( $r['id'] ) ) {
		$id = ' id="' . esc_attr( $r['id'] ) . '"';
	}

	if ( ! empty( $r['classes'] ) && is_array( $r['classes'] ) ) {
		$classes = ' class="' . join( ' ', array_map( 'sanitize_html_class', $r['classes'] ) ) . '"';
	}

	if ( !empty( $r['custom_attr'] ) && is_array( $r['custom_attr'] ) ) {
		foreach ( $r['custom_attr'] as $key => $val) {
			if ( str_starts_with($key, "no_data_") ) {
				$custom_attrs .= ' ' . esc_attr(str_replace('no_data_', '', $key ) ) . '="' . esc_attr($val) . '"';
			} else {
				$custom_attrs .= ' data-' . esc_attr($key) . '="' . esc_attr($val) . '"';	
			}
		}
	}
	
	return "<li{$id}{$classes}{$custom_attrs}>{$content}</li>";

}

/**
 * CB Templates Get Link
 * 
 * Returns a formatted anchor tag.
 * 
 * @param array $args An array of options. { 
 *   @type array $classes An array of classes to add to the element.
 *   @type string $href The href for the element. Default '#'.
 *   @type string $id The id for the element.
 *   @type string $content The text content of the element.
 *   @type array $custom_attr An array of custom data attributes for the element.
 * }
 * 
 * @return string The formatted anchor markup.
 */
function cb_templates_get_link($args = array()) {
	
	$r = wp_parse_args( $args, array(
		'classes' => array(),
		'href' => '',
		'id' => '',
		'content' => '',
		'custom_attr' => array(),
	));
	
	$id = '';
	$classes = '';
	$custom_attrs = '';
	$default_classes = array( 'cb-button' );
	$content = trim( $r['content'] );
	$url = ! empty($r['href']) ? esc_url( $r['href'] ) : '#';
	$href = ' href ="' . $url . '"';
	
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
	
	return "<a{$href}{$id}{$classes}{$custom_attrs}>{$content}</a>";
	
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
function cb_templates_ajax_table( $component = '', $paginated = true ) {

	if ( empty( $component ) ) {
		return;
	}

	$pagination = '';
	$component = trim( $component );
	$with_dashes = str_replace( '_', '-', $component );

	if ( $paginated ) {
		$pagination = cb_templates_container(array(
			'classes' => array("cb-{$with_dashes}-pagination-container"),
			'output'  => cb_templates_get_pagination($with_dashes)
		));
	}

	$table = cb_templates_container(array(
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
function cb_templates_get_pagination( $component = '' ) {

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
	
	$with_dashes = str_replace( '_', '-', $component );

	foreach ( $button_args as $placement => $content ) {
		$attr_val = $placement === 'first' ? 1 : '';
		$pagination_buttons .= cb_templates_get_button(array(
			'classes' => array( "cb-{$with_dashes}-pagination-{$placement}", "cb-{$with_dashes}-pagination-button" ),
			'content' => $content,
			'custom_attr' => array("cb-{$with_dashes}-page" => $attr_val )
		));
	}

	$pagination = cb_templates_container(array(
		'classes' => array("cb-{$with_dashes}-pagination"),
		'output'            => $pagination_buttons,
	));

	return $pagination;

}

/**
 * CB Templates Format Heading
 * 
 * Format the markup for a heading element.
 * 
 * @since Confetti_Bits 2.3.0
 * 
 * @param string $content The content for the heading element. Default empty.
 * @param int $level The level for the heading element. Default 4.
 * 
 * @return string the formatted heading.
 */
function cb_templates_format_heading( $content = '', $level = 4 ) {
	return sprintf( '<h%1$s class="cb-heading">%2$s</h%1$s>', $level, $content );
}

/**
 * CB Templates Heading
 * 
 * Output the markup for a heading element.
 * 
 * @since Confetti_Bits 2.3.0
 * 
 * @param string $content The content for the heading element. Default empty.
 * @param int $level The level for the heading element. Default 4.
 */
function cb_templates_heading( $content = '', $level = 4 ) {
	echo cb_templates_format_heading( $content, $level );
}

/**
 * CB Templates Get Nav Items
 * 
 * Returns a list of dynamically populated nav items as a string.
 * 
 * @since Confetti_Bits 2.3.0
 * 
 * @param string $component The component whose nav we need to make.
 * @param array $items A 2D array of items to put into the nav. { 
 *   @type array $label The label that will be used in the markup
 *     acts as a key for the nav item options { 
 *       @type bool $active Adds an "active" class onto the list item if true.
 *       @type array $custom_attr An optional array of key => value pairs.
 *       @type string $href An href for the anchor element that will go inside
 *         the list item.
 *   }
 * }
 * 
 * @return string The markup of all the collective nav items.
 */
function cb_templates_get_nav_items( $component = '', $items = array() ) {

	if ( empty($component) || empty( $items ) ) {
		return;
	}

	$markup = '';
	$with_dashes = str_replace( '_', '-', $component );

	foreach ( $items as $label => $options ) {

		$active = isset($options['active']) && $options['active'] === true ? 'active' : '';
		$attrs = !empty($options['custom_attr']) && is_array($options['custom_attr']) ? $options['custom_attr'] : array();
		$href = !empty( $options['href'] ) ? esc_url( $options['href'] ) : '#';

		$markup .= cb_templates_get_list_item(array(
			'classes' => array( "cb-{$with_dashes}-nav-item", $active ),
			'custom_attr' => $attrs,
			'content' => cb_templates_get_link(array(
				'classes' => array( "cb-{$with_dashes}-nav-link" ),
				'href' => $href,
				'content' => $label
			))
		));
	}

	return $markup;

}

/**
 * CB Templates Get Nav
 * 
 * Returns a dynamically populated nav as a string.
 * 
 * @since Confetti_Bits 2.3.0
 * 
 * @param string $component The component whose nav we need to make.
 * @param array $items A 2D array of items to put into the nav. { 
 *   @type array $label The label that will be used in the markup
 *     acts as a key for the nav item options { 
 *       @type bool $active Adds an "active" class onto the list item if true.
 *       @type array $custom_attr An optional array of key => value pairs.
 *       @type string $href An href for the anchor element that will go inside
 *         the list item.
 *   }
 * }
 * 
 * @return string The nav markup.
 */
function cb_templates_get_nav( $component = '', $items = array() ) {

	if ( empty( $component ) || empty( $items ) ) {
		return;
	}
	
	$with_dashes = str_replace( '_', '-', $component );

	return cb_templates_container(array(
		'container' => 'ul',
		'classes' => array("cb-{$with_dashes}-nav"),
		'output' => cb_templates_get_nav_items( $component, $items )
	));

}