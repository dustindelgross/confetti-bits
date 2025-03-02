<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * CB Templates Forms
 *
 * These functions are going to help us easily create new form markup for the platform.
 *
 * @TODO: Clean this up and start deprecating some stuff.
 *
 * @package ConfettiBits\Templates
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
 * @package ConfettiBits\Templates
 * @since 2.3.0
 */
function cb_templates_get_text_input( $args = [] ) {

	$r = wp_parse_args( $args, [
		'label' => 'Text Input',
		'name' => '',
		'placeholder' => '',
		'value' => '',
		'disabled' => false,
		'hidden' => false,
		'textarea' => false,
		'required' => false,
		'container_classes' => [],
		'classes' => ['cb-form-line']
	]);

	if ( empty( $r['name'] ) ) {
		return;
	}




	$input_tag = $r['textarea'] ? array('textarea', '></textarea') : array('input', '/');
	$input_type = $r['textarea'] ? '' : ' type="text"';
	$disabled = $r['disabled'] ? 'disabled' : '';
	$hidden = $r['hidden'] ? 'hidden' : '';
	$required = $r['required'] ? 'required' : '';
	$placeholder = !empty($r['placeholder']) ? ' placeholder="' . $r['placeholder'] . '"' : '';
	$value = !empty($r['value']) ? ' value="' . $r['value'] . '"' : '';
	$container_classes = implode(' ', $r['container_classes']);
	$classes = ' class="' . implode(' ', array_merge($r['classes'], ['cb-form-line'])) . '"';
	$label = "<label for='{$r['name']}'>{$r['label']}</label>";
	$input_markup = "<{$input_tag[0]}
		{$input_type}
		name='{$r['name']}'
		id='{$r['name']}'
		{$classes}
		{$placeholder}
		{$value}
		{$disabled}
		{$hidden}
		{$input_tag[1]}>";

	return sprintf(
		"<ul class='cb-form-page-section %s'>
		<li class='cb-form-line'>%s%s</li></ul>",
		$container_classes,
		$label,
		$input_markup
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
 * @package ConfettiBits\Templates
 * @since 2.3.0
 */
function cb_text_input( $args = [] ) {
	echo cb_templates_get_text_input($args);
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
function cb_templates_get_select_input($args = array())
{

	$r = wp_parse_args( $args, [
		'name' => '',
		'label' => 'Select from the Following',
		'placeholder' => '',
		'required' => false,
		'select_options' => []
	]);

	$required = $r['required'] ? 'required' : "";

	if (!empty($r['placeholder'])) {
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
		$r['name'], $r['label'],
		$required,
		$options
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
function cb_templates_get_select_options( $args = [] ) {

	$markup = '';

	foreach ($args as $label => $options) {

		if (empty($label)) {
			return;
		}

		$classes = isset($options['classes']) ? ' class="' . join(' ', $options['classes']) . '"' : '';
		$value = isset($options['value']) ? ' value="' . $options['value'] . '"' : '';
		$selected = isset($options['selected']) ? ' selected' : '';
		$disabled = isset($options['disabled']) ? ' disabled' : '';

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
function cb_select_input( $args = [] ) {
	echo cb_templates_get_select_input($args);
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
 * @package ConfettiBits\Templates
 * @since 2.3.0
 */
function cb_templates_get_number_input( $args = [] ) {

	$r = wp_parse_args( $args, [
		'label' => 'Number',
		'name' => 'cb_number',
		'value' => '',
		'placeholder' => 'ex: 5',
		'min' => '',
		'max' => '',
		'required' => false,
		'disabled' => false,
		'readonly' => false
	]);

	$esc_name = esc_attr($r['name']);
	$value = !empty($r['value']) ? ' value="' . $r['value'] . '"' : '';
	$placeholder = empty($r['placeholder']) ? '' : ' placeholder="' . $r['placeholder'] . '"';
	$minmax = '';

	if ($r['min'] !== '' && is_int($r['min'])) {
		$minmax .= ' min="' . $r['min'] . '"';
	}

	if ($r['max'] !== '' && is_int($r['max'])) {
		$minmax .= ' max="' . $r['max'] . '"';
	}

	$required = $r['required'] ? 'required' : "";
	$disabled = $r['disabled'] ? 'disabled' : "";
	$readonly = $r['readonly'] ? 'readonly' : "";

	return sprintf(
		'<ul class="cb-form-page-section"><li class="cb-form-line">
		<label for="%1$s">%2$s</label>
		<input type="number" name="%1$s" class="cb-form-number" id="%1$s" %3$s%4$s%5$s%6$s%7$s%8$s />
		</li></ul>',
		$esc_name, $r['label'],
		$minmax,
		$placeholder,
		$value,
		$required,
		$disabled,
		$readonly
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
 * @package ConfettiBits\Templates
 * @since 2.3.0
 */
function cb_number_input( $args = [] ) {
	echo cb_templates_get_number_input($args);
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
 * @package ConfettiBits\Templates
 * @since 2.1.0
 */
function cb_templates_get_file_input( $args = [] ) {

	$r = wp_parse_args( $args, [
		'label' => 'Upload a File',
		'container_id' => '',
		'name' => 'cb_file_upload',
		'required' => false,
		'multiple' => false,
		'capture' => false,
		'disabled' => false,
		'accepts' => []
	]);

	$required = $r['required'] ? 'required' : '';
	$disabled = $r['disabled'] ? 'disabled' : '';
	$multiple = $r['multiple'] ? 'multiple' : '';
	$capture = $r['capture'] ? $r['capture'] : '';
	$accepts = "";
	$markup = "";

	if (empty($r['accepts'])) {
		return;
	}

	if (!empty($r['accepts'])) {
		$accepts = is_array($r['accepts']) ? ' accept="' . implode(', ', $r['accepts']) . '"' : ' accept="' . trim(esc_attr($r['accepts'])) . '"';
	}

	$container_id = !empty($r['container_id']) ? " id='{$r['container_id']}'" : '';

	$label = sprintf('<label for="%s"><span>%s</span></label>', $r['name'], $r['label']);
	$input = sprintf(
		'<input placeholder="" class="cb-file-input" type="file"
		name="%s" id="%s" %s %s %s %s %s />',
		$r['name'], $r['name'],
		$required,
		$accepts,
		$multiple,
		$capture,
		$disabled
	);

	$markup = sprintf('<ul class="cb-form-page-section">
	<li class="cb-form-line">
	<div class="cb-file-input-container"%s>%s%s</div></li></ul>', $container_id, $label, $input);

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
 * @package ConfettiBits\Templates
 * @since 2.1.0
 */
function cb_file_input( $args = [] ) {
	echo cb_templates_get_file_input($args);
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
 * @package ConfettiBits\Templates
 * @since 2.3.0
 */
function cb_templates_get_submit_input( $args = [] ) {
	$r = wp_parse_args( $args, [
		'name' => '',
		'value' => 'Submit',
		'disabled' => false,
		'classes' => ['cb-submit'],
		'custom_attrs' => []
	]);

	$disabled = $r['disabled'] ? 'disabled' : '';
	$id = !empty($r['name']) ? ' id="' . $r['name'] . '"' : '';
	$name = !empty($r['name']) ? ' name="' . $r['name'] . '"' : '';
	$classes = ' class="' . implode( ' ', $r['classes'] ) . '"';
	$custom_attrs = ! empty($r['custom_attrs']) ? implode( ' ', array_map(function ($key, $value) {
		return sprintf( '%s="%s"', $key, $value );
	}, array_keys($r['custom_attrs']), array_values($r['custom_attrs']))) : '';

	$markup = "
		<input
		type='submit'
		{$classes}
		value='{$r['value']}'
		{$name}
		{$id}
		{$disabled} 
		{$custom_attrs} />";

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
 * @package ConfettiBits\Templates
 * @since 2.1.0
 */
function cb_submit_input( $args = [] ) {
	echo cb_templates_get_submit_input($args);
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
 * @package ConfettiBits\Templates
 * @since 2.3.0
 */
function cb_templates_get_hidden_input( $args = [] ) {

	$r = wp_parse_args( $args, [
		'name' => '',
		'value' => '',
		'disabled' => false,
		'multiple' => false,
		'classes' => ['cb-hidden']
	]);

	if (empty($r['name'])) {
		return;
	}

	$esc_name = esc_attr($r['name']);
	$disabled = $r['disabled'] ? ' disabled' : '';
	$multiple = $r['multiple'] ? ' multiple' : '';
	$classes = ' class="' . join(' ', $r['classes']) . '"';
	$name = ' name="' . $esc_name . '"';
	$id = ' id="' . $esc_name . '"';
	$value = ' value="' . $r['value'] . '"';

	return sprintf('<input type="hidden"%1$s%2$s%3$s%4$s />', $classes, $name, $id, $value);

}

/**
 * CB Hidden Input
 *
 * Outputs the hidden input markup.
 *
 * @param 	array 	$args	The array of arguments that get passed to the getter.
 * 							It would be wise to check what all is needed.
 *
 * @package ConfettiBits\Templates
 * @since 2.3.0
 */
function cb_hidden_input( $args = [] ) {
	echo cb_templates_get_hidden_input($args);
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
 * @package ConfettiBits\Templates
 * @since 2.2.0
 */
function cb_templates_get_checkbox_input( $args = [] ) {

	$r = wp_parse_args( $args,[
		'name' => 'cb_checkbox_option',
		'label' => 'Option',
		'value' => '',
		'checked' => false,
		'disabled' => false,
		'other' => false
	]);

	$disabled = $r['disabled'] ? 'disabled' : '';
	$checked = $r['checked'] ? 'checked' : '';
	$value = !empty($r['value']) ? "value={$r['value']}" : '';


	if ($r['other']) {
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

	if (!$r['other']) {

		$value = !empty($r['value']) ? "value='{$r['value']}'" : '';
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
 * @package ConfettiBits\Templates
 * @since 2.1.0
 */
function cb_checkbox_input( $args = [] ) {
	echo cb_templates_get_checkbox_input($args);
}

/**
 * CB Format Dropzone Input
 *
 * Creates a hidden input and a container that can be used for drag-and-drop file uploads.
 *
 * @param array $args An array of arguments for the input. Name, id, value, disabled, multiple, etc.
 *
 * @package ConfettiBits\Templates
 * @since 2.1.0
 */
function cb_templates_get_dropzone_input( $args = [] ) {

	$r = wp_parse_args(
		$args,
		array(
			'id' => '',
			'container_id' => '',
			'name' => '',
			'value' => '',
			'disabled' => false,
			'multiple' => false
		)
	);

	$disabled = $r['disabled'] ? 'disabled' : '';
	$multiple = $r['multiple'] ? 'multiple' : '';
	$id = !empty($r['id']) ? "id={$r['id']}" : '';
	$container_id = !empty($r['container_id']) ? "id={$r['container_id']}" : '';
	$file_types = !empty($r['filetypes']) && is_array($r['filetypes']) ?
		"data-valid-types" . implode(' ', $r['filetypes'])
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
 * @package ConfettiBits\Templates
 * @since 2.1.0
 */
function cb_dropzone_input( $args = [] ) {
	echo cb_templates_get_dropzone_input($args);
}

/**
 * Alias for cb_templates_get_toggle_switch_input().
 *
 * @package ConfettiBits\Templates
 * @since 2.2.0
 */
function cb_templates_get_toggle_switch( $args = [])
{
	return cb_templates_get_toggle_switch_input($args);
}

/**
 * Gets markup for a toggle switch input.
 *
 * @param	array	$args {
 *     An array of options for each input you want to include,
 *     structured in a 2D array of options.
 *
 * 		@type	string	$name		The value used in the for, name, and id attributes.
 * 		@type	string	$label		The label for the option.
 * 		@type	string	$value		The value of the option.
 * 		@type	bool	$checked	Whether the field is checked or not.
 * 		@type	bool	$disabled	Whether the field is disabled or not.
 * }
 *
 * @return string The formatted markup.
 *
 * @package ConfettiBits\Templates
 * @since 3.0.0
 */
function cb_templates_get_toggle_switch_input($args = [])
{

	$r = wp_parse_args($args, [
		'name' => 'cb_toggle_option',
		'label' => 'Option',
		'value' => '',
		'checked' => false,
		'disabled' => false,
		'classes' => [],
		'container_classes' => []
	]);

	$disabled = $r['disabled'] ? 'disabled' : '';
	$checked = $r['checked'] ? 'checked' : '';
	$classes = sprintf('class="%s"', implode(' ', array_merge($r['classes'], ['cb-toggle-switch'])));
	$container_classes = sprintf('class="%s"', implode(' ', array_merge($r['container_classes'], ['cb-toggle-switch-container'])));

	$markup = "<div {$container_classes}>
				<input
					type='checkbox'
					{$classes}
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
 * @package ConfettiBits\Templates
 * @since 2.2.0
 */
function cb_toggle_switch($args = [])
{
	echo cb_templates_get_toggle_switch_input($args);
}

/**
 * CB Templates Get Form
 *
 * Gets the markup for a form element.
 *
 * @return string The formatted markup.
 *
 * @package ConfettiBits\Templates
 * @since 2.3.0
 */
function cb_templates_get_form($args = array())
{

	$cb = Confetti_Bits();
	$r = wp_parse_args(
		$args,
		[
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

	if (empty($r['name']) || empty($r['method'])) {
		return;
	}

	if (!in_array($method, $valid_methods)) {
		return;
	}

	$name = ' name="' . esc_attr($r['name']) . '"';
	$id = ' id="' . esc_attr($r['name']) . '"';
	$enctype = $method === 'POST' ? ' enctype="' . $r['enctype'] . '"' : '';
	$autocomplete = !empty($r['autocomplete']) ? ' autocomplete="' . $r['autocomplete'] . '"' : '';
	$method_output = ' method="' . $method . '"';
	$classes = !empty($r['classes']) ? ' class="' . implode(' ', $r['classes']) . '"' : '';
	$action = ' action="' . esc_url($r['action']) . '"';

	return "<form{$name}{$id}{$enctype}{$autocomplete}{$method_output}{$classes}{$action}>{$r['output']}</form>";

}

/**
 * Outputs the markup for a form element.
 *
 * @see cb_templates_get_form()
 *
 * @package ConfettiBits\Templates
 * @since 2.3.0
 */
function cb_form($args = [])
{
	echo cb_templates_get_form($args);
}

/**
 * Gets the markup for a form module.
 *
 * Use this when you want the whole UI kit and caboodle
 * on the Confetti Bits dashboard (i.e., you just want a
 * form inside of a module).
 *
 * @param array $args {
 *     An array of arguments.
 *
 *     @type string $component The name of the component.
 *     @type string $method The form method.
 *     @type array $classes Classes for the form element.
 *     @type string $action The URL for the form handler.
 *     @type string $output The markup that goes into the form.
 *     @type string $enctype The encoding for the form.
 *     @type string $autocomplete Whether the form should include
 * 								  autocomplete features.
 * }
 *
 * @return string The formatted markup of the form module.
 *
 * @package ConfettiBits\Templates
 * @since 2.3.0
 */
function cb_templates_get_form_module($args = [])
{
	$cb = Confetti_Bits();
	$r = wp_parse_args($args, [
		'component' => '',
		'method' => '',
		'classes' => [],
		'container_classes' => [],
		'action' => $cb->page,
		'output' => '',
		'enctype' => 'multipart/form-data',
		'autocomplete' => ''
	]);

	$valid_methods = ['GET', 'PUT', 'PATCH', 'POST', 'DELETE'];
	$method = strtoupper($r['method']);

	if (empty($r['component']) || empty($r['method'])) {
		return;
	}

	if (!in_array($method, $valid_methods)) {
		return;
	}

	$name = ' name="cb_' . esc_attr($r['component']) . '_form"';
	$container_id = 'cb_' . esc_attr($r['component']) . '_form_container';
	$id = ' id="cb_' . esc_attr($r['component']) . '_form"';
	$enctype = $method === 'POST' ? ' enctype="' . $r['enctype'] . '"' : '';
	$autocomplete = !empty($r['autocomplete']) ? ' autocomplete="' . $r['autocomplete'] . '"' : '';
	$method_output = ' method="' . $method . '"';
	$classes = !empty($r['classes']) ? ' class="' . implode(' ', $r['classes'] ) . '"' : '';
	$action = ' action="' . esc_url($r['action']) . '"';
	$output = is_array($r['output']) ? cb_templates_get_form_output($r['output']) : $r['output'];

	return cb_templates_container([
		'classes' => $r['container_classes'],
		'name' => $container_id,
		'output' => "<form{$name}{$id}{$enctype}{$autocomplete}{$method_output}{$classes}{$action}>{$output}</form>"
	]);

}

/**
 * Returns formatted markup for a form button. Note that this
 * should not be used as a submit button; we typically use 
 * input[type=submit] for that functionality. Here we'll
 * default to button[type=button] to ensure that we can have it
 * do what we want and not make the app have a heart attack.
 * 
 * @param array $args { 
 * 		An associative array of arguments.
 * 		Accepts various attributes of button elements, with 
 * 		support for your own custom attributes as well.
 * }
 * @return string The formatted markup.
 * 
 * @package ConfettiBits\Templates
 * @since 3.0.0
 */
function cb_templates_get_form_button( $args = [] ) {

	$r = wp_parse_args($args, [
		'type' => 'button',
		'name' => '',
		'classes' => ['btn', 'btn-primary'],
		'custom_attrs' => [],
		'value' => ''
	]);

	$type = ' type="' . esc_attr($r['type']) . '"';

	//	$id = $r['id'] !== '' ? ' id="' . esc_attr($r['id']) . '"' : '';
	$name = $r['name'] !== '' ? ' id="' . esc_attr($r['name']) . '"' : '';
	$classes = ' class="' . implode( ' ', $r['classes'] ) . '"';
	$custom_attrs = ! empty($r['custom_attrs']) ? implode( ' ', array_map(function ($key, $value) {
		return sprintf( '%s="%s"', $key, $value );
	}, array_keys($r['custom_attrs']), array_values($r['custom_attrs']))) : '';

	return "<button{$type}{$name}{$custom_attrs}{$classes}>{$r['value']}</button>";

}

/**
 * Outputs the form module's markup.
 *
 * @param array $args {
 *     @see cb_templates_get_form_module()
 * }
 *
 * @package ConfettiBits\Templates
 * @since 2.3.0
 */
function cb_form_module($args = [])
{
	echo cb_templates_get_form_module($args);
}

/**
 * Dynamically populates markup and input elements for a form.
 *
 * When given a heading, a component, and input arguments,
 * this function can generate an entirely unique set of markup
 * to put into a form element. It naturally prepends the
 * "cb_" prefix to the beginning to stick within our namespace,
 * then supplies the value supplied by the 'component' argument,
 * and finally appends the value defined in the input_args['name']
 * argument to create a (hopefully) entirely unique input element,
 * with the given type supplied in the input_args['type'] argument.
 * Argument... Argument... Argument. Doesn't sound like a word
 * anymore.
 *
 * @param array $args {
 *     An array of - you guessed it - arguments.
 *
 *     @type string $component The name for the component. Used
 * 							   to generate unique name and id
 * 							   attributes in the input elements.
 *     @type string $heading The heading to use in the output.
 * 							 Basically for flavor.
 *
 * }
 */
function cb_templates_get_form_output($args = [])
{

	$r = wp_parse_args($args, [
		'component' => '',
		'heading' => '',
		'content' => [],
		'inputs' => [],
		'modal' => false
	]);

	if ($r['component'] === '') {
		return;
	}

	$content = '';
	$content .= $r['modal'] ? '<div class="modal-content"><div class="modal-header">' : '';
	$content .= $r['heading'] !== '' ? cb_templates_get_heading($r['heading']) : '';
	$content .= $r['modal'] ? cb_templates_get_form_button([
		'classes' => ['btn', 'btn-close'],
		'custom_attrs' => [
			'data-bs-dismiss' => 'modal',
			'aria-label' => 'Close',
		],
		'value' => ''
	]) : '';
	$content .= $r['modal'] ? '</div><div class="modal-body">' : '';
	if (!empty($r['content'])) {
		$content .= is_array($r['content']) ? 
			sprintf("<p class='%s cb-form-content'>%s</p>", "cb-{$r['component']}-form-content", $r['content'])
			: "<p class='cb-{$r['component']}-form-content cb-form-content'>{$r['content']}</p>";
	}

	if (!empty($r['inputs'])) {

		foreach ($r['inputs'] as $input) {

			if ( $input['type'] === 'new_node' ) {
				$content .= cb_templates_recursive_helper($input['args']);
				continue;
			}

			if ( $input['type'] === 'button' ) {
				$input['args']['name'] = "cb_{$r['component']}_{$input['args']['name']}";
				$content .= cb_templates_get_form_button($input['args']);
				continue;
			}

			if ( $input['type'] === 'container' ) {
				$input['args']['name'] = "cb_{$r['component']}_{$input['args']['name']}";
				$content .= call_user_func("cb_templates_container", $input['args']);
				continue;
			}

			if ( $input['type'] === 'date' ) {
				$content .= call_user_func("cb_templates_get_{$input['type']}_input", $input['args']);
				continue;
			}

			if ( $input['type'] !== 'datetime_picker' ) {
				$input['args']['name'] = "cb_{$r['component']}_{$input['args']['name']}";
				$content .= call_user_func("cb_templates_get_{$input['type']}_input", $input['args']);
				continue;
			}


			$input['args']['date']['name'] = "cb_{$r['component']}_{$input['args']['date']['name']}";
			$input['args']['time']['name'] = "cb_{$r['component']}_{$input['args']['time']['name']}";
			$content .= call_user_func( "cb_templates_get_datetime_picker_input", $input['args'] );
		}
	}

	$content .= $r['modal'] ? '</div></div>' : '';

	return $content;

}

/**
 * A recursive helper function to help us process complex content trees.
 * 
 * It'll recurse through each node that's labeled as a 'new_node' and return
 * a string of content that's ready to echo out into the world.
 * 
 * @param array $args { 
 * 		An associative array of arguments. It expects a few things here:
 * 			@type array $new_node A node to recurse into for processing.
 * 			@type array $node_content { 
 * 				A node with content to process. Expects something like the following:
 * 				@type string $type The type of content.
 * 				@type array $args An array of arguments to pass to the processing function.
 * 			}
 * }
 * 
 * @return string The glorious content.
 * 
 * @package ConfettiBits\Templates
 * @since 3.0.0
 */
function cb_templates_recursive_helper($node = []) {

	if ( isset( $node['new_node'] ) ) {
		return cb_templates_recursive_helper($node['new_node']);
	}

	if ( !empty($node['node_content']) ) {

		if ( $node['node_content']['type'] === 'new_node' ) {
			return cb_templates_recursive_helper($node['node_content']['args']);
		}

		if ( $node['node_content']['type'] === 'container' ) {
			return call_user_func("cb_templates_container", $node['node_content']['args']);
		}

		if ( $node['node_content']['type'] === 'datetime_picker' ) {
			$node['args']['date']['name'] = "cb_{$node['args']['component']}_{$node['args']['date']['name']}";
			$node['args']['time']['name'] = "cb_{$node['args']['component']}_{$node['args']['time']['name']}";
			return call_user_func( "cb_templates_get_datetime_picker_input", $node['args'] );
		}

		if ( $node['node_content']['type'] !== 'datetime_picker' && $node['node_content']['type'] !== 'container' ) {
			return call_user_func("cb_templates_get_{$node['node_content']['type']}_input", $node['node_content']['args']);
		}

	}

	// Return nothing if we didn't pass any of the tests above.
	return '';

}

/**
 * Gets markup for a time picker.
 *
 * @param array $args {
 *     An associative array of arguments for the time picker.
 *
 *     @type string $name The name attribute for the input.
 *     @type string $label The label for the input.
 *	   @type string $classes The classes for the input.
 *     @type string $value The value for the input.
 *     @type string $placeholder The placeholder for the input.
 *     @type string $min The minimum time for the input.
 *     @type string $max The maximum time for the input.
 *     @type string $step The step for the input.
 *
 * }
 *
 * @return string The markup for the time picker input.
 *
 * @package ConfettiBits\Templates
 * @since 3.0.0
 */
function cb_templates_get_time_picker_input($args = [])
{

	$r = wp_parse_args($args, [
		'name' => '',
		'label' => '',
		'classes' => ['cb-time-picker', 'mb-3'],
		'value' => '',
		'placeholder' => '',
		'min' => '',
		'max' => '',
		'step' => 900,
		'required' => false,
	]);

	if (empty($r['name'])) {
		return;
	}

	$name = esc_attr($r['name']);
	$classes = !empty($r['classes']) ? ' class="' . implode(' ', $r['classes']) . '"' : '';
	$label = !empty($r['label']) ? sprintf('<label for="%s">%s</label>', $name, $r['label']) : '';
	$value = !empty($r['value']) ? ' value="' . esc_attr($r['value']) . '"' : '';
	$placeholder = !empty($r['placeholder']) ? ' placeholder="' . esc_attr($r['placeholder']) . '"' : '';
	$min = !empty($r['min']) ? ' min="' . esc_attr($r['min']) . '"' : '';
	$max = !empty($r['max']) ? ' max="' . esc_attr($r['max']) . '"' : '';
	$step = !empty($r['step']) ? ' step="' . esc_attr($r['step']) . '"' : '';
	$required = $r['required'] ? ' required' : '';

	return sprintf(
		'<div %s>
		%s
		<input type="time" name="%s" id="%s"%s%s%s%s%s%s />
		</div>',
		$classes,
		$label,
		$name,
		$name,
		$required,
		$value,
		$placeholder,
		$min,
		$max,
		$step,

	);

}

/**
 * Outputs markup for a time picker.
 * 
 * @see cb_templates_get_time_picker_input()
 * 
 * @package ConfettiBits\Templates
 * @since 3.0.0
 */
function cb_time_picker_input( $args = [] ) {
	echo cb_templates_get_time_picker_input($args);
}

/**
 * Gets markup for a date picker input.
 * 
 * @param array $args { 
 *   An associative array of arguments for the inputs.
 * 
 *     @type string			$label 			A label for the input.
 * 
 *     @type string			$name			The name attribute for the input. Required.
 * 
 *     @type string 		$placeholder	A placeholder date string.
 * 											Default current date, formatted 'm/d/Y'.
 * 
 *     @type string 		$value			A preset value for the input.
 * 
 *     @type bool 			$disabled 		Whether the input should be disabled by default.
 * 									  		Default false.
 * 
 *     @type bool 			$hidden 		Whether the input should be hidden by default.
 * 						  					Default false.
 * 
 *     @type bool 			$required 		Whether the input should be required by default. 
 * 									  		Default false.
 * 
 *     @type string|array	$classes 		An array of classes that will be added
 * 											to the container element.
 * 											Default ['cb-date-picker'].
 * 
 * }
 * 
 * @return string The formatted markup for the inputs.
 * 
 * @package ConfettiBits\Templates
 * @since 3.0.0
 */
function cb_templates_get_date_picker_input( $args = [] ) {

	$r = wp_parse_args( $args, [
		'label' => '',
		'name' => '',
		'placeholder' => cb_core_current_date(true, 'm/d/Y'),
		'value' => '',
		'disabled' => false,
		'hidden' => false,
		'required' => false,
		'classes' => ['cb-date-picker', 'mb-3']
	]);

	if ( empty( $r['name'] ) ) {
		return;
	}

	if ( !is_array( $r['classes'] ) ) {
		$r['classes'] = preg_split( '/[\s,]+/', $r['classes'] );
	}

	$esc_name = esc_attr($r['name']);
	$label = sprintf('<label for="%s">%s</label>', $esc_name, $r['label'] );
	$name = ' name="' . $esc_name . '"';
	$id = ' id="' . $esc_name . '"';
	$placeholder = !empty( $r['placeholder'] ) ? ' placeholder="' . $r['placeholder'] . '"' : '';
	$value = !empty( $r['value'] ) ? ' value="' . $r['value'] . '"' : '';
	$disabled = !empty( $r['disabled'] ) ? ' disabled' : '';
	$hidden = !empty( $r['hidden'] ) ? ' style="display:none;"' : '';
	$required = !empty( $r['required'] ) ? ' required' : '';

	$content = sprintf(
		'%s<input type="text" %s%s%s%s%s%s%s />',
		$label, $name, $id, $placeholder, $value, $disabled, $hidden, $required
	);

	return cb_templates_container([
		'classes' => $r['classes'],
		'output' => $content
	]);

}


/**
 * Gets markup for both date and time inputs on one line.
 * 
 * @param array $args { 
 *   An associative array of arguments for the inputs.
 * 
 *     @type array $date { 
 *         @see cb_templates_get_date_picker_input()
 *     }
 * 
 *     @type array $time { 
 *         @see cb_templates_get_time_picker_input()
 *     }
 * 
 * }
 * 
 * @return string The formatted markup for the inputs.
 * 
 * @package ConfettiBits\Templates
 * @since 3.0.0
 */
function cb_templates_get_datetime_picker_input( $args = [] ) {

	$r = wp_parse_args($args, [
		'date' => [
			'name' => '',
			'label' => '',
			'classes' => ['cb-date-picker'],
			'value' => '',
			'placeholder' => '',
		],
		'time' => [
			'name' => '',
			'label' => '',
			'classes' => ['cb-time-picker'],
			'value' => '',
			'placeholder' => '',
			'min' => '',
			'max' => '',
			'step' => 900
		]
	]);

	$date_picker = cb_templates_get_date_picker_input( $r['date'] );
	$time_picker = cb_templates_get_time_picker_input( $r['time'] );

	return cb_templates_container([
		'classes' => ['cb-datetime-picker'],
		'output' => $date_picker . $time_picker
	]);

}

/**
 * Outputs markup for a datetime picker input.
 * 
 * @see cb_templates_get_datetime_picker_input()
 * 
 * @package ConfettiBits\Templates
 * @since 3.0.0
 */
function cb_datetime_picker( $args = [] ) {
	echo cb_templates_get_datetime_picker_input($args);
}

/**
 * Formats markup for a datetime-local input.
 * 
 * I cannot believe I didn't know this existed until so recently.
 * 
 * @param array $args { 
 *     An associative array of arguments.
 * 
 *     @type string $label A label for the input.
 *     @type string $name A name for the input.
 *     @type string $placeholder A placeholder for the input.
 *     @type string $value A default value for the input.
 *     @type bool $disabled Whether the input is disabled by default.
 *     @type bool $hidden Whether the input is hidden by default.
 *     @type bool $required Whether the input is required by default.
 *     @type array $classes An array of class selectors to give the input.
 * 
 * }
 * 
 * @return string The formatted markup.
 * 
 * @package ConfettiBits\Templates
 * @since 3.0.0
 */
function cb_templates_get_datetime_local_input( $args = [] ) {

	$r = wp_parse_args( $args, [
		'label' => '',
		'name' => '',
		'placeholder' => cb_core_current_date(true, 'm/d/Y'),
		'value' => '',
		'disabled' => false,
		'hidden' => false,
		'required' => false,
		'classes' => ['cb-datetime-local']
	]);

	if ( empty( $r['name'] ) ) {
		return;
	}

	if ( !is_array( $r['classes'] ) ) {
		$r['classes'] = preg_split( '/[\s,]+/', $r['classes'] );
	}

	$esc_name = esc_attr($r['name']);
	$label = sprintf('<label for="%s">%s</label>', $esc_name, $r['label'] );
	$name = ' name="' . $esc_name . '"';
	$id = ' id="' . $esc_name . '"';
	$placeholder = !empty( $r['placeholder'] ) ? ' placeholder="' . $r['placeholder'] . '"' : '';
	$value = !empty( $r['value'] ) ? ' value="' . $r['value'] . '"' : '';
	$disabled = !empty( $r['disabled'] ) ? ' disabled' : '';
	$hidden = !empty( $r['hidden'] ) ? ' style="display:none;"' : '';
	$required = !empty( $r['required'] ) ? ' required' : '';

	$content = sprintf(
		'%s<input type="datetime-local" %s%s%s%s%s%s%s />',
		$label, $name, $id, $placeholder, $value, $disabled, $hidden, $required
	);

	return cb_templates_container([
		'classes' => $r['classes'],
		'output' => $content
	]);	

}

/**
 * Outputs markup for a datetime-local input.
 * 
 * @see cb_templates_get_datetime_local_input()
 * 
 * @package ConfettiBits\Templates
 * @since 3.0.0
 */
function cb_datetime_local_input( $args = [] ) {
	echo cb_templates_get_datetime_local_input($args);
}

/**
 * Outputs markup for an actually nice date input. Uses
 * Bootstrap styling and plugs in some custom data based on
 * arguments passed.
 * 
 * @param array $args { 
 * 		An optional array of arguments. 
 * } 
 */
function cb_templates_get_date_input( $args = [] ) {

	$r = wp_parse_args( $args, [
		'label' => '',
		'component' => '',
		'placeholder' => cb_core_current_date(true, 'm/d/Y'),
		'value' => '',
		'disabled' => false,
		'readonly' => true,
		'required' => false
	]);

	$component_prefix = "cb_{$r['component']}";
	$with_dashes = str_replace('_', '-', $component_prefix);
	$placeholder = esc_attr($r['placeholder']);
	$readonly = $r['readonly'] ? 'readonly' : ''; 
	$disabled = $r['disabled'] ? 'disabled' : ''; 
	$required = $r['required'] ? 'required' : ''; 
	$value = !empty($r['value']) ? cb_core_sanitize_string($r['value']) : ''; 
	$label = !empty($r['label']) ? cb_core_sanitize_string($r['label']) : '';

	$input = cb_templates_get_text_input([
		'name' => "{$component_prefix}_calendar_date_input",
		'value' => $value,
		'label' => $label,
		'placeholder' => $placeholder
	]);

	// <input type='text' class='form-control' 
	//	id='{$component_prefix}_calendar_date_input' 
	//	name='{$component_prefix}_calendar_date_input' {$placeholder} readonly>

	return "<div class='date-picker-container position-relative'>
		{$input}
		<div class='d-none {$with_dashes}-calendar position-absolute card z-3' style='min-width:30vw;max-width:100%;width:30rem;'>
			<div class='card-header text-center row row-cols-4 mx-0 justify-content-center'>
				<div class='col text-center'>
					<button type='button' class='btn btn-outline-secondary {$with_dashes}-calendar-prev-btn'>Prev</button>
				</div>
				<div class='col text-center'>
					<select class='{$with_dashes}-calendar-month-dropdown form-select'></select>
				</div>
				<div class='col text-center'>
					<select class='{$with_dashes}-calendar-year-dropdown form-select'></select>
				</div>
				<div class='col text-center'>
					<button type='button' class='btn btn-outline-secondary {$with_dashes}-calendar-next-btn'>Next</button>
				</div>
			</div>
			<div class='card-body text-center'>
				<div class='my-2 fw-semibold'>
					<div class='row'>
						<span class='col' style='flex: 0 1 calc(100%/7);'>S</span>
						<span class='col' style='flex: 0 1 calc(100%/7);'>M</span>
						<span class='col' style='flex: 0 1 calc(100%/7);'>T</span>
						<span class='col' style='flex: 0 1 calc(100%/7);'>W</span>
						<span class='col' style='flex: 0 1 calc(100%/7);'>T</span>
						<span class='col' style='flex: 0 1 calc(100%/7);'>F</span>
						<span class='col' style='flex: 0 1 calc(100%/7);'>S</span>
					</div>
				</div>
				<div class=' text-center'>
					<div id='{$component_prefix}_calendar_days' class='row'></div>
				</div>
			</div>
		</div>
	</div>";

}

/**
 * Outputs markup for a pretty date input.
 * 
 * @param array $args An optional array of arguments. 
 * {
 * 		@type string $component Used for HTML naming conventions.
 * 		@type array $container_classes An array of classes that will be added to the element's styling.
 * 		@type int $minute_step Allows the option for time blocks, instead of 60 different options in the select markup. Default 1.
 * 		@type array $value A two-value array of integers that represent the hour and minute, so you can provide defaults on render.
 * }
 * 
 * @return string The formatted markup.
 * 
 * @package Templates
 * @since 3.0.0
 */
function cb_date_input($args = []) {
	echo cb_templates_get_date_input($args);
}

function cb_templates_get_time_selector_input( $args = [] ) {

	$r = wp_parse_args( $args, [
		'component' => '',
		'container_classes' => [],
		'minute_step' => 1,
		'value' => [1,0]
	]);

	$hours_options = '';
	$minutes_options = '';
	$values = array_map('intval', $r['value']);

	for ( $i = 1; $i <= 12; $i++ ) {
		$hours_options .= '<option value="' . $i . '" ' . ($i === $values[0] ? "selected" : "") . '>' . $i . '</option>';
	}

	for ( $j = 0; $j <= 60; $j+= $r['minute_step'] ) {
		$minutes_options .= '<option value="' . ($j < 10 ? 0 : "") . $j . '"' . ($j === $values[1] ? " selected" : "") . '>' . ($j < 10 ? 0 : '') . $j . '</option>';
	}
	
	$classes = ' class="' . implode(' ', array_merge( ['d-flex', 'my-2'], $r['container_classes'] ) ) . '"';
	$ids = ["cb_{$r['component']}_time_selector_container", "cb_{$r['component']}_time_selector_hour", "cb_{$r['component']}_time_selector_minute", "cb_{$r['component']}_time_selector_meridiem" ];
	
	return sprintf('<div %s id="%s" style="gap:.25rem;">
      <select class="form-select" id="%s" >
			%s
      </select>
      <select class="form-select" id="%s" >
			%s
      </select>
      <select class="form-select" id="%s" >
        <option value="AM" selected>AM</option>
        <option value="PM">PM</option>
      </select>
    </div>', $classes, $ids[0], $ids[1], $hours_options, $ids[2], $minutes_options, $ids[3] );

}

function cb_time_selector_input( $args = [] ) {
	
	echo cb_templates_get_time_selector_input($args);
	
}