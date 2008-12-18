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
 * adds the core into the header to only load it once
 *
 * @author Thomas Allmer <thomas.allmer@webteam.at>
 *
 */
 
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
require_once(t3lib_extMgm::extPath('tinymce_rte').'class.tx_tinymce_rte_base.php');

class tx_tinymce_rte_header {

	/**
	 * Hook-function: inject JavaScript code before the BE page is compiled
	 * called in typo3/template.php:startPage
	 *
	 * @param array $parameters (not very usable, as it just contains a title..)
	 * @param template $pObj
	 */
	function preStartPageHook($parameters, $pObj) {
		// Only add JS if this is the top TYPO3 frame/document
		if ( $pObj->bodyTagId == 'typo3-backend-php' ) {
			// $pageTSconfig = t3lib_BEfunc::getPagesTSconfig(1);
			// $this->conf = $pageTSconfig['RTE.']['default.'];
			
			include (t3lib_extMgm::extPath('tinymce_rte') . 'hooks/tinymce_rte_conf.php');
			
			$tinymce_rte = t3lib_div::makeInstance('tx_tinymce_rte_base');
			$this->conf = $tinymce_rte->init( $this->conf );
			
			// print_r($this->conf);
			// die();
			
			$pObj->JScode .= $tinymce_rte->getCoreScript( $this->conf );
			
			// print_r( $tinymce_rte->getCoreScript( $this->conf ) );
			// die();
			
			$this->conf['init.']['mode'] = 'none';
			$pObj->JScode .= $tinymce_rte->getInitScript( $this->conf['init.'] );
			
			$pObj->JScode .= '
				<script type="text/javascript">
					function typo3filemanager(field_name, url, type, win) {
						document.getElementById("content").contentWindow.list_frame.typo3filemanager(field_name, url, type, win);
					}
				</script>
			';
			
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/hooks/class.tx_tinymce_rte_header.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinymce_rte/hooks/class.tx_tinymce_rte_header.php']);
}
?>