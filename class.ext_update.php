<?php
	/***************************************************************
	*  Copyright notice
	*
	*  (c) 2008 Peter Klein <peter@umloud.dk>
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
	*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	*  GNU General Public License for more details.
	*
	*  This copyright notice MUST APPEAR in all copies of the script!
	***************************************************************/

require_once('patcher/class.phppatcher.php');
require_once('patcher/class.jsmin.php');
/**
 * Class for updating/patching TinyMCE files for specific TYPO3 usage
 *
 * @author	 Peter Klein <peter@umloud.dk>
 */
class ext_update {
	var $patches = array(
			'fullscreen' => array(
				'desc' => 'Patch window size of fullscreen plugin, in order to make TYPO3\'s save buttons visible.',
				'diff' => 'patcher/fullscreen.diff'
			)
		 );
	/**
	 * Main function, returning the HTML content of the module
	 *
	 * @return	string		HTML
	 */
	function main()	{
		if (t3lib_div::_GP('update') ) {
			$class = t3lib_div::makeInstance("PhpPatcher");
			$patchObj = new $class(t3lib_extMgm::extPath('tinymce_rte'));
			foreach ($this->patches as $plugin => $conf) {
				if (intval(t3lib_div::_GP($plugin))) {
					$patchObj->ClearCache();
					if ($diff = file_get_contents($patchObj->root.$conf['diff'])) {
						if ($patchObj->Merge($diff)) {
							if ($patchObj->ApplyPatch()) {
								$content.= '<p>Plugin <strong>'.$plugin.'</strong> patched sucessfully.</p>';
							}
							else {
								$content.= '<p><strong>'.$plugin.'</strong> patching failed<br />Error: '.preg_replace('|'.preg_quote($patchObj->root).'|i','',$patchObj->msg).'</p>';
							}
						}
						else {
							$content.= '<p><strong>'.$plugin.'</strong> patching failed<br />Error: '.preg_replace('|'.preg_quote($patchObj->root).'|i','',$patchObj->msg).'</p>';
						}
					}
					else {
						$content.= '<p><strong>'.$plugin.'</strong> patching failed<br />Error: Diff file '.$conf['diff'].' not found.</p>';
					}
				}
				else {
				 // maybe some unpatch function here?
				}
				$this->removeCachedFiles();
			}
		}
		else {
			$content = '<form name="tinymcepatcher_form" action="'.htmlspecialchars(t3lib_div::linkThisScript()).'" method="post">';
			foreach ($this->patches as $plugin => $conf) {
				$content.= '<p><input type="checkbox" name="'.$plugin.'" value="1" checked /> <strong>'.$plugin.':</strong> '.$conf['desc'].'</p>';
			}
			$content .= '<p>&nbsp;</p><p><input type="submit" name="update" value="Update" /></p>';
			$content .= '</form>';
		}
		return $content;
	}

	/**
	 * Removes TinyMCE gzip cache files and TYPO3 cache files.
	 *
	 * @return	void
	 */
	function removeCachedFiles() {
		$path = PATH_site . 'typo3temp/tinymce_rte/';
		if (is_dir($path)) {
			// Remove TinyMCE gzip cache files.
			$cfiles = t3lib_div::getFilesInDir($path);
			foreach ($cfiles as $cfile) {
				if (preg_match('/tiny_mce_\w{32}\.gz/', $cfile)) {
					@unlink($path.$cfile);
				}
			}
		}
		// Remove TYPO3 cache files.
		t3lib_extMgm::removeCacheFiles();
	}

	/**
	 * access is always allowed
	 *
	 * @return	boolean		Always returns true
	 */
	function access() {
		return true;
	}

	
}

// Include extension?
if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tinymce_rte/class.ext_update.php']))	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tinymce_rte/class.ext_update.php']);
}


?>
