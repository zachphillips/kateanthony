<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Low Variables Type Class
 *
 * The Low Variables Type base class, to be extended by other classes
 *
 * @package         low-variables-ee_addon
 * @author          Lodewijk Schutte <hi@gotolow.com>
 * @link            http://gotolow.com/addons/low-variables
 * @copyright       Copyright (c) 2009-2011, Low
 */

class Low_variables_type {

	/**
	 * Separators
	 *
	 * @var array
	 */
	var $separators = array(
		'newline'	=> "\n",
		'pipe'		=> '|',
		'comma'		=> ','
	);

	/**
	 * Interfaces for multiple selects
	 *
	 * @var array
	 */
	var $multi_interfaces = array(
		'select',
		'drag-list'
	);

	/**
	 * Assets loaded
	 *
	 * @var bool
	 */
	var $assets_loaded = FALSE;

	// --------------------------------------------------------------------

	/**
	 * PHP4 Constructor
	 *
	 * @see	__construct()
	 */
	function Low_variables_type($get_language_file = TRUE)
	{
		$this->__construct($get_language_file);
	}

	// --------------------------------------------------------------------

	/**
	 * PHP5 Constructor
	 *
	 * @param	bool	$get_language_file
	 * @return	void
	 */
	function __construct($get_language_file = TRUE)
	{
		// -------------------------------------
		//  Get global instance
		// -------------------------------------

		$this->EE =& get_instance();

		// -------------------------------------
		//  Load stuff
		// -------------------------------------

		$this->EE->load->helper('form');

		// -------------------------------------
		//  Get the class name, which equals the type
		// -------------------------------------

		$this->type = strtolower(get_class($this));

		// -------------------------------------
		//  Set default settings if not provided
		// -------------------------------------

		if ( ! isset($this->default_settings))
		{
			$this->default_settings = array();
		}

		// -------------------------------------
		//  Get language files for this type
		// -------------------------------------

		if ($get_language_file)
		{
			// @todo: load variable type language file, but check if it exists first.
			// For now, you have to explicitly set it in $this->language_files
			// The below code cannot fail silently, so just fuhgeddaboudit...

			// $this->EE->lang->loadfile($this->type, 'low_variables');

			// get extra language file, if specified
			if (isset($this->language_files) && is_array($this->language_files))
			{
				foreach($this->language_files AS $file)
				{
					$this->EE->lang->loadfile($file, 'low_variables');
				}
			}
		}

		// -------------------------------------
		//  Set custom name, if it's there
		// -------------------------------------

		if ( isset($this->info['name']) && (($name = $this->EE->lang->line($this->type.'_type_name')) != $this->type.'_type_name')  )
		{
			$this->info['name'] = $name;
		}

	}

	// --------------------------------------------------------------------

	/**
	 * Load assets for this file type
	 *
	 * @return	bool
	 */
	function load_assets()
	{
		// -------------------------------------
		//  Load assets or bail?
		// -------------------------------------

		if ( ! (isset($this->assets) && is_array($this->assets) && count($this->assets) && !$this->assets_loaded) )
		{
			return FALSE;
		}

		// -------------------------------------
		//  Set url for assets
		// -------------------------------------

		$this->asset_url = 	$this->EE->config->item('theme_folder_url') . "third_party/low_variables/types/{$this->type}/";

		// -------------------------------------
		//  Load CSS files
		// -------------------------------------

		if (isset($this->assets['css']))
		{
			$css = (!is_array($this->assets['css'])) ? array($this->assets['css']) : $this->assets['css'];

			foreach ($css AS $file)
			{
				$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->asset_url.$file.'" />');
			}
		}

		// -------------------------------------
		//  Load JS files
		// -------------------------------------

