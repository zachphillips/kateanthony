<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Low_checkbox extends Low_variables_type {

	var $info = array(
		'name'		=> 'Checkbox',
		'version'	=> LOW_VAR_VERSION
	);

	var $default_settings = array(
		'label' => ''
	);

	// --------------------------------------------------------------------

	/**
	 * Display settings sub-form for this variable type
	 *
	 * @param	mixed	$var_id			The id of the variable: 'new' or numeric
	 * @param	array	$var_settings	The settings of the variable
	 * @return	array	
	 */
	function display_settings($var_id, $var_settings)
	{
		// -------------------------------------
		//  Init return value
		// -------------------------------------

		$r = array();

		// -------------------------------------
		//  Check current value from settings
		// -------------------------------------

		$label = $this->get_setting('label', $var_settings);

		// -------------------------------------
		//  Build label setting
		// -------------------------------------

		$r[] = array(
			$this->setting_label($this->EE->lang->line('variable_checkbox_label')),
			form_input(array(
				'name' => $this->input_name('label'),
				'value' => $label,
				'class' => 'medium'
			))
		);

		// -------------------------------------
		//  Return output
		// -------------------------------------

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Display input field for regular user
	 *
	 * @param	int		$var_id			The id of the variable
	 * @param	string	$var_data		The value of the variable
	 * @param	array	$var_settings	The settings of the variable
	 * @return	string
	 */
	function display_input($var_id, $var_data, $var_settings)
	{
		// -------------------------------------
		//  Init return value
		// -------------------------------------

		$r = '';

		// -------------------------------------
		//  Check current value from settings
		// -------------------------------------

		$label = $this->get_setting('label', $var_settings);

		// -------------------------------------
		//  Build checkbox
		// -------------------------------------

		$r .= '<label class="low-checkbox">'
			.	form_checkbox("var[{$var_id}]", 'y', ($var_data == 'y'))
			.	htmlspecialchars($label)
			. '</label>';

		// -------------------------------------
		//  Return checkbox
		// -------------------------------------

		return $r;
	}

}