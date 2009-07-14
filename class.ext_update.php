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

// TYPO3 version check
if (TYPO3_branch>=4.2) {
	require_once('patcher/class.pmkpatcher.php');
	require_once(PATH_typo3.'contrib/jsmin/jsmin.php');
}

/**
 * Class for updating/patching TinyMCE files for specific TYPO3 usage
 *
 * @author	 Peter Klein <peter@umloud.dk>
 */

class ext_update {
	var $desc = array(
		'required' => array(
			'title' => 'TinyMCE/TYPO3 compability patches',
			'desc' => 'If you manually update the TinyMCE code to a new version, you will need to apply the patches below to the newly installed files, in order to make them compatible with TYPO3.<br />NOTE: The TYPO3 TinyMCE RTE extension is ALWAYS shipped with these patches installed. So if you have downloaded it from TER, you don\'t need to apply these patches.'
		),
		'optional' => array(
			'title' => 'TinyMCE/TYPO3 optional patches',
			'desc' => 'These patches are optional, and IS NOT applied to the TinyMCE RTE as default, but some you can to apply later if you need/want.'
		),
		'clearcache' => array(
			'title' => 'Clear gzip cache',
			'desc' => 'When you change parts of the TinyMCE installation manually, or install language packs, the cached gzip files still contains the old settings. In order to activate the new settings, the gzip cache must be cleared. If that doesn\'t help, try clearing the browser cache too.'
		),
	);

