<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Thomas Allmer (thomas.allmer@webteam.at)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * A RTE using TinyMCE
 *
 * @author Thomas Allmer <thomas.allmer@webteam.at>
 *
 */
 
require_once(PATH_t3lib.'class.t3lib_rteapi.php');
require_once(PATH_t3lib.'class.t3lib_tsparser.php');
require_once(PATH_t3lib.'class.t3lib_cs.php');
require_once(PATH_t3lib.'class.t3lib_page.php');

class tx_tinymce_rte_base extends t3lib_rteapi {

	var $forceUTF8 = true;

	/**
	 * Draws the RTE
	 *
	 * @param	object	Reference to parent object, which is an instance of the TCEforms.
	 * @param	string		The table name
	 * @param	string		The field name
	 * @param	array		The current row from which field is being rendered
	 * @param	array		Array of standard content for rendering form fields from TCEforms. See TCEforms for details on this. Includes for instance the value and the form field name, java script actions and more.
	 * @param	array		"special" configuration - what is found at position 4 in the types configuration of a field from record, parsed into an array.
	 * @param	array		Configuration for RTEs; A mix between TSconfig and otherwise. Contains configuration for display, which buttons are enabled, additional transformation information etc.
	 * @param	string		Record "type" field value.
	 * @param	string		Relative path for images/links in RTE; this is used when the RTE edits content from static files where the path of such media has to be transformed forth and back!
	 * @param	integer	PID value of record (true parent page id)
	 * @return	string		HTML code for RTE!
	 */
	function drawRTE($parentObject, $table, $field, $row, $PA, $specConf, $thisConfig, $RTEtypeVal, $RTErelPath, $thePidValue) {
		$code = '';
		$parentObject->RTEcounter = rand();
		
		// get the language code of the Content Element
		$row['ISOcode'] = $parentObject->getAvailableLanguages();
		$row['ISOcode'] = strtolower( $row['ISOcode'][$row['sys_language_uid']]['ISOcode'] );
		
		$this->currentPage = $row['pid'];
		if ($this->currentPage < 0) {
			$pidRow = t3lib_BEfunc::getRecord($table, abs($this->currentPage),'pid');
			$this->currentPage = $pidRow['pid'];
		}
		
		$config = $this->init($thisConfig, $parentObject->RTEcounter, $PA);
		
		$configOrder = $this->getConfigOrder($table, $row, $PA);
		$config = $this->mergeLocationConfig($config, $configOrder, $PA);
		
		if ( $row['ISOcode'] == 'def' )
			$row['ISOcode'] = $config['defaultLanguageFE'];
		$row['ISOcode'] = ($row['ISOcode'] == 'en') ? 'default' : $row['ISOcode'];
		
		$config = $this->fixTinyMCETemplates($config, $row);
		$code .= $this->getFileDialogJS( $config, $this->getPath('EXT:tinymce_rte/./'), $parentObject, $table, $field, $row);

		//add callback javascript file
		if ( $config['callbackJavascriptFile'] != '' ) {
			$config['callbackJavascriptFile'] = $this->getPath($config['callbackJavascriptFile']);
			$code .= '<script type="text/javascript" src="' . $config['callbackJavascriptFile'] . '"></script>';
		}
		
		//loads the current Value and create the textarea
		$value = $this->transformContent('rte', $PA['itemFormElValue'], $table, $field, $row, $specConf, $thisConfig, $RTErelPath, $thePidValue);
		$code .= $this->getTextarea($parentObject, $PA, $value, $config);
		
		return $code;
	}
	
