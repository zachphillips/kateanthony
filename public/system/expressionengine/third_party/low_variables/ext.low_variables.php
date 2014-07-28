<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include(PATH_THIRD.'low_variables/config.php');

/**
 * Low Variables Extension class
 *
 * Saves settings for the Low Variables module
 *
 * @package         low-variables-ee_addon
 * @author          Lodewijk Schutte <hi@gotolow.com>
 * @link            http://gotolow.com/addons/low-variables
 * @copyright       Copyright (c) 2009-2011, Low
 */

class Low_variables_ext
{
	/**
	 * Extension name
	 *
	 * @var	string
	 */
	var $name = LOW_VAR_NAME;

	/**
	 * Extension version
	 *
	 * @var	string
	 */
	var $version = LOW_VAR_VERSION;

	/**
	 * Extenstion description
	 *
	 * @var	string
	 */
	var $description = 'Low Variables module settings';

	/**
	 * Do settings exist?
	 *
	 * @var	string	y|n
	 */
	var $settings_exist = 'y';

	/**
	 * Documentation URL
	 *
	 * @var	string
	 */
	var $docs_url = LOW_VAR_DOCS;

	/**
	 * Settings array
	 *
	 * @var	array
	 */
	var $settings = array();

	/**
	 * Default settings array
	 *
	 * @var	array
	 */
	var $default_settings = array(
		'license_key'          => '',
		'can_manage'           => array(1),
		'register_globals'     => 'n',
		'register_member_data' => 'n',
		'enabled_types'        => array(LOW_VAR_DEFAULT_TYPE)
	);

	// --------------------------------------------------------------------

	/**
	 * PHP4 Constructor
	 *
	 * @see	__construct()
	 */
	function Low_variables_ext($settings = FALSE)
	{
		$this->__construct($settings);
	}

	// --------------------------------------------------------------------

	/**
	 * PHP5 Constructor
	 *
	 * @return	void
	 */
	function __construct($settings = FALSE)
	{
		// -------------------------------------
		//  Get global instance
		// -------------------------------------

		$this->EE =& get_instance();

		// -------------------------------------
		//  Package path
		// -------------------------------------

		$this->EE->load->add_package_path(PATH_THIRD.'low_variables');

		// -------------------------------------
		//  Load helper
		// -------------------------------------

		$this->EE->load->helper('Low_variables');

		$this->settings = $settings;
	}

	// --------------------------------------------------------------------

