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

require_once('patcher/class.pmkpatcher.php');
require_once(PATH_typo3.'contrib/jsmin/jsmin.php');

/**
 * Class for updating/patching TinyMCE files for specific TYPO3 usage
 *
 * @author	 Peter Klein <peter@umloud.dk>
 */

class ext_update {

	/**
	 * Main function, returning the HTML content of the module
	 *
	 * @return	string		HTML
	 */
	function main()	{
		global $BACK_PATH;
		
		$this->diffPath = t3lib_extMgm::extPath('tinymce_rte').'patcher/diffs/';
		$this->filePath = t3lib_extMgm::extPath('tinymce_rte');
		$content = '';
		
		if (t3lib_div::_GP('update')) {
			$content = '<h2 class="typo3-tstemplate-ceditor-subcat">Applying TinyMCE/TYPO3 compability patches</h2>';
			$patches = t3lib_div::_GP('patch');
			$updated = false;
			foreach ($patches as $patchName => $value) {
				if ($value = intval($value)) {
					$content .= $this->appplyPatch($patchName,$this->diffPath,$value-1);
					$updated = true;
				}
			}
			$content .= '<div style="padding-top: 10px;"></div>';
			$content .= ($updated) ? 'Patching done..' : 'Noting selected to patch..';
			$content .= '<div style="padding-top: 25px;"></div><a href="'.htmlspecialchars(t3lib_div::linkThisScript()).'" class="typo3-goBack"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/goback.gif','width="14" height="14"').' alt="" />Go back</a>';
			// Remove cache files
			$this->removeCachedFiles();
		}
		else {
			// display form
			$content .= $this->displayDiffs($this->diffPath);
		}
		return $content;
	}
	
	function appplyPatch($patchName,$diffPath,$rev) {
		$diffData = @file_get_contents($diffPath.$patchName.'.diff');
		if (!$diffData) return 'Could not read '.$patchName.'.diff';
		$diffArray = pmkpatcher::parseDiff($diffData,$this->filePath,$rev);
		if (!count($diffArray)) return 'No diff data found in '.$patchName.'.diff';
		$content .= '<h3>'.($rev ? 'Unpatching' : 'Patching').' file "'.$diffArray['destinationfile'].'"</h3>';
		$fileExt = strtolower(pathinfo($diffArray['destinationfile'],PATHINFO_EXTENSION));
		if ($fileExt=='js' && $rev) {
			// If unpatch is selected, and the file is a javascript file,
			// Then the .src file is just copied into the destination instead of unpatching.
			// Unpatching of .js files is not possible due to the minifying of the javascripts.
			$patchedData = @file_get_contents($this->filePath.$diffArray['sourcefile']);
			$content .= '<p>File unpatched sucessfully.</p>';
		}
		else {
			$patched = pmkpatcher::applyDiff($diffArray,$this->filePath,$rev);
			if (is_array($patched)) {
				$patchedData = $patched['patcheddata'];
				$content .= '<p>File '.($rev ? 'unpatched' : 'patched').' sucessfully.</p>';
			}
			else {
				// Error msg
				$content .= $patched;
				return $content;
			}
		}
		if ($fileExt=='js') {
			// Minify data if extension is .js
			$patchedData = JSMin::minify($patchedData);
		}
		file_put_contents($this->filePath.$diffArray['destinationfile'],$patchedData);
		return $content;
	}
	
	function displayDiffs($diffPath) {
		$diffFiles =  t3lib_div::getFilesInDir($diffPath,'diff',0,'1');
		if (!count($diffFiles)) return false;
		$content = '';
		foreach ($diffFiles as $diffFile) {
			$diffData = @file_get_contents($diffPath.$diffFile);
			if (!$diffData) continue;
			$diffArray = pmkpatcher::parseDiff($diffData,$this->filePath);
			if (!count($diffArray)) return false;
			$name = htmlspecialchars(pathinfo($diffPath.$diffFile,PATHINFO_FILENAME));
			$content .= '<dl class="typo3-tstemplate-ceditor-constant">
	<dt class="typo3-tstemplate-ceditor-label">Patch for '.$diffArray['destinationfile'].'</dt>
	<dt class="typo3-dimmed">['.$name.']</dt>';
			if (count($diffArray['comment'])) {
			$content .= '
	<dd>'.implode('<br />',$diffArray['comment']).'</dd>';
			}
			$content .= '
	<dd>
		<div class="typo3-tstemplate-ceditor-row">
			<select name="patch['.$name.']">
				<option value="0" selected="selected">Do nothing</option>
				<option value="1">Patch file</option>
				<option value="2">Unpatch file</option>
			</select>
		</div>
	</dd>
</dl>';
		}
		if (!$content) return false;
		$content = '<h2 class="typo3-tstemplate-ceditor-subcat">TinyMCE/TYPO3 compability patches</h2>'.
			$content .'<input name="update" value="Update" type="submit">' .
			$this->displayDetails();
		return '<form name="tinymcepatcher_form" action="'.htmlspecialchars(t3lib_div::linkThisScript()).'" method="post">'.$content.'</form>';
		
	}
	
	function displayDetails() {
		$content = '<h3 class="uppercase">Details:</h3>
<table border="0" cellpadding="1" cellspacing="2">
	<tbody>
		<tr class="bgColor5">
			<td><strong>General information:</strong></td>
		</tr>
		<tr class="bgColor4" >
			<td>If you manually update the TinyMCE code to a new version, you will need to apply the above patches to the newly installed files, in order to make it compatible with TYPO3.</td>
		</tr>
		<tr class="bgColor4">
			<td>NOTE: The TYPO3 TinyMCE RTE extension is ALWAYS shipped with the patches installed, so if you have downloaded it from TER, you don\'t need to apply the patches.</td>
		</tr>
	</tbody>
</table>';
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
