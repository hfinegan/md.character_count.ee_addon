<?php
/* ===========================================================================
ext.md_character_count.php ---------------------------
Add a customizable character count to CP publish form fields in the 
ExpressionEngine control panel. (Textareas, text inputs and MD Markitup fields)
            
INFO ---------------------------
Developed by: Ryan Masuga, masugadesign.com
Created:		Oct 15 2008
Last Mod:		Nov 14 2008

http://expressionengine.com/docs/development/extensions.html
=============================================================================== */
if ( ! defined('EXT')) { exit('Invalid file request'); }

if ( ! defined('MD_CC_version')){
	define("MD_CC_version",         "1.0.4");
	define("MD_CC_docs_url",        "http://www.masugadesign.com/the-lab/scripts/md-character-count/");
	define("MD_CC_addon_id",        "MD Character Count");
	define("MD_CC_extension_class", "Md_character_count");
	define("MD_CC_cache_name",      "mdesign_cache");
}


class Md_character_count
{
	var $settings		= array();
	var $name           = 'MD Character Count';
	// var $type        = 'md_notes'; // only used for custom field extensions
	var $version        = MD_CC_version;
	var $description    = 'Add a customizable character count to CP publish form fields (Textareas, text inputs and MD Markitup fields)';
	var $settings_exist = 'y';
	var $docs_url       = MD_CC_docs_url;


	/**
	* PHP4 Constructor
	*
	* @see __construct()
	*/
	function Md_character_count($settings='')
	{
		$this->__construct($settings);
	}

	/**
	* PHP 5 Constructor
	*
	* @param	array|string $settings Extension settings associative array or an empty string
	* @since	Version 1.0.3
	*/
	function __construct($settings='')
	{
		global $IN, $SESS;
		if(isset($SESS->cache['mdesign']) === FALSE){ $SESS->cache['mdesign'] = array();}
		$this->settings = $this->_get_settings();
		$this->debug = $IN->GBL('debug');
	}


	/**
	* Get the site specific settings from the extensions table
	*
	* @param	$force_refresh	bool	Get the settings from the DB even if they are in the $SESS global
	* @param	$return_all			bool	Return the full array of settings for the installation rather than just this site
	* @return array 	If settings are found otherwise false. Site settings are returned by default. 
	*					Installation settings can be returned is $return_all is set to true
	* @since 	Version 2.0.0
	*/	
	function _get_settings($force_refresh = FALSE, $return_all = FALSE)
	{
		global $SESS, $DB, $REGX, $LANG, $PREFS;

		// assume there are no settings
		$settings = FALSE;

		// Get the settings for the extension
		if(isset($SESS->cache['mdesign'][MD_CC_addon_id]['settings']) === FALSE || $force_refresh === TRUE)
		{
			// check the db for extension settings
			$query = $DB->query("SELECT settings FROM exp_extensions WHERE enabled = 'y' AND class = '" . MD_CC_extension_class . "' LIMIT 1");

			// if there is a row and the row has settings
			if ($query->num_rows > 0 && $query->row['settings'] != '')
			{
				// save them to the cache
				$SESS->cache['mdesign'][MD_CC_addon_id]['settings'] = $REGX->array_stripslashes(unserialize($query->row['settings']));
			}
		}

		// check to see if the session has been set
		// if it has return the session
		// if not return false
		if(empty($SESS->cache['mdesign'][MD_CC_addon_id]['settings']) !== TRUE)
		{
			$settings = ($return_all === TRUE) ?  $SESS->cache['mdesign'][MD_CC_addon_id]['settings'] : $SESS->cache['mdesign'][MD_CC_addon_id]['settings'][$PREFS->ini('site_id')];
		}
		return $settings;
	}	


