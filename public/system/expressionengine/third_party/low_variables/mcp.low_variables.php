<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include(PATH_THIRD.'low_variables/config.php');

/**
 * Low Variables Module Class - CP
 *
 * The Low Variables Control Panel master class that handles all of the CP Requests and Displaying
 *
 * @package         low-variables-ee_addon
 * @author          Lodewijk Schutte <hi@gotolow.com>
 * @link            http://gotolow.com/addons/low-variables
 * @copyright       Copyright (c) 2009-2011, Low
 */ 

class Low_variables_mcp {

	/**
	 * URL to module docs
	 *
	 * @var	string
	 */
	var $docs_url = LOW_VAR_DOCS;

	/**
	 * Data array for views
	 *
	 * @var	array
	 */
	var $data = array();

	/**
	 * Extension settings
	 *
	 * @var	array
	 */
	var $settings = array();

	/**
	 * Holder for error messages
	 *
	 * @var	string
	 */
	var $error_msg = '';

	// --------------------------------------------------------------------

	/**
	 * PHP4 Constructor
	 *
	 * @see	__construct()
	 */
	function Low_variables_mcp()
	{
		$this->__construct();
	}

	// --------------------------------------------------------------------

	/**
	 * PHP5 Constructor
	 *
	 * @return	void
	 */
	function __construct()
	{
		// -------------------------------------
		//  Get global object
		// -------------------------------------

		$this->EE =& get_instance();

		// -------------------------------------
		//  Get settings from extension, cache or DB
		// -------------------------------------

		if ( ! $this->_get_settings()) return;

		// -------------------------------------
		//  License check.
		//  Removing this makes baby Jesus cry
		// -------------------------------------

		if ( ! $this->_license()) return;

		// -------------------------------------
		//  Define the package path
		// -------------------------------------

		$this->EE->load->add_package_path(PATH_THIRD.'low_variables');

		// -------------------------------------
		//  Load helper
		// -------------------------------------

		$this->EE->load->helper('Low_variables');

		// -------------------------------------
		//  Include variable types
		// -------------------------------------

		$this->_include_types();

		// -------------------------------------
		//  Define base url for module
		// -------------------------------------

		$this->base_url = $this->data['base_url'] = BASE.AMP.'C=addons_modules&amp;M=show_module_cp&amp;module=low_variables';
	}

	// --------------------------------------------------------------------

	/**
	 * Home screen
	 *
	 * @param	string
	 * @return	void
	 */
	function index()
	{
		if ($this->error_msg != '')
		{
			return $this->error_msg;
		}

		// -------------------------------------
		//  Retrieve feedback message
		// -------------------------------------

		if ($this->data['message'] = $this->EE->session->flashdata('msg'))
		{
			$this->_ee_notice($this->data['message']);
		}

		// -------------------------------------
		//  Check for skipped items
		// -------------------------------------

		$skipped = $this->EE->session->flashdata('skipped');

		// -------------------------------------
		//  Title and Crumbs
		// -------------------------------------

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('low_variables_module_name'));

		// -------------------------------------
		//  Prep SQL variables
		// -------------------------------------

		$sql_where_hidden = $this->settings['is_manager'] ? '' : "AND low.is_hidden = 'n'";
		$sql_site_id      = $this->EE->db->escape_str($this->EE->config->item('site_id'));

		// -------------------------------------
		//  Get variables
		// -------------------------------------

		$sql = "SELECT
				ee.variable_id AS var_id,
				ee.variable_name AS var_name,
				ee.variable_data AS var_data,
				IFNULL(low.group_id,0) AS var_group_id,
				low.variable_label AS var_label,
				low.variable_notes AS var_notes,
				low.variable_type AS var_type,
				low.variable_settings AS var_settings,
				IFNULL(low.variable_order,0) AS var_order,
				'' AS var_input
			FROM
				exp_global_variables AS ee
			LEFT JOIN
				exp_low_variables AS low
			ON
				ee.variable_id = low.variable_id
			LEFT JOIN
				exp_low_variable_groups AS vg
			ON
				low.group_id = vg.group_id
			WHERE
				ee.site_id = '{$sql_site_id}'
				{$sql_where_hidden}
			ORDER BY
				vg.group_order ASC,
				vg.group_label ASC,
				var_order ASC,
				var_name ASC
		";
		$query = $this->EE->db->query($sql);

