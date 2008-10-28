<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Marcus Schwemer (schwemer@netzwerkberatung.de)
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
 * class.tx_tinymcerte_pi1.php
 *
 * Provides tinymce_rte for use in frontend plugins
 *
 * @author Marcus Schwemer <schwemer@netzwerkberatung.de)
 *
 */

/**
 * This class provides the tinymce_rte for usage in FE-plugins
 *
 * @author Marcus Schwemer <schwemer@netzwerkberatung.de>
 * @package Typo3
 * @subpackage tinymce_rte
 */

require_once(t3lib_extMgm::extPath('tinymce_rte').'class.tx_tinymce_rte_base.php');

class tx_tinymcerte_pi1 extends tx_tinymce_rte_base
{
	var $pageTSConfig = array();
	var $rteSetup = array();
	var $httpTypo3Path;
	var $extHttpPath;

	/**
	 * 	 * Adds the tinymce_rte to a textarea
	 *
	 * @param	object		$parentObject 	Reference to parent object, which is an instance of the TCEforms.
	 * @param	string		$table 			The table name
	 * @param	string		$field			The field name
	 * @param	array		$row			The current row from which field is being rendered
	 * @param	array		$PA				Array of standard content for rendering form fields from TCEforms. See TCEforms for details on this. Includes for instance the value and the form field name, java script actions and more.
	 * @param	array		$specConf		"special" configuration - what is found at position 4 in the types configuration of a field from record, parsed into an array.
	 * @param	array		$thisConfig		Configuration for RTEs; A mix between TSconfig and otherwise. Contains configuration for display, which buttons are enabled, additional transformation information etc.
	 * @param	string		$RTEtypeVal		Record "type" field value.
	 * @param	string		$RTErelPath		Relative path for images/links in RTE; this is used when the RTE edits content from static files where the path of such media has to be transformed forth and back!
	 * @param	integer		$thePidValue	PID value of record (true parent page id)
	 * @return	string		HTML code for tinymce_rte
	 */

	function drawRTE($parentObject,$table,$field,$row,$PA,$specConf,$thisConfig,$RTEtypeVal,$RTErelPath,$thePidValue) {

		// relativ paths to tinymce files
		$mceRelPath = 'res/tiny_mce/tiny_mce.js';
		$mceRelGzipPath = 'res/tiny_mce/tiny_mce_gzip.js';

		// html code for rte creation
		$code = '';

		//loads the current Value
		$value = $this->transformContent('rte',$PA['itemFormElValue'],$table,$field,$row,$specConf,$thisConfig, $RTErelPath ,$thePidValue);

		$this->initClassVars();

		$config = $this->getConfiguration($parentObject);

		$loaded = ( t3lib_extmgm::isLoaded( $this->rteSetup['languagesExtension'] ) ) ? 1 : 0;

		if ($this->rteSetup['gzip']) {
			$code .= '
				<script type="text/javascript" src="'. $this->extHttpPath . $mceRelGzipPath . '"></script>
				<script type="text/javascript">
				/* <![CDATA[ */
				tinyMCE_GZ.init({
					plugins : "' . $this->conf['init']['plugins'] . '",
					themes : "advanced",
					languages : "' . $this->conf['init']['language'] .'",
					disk_cache : ' . $this->rteSetup['gzipFileCache'] . ',
					langExt : "' . $this->rteSetup['languagesExtension'] . '",
					langExtLoaded : ' . $loaded  . ',
					debug : false
				});
				/* ]]> */
				</script>
			';
		} else {
		  $code .= '<script type="text/javascript" src="'. $this->extHttpPath . $mceRelPath . '"></script>';
			if ( t3lib_extmgm::isLoaded($this->rteSetup['languagesExtension']) && ($this->conf['init']['language'] != 'en') && ($this->conf['init']['language'] != 'de') ) {
				$code .= '<script type="text/javascript"> /* <![CDATA[ */';
				$code .= $this->loadLanguageExtension($this->conf['init']['language'], $this->conf['init']['plugins'], t3lib_extMgm::siteRelPath($this->rteSetup['languagesExtension']) . '/tiny_mce' );
				$code .= '/* ]]> */</script>';
			}
		}

		$code .= '
			<script type="text/javascript">
			/* <![CDATA[ */
				tinyMCE.init(
					' . $this->parseConfig($config) .  '
				);
			/* ]]> */
			</script>
		';

		$code .= $this->triggerField($PA['itemFormElName']);
		$code .= '<textarea id="RTEarea'.$pObj->RTEcounter.'" class="tinymce_rte" name="'.htmlspecialchars($PA['itemFormElName']).'" rows="15" cols="80">'.t3lib_div::formatForTextarea($value).'</textarea>';

		return $code;


	}

	/**
	 * This function initializes the class viariables
	 *
	 * @param none
	 * @return void
	 */

	function initClassVars() {

		// first get the http-path to typo3:
		$this->httpTypo3Path = substr( substr( t3lib_div::getIndpEnv('TYPO3_SITE_URL'), strlen( t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') ) ), 0, -1 );
		if (strlen($this->httpTypo3Path) == 1) {
			$this->httpTypo3Path = '/';
		} else {
			$this->httpTypo3Path .= '/';
		}

		// Get the path to this extension:
		$this->extHttpPath = $this->httpTypo3Path.t3lib_extMgm::siteRelPath('tinymce_rte');

		$this->pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();

		if (is_array($this->pageTSConfig) && is_array($this->pageTSConfig['RTE.']['default.'])) {
			$this->rteSetup = $this->pageTSConfig['RTE.']['default.'];
		}
	}

	/**
	 * Reads the configuration from PageTS and TS-Setup and merges them into one array
	 *
	 * @param object $parentObject
	 * @return array Array with all configuration parameters
	 */
	function getConfiguration($parentObject) {

		if (is_array($this->rteSetup['init.'])) {
			$rteInit = $this->rteSetup['init.'];
		} else {
			$rteInit = array();
		}

		if (is_array($this->rteSetup) && is_array($this->rteSetup['FE.'])) {
			$feSetup = $this->rteSetup['FE.'];
		}

		if (is_array($feSetup['init.'])) {
			$feInit = $feSetup['init.'];
		} else {
			$feInit = array();
		}

		if (is_array($parentObject->conf['tinymce_rte.'])) {
			$tsSetup = $parentObject->conf['tinymce_rte.'];
		}

		if (is_array($tsSetup['init.'])) {
			$tsInit = $tsSetup['init.'];
		} else {
			$tsInit = array();
		}

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

		$this->conf['init'] = array(
			'language' => $this->language,
			'document_base_url' => t3lib_div::getIndpEnv('TYPO3_SITE_URL')
		);

		$this->conf['init'] = array_merge($this->conf['init'], $rteInit, $feInit, $tsInit);

		return $this->conf['init'];

	}

}

// Default-Code for using XCLASS (dont touch)
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/pi1/class.tx_tinymcerte_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/pi1/class.tx_tinymcerte_pi1.php']);
}

?>
