<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Low_select_entries extends Low_variables_type {

	var $info = array(
		'name'		=> 'Select Entries',
		'version'	=> LOW_VAR_VERSION
	);

	var $default_settings = array(
		'channels'	=> array(),
		'multiple'	=> 'y',
		'separator'	=> 'pipe',
		'multi_interface' => 'select'
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
		//  Build setting: channels
		//  First, get all blogs for this site
		// -------------------------------------

		$query = $this->EE->db->query("SELECT channel_id, channel_title FROM exp_channels
							WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'
							ORDER BY channel_title ASC");

		$all_blogs = $this->flatten_results($query->result_array(), 'channel_id', 'channel_title');

		// -------------------------------------
		//  Then, get current blogs from settings
		// -------------------------------------

		$current = $this->get_setting('channels', $var_settings);

		$r[] = array(
			$this->setting_label($this->EE->lang->line('channels')),
			form_multiselect($this->input_name('channels', TRUE), $all_blogs, $current)
		);

		// -------------------------------------
		//  Build setting: multiple?
		// -------------------------------------

		$multiple = $this->get_setting('multiple', $var_settings);

		$r[] = array(
			$this->setting_label($this->EE->lang->line('allow_multiple_entries')),
			'<label class="low-checkbox">'.form_checkbox($this->input_name('multiple'), 'y', $multiple, 'class="low-allow-multiple"').
			$this->EE->lang->line('allow_multiple_files_label').'</label>'
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

		$channels = $this->get_setting('channels', $var_settings);
		$multiple = $this->get_setting('multiple', $var_settings);
		$separator = $this->get_setting('separator', $var_settings);
		$multi_interface = $this->get_setting('multi_interface', $var_settings);

		// -------------------------------------
		//  Prep current data
		// -------------------------------------

		$current = explode($this->separators[$separator], $var_data);

		// -------------------------------------
		//  No channels? Bail.
		// -------------------------------------

		if (empty($channels))
		{
			return $this->EE->lang->line('no_channel_selected');
		}

		// -------------------------------------
		//  Get entries
		// -------------------------------------

		$sql_channels = implode(',', $this->EE->db->escape_str($channels));

		$sql = "SELECT
				e.entry_id, e.title, w.channel_title
			FROM
				exp_channel_titles e, exp_channels w
			WHERE
				e.channel_id = w.channel_id
			AND
				w.channel_id IN ({$sql_channels})
			ORDER BY
				w.channel_title ASC,
				e.title ASC
		";
		$query = $this->EE->db->query($sql);

		// -------------------------------------
		//  Compose nested category array
		// -------------------------------------

		$entries = array();

		if ($multiple == 'y' && $multi_interface == 'drag-list')
		{
			$entries = $this->flatten_results($query->result_array(), 'entry_id', 'title');
		}
		else
		{
			foreach($query->result_array() AS $row)
			{
				$entries[$row['channel_title']][$row['entry_id']] = $row['title'];
			}
		}

		// -------------------------------------
		//  Create interface
		// -------------------------------------

		if ($multiple == 'y' && $multi_interface == 'drag-list')
		{
			// sort entries again
			asort($entries);

			$r = $this->drag_lists($var_id, $entries, $current);
		}
		else
		{
			$r = $this->select_element($var_id, $entries, $current, ($multiple == 'y'));
		}

		// -------------------------------------
		//  Return select element
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