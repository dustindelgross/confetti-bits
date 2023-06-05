<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CB Templates Forms
 * 
 * These functions are going to help us easily create new form markup for the platform.
 * 
 * @TODO: Clean this up and start deprecating some stuff.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.1.0
 */

/**
 * CB Templates Get Text Input
 * 
 * Formats a text input that plays nice with our scripts.
 * 
 * @param array $args A list of arguments { 
 * 
 * @type string label		The label for the input
 * @type string name		The form input and label name
 * @type string placeholder	The placeholder for the input
 * @type string value		The default value of the input
 * 
 * @type bool	$disabled			Disables the element if true. Default false.
 * @type bool	$hidden 			Hides the element if true. Default false.
 * @type bool	$textarea			Changes markup to instead be a textarea input.
 * @type arr	$container_classes	Provides the option to add container classes.
 * 
 * }
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_templates_get_text_input( $args = array() ) {

	$r = wp_parse_args(
		$args,
		array(
			'label'				=> 'Text Input',
			'name'				=> 'cb_text_input',
			'placeholder'		=> '',
			'value'				=> '',
			'disabled'			=> false,
			'hidden'			=> false,
			'textarea'			=> false,
			'required'			=> false,
			'container_classes'	=> []
		)
	);

	$input_tag		= $r['textarea'] ? array( 'textarea', '></textarea' ) : array( 'input', '/' );
	$input_type		= $r['textarea'] ? '' : ' type="text"';
	$disabled		= $r['disabled'] ? 'disabled' : '';
	$hidden			= $r['hidden'] ? 'hidden' : '';
	$required 		= $r['required'] ? 'required' : '';
	$placeholder	= !empty( $r['placeholder'] ) ? ' placeholder="' . $r['placeholder'] . '"' : '';
	$value			= !empty( $r['value'] ) ?? ' value="' . $r['value'] . '"';
	$container_classes = implode( ' ', $r['container_classes'] );
	$label = "<label for='{$r['name']}'>{$r['label']}</label>";
	$input_markup = "<{$input_tag[0]}
		{$input_type} 
		name='{$r['name']}' 
		id='{$r['name']}' 
		class='cb-form-textbox' 
		{$placeholder} 
		{$value} 
		{$disabled} 
		{$hidden} 
		{$input_tag[1]}>";

	return sprintf(
		"<ul class='cb-form-page-section %s'>
		<li class='cb-form-line'>%s%s</li></ul>", $container_classes, $label, $input_markup
	);

}

/**
 * CB Text Input
 * 
 * Outputs the text input. 
 * 
 * @param 	array 	$args	The array of arguments that get passed to the getter.
 * 							It would be wise to check what all is needed.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_text_input( $args = array() ) {
	echo cb_templates_get_text_input( $args );
}


/**
 * CB Templates Get Select Input
 * 
 * Creates a select input element.
 * 
 * @param array $args Accepts a bunch of optional arguments.
 * 
 * @type string $for The name and for label of the select element.
 * @type string $label The readable label of the element.
 * @type array $select_options The options housed inside the select element. 
 * 		Accepts a 2D array of option names and their attributes, which get pushed to cb_select_options.
 * 		Required.
 * 		@see cb_select_options()
 * 
 * @package ConfettiBits\Templates
 * @since 2.3.0
 */
function cb_templates_get_select_input( $args = array() ) {

	$r = wp_parse_args(
		$args,
		array(
			'name'				=> '',
			'label'				=> 'Select from the Following',
			'placeholder'		=> '',
			'required'			=> false,
			'select_options'	=> []
		)
	);

	$required = $r['required'] ? 'required' : "";

	if ( ! empty($r['placeholder'] ) ) {
		$r['select_options'] = array_merge( 
			[$r['placeholder'] => ['selected' => true]],
			$r['select_options']
		);
	}

	$options = cb_templates_get_select_options($r['select_options']);

	$markup = sprintf(
		'<ul class="cb-form-page-section"><li class="cb-form-line">
		<label for="%1$s">%2$s</label>
		<select class="cb-form-textbox cb-form-selector"
			name="%1$s" id="%1$s" %3$s>%4$s
		</select></li></ul>', 
		$r['name'], $r['label'], $required, $options 
	);

	return $markup;

}

