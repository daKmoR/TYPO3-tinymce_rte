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
 * an easy way to get TYPO3 data into the TinyMCE Template System
 *
 * @author Thomas Allmer <thomas.allmer@webteam.at>
 *
 */
 
 
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
require_once(t3lib_extMgm::extPath('tinymce_rte').'class.tx_tinymce_rte_base.php');

class tx_tinymce_rte_templates {

	var $content = '';
	var $pageId = 0;
	var $templateId = 0;
	
	function init() {
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		
		if( !t3lib_div::_GP('pageId') || !t3lib_div::_GP('templateId') || (t3lib_div::_GP('sys_language_uid') < 0) || (t3lib_div::_GP('sys_language_uid') == "")  ) {
			die('if you want to us this mod you need at least to define pageId, templateId and sys_language_uid as GET parameter. Example path/to/TinyMCETemplate.php?pageid=7&templateId=12&sys_language_uid=0');
		}
		
		$this->pageId = t3lib_div::_GP('pageId');
		$this->templateId = t3lib_div::_GP('templateId');
		$this->sys_language_uid = t3lib_div::_GP('sys_language_uid');
		
		$this->conf = t3lib_BEfunc::getPagesTSconfig( $this->pageId );
		
		$this->conf = $this->conf['RTE.']['default.'];
		
		$tinymce_rte = t3lib_div::makeInstance('tx_tinymce_rte_base');
		$this->conf = $tinymce_rte->init( $this->conf );
		$row = array('pid' => $this->pageId, 'sys_language_uid' => $this->sys_language_uid );
		
		$this->conf = $tinymce_rte->fixTinyMCETemplates( $this->conf, $row );
		
		if ( is_array($this->conf['TinyMCE_templates.'][$this->templateId]) && is_file($this->conf['TinyMCE_templates.'][$this->templateId]['include']) )
		  include_once($this->conf['TinyMCE_templates.'][$this->templateId]['include']);
		
	}
	
	function printContent() {
		echo $this->content;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/mod5/class.tx_tinymce_rte_templates.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/mod5/class.tx_tinymce_rte_templates.php']);
}
	
?>