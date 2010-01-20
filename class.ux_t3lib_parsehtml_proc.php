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
 * allow to use this functions with relative path; search for //XCLASS  to see changes
 * 3 changes
 *
 * @author Thomas Allmer <thomas.allmer@webteam.at>
 *
 */

class ux_t3lib_parsehtml_proc extends t3lib_parsehtml_proc {
	
	function TS_links_rte($value) {
		$value = $this->TS_AtagToAbs($value);

			// Split content by the TYPO3 pseudo tag "<link>":
		$blockSplit = $this->splitIntoBlock('link',$value,1);
		foreach($blockSplit as $k => $v)	{
			$error = '';
			if ($k%2)	{	// block:
				$tagCode = t3lib_div::unQuoteFilenames(trim(substr($this->getFirstTag($v),0,-1)),true);
				$link_param = $tagCode[1];
				$href = '';
				$siteUrl = $this->siteUrl();
					// Parsing the typolink data. This parsing is roughly done like in tslib_content->typolink()
				if(strstr($link_param,'@'))	{		// mailadr
					$href = 'mailto:'.preg_replace('/^mailto:/i','',$link_param);
				} elseif (substr($link_param,0,1)=='#') {	// check if anchor
					$href = $siteUrl.$link_param;
				} else {
					$fileChar=intval(strpos($link_param, '/'));
					$urlChar=intval(strpos($link_param, '.'));

						// Detects if a file is found in site-root OR is a simulateStaticDocument.
					list($rootFileDat) = explode('?',$link_param);
					$rFD_fI = pathinfo($rootFileDat);
					if (trim($rootFileDat) && !strstr($link_param,'/') && (@is_file(PATH_site.$rootFileDat) || t3lib_div::inList('php,html,htm',strtolower($rFD_fI['extension']))))	{
						$href = $siteUrl.$link_param;
					} elseif($urlChar && (strstr($link_param,'//') || !$fileChar || $urlChar<$fileChar))	{	// url (external): If doubleSlash or if a '.' comes before a '/'.
						if (!preg_match('/^[a-z]*:\/\//',trim(strtolower($link_param))))	{$scheme='http://';} else {$scheme='';}
						$href = $scheme.$link_param;
					} elseif($fileChar)	{	// file (internal)
						$href = $siteUrl.$link_param;
					} else {	// integer or alias (alias is without slashes or periods or commas, that is 'nospace,alphanum_x,lower,unique' according to tables.php!!)
							// Splitting the parameter by ',' and if the array counts more than 1 element it's a id/type/parameters triplet
						$pairParts = t3lib_div::trimExplode(',', $link_param, TRUE);
						$idPart = $pairParts[0];
						$link_params_parts = explode('#', $idPart);
						$idPart = trim($link_params_parts[0]);
						$sectionMark = trim($link_params_parts[1]);
						if (!strcmp($idPart,''))	{ $idPart=$this->recPid; }	// If no id or alias is given, set it to class record pid
							// Checking if the id-parameter is an alias.
						if (!t3lib_div::testInt($idPart))	{
							list($idPartR) = t3lib_BEfunc::getRecordsByField('pages','alias',$idPart);
							$idPart = intval($idPartR['uid']);
						}
						$page = t3lib_BEfunc::getRecord('pages', $idPart);
						if (is_array($page))	{	// Page must exist...
							// XCLASS changed from $href = $siteUrl .'?id=' . $idPart . ($pairParts[2] ? $pairParts[2] : '') . ($sectionMark ? '#' . $sectionMark : '');
							$href = $idPart . ($pairParts[2] ? $pairParts[2] : '') . ($sectionMark ? '#' . $sectionMark : '');
							// linkHandler - allowing links to start with registerd linkHandler e.g.. "record:"
						} elseif (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler'][array_shift(explode(':', $link_param))])) {
							$href = $link_param;
						} else {
							#$href = '';
							$href = $siteUrl.'?id='.$link_param;
							$error = 'No page found: '.$idPart;
						}
					}
				}

				// Setting the A-tag:
				$bTag = '<a href="'.htmlspecialchars($href).'"'.
							($tagCode[2]&&$tagCode[2]!='-' ? ' target="'.htmlspecialchars($tagCode[2]).'"' : '').
							($tagCode[3]&&$tagCode[3]!='-' ? ' class="'.htmlspecialchars($tagCode[3]).'"' : '').
							($tagCode[4] ? ' title="'.htmlspecialchars($tagCode[4]).'"' : '').
							($error ? ' rteerror="'.htmlspecialchars($error).'" style="background-color: yellow; border:2px red solid; color: black;"' : '').	// Should be OK to add the style; the transformation back to databsae will remove it...
							'>';
				$eTag = '</a>';
				$blockSplit[$k] = $bTag.$this->TS_links_rte($this->removeFirstAndLastTag($blockSplit[$k])).$eTag;
			}
		}

			// Return content:
		return implode('',$blockSplit);
	}
	