/**
 * CB Templates Get Select Options
 * 
 * Takes care of some of the business for making select inputs.
 * 
 * @param array $args Arguments for the select input, listed below. Required.
 * 		@type string $name The name of the option. Required.
 * 		@type array $options The input values, and whether they are disabled or selected.
 * 			@type string	$value The value for the option. Required.
 * 			@type bool		$disabled Whether the option is disabled or not. Optional.
 * 			@type bool		$selected Whether the option is selected by default. Optional. 
 * 
 * @see cb_select_input()
 * 
 * @package ConfettiBits\Templates
 * @since 2.3.0
 */
function cb_templates_get_select_options( $args = array() ) {

	$markup = '';
	
	foreach ( $args as $label => $options ) {

		if ( empty( $label ) ) {
			return;
		}

		$classes = isset( $options['classes'] ) ? ' class="' . join( ' ', $options['classes'] ) . '"' : '';
		$value = isset($options['value'] ) ? ' value="' . $options['value'] . '"' : '';
		$selected = isset( $options['selected'] ) ? ' selected' : '';
		$disabled = isset( $options['disabled'] ) ? ' disabled' : '';

		$markup .= "<option{$classes}{$value}{$selected}{$disabled}>{$label}</option>";

	}

	return $markup;

}

/**
 * Confetti Bits Select Input
 * 
 * Outputs the select input. 
 * 
 * @param 	array 	$args	The array of arguments that get passed to the getter.
 * 							It would be wise to check what all is needed.
 * 
 * @see cb_get_select_input()
 * 
 * @package ConfettiBits\Templates
 * @since 2.3.0
 */
function cb_select_input( $args = array() ) {
	echo cb_templates_get_select_input( $args );
}

/**
 * CB Templates Get Number Input
 * 
 * Use this to create a number input element.
 * 
 * @param array $args An array of arguments for the input element. Details below. All settings are optional.
 * 
 * @type string 	$label The label used for the number input. Default 'Number' Optional.
 * @type string 	$for Used for the for attribute in the label, and the name attribute and id for the input. 
 * 						Default 'cb_number' Optional.
 * @type int 		$value The default value of the input element. Optional.
 * @type int|string $placeholder The placeholder for the input element. Optional.
 * @type bool 		$disabled Whether the element is disabled. Default false. Optional.
 * @type bool 		$readonly Whether the element is readonly. Default false. Optional.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_templates_get_number_input( $args = array() ) {

	$r = wp_parse_args(
		$args,
		array(
			'label'			=> 'Number',
			'name'			=> 'cb_number',
			'value'			=> '',
			'placeholder'	=> 'ex: 5',
			'min'			=> '',
			'max'			=> '',
			'required'		=> false,
			'disabled'		=> false,
			'readonly'		=> false
		)
	);

	$esc_name = esc_attr($r['name']);
	$value = !empty( $r['value'] ) ? ' value="' . $r['value'] . '"' : '';
	$placeholder	= empty( $r['placeholder'] ) ? '' : $r['placeholder'];
	$minmax	= '';

	if ( $r['min'] === '' || ! is_int( $r['min'] ) ) {
		$minmax .= ' min="' . $r['min'] . '"';
	}

	if ( $r['max'] === '' || ! is_int( $r['max'] ) ) {
		$minmax .= ' max="' . $r['max'] . '"';	
	}

	$required		= $r['required'] ? 'required' : "";
	$disabled		= $r['disabled'] ? 'disabled' : "";
	$readonly		= $r['readonly'] ? 'readonly' : "";

	return sprintf(
		'<ul class="cb-form-page-section"><li class="cb-form-line">
		<label for="%1$s">%2$s</label>
		<input type="number" name="%1$s" id="%1$s" %3$s%4$s%5$s%6$s%7$s%8$s />
		</li></ul>', 
		$esc_name, $r['label'], $minmax, $placeholder, $value, $required, $disabled, $readonly 
	);

}

/**
 * CB Number Input
 * 
 * Outputs the number input.
 * 
 * @param 	array 	$args	The array of arguments that get passed to the getter.
 * 							It would be wise to check what all is needed.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_number_input( $args = array() ) {
	echo cb_templates_get_number_input( $args );
}

/**
 * Confetti Bits Get File Input.
 * 
 * Creates a file input element that accepts the specified filetypes.
 * 
 * @param	array	$args An array of arguments for the input element. All optional.
 * 
 * 		@type	string	$label		The label for the input. Default 'Upload a File'.
 * 		@type	string	$class		Custom class attribute for file input.
 * 		@type	string	$name		The value used in the for attribute of the label 
 * 									and the name and id attributes of the input.
 * 									Default 'cb_file_upload'.
 * 		@type	bool	$required	Whether or not the field is required. Default false.
 * 		@type	bool	$multiple	Whether or not the field allows multiple files. Default false.
 * 		@type	bool	$capture	Whether or not the field allows the use of a camera. Default false.
 * 		@type	bool	$disabled	Whether or not the field is disabled. Default false.
 * 		@type	array	$accepts	An array of filetypes that the input accepts.
 * 									Accepts either MIME type structures or filetype structures.
 * 									Should be compliant with your Wordpress configuration.
 * 									Default empty.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.1.0
 */