	/**
	 * Main function, returning the HTML content of the module
	 *
	 * @return	string		HTML
	 */
	function main()	{
		global $BACK_PATH;
		
		// PHP and TYPO3 version check
		if (intval(phpversion())<5 || TYPO3_branch<4.2) {
			return '<div style="padding-top: 10px;"></div><p>Updater <strong>requires</strong> PHP5 and TYPO3 v4.2+</p>';
		}
		
		$this->diffPath['required'] = t3lib_extMgm::extPath('tinymce_rte').'patcher/diffs/';
		$this->diffPath['optional'] = t3lib_extMgm::extPath('tinymce_rte').'patcher/diffs2/';
		$this->filePath = t3lib_extMgm::extPath('tinymce_rte');
		
		if (t3lib_div::_GP('update') && $descKey=t3lib_div::_GP('desckey')) {
			$content = '<h2 class="typo3-tstemplate-ceditor-subcat">'.$this->desc[$descKey]['title'].'</h2>';
			$patches = t3lib_div::_GP($descKey.'patch');
			$updated = false;
			foreach ($patches as $patchName => $value) {
				if ($value = intval($value)) {
					$content .= $this->appplyPatch($patchName,$this->diffPath[$descKey],$value-1);
					$updated = true;
				}
			}
			$content .= '<div style="padding-top: 10px;"></div>';
			$content .= ($updated) ? 'Patching done..' : 'Nothing selected to patch..';
			$content .= '<div style="padding-top: 25px;"></div><a href="'.htmlspecialchars(t3lib_div::linkThisScript()).'" class="typo3-goBack"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/goback.gif','width="14" height="14"').' alt="" />Go back</a>';
			// Remove cache files
			$this->removeCachedFiles();
		}
		elseif (t3lib_div::_GP('clear')) {
			$content = '<h2 class="typo3-tstemplate-ceditor-subcat">Clearing gzip and TYPO3 cache.</h2>';
			$content .= 'Gzip and TYPO3 cache cleared..';
			$content .= '<div style="padding-top: 25px;"></div><a href="'.htmlspecialchars(t3lib_div::linkThisScript()).'" class="typo3-goBack"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/goback.gif','width="14" height="14"').' alt="" />Go back</a>';
			// Remove cache files
			$this->removeCachedFiles();
		}
		else {
			$content .= '
				<script type="text/javascript">
					/* <![CDATA[ */
					function difftoggle(val,descKey) {
						els = document.getElementsByTagName("select");
						var matchregexp = new RegExp("^"+descKey+"patch");
						for (var i=0;i<els.length;i++) {
							if (els[i].name.match(matchregexp)) els[i].selectedIndex = val;
						}
						return false;
					}
				/* ]]> */
				</script>';
			// display form
			$content .= $this->displayDiffs($this->diffPath['required'],'required');
			$content .= $this->displayDiffs($this->diffPath['optional'],'optional');
			$content .= $this->displayRemoveCache();
		}
		return $content;
	}
	
	function appplyPatch($patchName,$diffPath,$rev) {
		$diffData = @file_get_contents($diffPath.$patchName.'.diff');
		if (!$diffData) return 'Could not read '.$patchName.'.diff';
		
		$diffArray = pmkpatcher::parseDiff($diffData,$this->filePath,$rev);
		if (!is_array($diffArray)) return 'No diff data found in '.$patchName.'.diff';
		
		$diffArray = pmkpatcher::applyDiff($diffArray,$this->filePath,$rev);
		if (!is_array($diffArray)) return $diffArray;
		
		foreach ($diffArray as $diffParts) {
			if (isset($diffParts['patcheddata'])) {
				$content .= '<h3>'.($rev ? 'Unpatching' : 'Patching').' file "'.$diffParts['destinationfile'].'"</h3>';
				$fileExt = strtolower(pathinfo($diffParts['destinationfile'],PATHINFO_EXTENSION));
				if ($fileExt=='js' && $rev && $diffParts['sourcefile']!=$diffParts['destinationfile']) {
					// If unpatch is selected, and the file is a javascript file,
					// Then the .src file is just copied into the destination instead of unpatching.
					// Unpatching of .js files is not possible due to the minifying of the javascripts.
					$patchedData = @file_get_contents($this->filePath.$diffParts['sourcefile']);
					$content .= '<p>File unpatched sucessfully.</p>';
				}
				else {
					$patchedData = $diffParts['patcheddata'];
					$content .= '<p>File '.($rev ? 'unpatched' : 'patched').' sucessfully.</p>';
				}
				if ($fileExt=='js' && $diffParts['sourcefile']!=$diffParts['destinationfile'] && $diffParts['sourcefile']!='/dev/null' ) {
					// Minify data if extension is .js
					$patchedData = JSMin::minify($patchedData);
				}
				@mkdir( dirname($this->filePath.$diffParts['destinationfile']), 0777, 1 );
				file_put_contents($this->filePath.$diffParts['destinationfile'],$patchedData);
			}
			else {
				// Process custom marker for adding or removing binary files
				$type = $diffParts['type'];
				$content .= '<h3>'.($rev ? 'Removing' : 'Adding').' file "'. ( ($rev && $type == 'binary-file') ? $diffParts['sourcefile'] : $diffParts['destinationfile']) .'"</h3>';
				
				if ($type == 'binary-file') {
					if ( $diffParts['sourcefile'] && $diffParts['destinationfile']!='/dev/null' ) {
						//make undo copy if file exists
						if (file_exists($this->path.$diffParts['sourcefile']) || is_file($this->path.$diffParts['sourcefile'])) {
							@mkdir(dirname($this->filePath.$diffParts['sourcefile']) . '/undo/', 0777, 1);
							if (!@copy($this->filePath.$diffParts['destinationfile'], dirname($this->filePath.$diffParts['sourcefile']) . '/undo/' . basename($this->filePath.$diffParts['sourcefile']) ))
								$content .= '<p>Failed to add undo file: '.$diffParts['destinationfile'].'</p>';
							else
								$content .= '<p>Undo file added sucessfully.</p>';
						}
						
						@mkdir( dirname($this->filePath.$diffParts['destinationfile']), 0777, 1 );
						if (!@copy($this->filePath.$diffParts['sourcefile'], $this->filePath.$diffParts['destinationfile']))
							$content .= '<p>Failed to add file: '.$diffParts['destinationfile'].'</p>';
						else
							$content .= '<p>File added sucessfully.</p>';
							
					} elseif ($diffParts['destinationfile']=='/dev/null') {
						if (!@unlink($this->filePath.$diffParts['sourcefile']))
							$content .= '<p>Failed to remove file: '.$diffParts['sourcefile'].'</p>';
						else
							$content .= '<p>File removed sucessfully.</p>';
					}
				}
				
				if (!$type) {
					if ($diffParts['sourcefile']=='/dev/null') {
						if (!@unlink($this->filePath.$diffParts['destinationfile']))
							$content .= '<p>Failed to remove file: '.$diffParts['destinationfile'].'</p>';
						else
							$content .= '<p>File removed sucessfully.</p>';
					}
				}
				
			}
		}
		return $content;
	}
	
	function displayDiffs($diffPath,$descKey) {
		$diffFiles =  t3lib_div::getFilesInDir($diffPath,'diff',0,'1');
		if (!count($diffFiles)) return false;
		$content = '';
		foreach ($diffFiles as $diffFile) {
			$diffData = @file_get_contents($diffPath.$diffFile);
			if (!$diffData) continue;
			$diffArray = pmkpatcher::parseDiff($diffData,$this->filePath);
			if (!is_array($diffArray)) return false;
			$name = htmlspecialchars(pathinfo($diffPath.$diffFile,PATHINFO_FILENAME));
			$content .= '<dl class="typo3-tstemplate-ceditor-constant">
	<dt class="typo3-tstemplate-ceditor-label">'.implode('<br />',$diffArray[0]['comment']).'</dt>
	<dt class="typo3-dimmed">['.$name.']</dt>';
			$files = array();
			foreach ($diffArray as $diffParts) {
				$files[] = $diffParts['destinationfile'];
			}
			$content .= '
	<dd>The following file'.(count($files)>1 ? 's':'').' will be modified:<br />'.implode('<br />',$files).'</dd>';
		$content .= '
	<dd>
		<div class="typo3-tstemplate-ceditor-row">
			<select name="'.$descKey.'patch['.$name.']">
				<option value="0" selected="selected">Do nothing</option>
				<option value="1">Patch file</option>
				<option value="2">Unpatch file</option>
			</select>
		</div>
	</dd>
</dl>';
		}
		if (!$content) return false;
		$content = '<div style="padding-top: 10px;"></div><h2 class="typo3-tstemplate-ceditor-subcat bgColor5">'.$this->desc[$descKey]['title'].'</h2>' .
'<div style="padding-bottom: 10px;">'.$this->desc[$descKey]['desc'].'</div>'.
			$content .
			'<input type="button" name="patchall" value="Select Patch all" onclick="return difftoggle(1,\''.$descKey.'\')" /> ' .
			'<input type="button" name="unpatchall" value="Select Unpatch all" onclick="return difftoggle(2,\''.$descKey.'\')" /> ' .
			'<input type="button" name="resetall" value="Reset all" onclick="return difftoggle(0,\''.$descKey.'\')" /> ' .
			'<input name="update" value="Update" type="submit" style="font-weight: bold;"/>
			<input type="hidden" name="desckey" value="'.$descKey.'" />';
		return '<form name="'.$descKey.'_form" action="'.htmlspecialchars(t3lib_div::linkThisScript()).'" method="post">'.$content.'</form>';
		
	}
	
	function displayRemoveCache() {
		$content = '<div style="padding-top: 10px;"></div><h2 class="typo3-tstemplate-ceditor-subcat bgColor5">'.$this->desc['clearcache']['title'].'</h2>
		'.$this->desc['clearcache']['desc'].'<br />
 		<div style="padding-top: 10px;"><input name="clear" value="Clear gzip cache" type="submit" style="font-weight: bold;"/></div>';
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
