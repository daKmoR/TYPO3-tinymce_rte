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
 * class.tx_tinymcerte_pi1.php
 *
 * Provides tinymce_rte for use in frontend plugins
 *
 * @author Thomas Allmer <thomas.allmer@webteam.at>
 *
 */

/**
 * This class provides the tinymce_rte for usage in FE-plugins
 *
 * @author Thomas Allmer <thomas.allmer@webteam.at>
 * @package Typo3
 * @subpackage tinymce_rte
 */

require_once(t3lib_extMgm::extPath('tinymce_rte').'class.tx_tinymce_rte_base.php');

class tx_tinymce_rte_pi1 extends tx_tinymce_rte_base {

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

		$pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
		$localRteId = $parentObject->RTEcounter . '.';

		$rteConfig = $pageTSConfig['RTE.']['default.']['FE.'];

		if (is_array($parentObject->conf['tinymce_rte.'][$localRteId])){
			$tmpConf = $this->array_merge_recursive_override($rteConfig, $parentObject->conf['tinymce_rte.'][$localRteId]);
		} elseif  (is_array($parentObject->conf['tinymce_rte.']['1.'])){
			$tmpConf = $this->array_merge_recursive_override($rteConfig, $parentObject->conf['tinymce_rte.']['1.']);
		} else {
			$tmpConf = $rteConfig;
		}

		return parent::drawRTE($parentObject,$table,$field,$row,$PA,$specConf,$tmpConf,$RTEtypeVal,$RTErelPath,$thePidValue);
	}

}

// Default-Code for using XCLASS (dont touch)
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/pi1/class.tx_tinymce_rte_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/pi1/class.tx_tinymce_rte_pi1.php']);
}
?>