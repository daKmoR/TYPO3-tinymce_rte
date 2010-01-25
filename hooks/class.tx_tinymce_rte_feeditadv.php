<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Marcus Schwemer (schwemer@netzwerkberatung.de)
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

require_once(t3lib_extMgm::extPath('tinymce_rte').'class.tx_tinymce_rte_base.php');

class tx_tinymce_rte_feeditadv {

	function addIncludes() {
		global $TSFE;
		
		$tinymce_rte = t3lib_div::makeInstance('tx_tinymce_rte_base');
		
		$pageTSconfig = t3lib_BEfunc::getPagesTSconfig( $TSFE->id );
		$config = $pageTSconfig['RTE.']['default.'];
		
		$config = $tinymce_rte->init( $config );
		
		$myIncludes = $tinymce_rte->getCoreScript( $config );
		$myIncludes .= "\n" . $tinymce_rte->getInitScript( $config['init.'] );
		
		return $myIncludes;
	}

}
?>