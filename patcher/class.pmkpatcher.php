<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2008 Peter Klein (peter@umloud.dk)
*  All rights reserved
*
*  This script is part free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
* 
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license 
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Example:
 * $diff = file_get_contents('diff/dif6.diff');
 * $path = 'F:/apachefriends/xampp/htdocs/typo3-4.2/typo3conf/ext/tinymce_rte/';
 * $path = '';
 *
 * $x = pmkpatcher::patch($diff,$path);
 * if (!is_array($x)) echo $x;
 * else echo $x['data'];
 */

/** 
 * class.pmkpatcher.php
 *
 * Class for patching files using unified .diff format
 *
 * @author	Peter Klein <peter@umloud.dk8>
 */
class pmkpatcher {
	protected $errorMsg ='';
	protected $destinationFile = '';
	protected $sourceFile = '';

  // -- Public Static Methods --------------------------------------------------

	/**
	 * Patch file using .diff file in unified diff format.
	 *
	 * @param	string			Content of unified .diff file to process
	 * @param	string			Otional path which is prepended to the source name found in the .diff
	 * @param	boolean			Optional flag for reversing the patching (patch will be removed from destination file)
	 * @return	array/string	If patching was sucessful, an array containing the patched file (data), an optional comment (comment), the source file name (source) and the destination file name (destination).
	 *							If patching failed, a string is returned, containing the error msg.
	 */
	public static function patch($diffFile,$path='',$rev=false) {
		$patcher = new pmkpatcher($path);
		$diffArray = $patcher->_parseDiff($diffFile,$rev);
		$patcheData = $patcher->_applyDiff($diffArray,$rev);
		return ($patcheData) ? array(
			'data' => $patcheData,
			'source' => $patcher->sourceFile,
			'destination' => $patcher->destinationFile,
			'comment' => $patcher->comment
		) : $patcher->errorMsg;
	}
	
	/**
	 * Unpatch file using .diff file in unified diff format.
	 *
	 * @param	string			Content of unified .diff file to process
	 * @param	string			Otional path which is prepended to the source name found in the .diff
	 * @return	array/string	If patching was sucessful, an array containing the patched file (data), an optional comment (comment), the source file name (source) and the destination file name (destination).
	 *							If patching failed, a string is returned, containing the error msg.
	 */
	public static function unpatch($diffFile,$path='') {
		return pmkpatcher::patch($diffFile,$path,true);
	}
	
	/**
	 * Returns parsed .diff file as array
	 *
	 * @param	string			Content of unified .diff file to process
	 * @param	string			Otional path which is prepended to the source name found in the .diff
	 * @param	boolean			Optional flag for reversing the patching (patch will be removed from destination file)
	 * @return	array/string	If parsing was sucessful, an array containing the parsed diff data (diffdata), an optional comment (comment), the source file name (source) and the destination file name (destination).
	 *							If parsing failed, a string is returned, containing the error msg.
	 */
	public static function parseDiff($diffFile,$path='',$rev=false) {
		$patcher = new pmkpatcher($path);
		$diffArray = $patcher->_parseDiff($diffFile,$rev);
		return ($diffArray) ? $diffArray : $patcher->errorMsg;
	}
	
	public static function applyDiff($diffArray,$path='',$rev=false) {
		$patcher = new pmkpatcher($path);
		$patchedData = $patcher->_applyDiff($diffArray,$rev);
		return ($patchedData) ? array('patcheddata' => $patchedData) : $patcher->errorMsg;
	}
	// -- Public Instance Methods ------------------------------------------------
	
	public function __construct($path = '') {
		$this->path = $path;
	}

  // -- Protected Instance Methods ---------------------------------------------
	
