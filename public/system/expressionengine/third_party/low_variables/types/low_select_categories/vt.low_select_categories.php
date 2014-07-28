<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Low_select_categories extends Low_variables_type {

	var $info = array(
		'name'		=> 'Select Categories',
		'version'	=> LOW_VAR_VERSION
	);

	var $default_settings = array(
		'multiple'			=> 'y',
		'category_groups'	=> array(),
		'separator'			=> 'pipe'
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
		//  Build setting: category groups
		//  First, get all groups for this site
		// -------------------------------------

		$query = $this->EE->db->query("SELECT group_id, group_name FROM exp_category_groups
							WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'
							ORDER BY group_name ASC");

		$all_groups = $this->flatten_results($query->result_array(), 'group_id', 'group_name');

		// -------------------------------------
		//  Then, get current groups from settings
		// -------------------------------------

		$current = $this->get_setting('category_groups', $var_settings);

		$r[] = array(
			$this->setting_label($this->EE->lang->line('category_groups')),
			form_multiselect($this->input_name('category_groups', TRUE), $all_groups, $current)
		);

		// -------------------------------------
		//  Build setting: multiple?
		// -------------------------------------

		$multiple = $this->get_setting('multiple', $var_settings);

		$r[] = array(
			$this->setting_label($this->EE->lang->line('allow_multiple_categories')),
			'<label class="low-checkbox">'.form_checkbox($this->input_name('multiple'), 'y', $multiple, 'class="low-allow-multiple"').
			$this->EE->lang->line('allow_multiple_categories_label').'</label>'
		);

		// -------------------------------------
		//  Build setting: separator
		// -------------------------------------

		$separator = $this->get_setting('separator', $var_settings);

		$r[] = array(
			$this->setting_label($this->EE->lang->line('separator_character')),
			$this->separator_select($separator)
		);

		// -------------------------------------
		//  Build setting: multi interface
		// -------------------------------------

		$multi_interface = $this->get_setting('multi_interface', $var_settings);

		$r[] = array(
			$this->setting_label($this->EE->lang->line('multi_interface')),
			$this->interface_select($multi_interface)
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
		//  Prep options
		// -------------------------------------

		$category_groups = $this->get_setting('category_groups', $var_settings);
		$multiple = $this->get_setting('multiple', $var_settings);
		$separator = $this->get_setting('separator', $var_settings);
		$multi_interface = $this->get_setting('multi_interface', $var_settings);

		// -------------------------------------
		//  Prep current data
		// -------------------------------------

		$current = explode($this->separators[$separator], $var_data);

		// -------------------------------------
		//  No groups? Bail.
		// -------------------------------------

		if (empty($category_groups))
		{
			return $this->EE->lang->line('no_category_groups_selected');
		}

		// -------------------------------------
		//  Get categories
		// -------------------------------------

		$sql_groups = implode(',', $this->EE->db->escape_str($category_groups));

		$sql = "SELECT
				c.cat_id, c.cat_name, g.group_name
			FROM
				exp_categories c, exp_category_groups g
			WHERE
				c.group_id = g.group_id
			AND
				g.group_id IN ({$sql_groups})
			ORDER BY
				g.group_name ASC,
				c.cat_name ASC
		";
		$query = $this->EE->db->query($sql);

		// -------------------------------------
		//  Compose nested category array
		// -------------------------------------

		$cats = array();

		if ($multiple == 'y' && $multi_interface == 'drag-list')
		{
			$cats = $this->flatten_results($query->result_array(), 'cat_id', 'cat_name');
		}
		else
		{
			foreach($query->result_array() AS $row)
			{
				$cats[$row['group_name']][$row['cat_id']] = $row['cat_name'];
			}
		}

		// -------------------------------------
		//  Create interface
		// -------------------------------------

		if ($multiple == 'y' && $multi_interface == 'drag-list')
		{
			// sort cats again
			asort($cats);

			$r = $this->drag_lists($var_id, $cats, $current);
		}
		else
		{
			$r = $this->select_element($var_id, $cats, $current, ($multiple == 'y'));
		}

		// -------------------------------------
		//  Return interface
		// -------------------------------------

		return $r;
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
		return is_array($var_data) ? implode($this->separators[$this->get_setting('separator', $var_settings)], $var_data) : $var_data;
	}

}