	function getTextarea($parentObject, $PA, $value, $config) {
		$code = $this->triggerField($PA['itemFormElName']);
		$code .= '<textarea id="RTEarea'.$parentObject->RTEcounter.'" class="tinymce_rte" name="'.htmlspecialchars($PA['itemFormElName']).'" rows="30" cols="100">'.t3lib_div::formatForTextarea($value).'</textarea>';
		
		if ( !$config['useFEediting'] ) {
			$config['init.']['window'] = 'self';
			$config['init.']['element_id'] = 'RTEarea' . $parentObject->RTEcounter;
			$config['init.']['reAddCss'] = 'true';
			$code .= '
				<script type="text/javascript">
					top.tinyMCE.execCommand("mceAddFrameControl", false, ' . $this->parseConfig($config['init.']) . ');
				</script>
			';
		} else {
			$code .= $this->getCoreScript( $config );
			$code .= $this->getInitScript( $config['init.'] );
		}
		return $code;
	}
	
	/**
	 * Returns true if the RTE is available. Here you check if the browser requirements are met.
	 * If there are reasons why the RTE cannot be displayed you simply enter them as text in ->errorLog
	 *
	 * @return	boolean		TRUE if this RTE object offers an RTE in the current browser environment
	 */
	function isAvailable()	{
		return true;
	}
	
	/**
	 * initial all the values for the RTE
	 * 
	 * @param	array		config to use
	 * @param	array		rteId (a counter)
	 * @return	array		initiated config
	 */	
	function init($config, $rteId = 1, $PA=array()) {
		global $LANG, $BE_USER;
		
		if ( TYPO3_branch == 4.1 && !t3lib_extMgm::isLoaded('tinymce_rte_patch41') )
			die('for TYPO3 4.1 you need to install the extension tinymce_rte_patch41');

		// get the language (also checks if lib is called from FE or BE, which might of use later.)
		if (TYPO3_MODE == 'FE') {
			$LANG = t3lib_div::makeInstance('language');
			$LANG->init($GLOBALS['TSFE']->tmpl->setup['config.']['language']);
			$LANG->includeLLFile('typo3conf/ext/tinymce_rte/mod1/locallang_browse_links.xml');
		} else {
			$LANG = $GLOBALS['LANG'];
		}
		
		$this->language = $LANG->lang;

		// language conversion from TLD to iso631
		if ( is_array($LANG->csConvObj->isoArray) && array_key_exists($this->language, $LANG->csConvObj->isoArray) )
			$this->language = $LANG->csConvObj->isoArray[$this->language];

		// check if TinyMCE language file exists
		$langpath = (t3lib_extmgm::isLoaded($config['languagesExtension'])) ? t3lib_extMgm::siteRelPath($config['languagesExtension']) : t3lib_extMgm::siteRelPath('tinymce_rte') . 'res/';
		if(!is_file(PATH_site . $langpath . 'tiny_mce/langs/' . $this->language . '.js')) {
		  $this->language = 'en';
		}

		$config['init.']['language'] = $this->language;
		$config['init.']['document_base_url'] = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
		$config['init.']['elements'] = 'RTEarea' . $rteId;
			
		// resolve EXT pathes for these values
		$config['init.']['spellchecker_rpc_url'] = $this->getPath($config['init.']['spellchecker_rpc_url']) . '?pageId=' . $this->currentPage;
		$config['tiny_mcePath'] = $this->getPath($config['tiny_mcePath']);
		$config['tiny_mceGzipPath'] = $this->getPath($config['tiny_mceGzipPath']);
		
		// defines if you want to force UTF8 on every config entry
		$this->forceUTF8 = $config['forceUTF8'] ? true : false;
		
		if( is_array($BE_USER->userTS['RTE.']) ) {
			$config = $this->array_merge_recursive_override($config, $BE_USER->userTS['RTE.']['default.']);
		}
		
		return $config;
	}
	