	protected function _parseDiff($diffFile, $rev=false) {
		
		$lines = preg_split('/\r\n|\r|\n/', $diffFile);
		$diffArray = array();
		$comment = array();
		foreach ($lines as $line) {
			// Continue looping until we find ---- at the beginning of a line.
			// Everything before that is treated as a comment.
			if (!preg_match('/^---/', $line)) {
				$comment[] = preg_replace('%^//\s*%', '', $line);
				continue;
			}
			$this->comment = $diffArray['comment'] = $comment;
			if (preg_match('/^--- ([^\t]*)/', $line, $regs)) {
				$this->sourceFile = $diffArray['sourcefile'] = $regs[1];
			} else {
				// No source filename specified.
				$this->errorMsg = 'No source filename specified.<br />';
				return false;
			}
			$line = current($lines);
			if (preg_match('/^\+\+\+ ([^\t]*)/', $line, $regs)) {
				$this->destinationFile = $diffArray['destinationfile'] = $regs[1];
			} else {
				// No destination filename specified.
				$this->errorMsg = 'No destination filename specified.<br />';
				return false;
			}
			$line = next($lines);
			$range = array();
			while (preg_match('/^@@\s+-(\d+)(,(\d+))?\s+\+(\d+)(,(\d+))?\s+@@$/', $line, $regs) || $line!==false) {
				$srcline = intval($regs[4]);
				$srcsize = ($regs[6]==='') ? 1 : intval($regs[6]);
				$dstline = intval($regs[1]);
				$dstsize = ($regs[3]==='') ? 1 : intval($regs[3]);
				$line = next($lines);
				$data = array();
				while ($line!==false) {
					$data[] = $line;
					$line = next($lines);
					if (preg_match('/^@@\s+-(\d+)(,(\d+))?\s+\+(\d+)(,(\d+))?\s+@@$/', $line)) {
						break;
					}
				}
				$range[] = array(
					'srcline' => $rev ? $dstline : $srcline,
					'srcsize' => $rev ? $dstsize : $srcsize,
					'dstline' => $rev ? $srcline : $dstline,
					'dstsize' => $rev ? $srcsize : $dstsize,
					'data' => $data
				);
			}
			$diffArray['diff'] = $range;
		}
		return $diffArray;
	}
	
	protected function _applyDiff($diffArray, $rev=false) {
		// Process diff data
		//$sourceFile = $this->path.($rev ? $diffArray['destinationfile'] : $diffArray['sourcefile']);
		$sourceFile = $this->path.$diffArray['sourcefile'];
		$source = @file_get_contents($sourceFile);
		if (!$source) {
			$this->errorMsg = '<p>Error: sourcefile not found.<br/>'.$sourceFile.'</p>';
			return false;
		}
		$sLines = preg_split('/\r\n|\r|\n/', $source);
		$destLines = $sLines;
		$offset = 0;
		foreach ($diffArray['diff'] as $data) {
			$diffPart = array_slice($this->array_filterPM($data['data'],'+',$rev),0,$data['srcsize']);
			$comparePart = array_slice($destLines,$data['dstline']-1+$offset,count($diffPart));
			// Compare diff part with (presumed) same part in destination file.
			$fail = array_diff($diffPart,$comparePart);
			if ($fail) {
				$this->errorMsg = '<p>Error: Diff file doesn\'t match sourcefile.<br/>';
				foreach ($fail as $linenum => $line) {
					$this->errorMsg .= 'Line: '.$linenum.' => '.htmlspecialchars($line).'<br />';
				}
				$this->errorMsg .= '</p>';
				return false;
			}
			else {
				// Apply diff data to destination file.
				$replace = $this->array_filterPM($data['data'],'-',$rev);
				// Make sure no unwanted lines (such as extra linefeeds) is included in the replacement data
				$replace = array_slice($replace,0,$data['srcsize']);
				array_splice($destLines,$data['dstline']-1+$offset,$data['dstsize'],$replace);
				$offset += $data['srcsize']-$data['dstsize'];
			}
		}
		// Get rid of the extra linefeed at the end, before returning result
		//$destLines = array_slice($destLines,0,count($sLines)+$offset+1);
		return implode(chr(10),$destLines);
	}
	
	protected static function array_filterPM($arr,$excl,$rev=false) {
		if ($rev) $excl = $excl=='+' ? '-' : '+';
		$res = array();
		array_walk($arr, create_function('$v,$k,$res','if ($v{0}!="'.$excl.'") $res[] = (string)substr($v,1);') ,&$res);
		return $res;
	}
}
?>
