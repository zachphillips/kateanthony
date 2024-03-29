<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include(PATH_THIRD.'low_variables/config.php');

/**
 * Low Variables UPD class
 *
 * @package         low-variables-ee_addon
 * @author          Lodewijk Schutte ~ Low <hi@gotolow.com>
 * @link            http://gotolow.com/addons/low-variables
 * @copyright       Copyright (c) 2009-2011, Low
 */
class Low_variables_upd {

	// --------------------------------------------------------------------
	// PROPERTIES
	// --------------------------------------------------------------------

	/**
	 * Version number
	 *
	 * @access      public
	 * @var         string
	 */
	public $version = LOW_VAR_VERSION;

	// --------------------------------------------------------------------

	/**
	 * EE Instance
	 *
	 * @access      private
	 * @var         object
	 */
	private $EE;

	// --------------------------------------------------------------------
	// METHODS
	// --------------------------------------------------------------------

	/**
	 * Constructor: sets EE instance
	 *
	 * @access      public
	 * @return      null
	 */
	public function __construct()
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
	}

	// --------------------------------------------------------------------

	/**
	 * Install the module
	 *
	 * @access      public
	 * @return      bool
	 */
	function install()
	{
		// --------------------------------------
		// Install tables
		// --------------------------------------

		$this->EE->db->query("CREATE TABLE IF NOT EXISTS `exp_low_variables` (
					`variable_id` int(6) unsigned NOT NULL,
					`group_id` int(6) unsigned NOT NULL,
					`variable_label` varchar(100) NOT NULL,
					`variable_notes` text NOT NULL,
					`variable_type` varchar(50) NOT NULL,
					`variable_settings` text NOT NULL,
					`variable_order` int(4) unsigned NOT NULL,
					`early_parsing` char(1) default 'n' NOT NULL,
					`is_hidden` char(1) default 'n' NOT NULL,
					PRIMARY KEY (`variable_id`))");

		$this->_create_groups_table();

		// --------------------------------------
		// Add row to modules table
		// --------------------------------------

		$this->EE->db->insert('exp_modules', array(
			'module_name'    => LOW_VAR_CLASS_NAME,
			'module_version' => LOW_VAR_VERSION,
			'has_cp_backend' => 'y'
		));

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstall the module
	 *
	 * @return	bool
	 */
	function uninstall()
	{
		// get module id
		$this->EE->db->select('module_id');
		$this->EE->db->from('exp_modules');
		$this->EE->db->where('module_name', LOW_VAR_CLASS_NAME);
		$query = $this->EE->db->get();

		// remove references from module_member_groups
		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		// remove references from modules
		$this->EE->db->where('module_name', LOW_VAR_CLASS_NAME);
		$this->EE->db->delete('modules');

		$this->EE->db->query("DROP TABLE IF EXISTS `exp_low_variables`");
		$this->EE->db->query("DROP TABLE IF EXISTS `exp_low_variable_groups`");

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Update the module
	 *
	 * @return	bool
	 */
	function update($current = '')
	{
		// -------------------------------------
		//  Same version? A-okay, daddy-o!
		// -------------------------------------

		if ($current == '' OR version_compare($current, LOW_VAR_VERSION) === 0)
		{
			return FALSE;
		}

		if (version_compare($current, '1.3.2', '<'))
		{
			$this->_update_132();
		}

		// -------------------------------------
		//  Upgrade to 1.3.4
		// -------------------------------------

		if (version_compare($current, '1.3.4', '<'))
		{
			$this->_update_134();
		}

		// Return TRUE to ypdate version number in DB
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Do update to 1.3.2
	 */
	private function _update_132()
	{
		// Add group_id foreign key in table
		$this->EE->db->query("ALTER TABLE `exp_low_variables` ADD `group_id` INT(6) UNSIGNED NOT NULL AFTER `variable_id`");
		$this->_create_groups_table();

		// Pre-populate groups, only if settings are found
		if ($settings = low_get_cache(LOW_VAR_CLASS_NAME, 'settings'))
		{
			// Do not pre-populate groups if group settings was not Y
			if (isset($settings['group']) && $settings['group'] != 'y') return;

			// Initiate groups array
			$groups = array();

			// Get all variables that have a low variables reference
			$sql = "SELECT ee.variable_id AS var_id, ee.variable_name AS var_name, ee.site_id
					FROM exp_global_variables AS ee, exp_low_variables AS low
					WHERE ee.variable_id = low.variable_id";
			$query = $this->EE->db->query($sql);

			// Loop through each variable, see if group applies
			foreach ($query->result_array() AS $row)
			{
				// strip off prefix
				if ($settings['prefix'])
				{
					$row['var_name'] = preg_replace('#^'.preg_quote($settings['prefix']).'_#', '', $row['var_name']);
				}

				// Get faux group name
				$tmp = explode('_', $row['var_name'], 2);
				$group = $tmp[0];
				unset($tmp);

				// Create new group if it does not exist
				if ( ! array_key_exists($group, $groups))
				{
					$this->EE->db->insert('exp_low_variable_groups', array(
						'group_label' => ucfirst($group),
						'site_id' => $row['site_id']
					));
					$groups[$group] = $this->EE->db->insert_id();
				}

				// Update Low Variable
				$this->EE->db->update('exp_low_variables', array(
					'group_id' => $groups[$group]
				), "variable_id = '{$row['var_id']}'");
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Do update to 1.3.2
	 */
	private function _update_134()
	{
		// Add group_id foreign key in table
		$this->EE->db->query("ALTER TABLE `exp_low_variables` ADD `is_hidden` CHAR(1) NOT NULL DEFAULT 'n'");

		// Set new attribute, only if settings are found
		if ($settings = low_get_cache(LOW_VAR_CLASS_NAME, 'settings'))
		{
			// Only update variables if prefix was filled in
			if ($prefix_length = strlen(@$settings['prefix']))
			{
				$sql = "SELECT variable_id FROM `exp_global_variables` WHERE LEFT(variable_name, {$prefix_length}) = '".$this->EE->db->escape_str($settings['prefix'])."'";
				$query = $this->EE->db->query($sql);
				if ($ids = low_flatten_results($query->result_array(), 'variable_id'))
				{
					// Hide wich vars
					$sql_in = $settings['with_prefixed'] == 'show' ? 'NOT IN' : 'IN';

					// Execute query
					$this->EE->db->query("UPDATE `exp_low_variables` SET is_hidden = 'y' WHERE variable_id {$sql_in} (".implode(',', $ids).")");
				}
			}

			// Update settings
			unset($settings['prefix'], $settings['with_prefixed'], $settings['ignore_prefixes']);
			$this->EE->db->query("UPDATE `exp_extensions` SET settings = '".$this->EE->db->escape_str(serialize($settings))."' WHERE class = 'Low_variables_ext'");
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Create groups table
	 */
	private function _create_groups_table()
	{
		$this->EE->db->query("CREATE TABLE IF NOT EXISTS `exp_low_variable_groups` (
					`group_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
					`site_id` int(6) unsigned NOT NULL,
					`group_label` varchar(100) NOT NULL,
					`group_notes` text NOT NULL,
					`group_order` int(4) unsigned NOT NULL,
					PRIMARY KEY (`group_id`))");
	}

}