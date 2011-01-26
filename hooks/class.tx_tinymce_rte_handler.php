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
 * allow to use links as "record:tt_news:3"
 * original by Daniel Poetzinger (AOE media GmbH) in extension linkhandler
 *
 * @author Thomas Allmer <thomas.allmer@webteam.at>
 *
 */
 
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

class tx_tinymce_rte_handler {

	function main($linktxt, $conf, $linkHandlerKeyword, $linkHandlerValue, $link_param, &$pObj) {
		$this->pObj = &$pObj;
		
		$pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig($GLOBALS['TSFE']->id);
		$linkConfig = $pageTSConfig['RTE.']['default.']['linkhandler.'];
		
		if ( !is_array($linkConfig) )
			return $linktxt;
			
		$linkHandlerData = t3lib_div::trimExplode(':', $linkHandlerValue);
		if ( !isset($linkConfig[$linkHandlerData[0].'.']) )
			return $linktxt;
		
		$localcObj = t3lib_div::makeInstance('tslib_cObj');
		$row = $this->getRecordRow($linkHandlerData[0], $linkHandlerData[1]);
		
		$localcObj->start($row, '');
		
		$lconf = array();
		if (is_array($linkConfig[$linkHandlerData[0].'.'][$row['pid'].'.'])) {
			$lconf = $linkConfig[$linkHandlerData[0].'.'][$row['pid'].'.'];
		} else {
			$lconf = $linkConfig[$linkHandlerData[0].'.']['default.'];
		}
		$link_paramA = t3lib_div::unQuoteFilenames($link_param, true);
		$linkClass = $link_paramA[2] == '-' ? '' : $link_paramA[2];
		$lconf['ATagParams'] = $this->pObj->getATagParams($conf) . ($linkClass ? ' class="' . $linkClass . '"' : '');

		if ($link_paramA[3]) {
			$lconf['title'] = $link_paramA[3];
		}
		
		// remove the tinymce_rte specific attributes
		unset( $lconf['select'], $lconf['sorting'] );
		
		return $localcObj->typoLink($linktxt, $lconf);
	}
	
	function getRecordRow($table,$uid) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid='.intval($uid).$this->pObj->enableFields($table), '', '');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return $row;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/hooks/class.tx_tinymce_rte_handler.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/hooks/class.tx_tinymce_rte_handler.php']);
}
?>
