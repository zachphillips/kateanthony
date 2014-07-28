<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Low_radio_group extends Low_variables_type {

	var $info = array(
		'name'		=> 'Radio Group',
		'version'	=> LOW_VAR_VERSION
	);

	var $default_settings = array(
		'options' => ''
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

		$options = $this->get_setting('options', $var_settings);

		// -------------------------------------
		//  Build options setting
		// -------------------------------------

		$r[] = array(
			$this->setting_label($this->EE->lang->line('variable_options'), $this->EE->lang->line('variable_options_help')),
			form_textarea(array(
				'name' => $this->input_name('options'),
				'value' => $options,
				'rows' => '7',
				'cols' => '40',
				'style' => 'width:75%'
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

		$options = $this->get_setting('options', $var_settings);
		$options = $this->explode_options($options);

		// -------------------------------------
		//  Build checkboxes
		// -------------------------------------

		foreach ($options AS $key => $val)
		{
			$r .= '<label class="low-radio">'
				.	form_radio("var[{$var_id}]", $key, ($key == $var_data))
				.	htmlspecialchars($val)
				. '</label>';
		}

		// -------------------------------------
		//  Return checkboxes
		// -------------------------------------

		return $r;
	}

}