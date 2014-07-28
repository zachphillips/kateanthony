<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include(PATH_THIRD.'low_variables/config.php');

/**
 * Low Variables Module Class
 *
 * Class to be used in templates
 *
 * @package         low-variables-ee_addon
 * @author          Lodewijk Schutte <hi@gotolow.com>
 * @link            http://gotolow.com/addons/low-variables
 * @copyright       Copyright (c) 2009-2011, Low
 */

class Low_variables {

	/**
	 * Return data
	 *
	 * @var	string
	 */
	var $return_data = '';

	// --------------------------------------------------------------------

	/**
	 * PHP4 Constructor
	 *
	 * @see	__construct()
	 * @since	1.1.4
	 */
	function Low_variables()
	{
		$this->__construct();
	}

	// --------------------------------------------------------------------

	/**
	 * PHP5 constructor
	 *
	 * @return	void
	 * @since	1.1.4
	 */
	function __construct()
	{
		// -------------------------------------
		//  Get global object
		// -------------------------------------

		$this->EE =& get_instance();

		// -------------------------------------
		//  Define the package path
		// -------------------------------------

		$this->EE->load->add_package_path(PATH_THIRD.'low_variables');

		// -------------------------------------
		//  Load helper
		// -------------------------------------

		$this->EE->load->helper('Low_variables');
	}

	// --------------------------------------------------------------------