	/**
	 * Merges the Configs for Locations into the main config
	 *
	 * @param	array  The config that should be modified
	 * @param array  
	 * @return array The altered config
	 */	
	function mergeLocationConfig($config, $configOrder = array('default'), $PA = array() ) {
		if (TYPO3_MODE == 'BE') {
			global $BE_USER;
		}
		
		if (!is_array($BE_USER->userTS['RTE.']))
			$BE_USER->userTS['RTE.'] = array();
	
		$pageTs = t3lib_BEfunc::getPagesTSconfig($this->currentPage);
		
		// Merge configs
		foreach ($configOrder as $order) {
			$order = explode('.', $order);
			// Only use this when order[0] matches tablename contained in $PA['itemFormElName']
			// otherwise all configurations delivered by the hook would be merged  
			if ( preg_match('/'.$order[0].'/', $PA['itemFormElName']) || ($order[0] == 'default' && $order[1] == 'lang') ) {
				// Added even cases , since we do not know what ext developers return using the hook
				// Do we need higher cases, since we do not know what will come from the hook?
				switch (count($order)) {
					case 7:
						$tsc = $pageTs['RTE.'][$order[0].'.'][$order[1].'.'][$order[2].'.'][$order[3].'.'][$order[4].'.'][$order[5].'.'][$order[6].'.'];
						$utsc = $BE_USER->userTS['RTE.'][$order[0].'.'][$order[1].'.'][$order[2].'.'][$order[3].'.'][$order[4].'.'][$order[5].'.'][$order[6].'.'];
					break;
					case 6:
						$tsc = $pageTs['RTE.'][$order[0].'.'][$order[1].'.'][$order[2].'.'][$order[3].'.'][$order[4].'.'][$order[5].'.'];
						$utsc = $BE_USER->userTS['RTE.'][$order[0].'.'][$order[1].'.'][$order[2].'.'][$order[3].'.'][$order[4].'.'][$order[5].'.'];
					break;
					case 5:
						$tsc = $pageTs['RTE.'][$order[0].'.'][$order[1].'.'][$order[2].'.'][$order[3].'.'][$order[4].'.'];
						$utsc = $BE_USER->userTS['RTE.'][$order[0].'.'][$order[1].'.'][$order[2].'.'][$order[3].'.'][$order[4].'.'];
					break;
					case 4:
						$tsc = $pageTs['RTE.'][$order[0].'.'][$order[1].'.'][$order[2].'.'][$order[3].'.'];
						$utsc = $BE_USER->userTS['RTE.'][$order[0].'.'][$order[1].'.'][$order[2].'.'][$order[3].'.'];
					break;
					case 3:
						$tsc = $pageTs['RTE.'][$order[0].'.'][$order[1].'.'][$order[2].'.'];
						$utsc = $BE_USER->userTS['RTE.'][$order[0].'.'][$order[1].'.'][$order[2].'.'];
					break;
					case 2:
						$tsc = $pageTs['RTE.'][$order[0].'.'][$order[1].'.'];
						$utsc = $BE_USER->userTS['RTE.'][$order[0].'.'][$order[1].'.'];
					break;
					default:
						$tsc = $pageTs['RTE.'][$order[0].'.'];
						$utsc = $BE_USER->userTS['RTE.'][$order[0].'.'];
					break;
				}
			}
			if ( isset($tsc) ) {
				$config = $this->array_merge_recursive_override($config, $tsc);
			}
			if ( isset($utsc) ) {
				$config = $this->array_merge_recursive_override($config, $utsc);
			}
		}
		
		unset( $config['field.'] );
		unset( $config['lang.'] );
		unset( $config['ctype.'] );
		
		return $config;
	}
	