	/**
	 * Transformation handler: 'ts_images' / direction: "db"
	 * Processing images inserted in the RTE.
	 * This is used when content goes from the RTE to the database.
	 * Images inserted in the RTE has an absolute URL applied to the src attribute. This URL is converted to a relative URL
	 * If it turns out that the URL is from another website than the current the image is read from that external URL and moved to the local server.
	 * Also "magic" images are processed here.
	 *
	 * @param	string		The content from RTE going to Database
	 * @return	string		Processed content
	 */
	function TS_images_db($value)	{

			// Split content by <img> tags and traverse the resulting array for processing:
		$imgSplit = $this->splitTags('img',$value);
		foreach($imgSplit as $k => $v)	{
			if ($k%2)	{	// image found, do processing:

					// Init
				$attribArray = $this->get_tag_attributes_classic($v,1);
				$siteUrl = $this->siteUrl();
				$sitePath = str_replace (t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST'), '', $siteUrl);

				$absRef = trim($attribArray['src']);		// It's always a absolute URL coming from the RTE into the Database.

					// make path absolute if it is relative and we have a site path wich is not '/'
				$pI=pathinfo($absRef);
				if($sitePath AND !$pI['scheme'] && t3lib_div::isFirstPartOfStr($absRef,$sitePath)) {
						// if site is in a subpath (eg. /~user_jim/) this path needs to be removed because it will be added with $siteUrl
					$absRef = substr($absRef,strlen($sitePath));
					$absRef = $siteUrl.$absRef;
				}

					// External image from another URL? In that case, fetch image (unless disabled feature).
				if (!t3lib_div::isFirstPartOfStr($absRef,$siteUrl) && !$this->procOptions['dontFetchExtPictures'])	{
					$externalFile = $this->getUrl($absRef);	// Get it
					if ($externalFile)	{
						$pU = parse_url($absRef);
						$pI=pathinfo($pU['path']);

						if (t3lib_div::inList('gif,png,jpeg,jpg',strtolower($pI['extension'])))	{
							$filename = t3lib_div::shortMD5($absRef).'.'.$pI['extension'];
							$origFilePath = PATH_site.$this->rteImageStorageDir().'RTEmagicP_'.$filename;
							$C_origFilePath = PATH_site.$this->rteImageStorageDir().'RTEmagicC_'.$filename.'.'.$pI['extension'];
							if (!@is_file($origFilePath))	{
								t3lib_div::writeFile($origFilePath,$externalFile);
								t3lib_div::writeFile($C_origFilePath,$externalFile);
							}
							$absRef = $siteUrl.$this->rteImageStorageDir().'RTEmagicC_'.$filename.'.'.$pI['extension'];

							$attribArray['src']=$absRef;
							$params = t3lib_div::implodeAttributes($attribArray,1);
							$imgSplit[$k] = '<img '.$params.' />';
						}
					}
				}

					// Check image as local file (siteURL equals the one of the image)
				
				if ( (strpos($absRef, 'http://') === FALSE) AND (strpos($absRef, 'https://') === FALSE) AND (strpos($absRef, 'ftp://') === FALSE) )	{  //XCLASS changed from: if (t3lib_div::isFirstPartOfStr($absRef,$siteUrl))	{
					$path = rawurldecode(substr($absRef,strlen($siteUrl)));	// Rel-path, rawurldecoded for special characters.
					$path = $absRef; //XCLASS added
					$filepath = t3lib_div::getFileAbsFileName($path);		// Abs filepath, locked to relative path of this project.
					

						// Check file existence (in relative dir to this installation!)
					if ($filepath && @is_file($filepath))	{

							// If "magic image":
						$pathPre=$this->rteImageStorageDir().'RTEmagicC_';
						if (t3lib_div::isFirstPartOfStr($path,$pathPre))	{
							// Find original file:
							$pI=pathinfo(substr($path,strlen($pathPre)));
							$filename = substr($pI['basename'],0,-strlen('.'.$pI['extension']));
							$origFilePath = PATH_site.$this->rteImageStorageDir().'RTEmagicP_'.$filename;
							if (@is_file($origFilePath))	{
								$imgObj = t3lib_div::makeInstance('t3lib_stdGraphic');
								$imgObj->init();
								$imgObj->mayScaleUp=0;
								$imgObj->tempPath=PATH_site.$imgObj->tempPath;

								$curInfo = $imgObj->getImageDimensions($filepath);	// Image dimensions of the current image
								$curWH = $this->getWHFromAttribs($attribArray);	// Image dimensions as set in the image tag
									// Compare dimensions:
								if ($curWH[0]!=$curInfo[0] || $curWH[1]!=$curInfo[1])	{
									$origImgInfo = $imgObj->getImageDimensions($origFilePath);	// Image dimensions of the current image
									$cW = $curWH[0];
									$cH = $curWH[1];
										$cH = 1000;	// Make the image based on the width solely...
									$imgI = $imgObj->imageMagickConvert($origFilePath,$pI['extension'],$cW.'m',$cH.'m');
									if ($imgI[3])	{
										$fI=pathinfo($imgI[3]);
										@copy($imgI[3],$filepath);	// Override the child file
											// Removing width and heigth form style attribute
										$attribArray['style'] = preg_replace('/((?:^|)\s*(?:width|height)\s*:[^;]*(?:$|;))/si', '', $attribArray['style']);
										$attribArray['width']=$imgI[0];
										$attribArray['height']=$imgI[1];
										$params = t3lib_div::implodeAttributes($attribArray,1);
										$imgSplit[$k]='<img '.$params.' />';
									}
								}
							}

						} elseif ($this->procOptions['plainImageMode']) {	// If "plain image" has been configured:

								// Image dimensions as set in the image tag, if any
							$curWH = $this->getWHFromAttribs($attribArray);
							if ($curWH[0]) $attribArray['width'] = $curWH[0];
							if ($curWH[1]) $attribArray['height'] = $curWH[1];

								// Removing width and heigth form style attribute
							$attribArray['style'] = preg_replace('/((?:^|)\s*(?:width|height)\s*:[^;]*(?:$|;))/si', '', $attribArray['style']);

								// Finding dimensions of image file:
							$fI = @getimagesize($filepath);

								// Perform corrections to aspect ratio based on configuration:
							switch((string)$this->procOptions['plainImageMode'])	{
								case 'lockDimensions':
									$attribArray['width']=$fI[0];
									$attribArray['height']=$fI[1];
								break;
								case 'lockRatioWhenSmaller':	// If the ratio has to be smaller, then first set the width...:
									if ($attribArray['width']>$fI[0])	$attribArray['width'] = $fI[0];
								case 'lockRatio':
									if ($fI[0]>0)	{
										$attribArray['height']=round($attribArray['width']*($fI[1]/$fI[0]));
									}
								break;
							}

								// Compile the image tag again:
							$params = t3lib_div::implodeAttributes($attribArray,1);
							$imgSplit[$k]='<img '.$params.' />';
						}
					} else {	// Remove image if it was not found in a proper position on the server!

							// Commented out; removing the image tag might not be that logical...
						#$imgSplit[$k]='';
					}
				}

					// Convert abs to rel url
				if ($imgSplit[$k])	{
					$attribArray=$this->get_tag_attributes_classic($imgSplit[$k],1);
					$absRef = trim($attribArray['src']);
					if (t3lib_div::isFirstPartOfStr($absRef,$siteUrl))	{
						$attribArray['src'] = $this->relBackPath.substr($absRef,strlen($siteUrl));
						if (!isset($attribArray['alt']))	$attribArray['alt']='';		// Must have alt-attribute for XHTML compliance.
						$imgSplit[$k]='<img '.t3lib_div::implodeAttributes($attribArray,1,1).' />';
					}
				}
			}
		}
		return implode('',$imgSplit);
	}	
		
		
}
	
?>