function cb_format_file_input( $args = array() ) {

	$r = wp_parse_args(
		$args,
		array(
			'label'			=> 'Upload a File',
			'container_id'	=> '',
			'name'			=> 'cb_file_upload',
			'required'		=> false,
			'multiple'		=> false,
			'capture'		=> false,
			'disabled'		=> false,
			'accepts'		=> array()
		)
	);

	$required	= $r['required'] ? 'required' : '';
	$disabled	= $r['disabled'] ? 'disabled' : '';
	$multiple	= $r['multiple'] ? 'multiple' : '';
	$capture	= $r['capture'] ? $r['capture'] : '';

	if ( empty( $r['accepts'] ) || ! is_array( $r['accepts'] ) ) {
		return;
	}

	foreach ( $r['accepts'] as $accept ) {
		if ( ! preg_match( '/[a-zA-Z]{2,5}\/[a-zA-Z]{2,5}/i', $accept ) && ! preg_match( '/\.[a-zA-Z]{2,5}/i', $accept ) ) {
			return;
		}
	}

	$container_id = ! empty( $r['container_id'] ) ? "id='{$r['container_id']}'" : '';

	$accepts	= !empty($r['accepts']) ? 'accept="' . implode( ', ', $r['accepts'] ) . '"' : '';	

	$markup = "<ul class='cb-form-page-section'>
	<li class='cb-form-line'>
	<div class='cb-file-input-container' {$container_id}>
	<label for='{$r['name']}'>";

	$markup .= "<input 
	placeholder=''
	class='cb-file-input'
	type='file' 
	name='{$r['name']}' 
	id='{$r['name']}' 
	{$required} 
	{$accepts} 
	{$multiple} 
	{$capture} 
	{$disabled} />";

	$markup .= "</label><span>{$r['label']}</span></div></li></ul>";

	return $markup;

}

/**
 * Confetti Bits File Input
 * 
 * Outputs the file input.
 * 
 * @param 	array 	$args	The array of arguments that get passed to the getter.
 * 							It would be wise to check what all is needed.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.1.0
 */
function cb_file_input( $args = array() ) {
	echo cb_format_file_input( $args );
}

/**
 * CB Templates Get Submit Input.
 * 
 * Creates a submit input element that submits a form.
 * 
 * @param	array	$args An array of arguments for the input element. All optional.
 * 
 * 		@type	string	$id			The value used in the id attribute of the input.
 * 									Default empty.
 * 		@type	value	$value		Value for the submit input. Default "Submit".
 * 		@type	bool	$disabled	Whether or not the field is disabled. Default false.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_templates_get_submit_input( $args = array() ) {
	$r = wp_parse_args(
		$args,
		array(
			'name'		=> '',
			'value'		=> 'Submit',
			'disabled'	=> false
		)
	);

	$disabled	= $r['disabled'] ? 'disabled' : '';	
	$id			= !empty( $r['name'] ) ? ' id="' . $r['name'] . '"' : '';
	$name		= !empty( $r['name'] ) ? ' name="' . $r['name'] . '"' : '';

	$markup = "<ul class='cb-form-page-section'>
		<li class='cb-form-line'>
		<input 
		type='submit' 
		class='cb-submit'
		value='{$r['value']}'
		{$name} 
		{$id} 
		{$disabled} />
		</li></ul>";

	return $markup;

}

/**
 * CB Submit Input
 * 
 * Outputs the submit input markup.
 * 
 * @param 	array 	$args	The array of arguments that get passed to the getter.
 * 							It would be wise to check what all is needed.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.1.0
 */
function cb_submit_input( $args = array() ) {
	echo cb_templates_get_submit_input( $args );
}