		if (isset($this->assets['js']))
		{
			$js = (!is_array($this->assets['js'])) ? array($this->assets['js']) : $this->assets['js'];

			foreach ($js AS $file)
			{
				$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$this->asset_url.$file.'"></script>');
			}
		}

		// -------------------------------------
		//  Set loaded to TRUE
		// -------------------------------------

		return ($this->assets_loaded = TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Insert manual js, used per instance of this type
	 *
	 * @return	void
	 */
	function insert_js($js)
	{
		$this->EE->cp->add_to_foot(NL.'<script type="text/javascript">'.NL.$js.NL.'</script>'.NL);
	}

	// --------------------------------------------------------------------

	/**
	 * Insert manual css, used per instance of this type
	 *
	 * @return	void
	 */
	function insert_css($css)
	{
		$this->EE->cp->add_to_head(NL.'<style type="text/css">'.NL.$css.NL.'</style>'.NL);
	}

	// --------------------------------------------------------------------

	/**
	 * Get setting from settings array, fallback to default, fallback to FALSE
	 *
	 * @param	string	$key		Name of setting to get
	 * @param	string	$settings	Current settings array
	 * @return	mixed
	 */
	function get_setting($key, $settings = array())
	{
		if (isset($settings[$key]))
		{
			$val = $settings[$key];
		}
		elseif (isset($this->default_settings[$key]))
		{
			$val = $this->default_settings[$key];
		}
		else
		{
			$val = FALSE;
		}

		return $val;
	}

	// --------------------------------------------------------------------

	/**
	 * Explode variable options to array
	 *
	 * @param	string	$options
	 * @return	array
	 */
	function explode_options($options = '')
	{
		// -------------------------------------
		//  Initiate output
		// -------------------------------------

		$return_data = array();

		// -------------------------------------
		//  Explode data on new line
		// -------------------------------------

		foreach (explode("\n", trim($options)) AS $option)
		{
			// -------------------------------------
			//  Allow for "key : value" options
			// -------------------------------------

			$option = explode(' : ', $option, 2);

			if (count($option) == 2)
			{
				$key = $option[0];
				$val = $option[1];
			}
			else
			{
				$key = $val = $option[0];
			}

			// -------------------------------------
			//  Add item to return data
			// -------------------------------------

			$return_data[$key] = $val;
		}

		// -------------------------------------
		//  Return exploded data
		// -------------------------------------

		return $return_data;
	}

	// --------------------------------------------------------------------

	/**
	 * Takes a DB result set, returns 'flat' array with key => val
	 *
	 * @param	array	$result_set		array with arrays (1 per result)
	 * @param	string	$key			key value for flat array
	 * @param	string	$val			value value for flat array
	 * @return	array
	 */
	function flatten_results($result_set, $key, $val)
	{
		$flat = array();

		foreach($result_set AS $row)
		{
			$flat[$row[$key]] = $row[$val];
		}

		return $flat;
	}

	// --------------------------------------------------------------------

	/**
	 * Return the nested array name for input fields
	 *
	 * @param	string	$setting_name		Name of the setting
	 * @param	bool	$multiple			Add brackets at the end
	 * @return	string
	 */
	function input_name($setting_name, $multiple = FALSE)
	{
		return 'variable_settings['.$this->type.']['.strtolower($setting_name).']'.($multiple?'[]':'');
	}

	// --------------------------------------------------------------------

	/**
	 * Return radiobuttons for separator choice
	 *
	 * @param	string	$selected		Name of the setting
	 * @return	string
	 */
	function separator_select($selected = 'newline')
	{
		$r = '';

		foreach ($this->separators AS $sep => $char)
		{
			$r .= '<label class="low-radio">'
				. 	form_radio($this->input_name('separator'), $sep, ($sep == $selected))
				. 	$this->EE->lang->line($sep)
				. '</label>';
		}

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Return select box for multi-interface choice
	 *
	 * @param	string	$selected		Name of the setting
	 * @return	string
	 * @since	1.1.5
	 */
	function interface_select($selected = 'select')
	{
		$options = array();

		foreach ($this->multi_interfaces AS $interface)
		{
			$options[$interface] = $this->EE->lang->line($interface);
		}

		return form_dropdown($this->input_name('multi_interface'), $options, $selected);
	}

	// --------------------------------------------------------------------

	/**
	 * Return the html for drag 'n drop 'n sortable lists
	 *
	 * @param	int		$var_id			ID of the variable
	 * @param	array	$rows			Associative array containing all possible values
	 * @param	array	$selected		Array containing selected keys
	 * @return	string
	 * @since	1.1.5
	 */
	function drag_lists($var_id, $rows, $selected = array())
	{
		// Initiate 'off' and 'on' lists
		$off = $on = array();

		// Sub-template of item in either list
		// Use hidden input type to set values
		$item = '<li><input type="hidden" name="%s" value="%s" />%s</li>';

		// -------------------------------------
		//  Do we have selected values?
		//  Then add them to $on first, in the correct order
		// -------------------------------------

		if (count($selected))
		{
			foreach ($selected AS $id)
			{
				// Skip unknown items
				if (!isset($rows[$id])) continue;

				// Add item to On list
				$on[] = sprintf($item, "var[{$var_id}][]", htmlspecialchars($id), htmlspecialchars($rows[$id]));

				// Unset item so it doesn't get added to the Off list
				unset($rows[$id]);
			}
		}

		// -------------------------------------
		//  Add rows to left list
		// -------------------------------------

		if (count($rows))
		{
			foreach ($rows AS $key => $val)
			{
				// Set input name to empty, so it isn't sent when submitting the form
				// We'll use JavaScript to set the correct name when the item is dragged to the other list
				$off[] = sprintf($item, '', htmlspecialchars($key), htmlspecialchars($val));
			}
		}

		// -------------------------------------
		//  Return sub-template of list sub-templates
		// -------------------------------------

		$r  = '<div class="low-drag-lists" id="low-drag-lists-'.$var_id.'">'
			.	'<ul class="low-off">'.implode(NL, $off).'</ul>'
			.	'<ul class="low-on">'.implode(NL, $on).'</ul>'
			. '</div>';

		return $r;

	}

	// --------------------------------------------------------------------

	/**
	 * Return the html for (multiple) select element
	 *
	 * @param	int		$var_id			ID of the variable
	 * @param	array	$rows			Associative array containing all possible values
	 * @param	array	$selected		Array containing selected keys
	 * @param	bool	$multiple		Return a multiple select element or not
	 * @return	string
	 * @since	1.2.0
	 */
	function select_element($var_id, $rows, $selected = array(), $multiple = FALSE, $none = TRUE)
	{
		// Determine element name
		$name = "var[{$var_id}]";

		// No optgroups when there's only one
		if (count($rows) == 1 && is_array(current($rows)))
		{
			$rows = current($rows);
		}

		// Entify the lot
		$rows = form_prep($rows);

		// Make sure $selected is an array
		if ( ! is_array($selected))
		{
			$selected = array($selected);
		}

		if ($multiple)
		{
			return form_multiselect($name.'[]', $rows, $selected);
		}
		else
		{
			// Add 'None' option on top of drop down
			if ($none)
			{
				$rows = array('' => '-- '.$this->EE->lang->line('none').' --') + $rows;
			}

			// Get the first selected element.
			// Otherwise we'll get a multiple select
			$selected = empty($selected) ? FALSE : $selected[0];

			return form_dropdown($name, $rows, $selected);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Return the html for label + description for a variable
	 *
	 * @param	string	$name			name or label of variable
	 * @param	string	$notes			Optional notes or description
	 * @return	string
	 * @since	1.2.0
	 */
	function setting_label($name, $notes = '')
	{
		$r = '<strong class="low-label">'.htmlspecialchars($name).'</strong>';

		if ($notes) $r .= '<div class="low-var-notes">'.$notes.'</div>';

		return $r;
	}

}