	/**
	 * Returns the BE location of the RTE
	 *
	 * @param	string		The table name
	 * @param	array		The current row from which field is being rendered
	 * @return	array		Config order!
	 */
	function getConfigOrder($table, $row, $PA = array()) {
		// Initial location is set to: Default config, then the table name
		$where = array(
			'default.lang.' . $row['ISOcode'],
			$table,
			$table . '.lang.' . $row['ISOcode']
		);
		
		// Custom location based on table name
		switch ($table) {
			case 'tt_content':

				// location based on tablename + tt_content Ctype
				$where[] = $table . '.ctype.' . $row['CType'];
				$where[] = $table . '.ctype.' . $row['CType'] . '.lang.' . $row['ISOcode'];
			
				// location based on tablename + tt_content column position is added
				$where[] = $table . '.field.colPos' . $row['colPos'];
				$where[] = $table . '.field.colPos' . $row['colPos'] . '.lang.' . $row['ISOcode'];
				
				$where[] = $table . '.field.colPos' . $row['colPos'] . '.ctype.' . $row['CType'];
				$where[] = $table . '.field.colPos' . $row['colPos'] . '.ctype.' . $row['CType'] . '.lang.' . $row['ISOcode'];;
				
				// TemplaVoila is installed
				if (t3lib_extMgm::isLoaded('templavoila')) {
					require_once(t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_api.php');
					$tvAPI = t3lib_div::makeInstance('tx_templavoila_api');
					
					// Add all nested TV fields to location
					$tmp = array();
					$uid = $row['uid'];
					if( $row['t3_origuid'] > 0 ) {
						$uid = $row['t3_origuid'];
					}
					$flex = array('table' => $table, 'uid' => $uid);
					while ($flex['table'] == $table) {
						$flex = array_shift($tvAPI->flexform_getPointersByRecord($flex['uid'], $row['pid']));
						// location based on tablename + TV field name is added
						$tmp[] = $table . '.field.' . $flex['field'] . '.ctype.' . $row['CType'] . '.lang.' . $row['ISOcode'];
						$tmp[] = $table . '.field.' . $flex['field'] . '.ctype.' . $row['CType'];
						$tmp[] = $table . '.field.' . $flex['field'] . '.lang.' . $row['ISOcode'];
						$tmp[] = $table . '.field.' . $flex['field'];
					}
					$where = array_merge($where,array_reverse($tmp));
				}
			break;
			default:
				$fieldStr = $PA['itemFormElName'];
				$fieldStrLength = strlen($fieldStr)-6;
				$fieldStr = substr($fieldStr,5,$fieldStrLength);
				$fields = explode('][', $fieldStr);
				$where[] = $fields[0].'.'.$fields[2];
			break;
		}
		// A hook  to allow pre-processing of custom tables
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tinymce_rte']['processTableConfiguration'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tinymce_rte']['processTableConfiguration'] as $_classRef) {
				$_procObj = &t3lib_div::getUserObj($_classRef);
				$tmp = array();
				$tmp = $_procObj->process_table_configuration($table, $row);
				$where = array_merge($where, $tmp);
			}
		}
		
		return $where;
	}
	
	/**
	 * including of all nessecary core files (gzip or seperate, additional language files, callbackJS)
	 * 
	 * @param	array		config to use
	 * @return	array		code incl. script tags
	 */
	function getCoreScript( $config ) {
		$code = '';
		
		$loaded = ( t3lib_extmgm::isLoaded($config['languagesExtension']) ) ? 1 : 0;
		if ($config['gzip'])
			$code .= '
				<script type="text/javascript" src="' . $config['tiny_mceGzipPath'] . '"></script>
				<script type="text/javascript">
				tinyMCE_GZ.init({
					plugins : "' . $config['init.']['plugins'] . '",
					themes : "advanced",
					languages : "' . $config['init.']['language'] .'",
					disk_cache : ' . $config['gzipFileCache'] . ',
					langExt : "' . $config['languagesExtension'] . '",
					langExtLoaded : ' . $loaded  . ',
					debug : false
				});
				</script>
			';
		else {
		  $code .= '<script type="text/javascript" src="' . $config['tiny_mcePath'] . '"></script>';
			if ( t3lib_extmgm::isLoaded($config['languagesExtension']) && ($config['init.']['language'] != 'en') && ($config['init.']['language'] != 'de') ) {
				$code .= '<script type="text/javascript">';
				$code .= $this->loadLanguageExtension($config['init.']['language'], $config['init.']['plugins'], $this->getPath('EXT:' . $config['languagesExtension'] .'/tiny_mce/') );
				$code .= '</script>';
			}
		}
		
		return $code;
	}
	
	/**
	 * create the init code for the RTE
	 * 
	 * @param	array		config to use
	 * @return	array		code incl. script tags
	 */
	function getInitScript( $config ) {
		$code = '';

		$code .= '
			<script type="text/javascript">
			/* <![CDATA[ */
				tinyMCE.init(
					' . $this->parseConfig($config) .  '
				);
			/* ]]> */
			</script>
		';
		return $code;
	}
	
	/**
	 * alternative to array_merge_recursive (which won't override valuse)
	 * 
	 * @param	array		source array
	 * @param	array		array to merge with
	 * @return	array		merged array (values are overwritten)
	 */
	function array_merge_recursive_override($arr,$ins) {
		if ( is_array($arr) ) {
			if( is_array($ins) ) foreach( $ins as $k => $v ) {
				if(isset($arr[$k])&&is_array($v)&&is_array($arr[$k]))
					$arr[$k] = $this->array_merge_recursive_override($arr[$k],$v);
				else 
					$arr[$k] = $v;
			}
		}
		elseif ( !is_array($arr) && ( strlen($arr) == 0 || $arr == 0 ) )
			$arr = $ins;
		return( $arr );
	}

	/**
	 * creates an valid array that can be parsed (recursive)
	 * removes "." from array keys and ensure that array values are in UTF-8 format
	 * 
	 * @param	array		config array to be fixed
	 * @return	array		fixed array
	 */
	function fixTSArray($config) {
		$output = array();
		foreach($config as $key => $value) {
			if( is_array($value) )
				$output[trim($key,'.')] = $this->fixTSArray($value);
			elseif ( $this->forceUTF8 === true )
				$output[trim($key,'.')] = $this->isUTF8($value) ? $value : utf8_encode($value);
			else
				$output[trim($key,'.')] = $value;
		}
		return $output;
	}
	
	/**
	 * Check if string is in UTF-8 format
	 * 
	 * @param	array	string to check
	 * @return	boolean	true if string is valid utf-8
	 */
	function isUTF8($str) {
		return preg_match('/\A(?:([\09\0A\0D\x20-\x7E]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})*)\Z/x', $str);
	}

	/**
	 * parses the array to a valid javascript object in JSON
	 * 
	 * @param	array		config array to be parced
	 * @return	string	javascript object in JSON
	 */
	function parseConfig($config) {
		if ( !function_exists('json_encode') )
			$code = t3lib_div::array2json($this->fixTSArray($config));
		else
			$code = json_encode($this->fixTSArray($config));

		// remove quotes around the setup call
		$code = preg_replace('/("setup":)\s*"(.+?)"(,")/i', '\1\2\3', $code);
		// remove quotes from numeric values
		$code = preg_replace('/(.*"theme_advanced_resizing_max_width"\s*:\s*)"(.*?)"(.*)/', '\1\2\3', $code);
		$code = preg_replace('/(.*"theme_advanced_resizing_min_width"\s*:\s*)"(.*?)"(.*)/', '\1\2\3', $code);
		$code = preg_replace('/(.*"theme_advanced_resizing_max_height"\s*:\s*)"(.*?)"(.*)/', '\1\2\3', $code);
		$code = preg_replace('/(.*"theme_advanced_resizing_min_width"\s*:\s*)"(.*?)"(.*)/', '\1\2\3', $code);
		$code = preg_replace('/(.*"custum_undo_redo_levels"\s*:\s*)"(.*?)"(.*)/', '\1\2\3', $code);
		$code = preg_replace('/(.*"table_cell_limit"\s*:\s*)"(.*?)"(.*)/', '\1\2\3', $code);
		$code = preg_replace('/(.*"table_row_limit"\s*:\s*)"(.*?)"(.*)/', '\1\2\3', $code);
		$code = preg_replace('/(.*"table_col_limit"\s*:\s*)"(.*?)"(.*)/', '\1\2\3', $code);
		return str_replace( array('"false"', '"true"', '"self"'), array('false', 'true', 'self'), $code);
	}
	
	/**
	 * loads all needed language files with the tinymce.Scritploader
	 * 
	 * @param	string	language to use in iso631 (example 'en', 'de' ...)
	 * @param	string	list of plugins (seperated with ',')
	 * @param	string	path of the language files
	 * @return	string	the javascript code to load all language files
	 */
	function loadLanguageExtension($lang, $plugins, $path) {
		$msg = '';
		foreach(explode(',', $plugins) as $plugin) {
			$msg .= 'tinymce.ScriptLoader.load("' . $path . '/plugins/' . $plugin . '/langs/' . $lang . '_dlg.js");';
		}
		$msg .= '
			tinymce.ScriptLoader.load("' . $path . '/themes/advanced/langs/' . $lang . '_dlg.js");
			tinymce.ScriptLoader.load("' . $path . '/themes/advanced/langs/' . $lang . '.js");
			tinymce.ScriptLoader.load("' . $path . '/langs/' . $lang . '.js");
		';
		return $msg;
	}
	
	/**
	 * returns the setupTSconfig for a given id
	 * 
	 * @param	int		current page id		
	 * @return	string	the corresponding setupTSconfig
	 */		
	function getSetupTS($pageUid) {
		$sysPageObj = t3lib_div::makeInstance('t3lib_pageSelect');
		$rootLine = $sysPageObj->getRootLine($pageUid);
		$TSObj = t3lib_div::makeInstance('t3lib_tsparser_ext');
		$TSObj->tt_track = 0;
		$TSObj->init();
		$TSObj->runThroughTemplates($rootLine);
		$TSObj->generateConfig();
		return $TSObj->setup;
	}
	
	/**
	 * resolves numerous pathes, creates a seperate array for the Templates inclusion, removes unnecessary code in the init part
	 * 
	 * @param	array		RTE config array
	 * @param	int		current page id		
	 * @return	string	the "fixed" RTE config
	 */	
	function fixTinyMCETemplates($config, $row) {
	  $init_templates = array();
		$templates = array();
	  if ( is_array($config['init.']['template_templates.']) ) {
			ksort($config['init.']['template_templates.']);
		  $i = 0;
		  foreach( $config['init.']['template_templates.'] as $template ) {
				$useInclude = false;
				if ( $template['include'] ) {
					if ( $template['src'] == '' ) $template['src'] = 'EXT:tinymce_rte/mod4/TinyMCETemplate.php';
					$templates[$i] = array( 'include' => $this->getPath($template['include'], 1) );
					unset($template['include']);
					$useInclude = true;
				}
				$init_templates[$i.'.'] = $template;
			  $init_templates[$i.'.']['src'] = $this->getPath($template['src']);
				if ( $useInclude ) {
					$init_templates[$i.'.']['src'] .= strpos($init_templates[$i.'.']['src'], '?') ? '&' : '?';
					$init_templates[$i.'.']['src'] .= 'pageId=' . $row['pid'] . '&templateId=' . $i . '&ISOcode=' . $row['ISOcode'] . '&mode=' . TYPO3_MODE;
				}
				$i++;
			}
			$config['init.']['template_templates.'] = array();
			$config['init.']['template_templates.'] = $init_templates;
		}
		$config['TinyMCE_templates.'] = $templates;
		
		return $config;
	}
	
	/**
	 * creates the javascript code (incl. <script> tags) for the typo3filemanager
	 * 
	 * @return	string	the javascript code to allow selection of pages in a TYPO3 dialog
	 */
	function getFileDialogJS($config, $path, $pObj, $table, $field, $row) {
		$msg = "";
		$msg .='
			<script language="javascript" type="text/javascript">
				/* <![CDATA[ */
				function typo3filemanager(field_name, url, type, win) {
					var tab = "";
					// xxx on start TinyMCE seem to not make all elements relative; this ensures it
					if ( url.indexOf("' . t3lib_div::getIndpEnv('TYPO3_SITE_URL') . '") > -1 ) url = url.substr(' . strlen( t3lib_div::getIndpEnv('TYPO3_SITE_URL') ) . ');
					if ( (type != "image") && (type != "media") ) type = "link";
					switch(type){
						case "media":
							tab = "file";
						case "link":
							var expPage = "";
							if (tab == "")
								tab = "' . $config['typo3filemanager.']['defaultTab'] . '";
							if ( url.indexOf("fileadmin") > -1 ) tab = "file";
							if ( (url.indexOf("http://") > -1) || (url.indexOf("ftp://") > -1) || (url.indexOf("https://") > -1) ) tab = "url";
							if ( url.indexOf("@") > -1 ) tab = "mail";
							var current = "&P[currentValue]=" + encodeURIComponent(url);
							template_file = "'.$path.'mod1/browse_links.php?act="+tab+"&mode=wizard&bparams="+type+"&P[ext]='. $this->getPath('EXT:tinymce_rte/./') .'&P[init]=tinymce_rte&P[formName]=' . /*$pObj->formName*/ 'editform' . '"+current+"&P[itemName]=data%5B'.$table.'%5D%5B'.$row["uid"].'%5D%5B'.$field.'%5D&P[fieldChangeFunc][TBE_EDITOR_fieldChanged]=TBE_EDITOR_fieldChanged%28%27'.$table.'%27%2C%27'.$row["uid"].'%27%2C%27'.$field.'%27%2C%27data%5B'.$table.'%5D%5B'.$row["uid"].'%5D%5B'.$field.'%5D%27%29%3B"+"&RTEtsConfigParams='.$table.'%3A136%3A'.$field.'%3A29%3Atext%3A'.$row["pid"].'%3A";
							break;
						case "image":
							tab = "plain";
							var current = "&expandFolder=' . rawurlencode($this->getPath('./',1)) . '" + encodeURIComponent(url.substr(0,url.lastIndexOf("/")));
							if ( (url.indexOf("RTEmagicC_") > -1) || (url == "") ) {
								current = "&expandFolder=' . rawurlencode($this->getPath($config['typo3filemanager.']['defaultImagePath'],1)) . '";
								tab = "magic";
							}
							if ( url == "" ) tab = "' . $config['typo3filemanager.']['defaultImageTab'] . '";
							template_file = "'.$path.'mod2/rte_select_image.php?act="+tab+current+"&RTEtsConfigParams='.$table.'%3A136%3A'.$field.'%3A29%3Atext%3A'.$row["pid"].'%3A";
							break;
					}

					tinyMCE.activeEditor.windowManager.open({
						file : template_file,
						width : ' . $config['typo3filemanager.']['window.']['width'] . ',
						height : ' . $config['typo3filemanager.']['window.']['height'] . ',
						resizable : "yes",
						inline : "yes",
						close_previous : "no"
					}, {
						window : win,
						input : field_name
					});
					return false;
				}
				/* ]]> */
			</script>
		';
		return $msg;
	}

	/**
	 * resolves a relative path
	 * 
	 * @param	string	path to be resolved
	 * @param	boolean	do you wan't absolute path or relative?
	 * @return	string	resolved path
	 */
	 function getPath($path, $abs = false) {
		$httpTypo3Path = substr( substr( t3lib_div::getIndpEnv('TYPO3_SITE_URL'), strlen( t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') ) ), 0, -1 );
		$httpTypo3Path = (strlen($httpTypo3Path) == 1) ? '/' : $httpTypo3Path . '/';
		if ($abs)
			return t3lib_div::getFileAbsFileName($path);
		return $httpTypo3Path . str_replace(PATH_site,'',t3lib_div::getFileAbsFileName($path));
  }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/class.tx_tinymce_rte_base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/class.tx_tinymce_rte_base.php']);
}
?>
