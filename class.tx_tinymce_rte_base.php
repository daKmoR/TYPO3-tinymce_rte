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

		// set a uniq rte id.
		$rteID = (TYPO3_MODE == 'BE') ? $parentObject->RTEcounter : $parentObject->cObj->data['uid'] . $parentObject->RTEcounter;

		//loads the current Value
		$value = $this->transformContent('rte',$PA['itemFormElValue'],$table,$field,$row,$specConf,$thisConfig, $RTErelPath ,$thePidValue);

		// get the language (also checks if lib is called from FE or BE, which might of use later.)
		$lang = (TYPO3_MODE == 'FE') ? $GLOBALS['TSFE'] : $GLOBALS['LANG'];
		$this->language = $lang->lang;

		// language conversion from TLD to iso631
		if ( array_key_exists($this->language, $lang->csConvObj->isoArray) )
			$this->language = $lang->csConvObj->isoArray[$this->language];

		// check if TinyMCE language file exists
		$langpath = (t3lib_extmgm::isLoaded($thisConfig['languagesExtension'])) ? t3lib_extMgm::siteRelPath($thisConfig['languagesExtension']) : t3lib_extMgm::siteRelPath('tinymce_rte') . 'res/';
		if(!is_file(PATH_site . $langpath . 'tiny_mce/langs/' . $this->language . '.js')) {
		  $this->language = 'en';
		}

		if (!is_array($BE_USER->userTS['RTE.']['default.']))
		  $BE_USER->userTS['RTE.']['default.'] = array();

		$this->conf = array( 'init.' => array(
			'language' => $this->language,
			'document_base_url' => t3lib_div::getIndpEnv('TYPO3_SITE_URL'),
			'elements' => 'RTEarea' . $rteID
		));
		
		$this->conf = $this->array_merge_recursive_override($this->conf, $thisConfig);
		$this->conf = $this->array_merge_recursive_override($this->conf, $BE_USER->userTS['RTE.']['default.']);

		//resolve EXT pathes for these values
		$this->conf['init.']['spellchecker_rpc_url'] = $this->getPath($this->conf['init.']['spellchecker_rpc_url']);
		$this->conf['tiny_mcePath'] = $this->getPath($this->conf['tiny_mcePath']);
		$this->conf['tiny_mceGzipPath'] = $this->getPath($this->conf['tiny_mceGzipPath']);

		$loaded = ( t3lib_extmgm::isLoaded($this->conf['languagesExtension']) ) ? 1 : 0;
		if ($this->conf['gzip'])
			$code .= '
				<script type="text/javascript" src="' . $this->conf['tiny_mceGzipPath'] . '"></script>
				<script type="text/javascript">
				tinyMCE_GZ.init({
					plugins : "' . $this->conf['init.']['plugins'] . '",
					themes : "advanced",
					languages : "' . $this->conf['init.']['language'] .'",
					disk_cache : ' . $this->conf['gzipFileCache'] . ',
					langExt : "' . $this->conf['languagesExtension'] . '",
					langExtLoaded : ' . $loaded  . ',
					debug : false
				});
				</script>
			';
		else {
		  $code .= '<script type="text/javascript" src="' . $this->conf['tiny_mcePath'] . '"></script>';
			if ( t3lib_extmgm::isLoaded($this->conf['languagesExtension']) && ($this->language != 'en') && ($this->language != 'de') ) {
				$code .= '<script type="text/javascript">';
				$code .= $this->loadLanguageExtension($this->language, $this->conf['init.']['plugins'], $this->getPath('EXT:' . $this->conf['languagesExtension'] .'/tiny_mce/') );
				$code .= '</script>';
			}
		}

		if ($parentObject->RTEcounter > 1)
			$code = ""; //don't reinclude the core js if there is already another RTE

		if ( TYPO3_MODE == 'FE' ) {
			$GLOBALS['TSFE']->additionalHeaderData['tinymce_rte'] = $code; // put core js into page header; prevents double inclusion;
			$code = '';
		}

		$code .= '
			<script type="text/javascript">
			/* <![CDATA[ */
				tinyMCE.init(
					' . $this->parseConfig($this->conf['init.']) .  '
				);
			/* ]]> */
			</script>
		';

		if (TYPO3_MODE == 'BE')
			$code .= $this->getFileDialogJS( $this->getPath('EXT:tinymce_rte/./'), $parentObject, $table, $field, $row);

		$code .= $this->triggerField($PA['itemFormElName']);
		$code .= '<textarea id="RTEarea'.$rteID.'" class="tinymce_rte" name="'.htmlspecialchars($PA['itemFormElName']).'" rows="30" cols="100">'.t3lib_div::formatForTextarea($value).'</textarea>';

		return $code;
	}

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

	function fixTSArray($config) {
		$output = array();
		foreach($config as $key => $value) {
			$output[trim($key,'.')] = is_array($value) ? $this->fixTSArray($value) : $value;
		}
		return $output;
	}

	function parseConfig($config) {
		if ((t3lib_div::int_from_ver(TYPO3_version) >= 4001000) && !(t3lib_div::int_from_ver(TYPO3_version) >= 4002000))
			$code = $this->array2json($this->fixTSArray($config));
		else
			$code = t3lib_div::array2json($this->fixTSArray($config));
		return str_replace( array('"false"', '"true"'), array('false', 'true'), $code);
	}

	function array2json($jsonArray) {
		if (!$GLOBALS['JSON']) {
			require_once(PATH_typo3.'contrib/json.php');
			$GLOBALS['JSON'] = t3lib_div::makeInstance('Services_JSON');
		}
		return $GLOBALS['JSON']->encode($jsonArray);
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

	function getFileDialogJS($path, $pObj, $table, $field, $row) {
		$msg = "";
		$msg .='
			<script language="javascript" type="text/javascript">
				/* <![CDATA[ */
				function typo3filemanager(field_name, url, type, win) {
					if ( type != "image") type = "link";
					switch(type){
						case "link":
							var expPage = "";
							var tab = "' . $this->conf['typo3filemanager.']['defaultTab'] . '";
							if ( url.indexOf("fileadmin") > -1 ) tab = "file";
							if ( (url.indexOf("http://") > -1) || (url.indexOf("ftp://") > -1) || (url.indexOf("https://") > -1) ) tab = "url";
							if ( url.indexOf("@") > -1 ) tab = "mail";
							var current = "&P[currentValue]=" + encodeURIComponent(url);
							if ( url.indexOf("#") > -1 ) {
								var pageCE = url.split("#");
								expPage = "&expandPage=" + pageCE[0] + "&cE=" + pageCE[1];
							}
							template_file = "'.$path.'mod1/browse_links.php?act="+tab+expPage+"&mode=wizard&P[ext]='. $this->getPath('EXT:tinymce_rte/./') .'&P[init]=tinymce_rte&P[formName]=' . /*$pObj->formName*/ 'editform' . '"+current+"&P[itemName]=data%5B'.$table.'%5D%5B'.$row["uid"].'%5D%5B'.$field.'%5D&P[fieldChangeFunc][TBE_EDITOR_fieldChanged]=TBE_EDITOR_fieldChanged%28%27'.$table.'%27%2C%27'.$row["uid"].'%27%2C%27'.$field.'%27%2C%27data%5B'.$table.'%5D%5B'.$row["uid"].'%5D%5B'.$field.'%5D%27%29%3B";
							break;
						case "image":
							var tab = "plain";
							var current = "&expandFolder=' . rawurlencode($this->getPath('./',1)) . '" + encodeURIComponent(url.substr(0,url.lastIndexOf("/")));
							if ( (url.indexOf("RTEmagicC_") > -1) || (url == "") ) {
								current = "&expandFolder=' . rawurlencode($this->getPath('./fileadmin/',1)) . '";
								tab = "magic";
							}
							if ( url == "" ) tab = "' . $this->conf['typo3filemanager.']['defaultImageTab'] . '";
							template_file = "'.$path.'mod2/rte_select_image.php?act="+tab+current+"&RTEtsConfigParams='.$table.'%3A136%3A'.$field.'%3A29%3Atext%3A'.$row["pid"].'%3A";
							break;
					}

					tinyMCE.activeEditor.windowManager.open({
						file : template_file,
						width : ' . $this->conf['typo3filemanager.']['window.']['width'] . ',
						height : ' . $this->conf['typo3filemanager.']['window.']['height'] . ',
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
