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
 
require_once('class.tx_tinymce_rte_templates.php');

// Make instance:
$TinyMCE_template = t3lib_div::makeInstance('tx_tinymce_rte_templates');
$TinyMCE_template->init();
$TinyMCE_template->printContent();
	
?>