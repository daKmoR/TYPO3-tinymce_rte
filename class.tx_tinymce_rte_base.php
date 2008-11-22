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
require_once(PATH_t3lib.'class.t3lib_cs.php');

class tx_tinymce_rte_base extends t3lib_rteapi {

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
		
		$config = $this->init($thisConfig, $parentObject->RTEcounter);
		$config = $this->fixTinyMCETemplates($config, $row);
		
		// include core and typo3filemanager only the first time
		if ( $parentObject->RTEcounter == 1 ) {
			$code .= $this->getFileDialogJS( $config, $this->getPath('EXT:tinymce_rte/./'), $parentObject, $table, $field, $row);
			$code .= $this->getCoreScript( $config );
		}
		
		$code .= $this->getInitScript( $config['init.'] );
		
		//loads the current Value and create the textarea
		$value = $this->transformContent('rte',$PA['itemFormElValue'],$table,$field,$row,$specConf,$thisConfig, $RTErelPath ,$thePidValue);
		$code .= $this->triggerField($PA['itemFormElName']);
		$code .= '<textarea id="RTEarea'.$parentObject->RTEcounter.'" class="tinymce_rte" name="'.htmlspecialchars($PA['itemFormElName']).'" rows="30" cols="100">'.t3lib_div::formatForTextarea($value).'</textarea>';
		
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
	function init($thisConfig, $rteId = 1) {
		global $LANG;
		if (TYPO3_MODE == 'BE') global $BE_USER;
		
		if ( TYPO3_branch == 4.1 && !t3lib_extMgm::isLoaded('tinymce_rte_patch41') )
			die('for TYPO3 4.1 you need to install the extension tinymce_rte_patch41');

		// get the language (also checks if lib is called from FE or BE, which might of use later.)
		$LANG = (TYPO3_MODE == 'FE') ? $GLOBALS['TSFE'] : $GLOBALS['LANG'];
		$this->language = $LANG->lang;

		// language conversion from TLD to iso631
		if ( array_key_exists($this->language, $LANG->csConvObj->isoArray) )
			$this->language = $LANG->csConvObj->isoArray[$this->language];

		// check if TinyMCE language file exists
		$langpath = (t3lib_extmgm::isLoaded($thisConfig['languagesExtension'])) ? t3lib_extMgm::siteRelPath($thisConfig['languagesExtension']) : t3lib_extMgm::siteRelPath('tinymce_rte') . 'res/';
		if(!is_file(PATH_site . $langpath . 'tiny_mce/langs/' . $this->language . '.js')) {
		  $this->language = 'en';
		}

		if (!is_array($BE_USER->userTS['RTE.']['default.']))
		  $BE_USER->userTS['RTE.']['default.'] = array();

		$config = array( 'init.' => array(
			'language' => $this->language,
			'document_base_url' => t3lib_div::getIndpEnv('TYPO3_SITE_URL'),
			'elements' => 'RTEarea' . $rteId
		));
		
		$config = $this->array_merge_recursive_override($config, $thisConfig);
		$config = $this->array_merge_recursive_override($config, $BE_USER->userTS['RTE.']['default.']);

		//resolve EXT pathes for these values
		$config['init.']['spellchecker_rpc_url'] = $this->getPath($config['init.']['spellchecker_rpc_url']);
		$config['tiny_mcePath'] = $this->getPath($config['tiny_mcePath']);
		$config['tiny_mceGzipPath'] = $this->getPath($config['tiny_mceGzipPath']);
		
		//do not us the default linkhandler config for tt_news unless it's loaded
		if ( !t3lib_extmgm::isLoaded('tt_news') )
			unset($config['linkhandler.']['tt_news.']);
			
		return $config;
	}	
	
	/**
	 * including of all nessecary core files (gzip or seperate, additional language files, callbackJS)
	 * 
	 * @param	array		config to use
	 * @return	array		code incl. script tags
	 */
	function getCoreScript( $config ) {
		$code = '';
		
		if ( $config['callbackJavascriptFile'] != '' ) { //add callback javascript file
			$config['callbackJavascriptFile'] = $this->getPath($config['callbackJavascriptFile']);
			$code .= '<script type="text/javascript" src="' . $config['callbackJavascriptFile'] . '"></script>';
		}			

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
		$code = '
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
	 * creates an valid array that can be parced (recursive)
	 * 
	 * @param	array		config array to be fixed
	 * @return	array		fixed array
	 */
	function fixTSArray($config) {
		$output = array();
		foreach($config as $key => $value) {
			$output[trim($key,'.')] = is_array($value) ? $this->fixTSArray($value) : $value;
		}
		return $output;
	}

	/**
	 * parses the array to a valid javascript object in JSON
	 * 
	 * @param	array		config array to be parced
	 * @return	string	javascript object in JSON
	 */
	function parseConfig($config) {
		$code = t3lib_div::array2json($this->fixTSArray($config));
		return str_replace( array('"false"', '"true"'), array('false', 'true'), $code);
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
		$msg = "";
		foreach(explode(",", $plugins) as $plugin) {
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
					if ( $template['src'] == '' ) $template['src'] = 'EXT:tinymce_rte/mod5/TinyMCETemplate.php';
					$templates[$i] = array( 'include' => $this->getPath($template['include'], 1) );
					unset($template['include']);
					$useInclude = true;
				}
				$init_templates[$i.'.'] = $template;
			  $init_templates[$i.'.']['src'] = $this->getPath($template['src']);
				if ( $useInclude ) {
					$init_templates[$i.'.']['src'] .= strpos($init_templates[$i.'.']['src'], '?') ? '&' : '?';
					$init_templates[$i.'.']['src'] .= 'pageId=' . $row['pid'] . '&templateId=' . $i . '&sys_language_uid=' . $row['sys_language_uid'];
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
								current = "&expandFolder=' . rawurlencode($this->getPath('./fileadmin/',1)) . '";
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
