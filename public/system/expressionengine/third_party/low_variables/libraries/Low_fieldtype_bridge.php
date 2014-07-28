<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Low Fieldtype Bridge Class
 *
 * Acts as bridge between variable types and fieldtypes
 *
 * @package         low-variables-ee_addon
 * @author          Lodewijk Schutte <hi@gotolow.com>
 * @link            http://gotolow.com/addons/low-variables
 * @copyright       Copyright (c) 2009-2011, Low
 */

class Low_fieldtype_bridge {

	/**
	 * Default settings fallback
	 *
	 * @var array
	 */
	var $default_settings = array();

	// --------------------------------------------------------------------

	/**
	 * PHP4 Constructor
	 *
	 * @see	__construct()
	 */
	function Low_fieldtype_bridge($info = array())
	{
		$this->__construct($info);
	}

	// --------------------------------------------------------------------

	/**
	 * PHP5 Constructor
	 *
	 * @param	array	$info
	 * @return	void
	 */
	function __construct($info = array())
	{
		if ($info)
		{
			$this->info = $info;
			$this->ftype = new $info['class'];
		}
	}

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
		if (method_exists($this->ftype, 'display_var_settings'))
		{
			$this->ftype->var_id = $var_id;
			$var_settings = $this->ftype->display_var_settings($var_settings);
		}

		return (array) $var_settings;
	}

	// --------------------------------------------------------------------

	/**
	 * Display settings sub-form for this variable type
	 *
	 * @param	mixed	$var_id			The id of the variable: 'new' or numeric
	 * @param	array	$var_settings	The settings of the variable
	 * @return	array	
	 */
	function save_settings($var_id, $var_settings)
	{
		if (method_exists($this->ftype, 'save_var_settings'))
		{
			if ($var_id != 'new') $this->ftype->var_id = $var_id;
			$var_settings = $this->ftype->save_var_settings($var_settings);
		}

		return $var_settings;
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
		$var_settings['field_name'] = $this->ftype->field_name = "var[{$var_id}]";

		$this->ftype->var_id = $var_id;
		$this->ftype->settings = $var_settings;

		return $this->ftype->display_var_field($var_data);
	}

	// --------------------------------------------------------------------

	/**
	 * Prep variable data for saving
	 *
	 * @param	int		$var_id			The id of the variable
	 * @param	mixed	$var_data		The value of the variable, array or string
	 * @param	array	$var_settings	The settings of the variable
	 * @return	string
	 */
	function save_input($var_id, $var_data, $var_settings)
	{
		if (method_exists($this->ftype, 'save_var_field'))
		{
			$var_settings['field_name'] = $this->ftype->field_name = "var[{$var_id}]";

			$this->ftype->var_id = $var_id;
			$this->ftype->settings = $var_settings;

			$var_data = $this->ftype->save_var_field($var_data);
		}

		return $var_data;
	}

	// --------------------------------------------------------------------

	/**
	 * Display template tag output
	 *
	 * @param	string	$tagdata	Tagdata of template tag
	 * @param	array	$data		Data of the variable, containing id, data, settings...
	 * @return	mixed				String if successful, FALSE if not
	 */
	function display_output($tagdata, $data)
	{
		if (method_exists($this->ftype, 'display_var_tag'))
		{
			$EE =& get_instance();
			$params = is_array($data['variable_settings']) ? array_merge($data['variable_settings'], $EE->TMPL->tagparams) : $EE->TMPL->tagparams;

			$this->ftype->var_id = $data['variable_id'];
			return $this->ftype->display_var_tag($data['variable_data'], $params, $tagdata);
		}
		else
		{
			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	function load_assets()
	{
		return FALSE;
	}
}