/**
 * CB Templates Get Hidden Input
 * 
 * Creates a hidden input that's either ready to receive a value or has a preset value.
 * 
 * Suggested utilities are getting things like user ids, logged-in usernames, emails, or empty inputs
 * that get calculated on the page via javascript. We probably haven't written things like that yet,
 * so you may have to set it up for your needs.
 * 
 * @param array $args An array of arguments for the input. Name, id, value, disabled, multiple.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_templates_get_hidden_input( $args = array() ) {

	$r = wp_parse_args(
		$args,
		array(
			'name'		=> '',
			'value'		=> '',
			'disabled'	=> false,
			'multiple'	=> false,
			'classes'	=> ['cb-hidden']
		)
	);

	if ( empty( $r['name'] ) ) {
		return;
	}

	$esc_name = esc_attr($r['name']);
	$disabled	= $r['disabled'] ? ' disabled' : '';	
	$multiple	= $r['multiple'] ? ' multiple' : '';
	$classes = ' class="' . join( ' ', $r['classes'] ) . '"';
	$name = ' name="' . $esc_name . '"';
	$id = ' id="' . $esc_name . '"';
	$value = ' value="' . $r['value'] . '"';

	return sprintf('<input type="hidden"%1$s%2$s%3$s%4$s />', $classes, $name, $id, $value );

}

/**
 * CB Hidden Input
 * 
 * Outputs the hidden input markup.
 * 
 * @param 	array 	$args	The array of arguments that get passed to the getter.
 * 							It would be wise to check what all is needed.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_hidden_input( $args = array() ) {
	echo cb_templates_get_hidden_input( $args );
}

/**
 * CB Templates Get Checkbox Input
 * 
 * Creates a checkbox input with a label and as many options as you want.
 * 
 * 
 * @param	array	$args	An array of options for each input you want to include, structured in
 * 							a 2D array of option names => array of option parameters. {
 * 				
 * 				@type	string	$name		The value used in the for, name, and id attributes.
 * 				@type	string	$label		The label for the option.
 * 				@type	string	$value		The value of the option.
 * 				@type	bool	$checked	Whether the field is checked or not.
 * 				@type	bool	$disabled	Whether the field is disabled or not.
 * 
 * }
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.2.0
 */
function cb_templates_get_checkbox_input( array $args ) {

	$r = wp_parse_args(
		$args,
		array(
			'name'		=> 'cb_checkbox_option',
			'label'		=> 'Option',
			'value'		=> '',
			'checked'	=> false,
			'disabled'	=> false,
			'other'		=> false
		)
	);

	$disabled	= $r['disabled'] ? 'disabled' : '';
	$checked	= $r['checked'] ? 'checked' : '';
	$value		= !empty( $r['value'] ) ? "value={$r['value']}" : '';


	if ( $r['other'] ) {
		$markup = "
	<div class='cb-checkbox-container'>
		<input 
		type='checkbox' 
		class='cb-checkbox'
		{$value}
		{$checked} 
		{$disabled} />
		<label for='{$r['name']}'>{$r['label']}</label>
		<input type='text' name='{$r['name']}' class='cb-textbox' />
		</div>";
	}

	if ( ! $r['other'] ) {

		$value		= !empty( $r['value'] ) ? "value='{$r['value']}'" : '';
		$markup = "
	<div class='cb-checkbox-container'>
		<input 
		type='checkbox' 
		class='cb-checkbox'
		name='{$r['name']}' 
		{$value}
		{$checked} 
		{$disabled} />
		<label for='{$r['name']}'>{$r['label']}</label></div>";

	}

	return $markup;

}

/**
 * CB Checkbox Input
 * 
 * Outputs the checkbox input markup.
 * 
 * @param 	array 	$args	The array of arguments that get passed to the getter.
 * 							It would be wise to check what all is needed.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.1.0
 */
function cb_checkbox_input( $args = array() ) {
	echo cb_templates_get_checkbox_input( $args );
}

/**
 * CB Format Dropzone Input
 * 
 * Creates a hidden input and a container that can be used for drag-and-drop file uploads.
 * 
 * @param array $args An array of arguments for the input. Name, id, value, disabled, multiple, etc.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.1.0
 */
function cb_format_dropzone_input( array $args ) {

	$r = wp_parse_args(
		$args,
		array(
			'id'			=> '',
			'container_id'	=> '',
			'name'			=> '',
			'value'			=> '',
			'disabled'		=> false,
			'multiple'		=> false
		)
	);

	$disabled		= $r['disabled'] ? 'disabled' : '';	
	$multiple		= $r['multiple'] ? 'multiple' : '';
	$id				= !empty( $r['id'] ) ? "id={$r['id']}" : '';
	$container_id	= !empty( $r['container_id'] ) ? "id={$r['container_id']}" : '';
	$file_types		= !empty( $r['filetypes'] ) && is_array( $r['filetypes'] ) ? 
		"data-valid-types" . implode( ' ', $r['filetypes'] ) 
		: '';
	$markup = 
		"<ul class='cb-form-page-section'>
			<li class='cb-form-line'>
				<div class='cb-file-input-container' {$container_id}>
					<input 
					type='hidden' 
					class='cb-hidden' 
					name='{$r['name']}'
					value='{$r['value']}'
					{$id}
					{$disabled} 
					{$multiple} />
				</div>
			</li>
		</ul>";

	return $markup;

}

