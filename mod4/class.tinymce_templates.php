<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Peter Klein (peter@umloud.dk)
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Example of using PHP/TYPO3 userfunction in the TinyMCE Templates plugin 
 *
 *  # Example of usage in TSConfig:
 *  RTE.default.init {
 *    theme_advanced_buttons3 := addToList(template)
 *    template_popup_width = 560
 *    template_popup_height = 480
 *    template_templates {
 *      0 {
 *        title = PHP based template/content
 *        src = typo3conf/ext/tinymce_rte/mod4/class.tinymce_templates.php
 *        description = Inserts name and email of current TYPO3 BE user
 *      }
 *    }
 *  }
 *
 * @author	Peter Klein <peter@umloud.dk>
 */
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
$LANG->includeLLFile('EXT:tinymce_rte/mod4/locallang.xml');

class tx_tinymce_templates {
	
	function main() {
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		
		$this->content = $LANG->getLL('author').': '.($BE_USER->user['realName'] ? $BE_USER->user['realName'] : $BE_USER->user['username']);
		$this->content .= $BE_USER->user['email'] ? '<br />'.$LANG->getLL('email').': <a href="mailto:'.$BE_USER->user['email'].'">'.$BE_USER->user['email'].'</a>' : '';
		$this->content = '<div style="border: 2px inset;padding: 2px 3px 3px 3px;margin: 10px 2px;">'.$this->content.'</div>';
	}
	
	function printContent() {
		echo $this->content;
	}

}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/mod4/class.tinymce_templates.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/mod4/class.tinymce_templates.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_tinymce_templates');
$SOBE->main();
$SOBE->printContent();
?>