	/**
	* Configuration for the extension settings page
	* 
	* @param $current array  The current settings for this extension. 
	*        We don't worry about those because we get the site specific settings
	* @since Version 1.0.3
	**/
	function settings_form($current)
	{
		global $DB, $DSP, $LANG, $IN, $PREFS, $SESS;

		// create a local variable for the site settings
		$settings = $this->_get_settings();

		$DSP->crumbline = TRUE;

		$DSP->title  = $LANG->line('extension_settings');
		$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
		$DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager', $LANG->line('extensions_manager')));

		$DSP->crumb .= $DSP->crumb_item($LANG->line('extension_title') . " {$this->version}");

		$DSP->right_crumb($LANG->line('disable_extension'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=toggle_extension_confirm'.AMP.'which=disable'.AMP.'name='.$IN->GBL('name'));

		$DSP->body = '';
		$DSP->body .= $DSP->heading($LANG->line('extension_title') . " <small>{$this->version}</small>");
		$DSP->body .= $DSP->form_open(
								array(
									'action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=save_extension_settings'
								),
								array('name' => strtolower(MD_CC_extension_class))
		);
	
	// EXTENSION ACCESS
	$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'border' => '0', 'style' => 'margin-top:18px; width:100%'));

	$DSP->body .= $DSP->tr()
		. $DSP->td('tableHeading', '', '2')
		. $LANG->line("access_rights")
		. $DSP->td_c()
		. $DSP->tr_c();

	$DSP->body .= $DSP->tr()
		. $DSP->td('tableCellOne', '30%')
		. $DSP->qdiv('defaultBold', $LANG->line('enable_extension_for_this_site'))
		. $DSP->td_c();

	$DSP->body .= $DSP->td('tableCellOne')
		. "<select name='enable'>"
		. $DSP->input_select_option('y', "Yes", (($settings['enable'] == 'y') ? 'y' : '' ))
		. $DSP->input_select_option('n', "No", (($settings['enable'] == 'n') ? 'y' : '' ))
		. $DSP->input_select_footer()
		. $DSP->td_c()
		. $DSP->tr_c()
		. $DSP->table_c();
		// Close EXTENSION ACCESS table

		// SCRIPTS
		$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'border' => '0', 'style' => 'margin-top:18px; width:100%;'));

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableHeading', '', '2')
			. $LANG->line("scripts_title")
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .= $DSP->tr()
			. $DSP->td('', '', '2')
			. "<div class='box' style='border-width:0 0 1px 0; margin:0; padding:10px 5px'><p>" . $LANG->line('scripts_info'). "</p></div>"
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableCellOne', '40%')
			. $DSP->qdiv('defaultBold', $LANG->line('jquery_core_path_label'))
			. $DSP->td_c();

		$DSP->body .= $DSP->td('tableCellOne')
			. $DSP->input_text('jquery_core_path', $settings['jquery_core_path'])
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableCellTwo', '40%')
			. $DSP->qdiv('default', $LANG->line('charcounter_plugin_path_label'))
			. $DSP->td_c();

		$DSP->body .= $DSP->td('tableCellTwo')
			. $DSP->input_text('charcounter_plugin_path', $settings['charcounter_plugin_path'])
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .= $DSP->table_c();
		// Close SCRIPTS table

		// FIELDS
		$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'border' => '0', 'style' => 'margin-top:18px; width:100%;'));

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableHeading', '', '4')
			. $LANG->line("fields_title")
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .= $DSP->tr()
			. $DSP->td('', '', '4')
			. "<div class='box' style='border-width:0 0 1px 0; margin:0; padding:10px 5px'><p>" . $LANG->line('fields_info'). "</p></div>"
			. $DSP->td_c()
			. $DSP->tr_c();

		$query = $DB->query("SELECT w.field_id, g.group_name, w.field_label, w.site_id FROM exp_weblog_fields w, exp_field_groups g WHERE w.site_id = ".
			  $PREFS->ini('site_id')." AND w.group_id = g.group_id AND field_type IN ( 'textarea', 'text', 'markitup' ) ORDER BY g.group_id, w.field_order");    

				$i = 0; $group_name = "none";

        foreach ($query->result as $row) {
			
			// added the '@' signs to avoid undefined indexes when first installing or making new fields
			$count_max		= @$settings['field_defaults'][$row['field_id']]['count_max'];
			$count_type		= @$settings['field_defaults'][$row['field_id']]['count_type'];
			$count_format	= @$settings['field_defaults'][$row['field_id']]['count_format'];	       
	        
	        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
	        
	        if ( $group_name != $row['group_name'] ) {
		        $DSP->body .=   $DSP->tr();
		        $DSP->body .=   $DSP->td('tableHeadingAlt', '', '4');
		        $DSP->body .=   $DSP->qdiv('defaultBold', "Weblog: ".$row['group_name'] );
		        $DSP->body .=   $DSP->td_c();
		        $DSP->body .=   $DSP->tr_c();
		
		        $DSP->body .=   $DSP->tr();
		        $DSP->body .=   $DSP->td($style, '25%');
						$DSP->body .= 	$DSP->qdiv('defaultBold', '&nbsp;');
		        $DSP->body .=   $DSP->td_c();
		
	        	$DSP->body .=   $DSP->td($style);
						$DSP->body .= 	$DSP->qdiv('defaultBold', $LANG->line('coltitle_count'));
	        	$DSP->body .=   $DSP->td_c();		

	        	$DSP->body .=   $DSP->td($style);
						$DSP->body .= 	$DSP->qdiv('defaultBold', $LANG->line('coltitle_count_type'));
	        	$DSP->body .=   $DSP->td_c();		
				
	        	$DSP->body .=   $DSP->td($style);
						$DSP->body .= 	$DSP->qdiv('defaultBold', $LANG->line('coltitle_count_format'));
	        	$DSP->body .=   $DSP->td_c();		
		
		        $DSP->body .=   $DSP->tr_c();
		
		        $group_name = $row['group_name'];
		      }

	        $DSP->body .=   $DSP->tr();

	        $DSP->body .=   $DSP->td($style, '25%');
	        $DSP->body .=   $DSP->qdiv('defaultBold', $row['field_label']);
	        $DSP->body .=   $DSP->td_c();

	        $DSP->body .=   $DSP->td($style);
					$DSP->body .= 	$DSP->input_text("field_defaults[".$row['field_id']."][count_max]", $count_max, '35', '40', 'input', '50px');
	        $DSP->body .=   $DSP->td_c();
		
		    // count_type [soft,hard]
	        $DSP->body .= $DSP->td($style);
					$DSP->body .= "<select name='field_defaults[".$row['field_id']."][count_type]'>"
		    . $DSP->input_select_option('true', "Soft", (($count_type == "true") ? 'y' : '' ))
		    . $DSP->input_select_option('false', "Hard", (($count_type == "false") ? 'y' : '' ))
		    . $DSP->input_select_footer()
		    . $DSP->td_c();

       
          // count_format
	        $DSP->body .=   $DSP->td($style);
					$DSP->body .= 	$DSP->input_text('field_defaults['.$row['field_id'].'][count_format]', $count_format, '35', '40', 'input', '250px');
	        $DSP->body .=   $DSP->td_c();	     
          // END count_format setting
		  
	        $DSP->body .=   $DSP->tr_c();              
        }

		$DSP->body .= $DSP->table_c();
		// Close FIELDS table

    //CSS
		$DSP->body .=   $DSP->table_open(array('class' => 'tableBorder', 'border' => '0', 'style' => 'margin-top:18px; width:100%'));

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableHeading', '', '2')
			. $LANG->line("css_title")
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableCellOne', '40%')
			. $DSP->qdiv('default', $LANG->line('css_info'))
			. $DSP->td_c();

		$DSP->body .= $DSP->td('tableCellOne')
			. $DSP->input_textarea('css', $settings['css'], 15, 'textarea', '99%')
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .=   $DSP->table_c();
		// Close CSS table

		// UPDATES
		$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'border' => '0', 'style' => 'margin-top:18px; width:100%'));

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableHeading', '', '2')
			. $LANG->line("check_for_updates_title")
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .= $DSP->tr()
			. $DSP->td('', '', '2')
			. "<div class='box' style='border-width:0 0 1px 0; margin:0; padding:10px 5px'><p>" . $LANG->line('check_for_updates_info') . "</p></div>"
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableCellOne', '40%')
			. $DSP->qdiv('defaultBold', $LANG->line("check_for_updates_label"))
			. $DSP->td_c();

		$DSP->body .= $DSP->td('tableCellOne')
			. "<select name='check_for_updates'>"
			. $DSP->input_select_option('y', "Yes", (($settings['check_for_updates'] == 'y') ? 'y' : '' ))
			. $DSP->input_select_option('n', "No", (($settings['check_for_updates'] == 'n') ? 'y' : '' ))
			. $DSP->input_select_footer()
			. $DSP->td_c()
			. $DSP->tr_c();
			
		$DSP->body .= $DSP->table_c();
		// Close UPDATES table

		$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit("Submit"))
			. $DSP->form_c();
	}


	/**
	* Takes the control panel html, adding the necessary JS to the head
	*
	* @param	string $out The control panel html
	* @return	string The modified control panel html
	* @since 	Version 1.0.0
	*/
	function show_full_control_panel_end($out)
	{
		global $DB, $EXT, $LANG, $IN, $REGX, $PREFS, $SESS;

		$SESS->cache['mdesign'][MD_CC_addon_id]['require_scripts'] = TRUE;

		// Check if we're not the only one using this hook
		if($EXT->last_call !== FALSE)
		{
			$out = $EXT->last_call;
		}
		
		// if we are on a publish or edit page
		if (isset($SESS->cache['mdesign'][MD_CC_addon_id]['require_scripts']) === TRUE && ($IN->GBL('C', 'GET') == 'publish' || $IN->GBL('C', 'GET') == 'edit'))
		  {
			if($this->settings['enable'] == 'y')
			  {
				$ccstuff = '';
				$s = '';

				if (isset($EXT->version_numbers['Cp_jquery']) === FALSE && empty($SESS->cache['scripts']['jquery']) === TRUE)
				  {
					$ccstuff .= '<!-- ccc --><script type="text/javascript" src="'.trim($this->settings['jquery_core_path']).'"></script>';
					$SESS->cache['scripts']['jquery']['1.2.6'] = TRUE;
				  }

				$ccstuff .= '<script type="text/javascript" src="'.trim($this->settings['charcounter_plugin_path']).'"></script>'."\n";
 
				$count_settings = $this->settings['field_defaults'];
				// // Not really needed here, but it's a redundant act of good measure to keep out the bad characters.
				$good = array("", "", "","", ""); 
				// It's really only necessary in the $_POST of the settings, but using it here just for safety sake.
				// These 2 items help keep bad information from being entered into the format string.
				$bad  = array("\'", '\"', '\\', ";", ":"); 

				foreach ( $count_settings as $key => $val )
				  {
					$count_max		= ereg_replace("[^0-9]", "", $val['count_max']);
					$count_type		= $val['count_type'];
					$count_format	= str_replace($bad, $good, $val['count_format']);
	
	  // only output those that have something in them
	  if ($count_max !== "") {
			// output the jquery for the field(s)
			$s .= '$("#field_id_'.$key.'").charCounter('.$count_max.','."\n";

					 	// if user has entered something in count_format, set the format to the user's input
					 	if ($count_format !== "")
					 	{
					 		$s .= "\t".'{'."\n\t".'format: "'. $count_format .'",';
					 	}
					 	// otherwise, output the default format
					 	else
					 	{
					 		$s .= "\t".'{'."\n\t".'format: "%1/'. $count_max .' characters remaining",';
					 	}
			
			 	// add the softcount
			 	$s .= "\n\t".'softcount: ' .$count_type."\n\t".'}'."\n".');'."\n\n";
			
			 }

		}
	
				if ( $s != '' ) 
				  {
					$ccstuff .= '<script type="text/javascript">'."\n".'$(document).ready( function(){' . "\n\n" . $s . "\n" . '});'."\n".'</script>'."\n\n";	
				  }
				  
				$ccstuff .= '<style type="text/css">'."\n".$this->settings['css']."\n".'</style>';
				
				// add the script string before the closing head tag
				$out = str_replace("</head>", $ccstuff . "</head>", $out);
			  }   
		  }
		return $out;
	 }

	function save_settings()
	{
		global $DB, $PREFS;

		// load the settings from cache or DB
		// force a refresh and return the full site settings
		$this->settings = $this->_get_settings(TRUE, TRUE);

		// unset the name
		unset($_POST['name']);
		  
		$good = array("", "", "","", "");			// This is where all this really comes in handy...
		$bad  = array("\'", '\"', '\\', ";", ":");	// These 2 items help keep bad information from being entered into the format string.

		foreach ($_POST['field_defaults'] as $key => $value)
		  {
		  unset($_POST['field_defaults_' . $key]);
		  $_POST['field_defaults'][$key]['count_max']		= ereg_replace("[^0-9]", "", $_POST['field_defaults'][$key]['count_max']);
			$_POST['field_defaults'][$key]['count_format']	= str_replace($bad, $good, $_POST['field_defaults'][$key]['count_format']);
		  }

		// add the posted values to the settings
		$settings[$PREFS->ini('site_id')] = $_POST;

		// update the settings
		$query = $DB->query($sql = 
			"UPDATE exp_extensions 
			 SET settings = '" . addslashes(serialize($settings)) . "' WHERE class = '" . MD_CC_extension_class . "'"
			);
	}


	/**
	* Returns the default settings for this extension
	* This is used when the extension is activated or when a new site is installed
	* It returns the default settings for the CURRENT site only.
	*/
	function _build_default_settings()
	{
		global $DB, $PREFS;
		
		$default_settings = array(
        'enable'            => 'y',
        'field_defaults'    => array(),
				'jquery_core_path'		      => "http://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js",
				'charcounter_plugin_path'		=> $PREFS->core_ini['site_url']."js/jquery.charcounter.js",
				'check_for_updates'	=> 'y',
				'css' => '
.charcounter {
  font-size: 11px;
	float: left;
	clear: left;
	padding: 6px 0 0 2px;
  }

.charcounter_err {
  color: #933;
  font-weight: bold;
  }'

        );

		// get all the sites
		$query = $DB->query("SELECT * FROM exp_weblogs WHERE site_id = '".$PREFS->core_ini['site_id']."'");

		// if there are weblogs
		if ($query->num_rows > 0)
		{
			// for each of the sweblogs
			foreach($query->result as $row)
			{
				// duplicate the default settings for this site
				// that way nothing will break unexpectedly
				$default_settings['field_defaults'][$row['site_id']][$row['weblog_id']] = array(
					'count_max'		=> '',
					'count_type'	=> 'false', //a string
					'count_format' 	=> ''
				);
			}
		}

		return $default_settings;

	}


	/**
	* Activates the extension
	*
	* @return	bool Always TRUE
	* @since	Version 1.0.3
	*/
	function activate_extension()
	{
		global $DB, $PREFS;
		
    $default_settings = $this->_build_default_settings();

		// get the list of installed sites
		$query = $DB->query("SELECT * FROM exp_sites");

		// if there are sites - we know there will be at least one but do it anyway
		if ($query->num_rows > 0)
		{
			// for each of the sites
			foreach($query->result as $row)
			{
				// build a multi dimensional array for the settings
				$settings[$row['site_id']] = $default_settings;
			}
		}	

		// get all the sites
		$query = $DB->query("SELECT * FROM exp_weblogs");

		// if there are weblogs
		if ($query->num_rows > 0)
		{
			// for each of the sweblogs
			foreach($query->result as $row)
			{
				// duplicate the default settings for this site
				// that way nothing will break unexpectedly
				$default_settings['field_defaults'][$row['site_id']][$row['weblog_id']] = array(
					'count_max'   => '',
					'count_type' 	=> 'false',
					'count_format'   => ''
				);
			}
		}
		
		$hooks = array(
		  'show_full_control_panel_end'         => 'show_full_control_panel_end',
		  // Two extra hooks that work with LG Addon Updater
		  'lg_addon_update_register_source'     => 'lg_addon_update_register_source',
			'lg_addon_update_register_addon'      => 'lg_addon_update_register_addon'
		);
		
		foreach ($hooks as $hook => $method)
		{
			$sql[] = $DB->insert_string( 'exp_extensions', 
				array('extension_id' 	=> '',
					'class'			=> get_class($this),
					'method'		=> $method,
					'hook'			=> $hook,
					'settings'	=> addslashes(serialize($settings)),
					'priority'	=> 10,
					'version'		=> $this->version,
					'enabled'		=> "y"
				)
			);
		}

		// run all sql queries
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		return TRUE;
	}	
	
	/**
	* Disables the extension the extension and deletes settings from DB
	* 
	* @since	Version 1.0.3
	*/
	function disable_extension()
	{
		global $DB;
		$DB->query("DELETE FROM exp_extensions WHERE class = '" . get_class($this) . "'");
	}
	
	/**
	* Updates the extension
	*
	* @param	string $current If installed the current version of the extension otherwise an empty string
	* @return	bool FALSE if the extension is not installed or is the current version
	* @since	Version 1.0.3
	*/ 
	function update_extension($current='')
	{
		global $DB;
		
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$DB->query("UPDATE exp_extensions
		            SET version = '".$DB->escape_str($this->version)."'
		            WHERE class = '".get_class($this)."'");
	}

	/**
	* Register a new Addon Source
	*
	* @param	array $sources The existing sources
	* @return	array The new source list
	* @since 	Version 1.0.3
	*/
	function lg_addon_update_register_source($sources)
	{
		global $EXT;
		// Check if we're not the only one using this hook
		if($EXT->last_call !== FALSE)
			$sources = $EXT->last_call;
		/*
		<versions>
			<addon id='LG Addon Updater' version='2.0.0' last_updated="1218852797" docs_url="http://leevigraham.com/" />
		</versions>
		*/
		if($this->settings['check_for_updates'] == 'y')
		{
			$sources[] = 'http://masugadesign.com/versions/';
		}
		return $sources;
	}

	/**
	* Register a new Addon
	*
	* @param	array $addons The existing sources
	* @return	array The new addon list
	* @since 	Version 1.0.3
	*/
	function lg_addon_update_register_addon($addons)
	{
		global $EXT;
		// Check if we're not the only one using this hook
		if($EXT->last_call !== FALSE)
			$addons = $EXT->last_call;
		if($this->settings['check_for_updates'] == 'y')
		{
			$addons[MD_CC_addon_id] = $this->version;
		}
		return $addons;
	}  

/* END class */
}
/* End of file ext.md_character_count.php */
/* Location: ./system/extensions/ext.md_character_count.php */ 