/**
 * CB Dropzone Input
 * 
 * Outputs the hidden input markup.
 * 
 * @param 	array 	$args	The array of arguments that get passed to the getter.
 * 							It would be wise to check what all is needed.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.1.0
 */
function cb_dropzone_input( array $args ) {
	echo cb_format_hidden_input( $args );
}

/**
 * CB Templates Get Toggle Switch
 * 
 * Gets toggle switch markup.
 * 
 * @param	array	$args	An array of options for each input you want to include, 
 * 							structured in a 2D array of options.
 * 				
 * 		@type	string	$name		The value used in the for, name, and id attributes.
 * 		@type	string	$label		The label for the option.
 * 		@type	string	$value		The value of the option.
 * 		@type	bool	$checked	Whether the field is checked or not.
 * 		@type	bool	$disabled	Whether the field is disabled or not.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.2.0
 */
function cb_templates_get_toggle_switch( array $args ) {

	$r = wp_parse_args(
		$args,
		array(
			'name'		=> 'cb_toggle_option',
			'label'		=> 'Option',
			'value'		=> '',
			'checked'	=> false,
			'disabled'	=> false
		)
	);

	$disabled	= $r['disabled'] ? 'disabled' : '';
	$checked	= $r['checked'] ? 'checked' : '';

	$markup = "<div class='cb-toggle-switch-container'>
				<input 
					type='checkbox' 
					class='cb-toggle-switch' 
					name='{$r['name']}'
					id='{$r['name']}' 
					value='{$r['value']}' 
					{$disabled} 
					{$checked} />
				<label for='{$r['name']}'>{$r['label']}</label>
			</div>";
	return $markup;

}

/**
 * CB Toggle Switch
 * 
 * Outputs toggle switch markup.
 * 
 * @param	array	$args	An array of options for each input you want to include, 
 * 							structured in a 2D array of options.
 * 				
 * 		@type	string	$name		The value used in the for, name, and id attributes.
 * 		@type	string	$label		The label for the option.
 * 		@type	string	$value		The value of the option.
 * 		@type	bool	$checked	Whether the field is checked or not.
 * 		@type	bool	$disabled	Whether the field is disabled or not.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.2.0
 */
function cb_toggle_switch( array $args ) {
	echo cb_templates_get_toggle_switch($args);
}

/**
 * CB Templates Get Form
 * 
 * Gets the markup for a form element.
 * 
 * @return string The formatted markup.
 * 
 * @package Confetti_Bits
 * @subpackage Templates
 * @since 2.3.0
 */
function cb_templates_get_form( $args = array() ) {

	$cb = Confetti_Bits();
	$r = wp_parse_args( 
		$args, [
			'name' => '',
			'method' => '',
			'classes' => ['cb-form'],
			'action' => $cb->page,
			'output' => '',
			'enctype' => 'multipart/form-data',
			'autocomplete' => '',
		]
	);

	$valid_methods = ['GET', 'PUT', 'PATCH', 'POST', 'DELETE'];
	$method = strtoupper($r['method']);

	if ( empty($r['name']) || empty($r['method'] ) ) {
		return;
	}

	if ( !in_array( $method, $valid_methods ) ) {
		return;
	}

	$name = ' name="' . esc_attr($r['name']) . '"';
	$id = ' id="' . esc_attr($r['name']) . '"';
	$enctype = $method === 'POST' ? ' enctype="' . $r['enctype'] . '"' : '';
	$autocomplete = !empty( $r['autocomplete'] ) ? ' autocomplete="' . $r['autocomplete'] . '"' : '';
	$method_output = ' method="' . $method . '"';
	$classes = ! empty( $r['classes'] ) ? ' class="' . implode(' ', $r['classes']) . '"' : '';
	$action = ' action="' . esc_url($r['action']) . '"';

	return "<form{$name}{$id}{$enctype}{$autocomplete}{$method_output}{$classes}{$action}>{$r['output']}</form>";

}