	/**
	 * Extension settings form
	 *
	 * @return	array
	 */
	function settings_form($current)
	{
		$this->EE->load->helper('form');

		// -------------------------------------
		//  Get member groups; exclude guests, pending and banned
		// -------------------------------------

		$query = $this->EE->db->query("SELECT group_id, group_title FROM exp_member_groups
							WHERE group_id NOT IN (2,3,4)
							AND site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'
							ORDER BY group_title ASC");

		// -------------------------------------
		//  Initiate member groups array
		// -------------------------------------

		$groups = array();

		// -------------------------------------
		//  Populate member groups array
		// -------------------------------------

		foreach ($query->result_array() AS $row)
		{
			$groups[$row['group_id']] = $row['group_title'];
		}

		// -------------------------------------
		//  Define settings array for display
		// -------------------------------------

		$settings = array_merge($this->default_settings, $current);
		$settings['member_groups'] = $groups;
		$settings['version'] = $this->version;
		$settings['name'] = str_replace('_ext', '', strtolower(get_class($this)));
		$settings['variable_types'] = $this->get_types();

		// -------------------------------------
		//  Build output
		// -------------------------------------

		$this->EE->cp->set_breadcrumb('#', $this->EE->lang->line('low_variables_module_name'));

		// -------------------------------------
		//  Load view
		// -------------------------------------

		return $this->EE->load->view('ext_settings', $settings, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Save extension settings
	 *
	 * @return	null
	 */
	function save_settings()
	{
		// Initiate settings array
		$settings = array();

		// Loop through default settings, check for POST values
		foreach ($this->default_settings AS $key => $val)
		{
			$settings[$key] = $this->EE->input->post($key, $val);
		}

		// Make sure it's always an array
		if ( ! is_array($settings['enabled_types']) )
		{
			$settings['enabled_types'] = array();
		}

		// Always make sure the default type is enabled, too
		if ( ! in_array(LOW_VAR_DEFAULT_TYPE, $settings['enabled_types']) )
		{
			$settings['enabled_types'][] = LOW_VAR_DEFAULT_TYPE;
		}

		// Make sure it's always an array
		if ( ! is_array($settings['can_manage']) )
		{
			$settings['can_manage'] = array();
		}

		// Save serialized settings
		$this->EE->db->update('exp_extensions', array('settings' => serialize($settings)), "class = '".ucfirst(get_class($this))."'");
	}

	// --------------------------------------------------------------------

	/**
	 * Optionally adds variables to Global Vars for early parsing
	 *
	 * @return	null
	 */
	function sessions_end(&$SESS)
	{
		// -------------------------------------
		//  Add extension settings to session cache
		//  Can't use helper yet, 'cause EE->session doesn't exist yet
		// -------------------------------------

		//low_set_cache(LOW_VAR_CLASS_NAME, 'settings', $this->settings);
		$SESS->cache[LOW_VAR_CLASS_NAME]['settings'] = $this->settings;

		// -------------------------------------
		//  Bail if it's not a page request
		// -------------------------------------

		if (REQ != 'PAGE') return;

		// -------------------------------------
		//  Initiate data array
		// -------------------------------------

		$early = array();

		// -------------------------------------
		//  Register member data?
		// -------------------------------------

		if ($this->settings['register_member_data'] == 'y')
		{
			// Variables to set
			$keys = array('member_id', 'group_id', 'group_description', 'username', 'screen_name',
			              'email', 'ip_address', 'location', 'total_entries', 'total_comments',
			              'private_messages', 'total_forum_posts', 'total_forum_topics');

			// Add logged_in_... vars to early parsing arrat
			foreach ($keys AS $key)
			{
				$early['logged_in_'.$key] = @$SESS->userdata[$key];
			}
		}

		if ($this->settings['register_globals'] == 'y')
		{
			// -------------------------------------
			//  Get global variables to parse early
			// -------------------------------------

			$query = $this->EE->db->query("SELECT ee.variable_name, ee.variable_data
								FROM exp_global_variables AS ee
								LEFT JOIN exp_low_variables AS low ON ee.variable_id = low.variable_id
								WHERE low.early_parsing = 'y'
								AND ee.site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'");

			// -------------------------------------
			//  Put results into data array
			// -------------------------------------

			foreach ($query->result_array() AS $row)
			{
				$early[$row['variable_name']] = $row['variable_data'];
			}
		}

		// -------------------------------------
		//  Look for existing language variable, set user language to it
		//  Disabled for now
		// -------------------------------------

		if (isset($this->EE->config->_global_vars['global:language']))
		{
			// $SESS->userdata['language'] = $this->EE->config->_global_vars['global:language'];
		}

		// -------------------------------------
		//  Add variables to early parsed global vars
		//  Option: make it a setting to switch order around?
		// -------------------------------------

		if ($early)
		{
			//$this->EE->config->_global_vars = array_merge($this->EE->config->_global_vars, $early);
			$this->EE->config->_global_vars = array_merge($early, $this->EE->config->_global_vars);
		}

		return $SESS;
	}

	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 *
	 * @param	bool	$install_mod
	 * @return	null
	 */	
	function activate_extension()
	{
		$this->EE->db->insert('exp_extensions',
			array(
				'class'    => ucfirst(get_class($this)),
				'method'   => 'sessions_end',
				'hook'     => 'sessions_end',
				'settings' => serialize($this->default_settings),
				'priority' => 2,
				'version'  => $this->version,
				'enabled'  => 'y'
			)
		); // end db->query
	}

	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 *
	 * @return	null
	 */
	function disable_extension()
	{
		$this->EE->db->query("DELETE FROM exp_extensions WHERE class = '".ucfirst(get_class($this))."'");
	}

	// --------------------------------------------------------------------

	/**
	 * Update Extension
	 *
	 * @param	string	$current
	 * @return	null
	 */
	function update_extension($current = '')
	{
		if ($current == '' OR (version_compare($current, $this->version) === 0) )
		{
			return FALSE;
		}

		// Enable all available types with this update
		if (version_compare($current, '1.2.5', '<'))
		{
			$this->settings['enabled_types'] = array_keys($this->get_types());
			$this->EE->db->query("UPDATE exp_extensions SET settings = '".$this->EE->db->escape_str(serialize($this->settings))."' WHERE class = '".ucfirst(get_class($this))."'");
		}

		// Add register_member_data to settings
		if (version_compare($current, '1.3.4', '<'))
		{
			$this->settings['register_member_data'] = 'n';
			$this->EE->db->query("UPDATE exp_extensions SET settings = '".$this->EE->db->escape_str(serialize($this->settings))."' WHERE class = '".ucfirst(get_class($this))."'");
		}

		// Sync version number
		$this->EE->db->query("UPDATE exp_extensions SET version = '".$this->EE->db->escape_str($this->version)."' WHERE class = '".ucfirst(get_class($this))."'");
	}

	// --------------------------------------------------------------------

	/**
	 * Get array of Variable Types
	 *
	 * This method can be called directly thoughout the package with Low_variables_ext::get_types()
	 * because the extension file will always be loaded
	 *
	 * @param	mixed	$which		FALSE for complete list or array containing which types to get
	 * @return	array
	 */
	function get_types($which = FALSE)
	{
		// -------------------------------------
		//  Get EE instance so this method can
		//  be called outside the scope of this class
		// -------------------------------------

		$EE =& get_instance();

		// -------------------------------------
		//  Initiate return value
		// -------------------------------------

		$types = array();

		// -------------------------------------
		//  Load libraries
		// -------------------------------------

		$EE->load->library('addons');
		$EE->load->library('low_variables_type');
		$EE->load->library('low_fieldtype_bridge');

		// -------------------------------------
		//  Set variable types path
		// -------------------------------------

		$types_path = PATH_THIRD.'low_variables/types/';

		// -------------------------------------
		//  If path is not valid, bail
		// -------------------------------------

		if ( ! is_dir($types_path) ) return;

		// -------------------------------------
		//  Read dir, create instances
		// -------------------------------------

		$dir = opendir($types_path);
		while (($type = readdir($dir)) !== FALSE)
		{
			// skip these
			if ($type == '.' || $type == '..' || !is_dir($types_path.$type)) continue;

			// if given, only get the given ones
			if (is_array($which) && ! in_array($type, $which)) continue;

			// determine file name
			$file = 'vt.'.$type.EXT;
			$path = $types_path.$type.'/';

			if ( ! class_exists($type) && file_exists($path.$file) )
			{
				include($path.$file);
			}

			// Got class? Get its details without instantiating it
			if (class_exists($type))
			{
				$vars = get_class_vars($type);

				$types[$type] = array(
					'path'			=> $path,
					'file'			=> $file,
					'name'			=> (isset($vars['info']['name']) ? $vars['info']['name'] : $type),
					'class'			=> ucfirst($type),
					'version'		=> (isset($vars['info']['version']) ? $vars['info']['version'] : ''),
					'is_default'	=> ($type == LOW_VAR_DEFAULT_TYPE),
					'is_fieldtype'	=> FALSE
				);
			}
		}

		// clean up
		closedir($dir);
		unset($dir);

		// -------------------------------------
		//  Get fieldtypes
		// -------------------------------------

		foreach ($EE->addons->get_installed('fieldtypes') AS $package => $ftype)
		{
			// if given, only get the given ones
			if (is_array($which) && ! in_array($ftype['class'], $which) && ! in_array($package, $which)) continue;

			// Include EE Fieldtype class
			if ( ! class_exists('EE_Fieldtype'))
			{
				include_once (APPPATH.'fieldtypes/EE_Fieldtype'.EXT);
			}

			if ( ! class_exists($ftype['class']) && file_exists($ftype['path'].$ftype['file']))
			{
				include_once ($ftype['path'].$ftype['file']);
			}

			// Check if fieldtype is compatible
			if (method_exists($ftype['class'], 'display_var_field'))
			{
				$vars = get_class_vars($ftype['class']);

				$types[$ftype['name']] = array(
					'path'			=> $ftype['path'],
					'file'			=> $ftype['file'],
					'name'			=> (isset($vars['info']['name']) ? $vars['info']['name'] : $ftype['name']),
					'class'			=> $ftype['class'],
					'version'		=> $ftype['version'],
					'is_default'	=> ($type == LOW_VAR_DEFAULT_TYPE),
					'is_fieldtype'	=> TRUE
				);
			}
		}

		// Sort types by alpha
		ksort($types);

		return $types;
	}

	// --------------------------------------------------------------------

}