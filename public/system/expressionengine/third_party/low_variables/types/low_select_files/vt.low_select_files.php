<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Low_select_files extends Low_variables_type {

	var $info = array(
		'name'		=> 'Select Files',
		'version'	=> LOW_VAR_VERSION
	);

	var $default_settings = array(
		'multiple'	=> 'n',
		'folders'	=> array(1),
		'separator'	=> 'newline',
		'upload'	=> ''
	);

	var $language_files = array(
		'upload'
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

		$folders = $this->get_setting('folders', $var_settings);

		// -------------------------------------
		//  Get all folders
		// -------------------------------------

		$query = $this->EE->db->query("SELECT id, name FROM exp_upload_prefs WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' ORDER BY name ASC");

		// flatten result set in array
		$all_folders = $this->flatten_results($query->result_array(), 'id', 'name');

		// -------------------------------------
		//  Build options setting
		// -------------------------------------

		$r[] = array(
			$this->setting_label($this->EE->lang->line('file_folders')),
			form_multiselect($this->input_name('folders', TRUE), $all_folders, $folders)
		);

		// -------------------------------------
		//  Build setting: Allow uploads?
		// -------------------------------------

		$upload_folders = array('0' => $this->EE->lang->line('no_uploads')) + $all_folders;
		$upload = $this->get_setting('upload', $var_settings);

		$r[] = array(
			$this->setting_label($this->EE->lang->line('upload_folder'), $this->EE->lang->line('upload_folder_help')),
			form_dropdown($this->input_name('upload'), $upload_folders, $upload)
		);

		// -------------------------------------
		//  Build setting: multiple?
		// -------------------------------------

		$multiple = $this->get_setting('multiple', $var_settings);

		$r[] = array(
			$this->setting_label($this->EE->lang->line('allow_multiple_files')),
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
		// Load Tools model, former cp.filebrowser.php
		$this->EE->load->model('tools_model');

		// get settings
		$multi = $this->get_setting('multiple', $var_settings);
		$multi_interface = $this->get_setting('multi_interface', $var_settings);

		// -------------------------------------
		//  Prep current data
		// -------------------------------------

		$current = explode($this->separators[$this->get_setting('separator', $var_settings)], $var_data);

		// -------------------------------------
		//  Prep options
		// -------------------------------------

		if ( ! ($folders = $this->get_setting('folders', $var_settings)) )
		{
			// no folder found error message
			return $this->EE->lang->line('no_folders_selected');
		}

		// Get prefs from DB
		$query = $this->EE->db->query("SELECT * FROM exp_upload_prefs WHERE id IN (".implode(',', $this->EE->db->escape_str($folders)).")");

		$filelist = array(); 

		foreach ($query->result_array() AS $dir)
		{
			$files = $this->EE->tools_model->get_files($dir['server_path'], $dir['allowed_types'], '', TRUE, TRUE);

			foreach ($files AS $file)
			{
				if ($multi == 'y' && $multi_interface == 'drag-list')
				{
					$filelist[$dir['url'].$file['name']] = $file['name'];
				}
				else
				{
					$filelist[$dir['name']][$dir['url'].$file['name']] = $file['name'];
				}
			}
		}

		// -------------------------------------
		//  Create interface
		// -------------------------------------

		if ($multi == 'y' && $multi_interface == 'drag-list')
		{
			// sort cats again
			asort($filelist);

			$r = $this->drag_lists($var_id, $filelist, $current);
		}
		else
		{
			$r = $this->select_element($var_id, $filelist, $current, ($multi == 'y'));
		}

		// -------------------------------------
		//  Add upload file thing?
		// -------------------------------------

		if ($upload = $this->get_setting('upload', $var_settings))
		{
			$upload_class = ($multi == 'y' && $multi_interface == 'drag-list') ? ' after-drag' : '';
			$upload_new = $this->EE->lang->line('upload_new_file');
			$cancel = $this->EE->lang->line('cancel_upload');

			// Shows toggle-link and file upload field
			$r  .=<<<EOUPLOAD
				<a href="#upload" class="low-upload-toggle{$upload_class}"
					onclick="$('#var{$var_id}-file-upload').slideDown(200);return false;">{$upload_new}</a>
				<div style="clear:both"></div>
				<div id="var{$var_id}-file-upload" class="low-upload-form{$upload_class}" style="display:none;">
					<input type="file" name="newfile[{$var_id}]" />
					<button type="button" onclick="$('#var{$var_id}_file_upload input').replaceWith($('<input type=file name=newfile[{$var_id}] />'));$('#var{$var_id}-file-upload').slideUp(200);">{$cancel}</button>
				</div>
EOUPLOAD;
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
		// Include upload library
		$this->EE->load->library('upload');

		// Get upload setting
		$upload = $this->get_setting('upload', $var_settings);

		// -------------------------------------
		//  Is there a valid upload for this var id?
		// -------------------------------------

		if ($upload && isset($_FILES['newfile']['name'][$var_id]) && !empty($_FILES['newfile']['name'][$var_id]))
		{
			// -------------------------------------
			//  Fetch upload folder from cache or DB
			// -------------------------------------

			$upload_cache = low_get_cache(LOW_VAR_CLASS_NAME, 'uploads');

			if (isset($upload_cache[$upload]))
			{
				$folder = $upload_cache[$upload];
			}
			else
			{
				// Fetch record from DB
				$query = $this->EE->db->query("SELECT * FROM exp_upload_prefs WHERE id = '".$this->EE->db->escape_str($upload)."'");

				if ($query->num_rows())
				{
					// get folder and register to session cache
					$folder = $upload_cache[$upload] = $query->row_array();
					low_set_cache(LOW_VAR_CLASS_NAME, 'uploads', $upload_cache);
				}
				else
				{
					// -------------------------------------
					//  Bail out if folder wasn't found
					// -------------------------------------

					$this->error_msg = 'folder_not_found';
					return FALSE;
				}
			}

			unset($upload_cache);

			// -------------------------------------
			//  Reset and fill $_FILES['userfile']
			// -------------------------------------

			$_FILES['userfile'] = array();

			// Get uploaded files details from $_FILES
			foreach ($_FILES['newfile'] AS $key => $val)
			{
				if (isset($val[$var_id]))
				{
					$_FILES['userfile'][$key] = $val[$var_id];
				}
			}

			// -------------------------------------
			//  Set parameters according to folder prefs
			// -------------------------------------

			$config = array(
				'upload_path'	=> $folder['server_path'],
				'allowed_types'	=> (($folder['allowed_types'] == 'img') ? 'gif|jpg|jpeg|png|jpe' : '*'),
				'max_size'		=> $folder['max_size'],
				'max_width'		=> $folder['max_width'],
				'max_height'	=> $folder['max_height']
			);

			$this->EE->upload->initialize($config);

			// -------------------------------------
			//  Upload the file
			// -------------------------------------

			if ( ! $this->EE->upload->do_upload() )
			{
				// Set error msg and bail if unsuccessful
				$this->error_msg = $this->EE->upload->error_msg;
				return FALSE;
			}

			// get the new file's full path; the data we're going to save
			$newfile = $folder['url'].$this->EE->upload->file_name;

			if (is_array($var_data))
			{
				// add it to the selected files
				$var_data[] = $newfile;
			}
			else
			{
				// or replace single value
				$var_data = $newfile;
			}

		} // END if upload?

		// Return new value
		return is_array($var_data) ? implode($this->separators[$this->get_setting('separator', $var_settings)], $var_data) : $var_data;

	}

}