		if ($query->num_rows() > 0)
		{
			// -------------------------------------
			//  Initiate $rows and $all_ids
			//  all_ids will contain all editable variable ids,
			//  so we can check if certain ids are missing (unchecked checkboxes, etc)
			//  and set their value to an empty string
			// -------------------------------------

			$rows = $all_ids = $alert = array();

			// -------------------------------------
			//  Loop through vars
			// -------------------------------------

			foreach($query->result_array() AS $i => $row)
			{
				$all_ids[] = $row['var_id'];

				// -------------------------------------
				//  Check if var is grouped
				// -------------------------------------

				$group = $row['var_group_id'];

				// -------------------------------------
				//  Check type and settings
				// -------------------------------------

				if ( ! $row['var_type'] || ! isset($this->types[$row['var_type']]))
				{
					$row['var_type'] = LOW_VAR_DEFAULT_TYPE;
				}

				if ( ! ($row['var_settings'] = $this->_sql_unserialize($row['var_settings'])))
				{
					$row['var_settings'] = array();
				}

				// -------------------------------------
				//  Check installed variable types
				//  And show only those settings
				// -------------------------------------

				if (is_object($this->types[$row['var_type']]))
				{
					// Refine settings
					$row['var_settings'] = isset($row['var_settings'][$row['var_type']])
											? $row['var_settings'][$row['var_type']]
											: $this->types[$row['var_type']]->default_settings;

					// Get input from var type
					$row['var_input'] = $this->types[$row['var_type']]->display_input($row['var_id'], $row['var_data'], $row['var_settings']);

					// Load CSS and JS
					$this->types[$row['var_type']]->load_assets();
				}

				// -------------------------------------
				//  Do we have a label?
				// -------------------------------------

				$row['var_name'] = ($row['var_label']) ? $row['var_label'] : ucwords($row['var_name']);

				// -------------------------------------
				//  Add to alert array if skipped
				// -------------------------------------

				if (is_array($skipped) && isset($skipped[$row['var_id']]))
				{
					$row['error_msg'] = $skipped[$row['var_id']];
					$alert[] = $row;
				}

				// -------------------------------------
				//  Add modified row to array
				// -------------------------------------

				$rows[$group][] = $row;
			}

			// -------------------------------------
			//  Create list of relevant groups
			// -------------------------------------

			// Get all groups first
			$groups = low_associate_results($this->_get_variable_groups(FALSE), 'group_id');

			// add the Ungrouped group at the end, if necessary
			if (isset($rows['0']))
			{
				$groups['0'] = array(
					'group_label' => $this->EE->lang->line('ungrouped'),
					'group_notes' => ''
				);

				// If first item in rows is ungrouped group, move it to the end of the array
				if (key($rows) == '0')
				{
					// Slice off ungrouped group, preserving keys
					$ungrouped = array_slice($rows, 0, 1, TRUE);

					// Remove ungrouped group from original
					unset($rows['0']);

					// Append ungrouped group to the end of the array
					$rows += $ungrouped;
				}
			}

			// Initiate group list
			$group_list = array();

			// Loop through all groups
			foreach ($groups AS $group_id => $row)
			{
				// Add # of vars in group to group row
				$row['count'] = isset($rows[$group_id]) ? count($rows[$group_id]) : 0;

				// Skip empty groups for non-managers
				if ( ! $this->settings['is_manager'] && $row['count'] == 0) continue;

				// Add row to group list
				$group_list[$group_id] = $row;
			}

			$this->data['variables'] = $rows;
			$this->data['groups'] = $groups;
			$this->data['group_list'] = $group_list;
			$this->data['show_groups'] = ! ( ! $this->settings['is_manager'] && count($group_list) <= 1);
			$this->data['empty_groups'] = array_keys(array_diff_key($groups, $rows));
			$this->data['all_ids'] = implode('|', $all_ids);
			$this->data['skipped'] = $alert;
			$this->data['active'] = 'home';

			// -------------------------------------
			//  Display rows
			// -------------------------------------

			if ($this->settings['is_manager']) $this->_manage_menu();
			$this->_load_assets();

			return $this->EE->load->view('home', $this->data, TRUE);
		}
		else
		{
			// Show No Variables message
			return $this->_no_vars();
		}

	}

	// --------------------------------------------------------------------

	/**
	 * Set right nav for var managers
	 *
	 * @return	void
	 */
	function _manage_menu()
	{
		$this->EE->cp->set_right_nav(array(
			'low_variables_module_name' => $this->base_url,
			'manage_variables'          => $this->base_url.AMP.'method=manage',
			'create_new'                => $this->base_url.AMP.'method=manage&amp;id=new',
			'create_new_group'          => $this->base_url.AMP.'method=groups&amp;id=new',
			'low_variables_docs'        => LOW_VAR_DOCS,
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Manage variables, either _show_all() or _edit_var()
	 *
	 * @param	string	$message
	 * @return	void
	 */
	function manage()
	{
		// -------------------------------------
		//  Check permissions
		// -------------------------------------

		if ( ! $this->settings['is_manager'] )
		{
			$this->EE->functions->redirect($this->base_url);
			exit;
		}

		// -------------------------------------
		//  Sync EE vars and Low Vars
		// -------------------------------------

		$this->_sync();

		// -------------------------------------
		//  Retrieve feedback message
		// -------------------------------------

		if ($this->data['message'] = $this->EE->session->flashdata('msg'))
		{
			$this->_ee_notice($this->data['message']);
		}

		// -------------------------------------
		//  Add manage menu
		// -------------------------------------

		$this->_manage_menu();

		// -------------------------------------
		//  Check if there's an ID to edit
		// -------------------------------------

		$method = $this->EE->input->get('id') ? '_edit_var' : '_show_all';

		return $this->$method();
	}

	// --------------------------------------------------------------------

	/**
	 * Show table of all variables
	 *
	 * @access	private
	 * @return	void
	 */
	function _show_all()
	{
		// -------------------------------------
		//  Title and Crumbs
		// -------------------------------------

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('manage_variables'));
		$this->EE->cp->set_breadcrumb($this->base_url, $this->EE->lang->line('low_variables_module_name'));

		// -------------------------------------
		//  Get variables
		// -------------------------------------

		$sql_site_id = $this->EE->db->escape_str($this->EE->config->item('site_id'));
		$sql_default_type = $this->EE->db->escape_str(LOW_VAR_DEFAULT_TYPE);

		// -------------------------------------
		//  Compose query and execute
		// -------------------------------------

		$sql = "SELECT ee.variable_id, ee.variable_name, low.variable_label, low.group_id,
					IF(low.variable_type != '',low.variable_type,'{$sql_default_type}') AS variable_type,
					IF(low.variable_order != '',low.variable_order,0) AS variable_order,
					IF(IFNULL(low.early_parsing,'n')='y','yes','no') AS early_parsing,
					IF(IFNULL(low.is_hidden,'n')='y','yes','no') AS is_hidden
				FROM exp_global_variables AS ee
				LEFT JOIN exp_low_variables AS low
				ON ee.variable_id = low.variable_id
				LEFT JOIN exp_low_variable_groups AS vg
				ON low.group_id = vg.group_id
				WHERE ee.site_id = '{$sql_site_id}'
				ORDER BY vg.group_order ASC, vg.group_label ASC, variable_order ASC, ee.variable_name ASC";
		$query = $this->EE->db->query($sql);

		if ($query->num_rows())
		{
			// -------------------------------------
			//  Initiate rows
			// -------------------------------------

			$rows = $query->result_array();
			$grouped = $ungrouped = array();

			// Put ungrouped vars at the bottom
			foreach ($rows AS $row)
			{
				if ( ! strlen($row['group_id']))
				{
					$row['group_id'] = '0';
				}

				if ($row['group_id'])
				{
					$grouped[] = $row;
				}
				else
				{
					$ungrouped[] = $row;
				}
			}

			unset($rows);

			$this->data['variables'] = array_merge($grouped, $ungrouped);
			$this->data['types'] = $this->types;
			$this->data['active'] = 'manage';

			// -------------------------------------
			//  Get variable groups
			// -------------------------------------

			$this->data['variable_groups'] = $this->_get_variable_groups() + array('0' => $this->EE->lang->line('ungrouped'));

			// -------------------------------------
			//  Load JavaScript
			// -------------------------------------

			$this->_load_assets();

			// -------------------------------------
			//  Load view
			// -------------------------------------

			return $this->EE->load->view('manage_list', $this->data, TRUE);
		}
		else
		{
			// Show No Variables message
			return $this->_no_vars();
		}

	}

	// --------------------------------------------------------------------

	/**
	 * Show No Variables found message, with optional Create New link
	 *
	 * @access	private
	 * @return	null
	 */

	function _no_vars()
	{
		// -------------------------------------
		//  Display alert message
		// -------------------------------------

		$msg = $this->EE->lang->line('no_variables_found');

		if ($this->settings['is_manager'])
		{
			$msg .= ' &rarr; <a href="'.$this->base_url.AMP.'method=manage&amp;id=new">'.$this->EE->lang->line('create_new').'</a>';
		}

		return $msg;
	}

	// --------------------------------------------------------------------

	/**
	 * Show edit form to edit single variable
	 *
	 * @access	private
	 * @return	null
	 */
	function _edit_var()
	{
		// -------------------------------------
		//  Title and Crumbs
		// -------------------------------------

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('edit_variable'));
		$this->EE->cp->set_breadcrumb($this->base_url, $this->EE->lang->line('low_variables_module_name'));

		// -------------------------------------
		//  Do we have errors in flashdata?
		// -------------------------------------

		$this->data['errors'] = $this->EE->session->flashdata('errors');

		// -------------------------------------
		//  Get variable groups
		// -------------------------------------

		$this->data['variable_groups'] = array('0' => '--') + $this->_get_variable_groups();

		// -------------------------------------
		//  Create new, clone or edit?
		// -------------------------------------

		$var_id   = $this->EE->input->get('id');
		$clone_id = $this->EE->input->get('clone');

		if ($var_id == 'new')
		{
			// -------------------------------------
			//  Init new array if var is new
			// -------------------------------------

			$this->data = array_merge($this->data, array(
				'variable_id'	=> 'new',
				'group_id'		=> '0',
				'variable_name'	=> '',
				'variable_label'=> '',
				'variable_notes'=> '',
				'variable_type'	=> LOW_VAR_DEFAULT_TYPE,
				'variable_settings' => array(),
				'variable_order'=> '0',
				'early_parsing'	=> 'n',
				'is_hidden'		=> 'n'
			));
		}

		// -------------------------------------
		//  Get var to edit or clone
		// -------------------------------------

		if ( ($var_id != 'new') || is_numeric($clone_id) )
		{
			// -------------------------------------
			//  Default selection
			// -------------------------------------

			$select = array(
				"IF(low.variable_type != '',low.variable_type,'".$this->EE->db->escape_str(LOW_VAR_DEFAULT_TYPE)."') AS variable_type",
				'low.group_id',
				'low.variable_label',
				'low.variable_notes',
				'low.variable_settings',
				'low.early_parsing',
				'low.is_hidden'
			);

			// -------------------------------------
			//  Select more when editing variable
			// -------------------------------------

			if ($var_id != 'new')
			{
				$select = array_merge($select, array(
					'low.variable_order',
					'ee.variable_id',
					'ee.variable_name'
				));

				$sql_var_id = $this->EE->db->escape_str($var_id);
			}
			else
			{
				$sql_var_id = $this->EE->db->escape_str($clone_id);
			}

			// -------------------------------------
			//  Get existing var: compose query and execute
			// -------------------------------------

			$sql_select = implode(', ', $select);
			$sql_site_id = $this->EE->db->escape_str($this->EE->config->item('site_id'));

			$sql = "SELECT
					{$sql_select}
				FROM
					exp_global_variables AS ee
				LEFT JOIN
					exp_low_variables AS low
				ON
					ee.variable_id = low.variable_id
				WHERE
					ee.site_id = '{$sql_site_id}'
				AND
					ee.variable_id = '{$sql_var_id}'
				LIMIT 1
			";
			$query = $this->EE->db->query($sql);

			// -------------------------------------
			//  Exit if no var was found
			// -------------------------------------

			if ($query->num_rows() == 0)
			{
				$this->EE->functions->redirect($this->base_url.AMP.'P=manage&amp;message=var_not_found');
				exit;
			}

			$this->data = array_merge($this->data, $query->row_array());
			$this->data['variable_settings'] = $this->data['variable_settings'] ? $this->_sql_unserialize($this->data['variable_settings']) : array();
		}

		// -------------------------------------
		//  Create types
		// -------------------------------------

		foreach ($this->types AS $type => $obj)
		{
			// Get current settings
			$settings = isset($this->data['variable_settings'][$type]) ? $this->data['variable_settings'][$type] : $obj->default_settings;

			// Call 'display_settings'
			$display = method_exists($obj, 'display_settings') ? $obj->display_settings($this->data['variable_id'], $settings) : array();

			$this->data['types'][$type] = array(
				'name' => $obj->info['name'],
				'settings' => $display
			);
		}

		// -------------------------------------
		//  Load view
		// -------------------------------------

		$this->_load_assets();

		return $this->EE->load->view('manage_var', $this->data, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Manage variable groups
	 *
	 * @return	null
	 */
	function groups()
	{
		// -------------------------------------
		//  Retrieve feedback message
		// -------------------------------------

		if ($this->data['message'] = $this->EE->session->flashdata('msg'))
		{
			$this->_ee_notice($this->data['message']);
		}

		// -------------------------------------
		//  Add manage menu
		// -------------------------------------

		$this->_manage_menu();

		// -------------------------------------
		//  Check if there's an ID to edit
		// -------------------------------------

		$method = ($this->EE->input->get('id') !== FALSE) ? '_edit_group' : '_show_groups';

		return $this->$method();
	}

	// --------------------------------------------------------------------

	/**
	 * Show edit group form
	 *
	 * @return	null
	 */
	function _edit_group()
	{
		// -------------------------------------
		//  Title and Crumbs
		// -------------------------------------

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('edit_group'));
		$this->EE->cp->set_breadcrumb($this->base_url, $this->EE->lang->line('low_variables_module_name'));

		// -------------------------------------
		//  Do we have errors in flashdata?
		// -------------------------------------

		$this->data['errors'] = $this->EE->session->flashdata('errors');
		$this->data['from'] = $this->EE->input->get_post('from');

		// -------------------------------------
		//  Create new or edit?
		// -------------------------------------

		$group_id = $this->EE->input->get('id');
		$sql_group_id = $this->EE->db->escape_str($group_id);
		$sql_site_id = $this->EE->db->escape_str($this->EE->config->item('site_id'));

		// -------------------------------------
		//  Get group details
		// -------------------------------------

		$this->data['variables'] = array();

		if ($group_id != 'new')
		{

			if ($group_id != '0')
			{
				$sql = "SELECT group_id, group_label, group_notes
						FROM exp_low_variable_groups
						WHERE group_id = '{$sql_group_id}'";
				$query = $this->EE->db->query($sql);

				$this->data = array_merge($this->data, $query->row_array());
			}
			else
			{
				$this->data['group_id'] = $group_id;
				$this->data['group_label'] = $this->EE->lang->line('ungrouped');
				$this->data['group_notes'] = '';
			}

			// -------------------------------------
			//  Get variables in group
			// -------------------------------------

			$sql_site_id = $this->EE->db->escape_str($this->EE->config->item('site_id'));

			// -------------------------------------
			//  Compose query and execute
			// -------------------------------------

			$sql = "SELECT ee.variable_id, ee.variable_name, low.variable_type, low.variable_label, IFNULL(low.variable_order,0) AS variable_order
					FROM exp_global_variables AS ee, exp_low_variables AS low
					WHERE ee.variable_id = low.variable_id
					AND ee.site_id = '{$sql_site_id}'
					AND low.group_id = '{$sql_group_id}' 
					ORDER BY variable_order ASC, ee.variable_name ASC";
			$query = $this->EE->db->query($sql);

			// -------------------------------------
			//  Add results to data array
			// -------------------------------------

			$this->data['variables'] = $query->result_array();
		}
		else
		{
			$this->data['group_id'] = 'new';
			$this->data['group_label'] = '';
			$this->data['group_notes'] = '';
		}

		$this->_load_assets();

		return $this->EE->load->view('group_edit', $this->data, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Save group
	 *
	 * @return	null
	 */
	function save_group()
	{
		// -------------------------------------
		//  Return url
		// -------------------------------------

		$return_url = $this->base_url;

		// -------------------------------------
		//  Get group_id
		// -------------------------------------

		if (($group_id = $this->EE->input->post('group_id')) === FALSE)
		{
			// No id found, exit!
			$this->EE->functions->redirect($return_url);
			exit;
		}
		else
		{
			$group_id = $this->EE->db->escape_str($group_id);
		}

		// Skip the following for Ungrouped
		if ($group_id != '0')
		{
			// -------------------------------------
			//  Get group_label
			// -------------------------------------

			if ( ! ($group_label = trim($this->EE->input->post('group_label'))) )
			{
				// No label found, exit!
				// TODO: create proper error message
				$this->EE->functions->redirect($return_url);
				exit;
			}
			else
			{
				$group_label = $this->EE->db->escape_str($group_label);
			}

			// -------------------------------------
			//  Insert / update group
			// -------------------------------------

			$data = array(
				'group_label' => $group_label,
				'group_notes' => $this->EE->input->post('group_notes'),
				'site_id' => $this->EE->config->item('site_id')
			);
		}

		if ($group_id == 'new')
		{
			$this->EE->db->insert('exp_low_variable_groups', $data);
			$group_id = $this->EE->db->insert_id();
		}
		else
		{
			if ($group_id > 0)
			{
				$this->EE->db->update('exp_low_variable_groups', $data, "group_id = '{$group_id}'");
			}

			// -------------------------------------
			//  Variable order
			// -------------------------------------

			if ($vars = $this->EE->input->post('vars'))
			{
				foreach ($vars AS $var_order => $var_id)
				{
					// -------------------------------------
					//  Escape variables
					// -------------------------------------

					$sql_var_id = $this->EE->db->escape_str($var_id);
					$sql_var_order = $this->EE->db->escape_str($var_order + 1);

					// -------------------------------------
					//  Update record
					// -------------------------------------

					$sql = "UPDATE `exp_low_variables` SET `variable_order` = '{$sql_var_order}' WHERE `variable_id` = '{$sql_var_id}'";
					$this->EE->db->query($sql);
				}
			}

		}

		if ($this->EE->input->post('from') == 'manage')
		{
			$return_url .= '&amp;method=manage';
		}

		$this->EE->session->set_flashdata('msg', 'group_saved');
		$this->EE->functions->redirect($return_url);
	}

	// --------------------------------------------------------------------

	/**
	 * Save new sort order for groups
	 *
	 * @return	null
	 */
	function save_group_order($redirect = FALSE)
	{
		// -------------------------------------
		//  Return url
		// -------------------------------------

		$return_url = $this->base_url; 

		// -------------------------------------
		//  Get POST variable
		// -------------------------------------

		if ($groups = $this->EE->input->get_post('groups'))
		{
			if ( ! is_array($groups))
			{
				$groups = explode('|', $groups);
			}

			foreach ($groups AS $group_order => $group_id)
			{
				// -------------------------------------
				//  Escape variables
				// -------------------------------------

				$sql_group_id = $this->EE->db->escape_str($group_id);
				$sql_group_order = $this->EE->db->escape_str($group_order + 1);

				// -------------------------------------
				//  Update/Insert record
				// -------------------------------------

				$sql = "UPDATE `exp_low_variable_groups` SET `group_order` = '{$sql_group_order}' WHERE `group_id` = '{$sql_group_id}'";
				$this->EE->db->query($sql);
			}

			// -------------------------------------
			//  Add feedback to return  url
			// -------------------------------------

			// $this->EE->session->set_flashdata('msg', 'low_variable_groups_saved');
			$return_url .= AMP.'method=groups';
		}

		// -------------------------------------
		//  Go home
		// -------------------------------------

		if ($redirect) $this->EE->functions->redirect($return_url);

		die('ok');
	}

	// --------------------------------------------------------------------

	/**
	 * Saves variable data
	 *
	 * @return	null
	 */
	function save()
	{
		// -------------------------------------
		//  Return url
		// -------------------------------------

		$return_url = $this->base_url;

		// -------------------------------------
		//  Get POST variables
		// -------------------------------------

		if ( ! ($vars = $this->EE->input->post('var')) )
		{
			$vars = array();
		}

		if ($all_ids = $this->EE->input->post('all_ids'))
		{
			$all_ids = explode('|', $all_ids);
		}

		// -------------------------------------
		//  Loop through vars and update
		// -------------------------------------

		if ($all_ids)
		{
			// -------------------------------------
			//  Get existing ids and their type
			// -------------------------------------

			// init types array
			$types = array();

			// get types and settings from DB
			$query = $this->EE->db->query("SELECT `variable_id`, `variable_type`, `variable_settings` FROM `exp_low_variables`");

			// -------------------------------------
			//  Loop thru results
			// -------------------------------------

			foreach ($query->result_array() AS $row)
			{
				// Set type to default if not found
				if ( ! $row['variable_type']  || ! in_array($row['variable_type'], $this->settings['enabled_types']) )
				{
					$row['variable_type'] = LOW_VAR_DEFAULT_TYPE;
				}

				// populate the types + settings array
				$types[$row['variable_id']] = array(
					'type' => $row['variable_type'],
					'settings' => $this->_get_type_settings($row['variable_type'], $row['variable_settings'])
				);
			}

			// -------------------------------------
			//  Get ids that weren't posted, set to empty
			// -------------------------------------

			foreach (array_diff($all_ids, array_keys($vars)) AS $missing_id)
			{
				$vars[$missing_id] = '';
			}

			$skipped = array();

			foreach ($vars AS $var_id => $var_data)
			{
				// Check if type is known
				if ( ! isset($types[$var_id]) )
				{
					$types[$var_id]= array(
						'type' => LOW_VAR_DEFAULT_TYPE,
						'settings' => $this->_get_type_settings(LOW_VAR_DEFAULT_TYPE)
					);
				}

				// -------------------------------------
				//  Does type require action?
				// -------------------------------------

				$var_type     = $types[$var_id]['type'];
				$var_settings = $types[$var_id]['settings'];

				if (method_exists($this->types[$var_type], 'save_input'))
				{
					// Set error message to empty string
					$this->types[$var_type]->error_msg = '';

					// if FALSE is returned, skip this var
					if (($var_data = $this->types[$var_type]->save_input($var_id, $var_data, $var_settings)) === FALSE)
					{
						$skipped[$var_id] = $this->types[$var_type]->error_msg;
						continue;
					}
				}

				// -------------------------------------
				//  Escape variables
				// -------------------------------------

				$sql_var_id = $this->EE->db->escape_str($var_id);
				$sql_var_data = $this->EE->db->escape_str($var_data);

				// -------------------------------------
				//  Update record
				// -------------------------------------

				$this->EE->db->query("UPDATE `exp_global_variables` SET `variable_data` = '{$sql_var_data}' WHERE `variable_id` = '{$sql_var_id}'");
			}

			// -------------------------------------
			//  Add feedback to return  url
			// -------------------------------------

			$this->EE->session->set_flashdata('msg', 'low_variables_saved');

			if ( ! empty($skipped))
			{
				$this->EE->session->set_flashdata('skipped', $skipped);
			}
		}

		// -------------------------------------
		//  Go home
		// -------------------------------------

		$this->EE->functions->redirect($return_url);
		exit;
	}

	// --------------------------------------------------------------------

	/**
	 * Saves variable list
	 *
	 * @return	null
	 */
	function save_list()
	{
		// -------------------------------------
		//  Return url
		// -------------------------------------

		$return_url = $this->base_url.AMP.'method=manage';

		// -------------------------------------
		//  Get vars from POST
		// -------------------------------------

		if ($vars = $this->EE->input->post('toggle'))
		{
			// -------------------------------------
			//  Get action to perform with list
			// -------------------------------------

			$action = $this->EE->input->post('action');

			if ($action == 'delete')
			{
				// Show delete confirmation
				return $this->_delete_confirmation($vars);
			}
			elseif (in_array($action, array_keys($this->types)))
			{
				// Change variable type of given items
				$this->_change_type($vars, $action);
			}
			elseif ($action == 'show')
			{
				// Set is_hidden to 'n'
				$this->_set_is_hidden($vars, 'n');
			}
			elseif ($action == 'hide')
			{
				// Set is_hidden to 'y'
				$this->_set_is_hidden($vars, 'y');
			}
			elseif ($action == 'enable_early_parsing')
			{
				// Turn on early parsing for these ids
				$this->_set_early_parsing($vars, 'y');
			}
			elseif ($action == 'disable_early_parsing')
			{
				// Turn off early parsing for these ids
				$this->_set_early_parsing($vars, 'n');
			}
			elseif (is_numeric($action))
			{
				// Move to different group
				$this->_move_to_group($vars, $action);
			}
		}

		$this->EE->functions->redirect($return_url);
	}

	// --------------------------------------------------------------------

	/**
	 * Asks for deletion confirmation
	 *
	 * @access	private
	 * @param	array	$vars
	 * @return	null
	 */
	function _delete_confirmation($vars = array())
	{
		// -------------------------------------
		//  Title and Crumbs
		// -------------------------------------

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('low_variables_delete_confirmation'));
		$this->EE->cp->set_breadcrumb($this->base_url, $this->EE->lang->line('low_variables_module_name'));
		$this->EE->cp->set_breadcrumb($this->base_url.AMP.'method=manage', $this->EE->lang->line('manage_variables'));

		// -------------------------------------
		//  Get var names
		// -------------------------------------

		$sql_vars = $this->_sql_in_array($vars);
		$var_names = array();

		$query = $this->EE->db->query("SELECT `variable_name` FROM `exp_global_variables` WHERE `variable_id` IN ({$sql_vars}) ORDER BY `variable_name` ASC");

		foreach ($query->result_array() AS $row)
		{
			$this->data['variable_names'][] = LD.$row['variable_name'].RD;
		}

		// -------------------------------------
		//  Show confirm message
		// -------------------------------------

		$this->data['variable_ids'] = implode('|', $vars);
		$this->data['confirm_message'] = $this->EE->lang->line('low_variables_delete_confirmation_'.(count($vars)==1?'one':'many'));

		$this->_manage_menu();
		$this->_load_assets();

		return $this->EE->load->view('manage_delete', $this->data, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes variables
	 *
	 * @return	null
	 */
	function delete()
	{
		// -------------------------------------
		//  Get var ids
		// -------------------------------------

		if ($vars = explode('|', $this->EE->input->post('variable_id')))
		{
			$sql_vars = $this->_sql_in_array($vars);

			// -------------------------------------
			//  Delete from both tables
			// -------------------------------------

			$this->EE->db->query("DELETE FROM `exp_global_variables` WHERE `variable_id` IN ({$sql_vars})");
			$this->EE->db->query("DELETE FROM `exp_low_variables` WHERE `variable_id` IN ({$sql_vars})");
		}

		// -------------------------------------
		//  Go to manage screen and show message
		// -------------------------------------

		$this->EE->session->set_flashdata('msg', 'low_variables_deleted');
		$this->EE->functions->redirect($this->base_url.AMP.'method=manage');
		exit;
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes variable group
	 *
	 * @return	null
	 */
	function delete_group()
	{
		// -------------------------------------
		//  Get group id
		// -------------------------------------

		if ($group_id = $this->EE->input->post('group_id'))
		{
			$sql_group_id = $this->EE->db->escape_str($group_id);

			// -------------------------------------
			//  Delete from both table, update vars
			// -------------------------------------

			$this->EE->db->query("DELETE FROM `exp_low_variable_groups` WHERE `group_id` = '{$sql_group_id}'");
			$this->EE->db->query("UPDATE `exp_low_variables` SET `group_id` = '0' WHERE `group_id` = '{$sql_group_id}'");
		}

		// -------------------------------------
		//  Go to manage screen and show message
		// -------------------------------------

		$this->EE->session->set_flashdata('msg', 'low_variable_group_deleted');
		$this->EE->functions->redirect($this->base_url);
		exit;
	}

	// --------------------------------------------------------------------

	/**
	 * Asks for group deletion confirmation
	 *
	 * @return	null
	 */
	function group_delete_confirmation()
	{
		// -------------------------------------
		//  Title and Crumbs
		// -------------------------------------

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('low_variables_group_delete_confirmation'));
		$this->EE->cp->set_breadcrumb($this->base_url, $this->EE->lang->line('low_variables_module_name'));
		// $this->EE->cp->set_breadcrumb($this->base_url.AMP.'method=manage', $this->EE->lang->line('manage_variables'));

		// -------------------------------------
		//  Get group name
		// -------------------------------------

		$sql_group = $this->EE->db->escape_str($this->EE->input->get('id'));
		$query = $this->EE->db->query("SELECT `group_label` FROM `exp_low_variable_groups` WHERE `group_id` = '{$sql_group}' LIMIT 1");
		$row = $query->row_array();

		// -------------------------------------
		//  Show confirm message
		// -------------------------------------

		$this->data['group_label'] = $row['group_label'];
		$this->data['group_id'] = $sql_group;
		$this->data['confirm_message'] = $this->EE->lang->line('low_variables_group_delete_confirmation_one');

		$this->_manage_menu();
		$this->_load_assets();

		return $this->EE->load->view('manage_delete_group', $this->data, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Changes given variables to given type
	 *
	 * @access	private
	 * @param	array	$vars
	 * @param	string	$type
	 * @return	null
	 */
	function _change_type($vars = array(), $type = LOW_VAR_DEFAULT_TYPE)
	{
		$this->EE->db->where_in('variable_id', $vars);
		$this->EE->db->update('exp_low_variables', array('variable_type' => $type));
		$this->EE->session->set_flashdata('msg', 'low_variables_saved');
	}

	// --------------------------------------------------------------------

	/**
	 * Changes is_hidden of variables
	 *
	 * @access	private
	 * @param	array	$vars
	 * @param	string	$val
	 * @return	null
	 */
	function _set_is_hidden($vars = array(), $val = 'n')
	{
		$this->EE->db->where_in('variable_id', $vars);
		$this->EE->db->update('exp_low_variables', array('is_hidden' => $val));
		$this->EE->session->set_flashdata('msg', 'low_variables_saved');
	}

	// --------------------------------------------------------------------

	/**
	 * Enables or disables early parsing for given vars
	 *
	 * @access	private
	 * @param	array	$vars
	 * @param	string	$val
	 * @return	null
	 */
	function _set_early_parsing($vars = array(), $val = 'n')
	{
		$this->EE->db->where_in('variable_id', $vars);
		$this->EE->db->update('exp_low_variables', array('early_parsing' => $val));
		$this->EE->session->set_flashdata('msg', 'low_variables_saved');
	}

	// --------------------------------------------------------------------

	/**
	 * Move given variables to given group
	 *
	 * @param	array	Variable ids
	 * @param	int		Group id
	 * @return	null
	 */
	function _move_to_group($vars = array(), $group_id = 0)
	{
		$this->EE->db->where_in('variable_id', $vars);
		$this->EE->db->update('exp_low_variables', array('group_id' => $group_id));
		$this->EE->session->set_flashdata('msg', 'low_variables_moved');
	}

	// --------------------------------------------------------------------

	/**
	 * Saves variable data
	 *
	 * @return	null
	 */
	function save_var()
	{
		// -------------------------------------
		//  Return url
		// -------------------------------------

		$return_url = $this->base_url.AMP.'method=manage';

		// -------------------------------------
		//  Get variable_id
		// -------------------------------------

		if ( ! ($variable_id = $this->EE->input->post('variable_id')) )
		{
			// No id found, exit!
			$this->EE->functions->redirect($return_url);
			exit;
		}
		else
		{
			$variable_id = $this->EE->db->escape_str($variable_id);
		}

		// -------------------------------------
		//  Get data from POST
		// -------------------------------------

		$ee_vars = $low_vars = $errors = array();

		// -------------------------------------
		//  Check variable name
		// -------------------------------------

		if (($var_name = $this->EE->input->post('variable_name')) && preg_match('/^[a-zA-Z0-9\-_]+$/', $var_name))
		{
			$ee_vars['variable_name'] = $var_name;
		}
		else
		{
			$errors[] = 'invalid_variable_name';
		}

		// -------------------------------------
		//  Check variable data
		// -------------------------------------

		if ($variable_id == 'new' && ($var_data = $this->EE->input->post('variable_data')))
		{
			$ee_vars['variable_data'] = $var_data;
		}

		// -------------------------------------
		//  Check boolean values
		// -------------------------------------

		foreach (array('early_parsing', 'is_hidden') AS $var)
		{
			$low_vars[$var] = ($value = $this->EE->input->post($var)) ? 'y' : 'n';
		}

		// -------------------------------------
		//  Check other regular vars
		// -------------------------------------

		foreach (array('group_id', 'variable_label', 'variable_notes', 'variable_type', 'variable_order') AS $var)
		{
			$low_vars[$var] = ($value = $this->EE->input->post($var)) ? $value : '';
		}

		// -------------------------------------
		//  Check Settings for missing values (silly checkboxes eh?)
		// -------------------------------------

		if (is_array($var_settings = $this->EE->input->post('variable_settings')) && is_object($this->types[$low_vars['variable_type']]))
		{
			if (method_exists($this->types[$low_vars['variable_type']], 'save_settings'))
			{
				// Settings?
				$settings = isset($var_settings[$low_vars['variable_type']]) ? $var_settings[$low_vars['variable_type']] : $this->types[$low_vars['variable_type']]->default_settings;

				// Call API for custom handling of settings
				$var_settings[$low_vars['variable_type']] = $this->types[$low_vars['variable_type']]->save_settings($variable_id, $settings);
			}
			else
			{
				// Default handling of settings
				foreach (array_keys($this->types[$low_vars['variable_type']]->default_settings) AS $setting)
				{
					if ( ! isset($var_settings[$low_vars['variable_type']][$setting]))
					{
						$var_settings[$low_vars['variable_type']][$setting] = '';
					}
				}
			}
		}

		$low_vars['variable_settings'] = $this->_sql_serialize($var_settings);

		// -------------------------------------
		//  Check for errors
		// -------------------------------------

		if ( ! empty($errors))
		{
			$msg = array();

			foreach ($errors AS $line)
			{
				$msg[] = $this->EE->lang->line($line);
			}

			$this->EE->session->set_flashdata('errors', $msg);
			$this->EE->functions->redirect($return_url.AMP.'id='.$variable_id);
			exit;
		}

		// -------------------------------------
		//  Check for suffixes
		// -------------------------------------

		// init vars
		$suffixes = $suffixed = array();

		if ($variable_id == 'new' && ($suffix = $this->EE->input->post('variable_suffix')))
		{
			foreach (explode(' ', $suffix) AS $sfx)
			{
				// Skip illegal ones
				if ( ! preg_match('/^[a-zA-Z0-9\-_]+$/', $sfx)) continue;

				// Remove underscore if it's there
				if (substr($sfx, 0, 1) == '_') $sfx = substr($sfx, 1);

				$suffixes[] = $sfx;
			}
		}

		// -------------------------------------
		//  Update EE table
		// -------------------------------------

		if ( ! empty($ee_vars))
		{
			if ($variable_id == 'new')
			{
				// -------------------------------------
				//  Add site id to array, INSERT new var
				//  Get inserted id
				// -------------------------------------

				$ee_vars['site_id'] = $this->EE->config->item('site_id');

				if ($suffixes)
				{
					foreach ($suffixes AS $sfx)
					{
						// Add suffix to name
						$data = $ee_vars;
						$data['variable_name'] = $ee_vars['variable_name'] . '_' . $sfx;

						// Insert row
						$this->EE->db->insert('exp_global_variables', $data);

						// Keep track of inserted rows
						$suffixed[$this->EE->db->insert_id()] = $sfx;
					}
				}
				else
				{
					$this->EE->db->insert('exp_global_variables', $ee_vars);

					$variable_id = $this->EE->db->insert_id();
				}
			}
			else
			{
				$this->EE->db->update('exp_global_variables', $ee_vars, "variable_id = '{$variable_id}'");
			}
		}

		// -------------------------------------
		//  Update low_variables table
		// -------------------------------------

		if ( ! empty($low_vars))
		{
			$update = $this->_get_existing_ids();

			// -------------------------------------
			//  Get default value for new sort order
			// -------------------------------------

			if ($low_vars['variable_order'] == 0)
			{
				$query = $this->EE->db->query("SELECT COUNT(*) AS max FROM exp_low_variables WHERE group_id = '{$low_vars['group_id']}'");

				if ($query->num_rows())
				{
					$row = $query->row();
					$low_vars['variable_order'] = (int) $row->max + 1;
				}
				else
				{
					$low_vars['variable_order'] = 1;
				}
			}

			if ($suffixed)
			{
				$i = (int) $low_vars['variable_order'];

				foreach ($suffixed AS $var_id => $sfx)
				{
					$row = $low_vars;
					$row['variable_label']
						= (strpos($low_vars['variable_label'], '{suffix}') !== FALSE)
						? str_replace('{suffix}', $sfx, $low_vars['variable_label'])
						: $low_vars['variable_label'] . " ({$sfx})";
					$row['variable_order'] = $i++;
					$rows[$var_id] = $row;
				}
			}
			else
			{
				$rows[$variable_id] = $low_vars;
			}

			// -------------------------------------
			//  INSERT/UPDATE rows
			// -------------------------------------

			foreach ($rows AS $var_id => $data)
			{
				if (in_array($var_id, $update))
				{
					$this->EE->db->update('exp_low_variables', $data, "variable_id = '{$var_id}'");
				}
				else
				{
					$data['variable_id'] = $var_id;

					$this->EE->db->insert('exp_low_variables', $data);
				}
				
				// post_save_var API call here
				
			}
		}
		else
		{
			// -------------------------------------
			//  Delete reference if no low_vars were found
			// -------------------------------------

			$this->EE->db->query("DELETE FROM `exp_low_variables` WHERE `variable_id` = '{$variable_id}'");
		}

		// -------------------------------------
		//  Return with message
		// -------------------------------------

		$this->EE->session->set_flashdata('msg', 'low_variables_saved');
		$this->EE->functions->redirect($return_url);
	}

	// --------------------------------------------------------------------

	/**
	 * Gets settings
	 *
	 * @access	private
	 * @return	null
	 */
	function _get_settings()
	{
		// -------------------------------------
		//  Get settings from extension, cache or DB
		// -------------------------------------

		if (($this->settings = low_get_cache(LOW_VAR_CLASS_NAME, 'settings')) === FALSE)
		{
			$query = $this->EE->db->query("SELECT settings FROM exp_extensions WHERE class = 'Low_variables_ext' LIMIT 1");

			if ($query->num_rows())
			{
				$row = $query->row();
				$this->settings = $this->_sql_unserialize($row->settings);
			}
		}

		if ( ! empty($this->settings))
		{
			// -------------------------------------
			//  Is current user a Variable Manager?
			// -------------------------------------

			$this->settings['is_manager'] = in_array($this->EE->session->userdata['group_id'], $this->settings['can_manage']);

			// -------------------------------------
			//  Add settings to data array for views
			// -------------------------------------

			$this->data['settings'] = $this->settings;

			return TRUE;
		}
		else
		{
			// -------------------------------------
			//  No settings? Show error and bail
			// -------------------------------------

			$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('low_variables_module_name'));
			$this->error_msg = $this->EE->lang->line('settings_not_found');

			return FALSE;
		}

	}

	// --------------------------------------------------------------------

	/**
	 * Include Variable Types
	 *
	 * @access	private
	 * @return	null
	 */
	function _include_types()
	{
		// -------------------------------------
		//  Check extension settings to get which types
		// -------------------------------------

		$which = is_array($this->settings['enabled_types']) ? $this->settings['enabled_types'] : FALSE;

		// -------------------------------------
		//  Get the types using extension function
		// -------------------------------------

		$types = Low_variables_ext::get_types($which);

		// -------------------------------------
		//  Initiate class for each enabled type
		// -------------------------------------

		foreach ($types AS $type => $info)
		{
			if (class_exists($info['class']))
			{
				$this->types[$type] = ($info['is_fieldtype'] === TRUE) ? new Low_fieldtype_bridge($info) : new $info['class'];
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get existing ids from exp_low_variables table
	 *
	 * @access	private
	 * @return	array
	 */
	function _get_existing_ids()
	{
		// -------------------------------------
		//  Initiate ids array
		// -------------------------------------

		$ids = array();

		// -------------------------------------
		//  Execute query
		// -------------------------------------

		$query = $this->EE->db->query("SELECT variable_id FROM exp_low_variables");

		// -------------------------------------
		//  Loop thru results
		// -------------------------------------

		foreach ($query->result_array() AS $row)
		{
			$ids[] = $row['variable_id'];
		}

		// -------------------------------------
		//  Return array of ids
		// -------------------------------------

		return $ids;
	}

	// --------------------------------------------------------------------

	/**
	 * Get settings array for given type
	 *
	 * @access	private
	 * @param	string		$type
	 * @param	mixed		$settings	(serialized) array	
	 * @return	mixed		Either array of settings or FALSE
	 */
	function _get_type_settings($type, $settings = '')
	{
		// Set type to default type if not defined
		if (!$type)
		{
			$type = LOW_VAR_DEFAULT_TYPE;
		}

		// unserialize if necessary
		if (is_string($settings))
		{
			$settings = $this->_sql_unserialize($settings);
		}

		// Get type settings
		if (isset($settings[$type]))
		{
			$set = $settings[$type];
		}
		// fallback to default settings
		elseif (isset($this->types[$type]->default_settings))
		{
			$set = $this->types[$type]->default_settings;
		}
		else
		{
			$set = FALSE;
		}

		return $set;
	}

	// --------------------------------------------------------------------

	/**
	 * Get variable groups
	 *
	 * @access	private
	 * @param	bool
	 * @return	array
	 * @since	1.3.2
	 */
	function _get_variable_groups($flat = TRUE)
	{
		$sql_site_id = $this->EE->db->escape_str($this->EE->config->item('site_id'));

		$sql = "SELECT group_id, group_label, group_notes
				FROM exp_low_variable_groups
				WHERE site_id = '{$sql_site_id}'
				ORDER BY group_order ASC, group_label ASC";
		$query = $this->EE->db->query($sql);

		return $flat ? low_flatten_results($query->result_array(), 'group_label', 'group_id') : $query->result_array();
	}

	// --------------------------------------------------------------------

	/**
	 * Returns escaped comma-separated array for SQL
	 *
	 * @access	private
	 * @param	array	$array
	 * @return	string
	 */
	function _sql_in_array($array = array())
	{
		$sql_array = array();

		foreach ($array AS $row)
		{
			$sql_array[] = "'".$this->EE->db->escape_str($row)."'";
		}

		return implode(',', $sql_array);
	}

	// --------------------------------------------------------------------

	/**
	 * Returns escaped serialized array for SQL. Backslashes, anyone?
	 *
	 * @access	private
	 * @param	array	$array
	 * @return	string
	 */
	function _sql_serialize($array = array())
	{
		//return preg_replace('/\\\("|\'|\\\)/', '\\\\\\\\\\\$1', serialize($array));
		return serialize($array);
	}

	// --------------------------------------------------------------------

	/**
	 * Returns stripslashed unserialized string from SQL
	 *
	 * @access	private
	 * @param	string	$str
	 * @return	string
	 */
	function _sql_unserialize($str = '')
	{
		//return (is_string($str)) ? $this->_array_stripslashes(unserialize($str)) : $str;
		return (is_string($str)) ? unserialize($str) : $str;
	}

	// --------------------------------------------------------------------

	/**
	 * License check
	 *
	 * @access	private
	 * @return	bool
	 */
	function _license()
	{
 		if ( ! ($this->settings['license_key'] && is_numeric($this->settings['license_key']) && (strlen($this->settings['license_key']) == 25))
				&&
			 ! ($this->settings['license_key'] && preg_match('/^[0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12}$/', $this->settings['license_key']))
			)
		{
			$title = $this->EE->lang->line('low_variables_module_name');
			$ext_url = BASE.AMP.'C=addons_extensions&amp;M=extension_settings&amp;file=low_variables';

			$this->EE->cp->set_variable('cp_page_title', $title);
			$this->error_msg = <<<EOM
				Your license key appears to be invalid. You can get a valid one here: <a href="{$this->docs_url}">{$title}</a>.
				Enter your key here: <a href="{$ext_url}">{$title} Extension settings</a>
EOM;
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Sync EE vars and Low vars
	 *
	 * Deletes Low Variables that reference to non-existing EE Variables
	 *
	 * @access	private
	 * @return	null
	 */
	function _sync()
	{
		// -------------------------------------
		//  Execute query
		// -------------------------------------

		$query = $this->EE->db->query("SELECT variable_id FROM exp_global_variables");

		// -------------------------------------
		//  Get results
		// -------------------------------------

		$ids = low_flatten_results($query->result_array(), 'variable_id');

		// -------------------------------------
		//  Delete non-existing rows in exp_low_variables
		// -------------------------------------

		if ( ! empty($ids))
		{
			$this->EE->db->query("DELETE FROM exp_low_variables WHERE variable_id NOT IN (".implode(',', $ids).")");

			// Get Low Variables
			$query = $this->EE->db->query("SELECT variable_id FROM exp_low_variables");

			$low_ids = low_flatten_results($query->result_array(), 'variable_id');

			// Get ids that do not exist in low_var but do exist in ee_var
			if ($diff = array_diff($ids, $low_ids))
			{
				foreach ($diff AS $var_id)
				{
					$this->EE->db->insert('exp_low_variables', array(
						'variable_id'   => $var_id,
						'group_id'      => '0',
						'variable_type' => LOW_VAR_DEFAULT_TYPE
					));
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Load assets: extra JS and CSS
	 *
	 * @access	private
	 * @return	void
	 * @since	1.1.5
	 */
	function _load_assets()
	{
		// -------------------------------------
		//  Assets to load
		// -------------------------------------

		$assets = array(
			'styles/low_variables.css',
			'scripts/jquery.cookie.js',
			'scripts/low_variables.js'
		);

		// -------------------------------------
		//  Define placeholder
		// -------------------------------------

		$header = array();

		// -------------------------------------
		//  Loop through assets
		// -------------------------------------

		$asset_url = $this->EE->config->item('theme_folder_url') . 'third_party/low_variables/';

		foreach ($assets AS $file)
		{
			// location on server
			$file_url = $asset_url.$file;

			if (substr($file, -3) == 'css')
			{
				$header[] = '<link charset="utf-8" type="text/css" href="'.$file_url.'" rel="stylesheet" media="screen" />';
			}
			elseif (substr($file, -2) == 'js')
			{
				$header[] = '<script charset="utf-8" type="text/javascript" src="'.$file_url.'"></script>';
			}
		}

		// -------------------------------------
		//  Add combined assets to header
		// -------------------------------------

		if ($header)
		{
			$this->EE->cp->add_to_head(NL.'<!-- Low Variables Assets -->'.NL.implode(NL, $header).NL.'<!-- / Low Variables Assets -->'.NL);
		}

		// Extra IE CSS and JS
		$extra_header = <<<EOH
			<!--[if lte IE 7]>
				<style type="text/css">
					#low-variables-list {zoom:1}
					#low-variables-list li {margin:-4px 0 0 0}
				</style>
			<![endif]-->
EOH;
		$this->EE->cp->add_to_head($extra_header);
	}

	// --------------------------------------------------------------------

	/**
	 * Show EE notification and hide it after a few seconds
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function _ee_notice($msg)
	{
		$this->EE->javascript->output(array(
			'$.ee_notice("'.$this->EE->lang->line($msg).'",{type:"success",open:true});',
			'window.setTimeout(function(){$.ee_notice.destroy()}, 2000);'
		));
	}

} // End Class