	/**
	 * Parse global template variables, call type class if necessary
	 *
	 * @return	string
	 */
	function parse()
	{
		// -------------------------------------
		//  Get parameter
		// -------------------------------------

		$var = $this->EE->TMPL->fetch_param('var');

		// -------------------------------------
		//  Site specific var?
		// -------------------------------------

		if (strpos($var, ':') !== FALSE)
		{
			$tmp = explode(':', $var, 2);
			$site_id = $this->_get_site_id($tmp[0]);
			$var = $tmp[1];
		}
		else
		{
			$site_id = $this->EE->config->item('site_id');
		}

		// -------------------------------------
		//  Set returndata
		// -------------------------------------

		$tagdata = $this->EE->TMPL->tagdata;

		// -------------------------------------
		//  Get variable data from cache or DB
		// -------------------------------------

		$data_cache = low_get_cache(LOW_VAR_CLASS_NAME, 'data');

		if (isset($data_cache[$site_id]))
		{
			$this->EE->TMPL->log_item('Low Variables: Getting variable data from Session Cache');

			$data = $data_cache[$site_id];
		}
		else
		{
			$this->EE->TMPL->log_item('Low Variables: Getting variable data from Database');

			// -------------------------------------
			//  Init data array
			// -------------------------------------

			$data = array();

			// -------------------------------------
			//  Query DB
			// -------------------------------------

			$rows = $this->_get_variables(array("ee.site_id = '".$this->EE->db->escape_str($site_id)."'"));

			// -------------------------------------
			//  Get results
			// -------------------------------------

			foreach ($rows AS $row)
			{
				// Prep settings
				$row['variable_settings'] = $this->_get_type_settings($row['variable_type'], $row['variable_settings']);

				// Add prep'd row to data array
				$data[$row['variable_name']] = $row;
			}

			// -------------------------------------
			//  Register to cache
			// -------------------------------------

			$data_cache[$site_id] = $data;
			low_set_cache(LOW_VAR_CLASS_NAME, 'data', $data_cache);
		}

		unset($data_cache);

		// -------------------------------------
		//  Get variable types from cache or DB
		// -------------------------------------

		if (($types = low_get_cache(LOW_VAR_CLASS_NAME, 'types')) === FALSE)
		{
			$settings = low_get_cache(LOW_VAR_CLASS_NAME, 'settings');
			$types = Low_variables_ext::get_types($settings['enabled_types']);
			low_set_cache(LOW_VAR_CLASS_NAME, 'types', $types);
		}

		// -------------------------------------
		//  Replace variables
		// -------------------------------------

		if ( ! empty($var) && isset($data[$var]))
		{
			// -------------------------------------
			//  Single variable defined: try to call its class
			// -------------------------------------

			// Get variable type for easy reference
			$type = $data[$var]['variable_type'];

			// If class doesn't exist, include its file
			if ( ! class_exists($types[$type]['class']) )
			{
				$this->EE->TMPL->log_item('Low Variables: Including type class '.$types[$type]['class']);

				if (isset($types[$type]) && file_exists($types[$type]['path'].$types[$type]['file']))
				{
					include_once $types[$type]['path'].$types[$type]['file'];
				}
				else
				{
					$this->EE->TMPL->log_item("Low Variables: Variable type {$type} is not installed or enabled");
					return;
				}
			}

			// -------------------------------------
			//  Check if correct method exists; if so, call it
			// -------------------------------------

			// Create object
			$OBJ = ($types[$type]['is_fieldtype'] === TRUE) ? new Low_fieldtype_bridge($types[$type]) : new $types[$type]['class'];

			if (method_exists($OBJ, 'display_output'))
			{
				$this->EE->TMPL->log_item('Low Variables: Calling '.get_class($OBJ).'::display_output()');

				// Call function
				$output = $OBJ->display_output($tagdata, $data[$var]);
			}
			else
			{
				$output = FALSE;
			}

			// Assign output to tagdata if valid
			if ($output !== FALSE)
			{
				$tagdata = $output;
			}
			else
			{
				$this->EE->TMPL->log_item('Low Variables: '.$type.'::display_output() not found, default parsing now');

				// Check for multiple values
				if ($this->EE->TMPL->fetch_param('multiple') == 'yes' && (($sep = $OBJ->get_setting('separator', $data[$var]['variable_settings'])) !== FALSE) )
				{
					if (strlen($data[$var]['variable_data']))
					{
						// Convert variable data to array
						$value_array = explode($OBJ->separators[$sep], $data[$var]['variable_data']);

						// Get labels, if present
						if ( ($value_labels = $OBJ->get_setting('options', $data[$var]['variable_settings'])) )
						{
							$value_labels = $OBJ->explode_options($value_labels);
						}

						// Limit results?
						if (($limit = $this->EE->TMPL->fetch_param('limit')) && is_numeric($limit) && $total_results > $limit)
						{
							$value_array = array_slice($value_array, 0, $limit);
						}

						// Initiate variables array for template
						$variables = array();

						// Fill variables array with rows
						foreach ($value_array AS $value)
						{
							$variables[] = array(
								'value'	=> $value,
								$var	=> $value,
								'label'	=> (isset($value_labels[$value]) ? $value_labels[$value] : '')
							);
						}

						// Parse template
						$tagdata = $this->EE->TMPL->parse_variables($tagdata, $variables);
					}
					else
					{
						// No values -- show No Results
						$tagdata = $this->EE->TMPL->no_results();
					}
				}
				else
				{
					// replace tagdata normally
					$tagdata = (empty($tagdata)) ? $data[$var]['variable_data'] : str_replace(LD.$var.RD, $data[$var]['variable_data'], $tagdata); 
				}
			}

			// Clean up
			unset($OBJ);

		}
		else
		{
			// -------------------------------------
			//  No single var was given, so just replace all vars with their values
			// -------------------------------------

			$this->EE->TMPL->log_item('Low Variables: Replacing all variables inside tag pair with their data');

			// Initiate variables array
			$variables = array();

			// Add regular variable
			foreach ($data AS $key => $row)
			{
				$variables[$key] = $row['variable_data'];
			}

			// Parse template
			$tagdata = $this->EE->TMPL->parse_variables($tagdata, array($variables));
		}

		// Assign tagdata to return data
		$this->return_data = $tagdata;

		// -------------------------------------
		//  Return parsed data
		// -------------------------------------

		return $this->return_data;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch and return options from var settings
	 *
	 * Usage: {exp:low_variables:options var="my_variable_name"} {value}, {label} {/exp:low_variables:options}
	 *
	 * @return	string
	 */
	function options()
	{
		// -------------------------------------
		//  Initiate return data
		// -------------------------------------

		$this->return_data = $this->EE->TMPL->tagdata;

		// -------------------------------------
		//  Get parameter
		// -------------------------------------

		if ( ! ($var = $this->EE->TMPL->fetch_param('var')) )
		{
			$this->EE->TMPL->log_item('Low Variables: No var-parameter found, returning raw data');

			return $this->return_data;
		}

		// -------------------------------------
		//  Site specific var?
		// -------------------------------------

		if (strpos($var, ':') !== FALSE)
		{
			$tmp = explode(':', $var, 2);
			$site_id = $this->_get_site_id($tmp[0]);
			$var = $tmp[1];
		}
		else
		{
			$site_id = $this->EE->config->item('site_id');
		}

		// -------------------------------------
		//  Get variable data from cache or DB
		// -------------------------------------

		$data_cache = low_get_cache(LOW_VAR_CLASS_NAME, 'data');

		if (isset($data_cache[$site_id][$var]))
		{
			$this->EE->TMPL->log_item("Low Variables: Getting variable data for {$var} from Session Cache");

			$row = $data_cache[$site_id][$var];
		}
		else
		{
			$this->EE->TMPL->log_item("Low Variables: Getting variable data for {$var} from Database");

			$where = array(
				"ee.variable_name = '".$this->EE->db->escape_str($var)."'",
				"ee.site_id = '".$this->EE->db->escape_str($site_id)."'"
			);

			$rows = $this->_get_variables($where, 1);

			if (count($rows) == 1)
			{
				$row = $rows[0];

				// Prep settings
				$row['variable_settings'] = $this->_get_type_settings($row['variable_type'], $row['variable_settings']);

				// add row to cache
				$data_cache[$site_id][$var] = $row;
				low_set_cache(LOW_VAR_CLASS_NAME, 'data', $data_cache);
			}
			else
			{
				$this->EE->TMPL->log_item("Low Variables: Variable '{$var}' not found, returning raw data");

				return $this->return_data;
			}
		}

		unset($data_cache);

		// -------------------------------------
		//  Load libraries
		// -------------------------------------

		$this->EE->load->library('Low_variables_type');

		// -------------------------------------
		//  Get variable options and parse 'em
		// -------------------------------------

		$options = isset($row['variable_settings']['options']) ? $this->EE->low_variables_type->explode_options($row['variable_settings']['options']) : FALSE;

		if ($options)
		{
			// Check if separator exists for multi-values variable data
			if (isset($row['variable_settings']['separator']) && isset($this->EE->low_variables_type->separators[$row['variable_settings']['separator']]))
			{
				// get separator
				$sep = $this->EE->low_variables_type->separators[$row['variable_settings']['separator']];

				// get current values
				$current = explode($sep, $row['variable_data']);
			}
			else
			{
				// single value, put in single array
				$current = array($row['variable_data']);
			}

			// Initiate variables array
			$variables = array();

			// loop through options, populate variables array
			foreach($options AS $key => $val)
			{
				$variables[] = array(
					'value' => $key,
					'label' => $val,
					'active' => (in_array($key, $current)?'y':''),
					'checked' => (in_array($key, $current)?' checked="checked"':''),
					'selected' => (in_array($key, $current)?' selected="selected"':'')
				);
			}

			// Parse template
			$this->return_data = $this->EE->TMPL->parse_variables($this->return_data, $variables);
		}
		else
		{
			// No values -- show No Results
			$this->EE->TMPL->log_item('Low Variables: No options found, returning no_results');
			$this->return_data = $this->EE->TMPL->no_results();
		}

		// return parsed data
		return $this->return_data;

	}

	// --------------------------------------------------------------------

	/**
	 * Get settings for given type from serialized settings
	 *
	 * @param	string	$type
	 * @param	string	$settings	Serialized array of settings
	 * @return	array
	 * @since	1.1.4
	 */
	function _get_type_settings($type, $settings)
	{
		// Prep settings
		$settings = unserialize($settings);

		// Focus on type's settings
		return (isset($settings[$type])) ? $settings[$type] : array();
	}

	// --------------------------------------------------------------------

	/**
	 * Get settings for given type from serialized settings
	 *
	 * @param	string	$type
	 * @param	string	$settings	Serialized array of settings
	 * @return	array
	 * @since	1.1.4
	 */
	function _get_variables($where = array(), $limit = 0)
	{
		$sql = "SELECT
				ee.variable_id, ee.variable_name, ee.variable_data, ee.site_id,
				low.variable_label, low.variable_type, low.variable_settings
			FROM
				exp_global_variables AS ee
			LEFT JOIN
				exp_low_variables AS low
			ON
				ee.variable_id = low.variable_id
			WHERE 1
		";

		if ($where)
		{
			$sql .= 'AND ('. implode(' AND ', $where) .') ';
		}

		if ($limit)
		{
			$sql .= 'LIMIT '.$limit;
		}

		$query = $this->EE->db->query($sql);

		return $query->result_array();
	}

	// --------------------------------------------------------------------

	/**
	 * Get site id for given site name from cache or DB
	 *
	 * @param	string	$site_name
	 * @return	int
	 * @since	1.3.3
	 */
	function _get_site_id($site_name)
	{
		if (($sites = low_get_cache(LOW_VAR_CLASS_NAME, 'sites')) === FALSE)
		{
			$query = $this->EE->db->query("SELECT site_id, site_name FROM exp_sites");
			$sites = low_flatten_results($query->result_array(), 'site_id', 'site_name');
			low_set_cache(LOW_VAR_CLASS_NAME, 'sites', $sites);
		}

		// Return site id, fallback to current site
		return array_key_exists($site_name, $sites) ? $sites[$site_name] : $this->EE->config->item('site_id');
	}

}