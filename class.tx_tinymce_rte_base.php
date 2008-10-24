<?php
require_once(PATH_t3lib.'class.t3lib_rteapi.php');
require_once(PATH_t3lib.'class.t3lib_cs.php');

class tx_tinymce_rte_base extends t3lib_rteapi {

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
		global $LANG, $BE_USER;
		$code = "";
		
		//loads the current Value
		$value = $this->transformContent('rte',$PA['itemFormElValue'],$table,$field,$row,$specConf,$thisConfig, $RTErelPath ,$thePidValue);

		// get the language
		$this->language = ( ($LANG->lang == 'default') || (!$LANG->lang) ) ? 'en' : $this->getISO2Lang($LANG->lang);
		
		$mcePath = 'EXT:tinymce_rte/res/tiny_mce/tiny_mce.js';
		$mceGzipPath = 'EXT:tinymce_rte/res/tiny_mce/tiny_mce_gzip.js';
		
		if (!is_array($BE_USER->userTS['RTE.']['default.']['init.']))
		  $BE_USER->userTS['RTE.']['default.']['init.'] = array();
		
		$this->conf['init'] = array(
			'language' => $this->language,
			'document_base_url' => t3lib_div::getIndpEnv('TYPO3_SITE_URL')
		);
		$this->conf['init'] = array_merge($this->conf['init'], $thisConfig['init.'], $BE_USER->userTS['RTE.']['default.']['init.']);
		
		$loaded = ( t3lib_extmgm::isLoaded($thisConfig['languagesExtension']) ) ? 1 : 0;
		if ($thisConfig['gzip'])
			$code .= '
				<script type="text/javascript" src="../' . $this->getPath($mceGzipPath) . '"></script>
				<script type="text/javascript">
				tinyMCE_GZ.init({
					plugins : "' . $this->conf['init']['plugins'] . '",
					themes : "advanced",
					languages : "' . $this->conf['init']['language'] .'",
					disk_cache : ' . $thisConfig['gzipFileCache'] . ',
					langExt : "' . $thisConfig['languagesExtension'] . '",
					langExtLoaded : ' . $loaded  . ',
					debug : false
				});
				</script>
			';
		else {
		  $code .= '<script type="text/javascript" src="../' . $this->getPath($mcePath) . '"></script>';
			if ( t3lib_extmgm::isLoaded($thisConfig['languagesExtension']) && ($this->language != 'en') && ($this->language != 'de') ) {
				$code .= '<script type="text/javascript">';
				$code .= $this->loadLanguageExtension($this->language, $this->conf['init']['plugins'], '../typo3conf/ext/' . $thisConfig['languagesExtension'] . '/tiny_mce' );
				$code .= '</script>';
			}
		}
		
		$code .= '
			<script type="text/javascript">
				tinyMCE.init(
					' . $this->parseConfig($this->conf['init']) .  '
				);
			</script>
		';
		
    $code .= $this->getFileDialogJS('../typo3conf/ext/tinymce_rte/', $pObj, $table, $field, $row, $thisConfig);
		
		$code .= $this->triggerField($PA['itemFormElName']);
		$code .= '<textarea id="RTEarea'.$pObj->RTEcounter.'" class="tinymce_rte" name="'.htmlspecialchars($PA['itemFormElName']).'" rows="15" cols="80">'.t3lib_div::formatForTextarea($value).'</textarea>';		
		
		return $code;
	}
	
	function fixTSArray($config) {
		$output = array();
		foreach($config as $key => $value) {
			$output[trim($key,'.')] = is_array($value) ? $this->fixTSArray($value) : $value;
		}
		return $output;
	}
	
	function parseConfig($config) {
		return json_encode($this->fixTSArray($config));
	}
	
	// returns languages in ISO [brought to you by Peter Klein]
	function getISO2Lang($typo3lang) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('lg_iso_2','static_languages','lg_typo3='.$GLOBALS['TYPO3_DB']->fullQuoteStr($typo3lang,'static_languages'));
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			return strtolower($row['lg_iso_2']);
		}
		else {
			return $typo3lang;
		}
	}
	
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
	
	function getFileDialogJS($path, $pObj, $table, $field, $row, $thisConfig) {
		$msg = "";
		$msg .=' 			
			<script language="javascript" type="text/javascript">
				//<![CDATA[
				
				/**
				 * Here are the additional Typo3-Functions
				 * At this time only "type" is used to
				 * manage Link or Image functions 
				 */
				function fileBrowserCallBack(field_name, url, type, win) {
					field=field_name;
					var expPage="";
					var editor_id="RTEarea'.$pObj->RTEcounter.'";
					var act="page";
          if(type!="image") type="link";
					switch(type){
						case "link":
							var selURL="";
							var current="";
							var node=tinyMCE.activeEditor.selection.getNode();
							do {
								if (node.nodeName.toLowerCase() == "a" && node.getAttribute("href") != "") {
									var act=node.getAttribute("t3page") ? node.getAttribute("t3page") : "page";
									var url=node.getAttribute("t3url") ? node.getAttribute("t3url") : node.getAttribute("href");
									var target=node.getAttribute("t3target") ? node.getAttribute("t3target") : (node.getAttribute("target") ? node.getAttribute("target") : "");
									if(url.indexOf("?id=")<0) {
										var selURL=url;
										current="&P[currentValue]="+selURL+" "+target;
									}
									else {
										var fz=url.indexOf("?")+4;
										selURL=url.substr(fz,url.length);
										current="&P[currentValue]="+selURL+" "+target;
									}
								}

							} while ((node = node.parentNode));

							if(selURL.indexOf("#")>-1) {
								var lT=selURL.split("#")
								var expPage="&expandPage="+lT[0]+"&cE="+lT[1];
								current=current.replace("#","%23");
							}
							

							template_file = "'.$path.'mod1/browse_links.php?act="+act+expPage+"&mode=wizard&P[ext]=../'.t3lib_extMgm::siteRelPath("tinymce_rte").'&P[init]=tinymce_rte&P[formName]=' . /*$pObj->formName*/ 'editform' . '"+current+"&P[itemName]=data%5B'.$table.'%5D%5B'.$row["uid"].'%5D%5B'.$field.'%5D&P[fieldChangeFunc][TBE_EDITOR_fieldChanged]=TBE_EDITOR_fieldChanged%28%27'.$table.'%27%2C%27'.$row["uid"].'%27%2C%27'.$field.'%27%2C%27data%5B'.$table.'%5D%5B'.$row["uid"].'%5D%5B'.$field.'%5D%27%29%3B";
							break;
	
						case "image":
							template_file = "'.$path.'mod2/rte_select_image.php?act=magic&RTEtsConfigParams='.$table.'%3A136%3A'.$field.'%3A29%3Atext%3A'.$row["pid"].'%3A";
							break;
					}

					tinyMCE.activeEditor.windowManager.open({
						file : template_file,
						width : ' . $thisConfig['typo3filemanager.']['width'] . ', 
						height : ' . $thisConfig['typo3filemanager.']['height'] . ',
						resizable : "yes",
						inline : "yes",
						close_previous : "no"
					}, {
						window : win,
						input : field_name
					});
					return false;
					
				}
				
				//]]>
			</script>
		';
		return $msg;
	}
	
  function getPath($path) {
    return str_replace(PATH_site,'',t3lib_div::getFileAbsFileName($path));
  }


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/class.tx_tinymce_rte_base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/class.tx_tinymce_rte_base.php']);
}
?>