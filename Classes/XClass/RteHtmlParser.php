<?php
namespace Rte\TinymceRte\Xclass;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Thomas Allmer <d4kmor@gmail.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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

/**
 *
 *
 * @package tinymce_rte
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */

class RteHtmlParser extends \TYPO3\CMS\Core\Html\RteHtmlParser {

	/**
	 * Transformation handler: 'ts_links' / direction: "rte"
	 * Converting <link tags> to <A>-tags
	 *
	 * @param string $value Content input
	 * @return string Content output
	 * @see TS_links_rte()
	 * @todo Define visibility
	 */
	public function TS_links_rte($value) {
		$conf = array();
		$value = $this->TS_AtagToAbs($value);
		// Split content by the TYPO3 pseudo tag "<link>":
		$blockSplit = $this->splitIntoBlock('link', $value, 1);
		$siteUrl = $this->siteUrl();
		foreach ($blockSplit as $k => $v) {
			$error = '';
			$external = FALSE;
			// Block
			if ($k % 2) {
				$tagCode = \TYPO3\CMS\Core\Utility\GeneralUtility::unQuoteFilenames(trim(substr($this->getFirstTag($v), 0, -1)), TRUE);
				$link_param = $tagCode[1];
				$href = '';
				// Parsing the typolink data. This parsing is roughly done like in tslib_content->typolink()
				if (strstr($link_param, '@')) {
					// mailadr
					$href = 'mailto:' . preg_replace('/^mailto:/i', '', $link_param);
				} elseif (substr($link_param, 0, 1) == '#') {
					// check if anchor
					$href = $siteUrl . $link_param;
				} else {
					// Check for FAL link-handler keyword:
					list($linkHandlerKeyword, $linkHandlerValue) = explode(':', trim($link_param), 2);
					if ($linkHandlerKeyword === 'file') {
						$href = $siteUrl . '?' . $linkHandlerKeyword . ':' . rawurlencode($linkHandlerValue);
					} else {
						$fileChar = intval(strpos($link_param, '/'));
						$urlChar = intval(strpos($link_param, '.'));
						// Parse URL:
						$pU = parse_url($link_param);
						// Detects if a file is found in site-root.
						list($rootFileDat) = explode('?', $link_param);
						$rFD_fI = pathinfo($rootFileDat);
						if (trim($rootFileDat) && !strstr($link_param, '/') && (@is_file((PATH_site . $rootFileDat)) || \TYPO3\CMS\Core\Utility\GeneralUtility::inList('php,html,htm', strtolower($rFD_fI['extension'])))) {
							$href = $siteUrl . $link_param;
						} elseif ($pU['scheme'] || $urlChar && (!$fileChar || $urlChar < $fileChar)) {
							// url (external): if has scheme or if a '.' comes before a '/'.
							$href = $link_param;
							if (!$pU['scheme']) {
								$href = 'http://' . $href;
							}
							$external = TRUE;
						} elseif ($fileChar) {
							// It is an internal file or folder
							// Try to transform the href into a FAL reference
							$fileOrFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($link_param);
							if ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
								// It's a folder
								$folderIdentifier = $fileOrFolderObject->getIdentifier();
								$href = $siteUrl . '?file:' . rawurlencode($folderIdentifier);
							} elseif ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
								// It's a file
								$fileIdentifier = $fileOrFolderObject->getIdentifier();
								$fileObject = $fileOrFolderObject->getStorage()->getFile($fileIdentifier);
								$href = $siteUrl . '?file:' . $fileObject->getUid();
							} else {
								$href = $siteUrl . $link_param;
							}
						} else {
							// integer or alias (alias is without slashes or periods or commas, that is 'nospace,alphanum_x,lower,unique' according to tables.php!!)
							// Splitting the parameter by ',' and if the array counts more than 1 element it's a id/type/parameters triplet
							$pairParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $link_param, TRUE);
							$idPart = $pairParts[0];
							$link_params_parts = explode('#', $idPart);
							$idPart = trim($link_params_parts[0]);
							$sectionMark = trim($link_params_parts[1]);
							if (!strcmp($idPart, '')) {
								$idPart = $this->recPid;
							}
							// If no id or alias is given, set it to class record pid
							// Checking if the id-parameter is an alias.
							if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($idPart)) {
								list($idPartR) = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField('pages', 'alias', $idPart);
								$idPart = intval($idPartR['uid']);
							}
							$page = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $idPart);
							if (is_array($page)) {
								// Page must exist...
								// Xclass changed from: $href = $siteUrl . '?id=' . $idPart . ($pairParts[2] ? $pairParts[2] : '') . ($sectionMark ? '#' . $sectionMark : '');
								$href = $idPart . ($pairParts[2] ? $pairParts[2] : '') . ($sectionMark ? '#' . $sectionMark : '');
							} elseif (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler'][array_shift(explode(':', $link_param))])) {
								$href = $link_param;
							} else {
								$href = $siteUrl . '?id=' . $link_param;
								$error = 'No page found: ' . $idPart;
							}
						}
					}
				}
				// Setting the A-tag:
				$bTag = '<a href="' . htmlspecialchars($href) . '"' . ($tagCode[2] && $tagCode[2] != '-' ? ' target="' . htmlspecialchars($tagCode[2]) . '"' : '') . ($tagCode[3] && $tagCode[3] != '-' ? ' class="' . htmlspecialchars($tagCode[3]) . '"' : '') . ($tagCode[4] ? ' title="' . htmlspecialchars($tagCode[4]) . '"' : '') . ($external ? ' data-htmlarea-external="1"' : '') . ($error ? ' rteerror="' . htmlspecialchars($error) . '" style="background-color: yellow; border:2px red solid; color: black;"' : '') . '>';
				$eTag = '</a>';
				// Modify parameters
				if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksRte_PostProc']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksRte_PostProc'])) {
					$parameters = array(
						'conf' => &$conf,
						'currentBlock' => $v,
						'url' => $href,
						'tagCode' => $tagCode,
						'external' => $external,
						'error' => $error
					);
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksRte_PostProc'] as $objRef) {
						$processor = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($objRef);
						$blockSplit[$k] = $processor->modifyParamsLinksRte($parameters, $this);
					}
				} else {
					$blockSplit[$k] = $bTag . $this->TS_links_rte($this->removeFirstAndLastTag($blockSplit[$k])) . $eTag;
				}
			}
		}
		// Return content:
		return implode('', $blockSplit);
	}

	/**
	 * Transformation handler: 'ts_images' / direction: "db"
	 * Processing images inserted in the RTE.
	 * This is used when content goes from the RTE to the database.
	 * Images inserted in the RTE has an absolute URL applied to the src attribute. This URL is converted to a relative URL
	 * If it turns out that the URL is from another website than the current the image is read from that external URL and moved to the local server.
	 * Also "magic" images are processed here.
	 *
	 * @param string $value The content from RTE going to Database
	 * @return string Processed content
	 * @todo Define visibility
	 */
	public function TS_images_db($value) {
		// Split content by <img> tags and traverse the resulting array for processing:
		$imgSplit = $this->splitTags('img', $value);
		foreach ($imgSplit as $k => $v) {
			// image found, do processing:
			if ($k % 2) {
				// Init
				$attribArray = $this->get_tag_attributes_classic($v, 1);
				$siteUrl = $this->siteUrl();
				$sitePath = str_replace(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'), '', $siteUrl);
				// It's always a absolute URL coming from the RTE into the Database.
				$absRef = trim($attribArray['src']);
				// Make path absolute if it is relative and we have a site path wich is not '/'
				$pI = pathinfo($absRef);
				if ($sitePath and !$pI['scheme'] && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($absRef, $sitePath)) {
					// If site is in a subpath (eg. /~user_jim/) this path needs to be removed because it will be added with $siteUrl
					$absRef = substr($absRef, strlen($sitePath));
					$absRef = $siteUrl . $absRef;
				}
				// External image from another URL? In that case, fetch image (unless disabled feature).
				if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($absRef, $siteUrl) && !$this->procOptions['dontFetchExtPictures']) {
					// Get it
					$externalFile = $this->getUrl($absRef);
					if ($externalFile) {
						$pU = parse_url($absRef);
						$pI = pathinfo($pU['path']);
						if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('gif,png,jpeg,jpg', strtolower($pI['extension']))) {
							$fileName = \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5($absRef) . '.' . $pI['extension'];
							$folder = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier($this->rteImageStorageDir());
							if ($folder instanceof \TYPO3\CMS\Core\Resource\Folder) {
								$fileObject = $folder->createFile($fileName)->setContents($externalFile);
								/** @var $magicImageService \TYPO3\CMS\Core\Resource\Service\MagicImageService */
								$magicImageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Service\\MagicImageService');
								$imageConfiguration = array(
									'width' => $attribArray['width'],
									'height' => $attribArray['height'],
									'maxW' => 300,
									'maxH' => 1000
								);
								$magicImage = $magicImageService->createMagicImage($fileObject, $imageConfiguration, $this->rteImageStorageDir());
								if ($magicImage instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
									$filePath = $magicImage->getForLocalProcessing(FALSE);
									$imageInfo = @getimagesize($filePath);
									$attribArray['width'] = $imageInfo[0];
									$attribArray['height'] = $imageInfo[1];
									$attribArray['data-htmlarea-file-uid'] = $fileObject->getUid();
									$absRef = $siteUrl . substr($filePath, strlen(PATH_site));
								}
								$attribArray['src'] = $absRef;
								$params = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeAttributes($attribArray, 1);
								$imgSplit[$k] = '<img ' . $params . ' />';
							}
						}
					}
				}
				// Check image as local file (siteURL equals the one of the image)
				// Xclass changed from: if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($absRef, $siteUrl)) {
				if ( (strpos($absRef, 'http://') === FALSE) AND (strpos($absRef, 'https://') === FALSE) AND (strpos($absRef, 'ftp://') === FALSE) )	{
					// Rel-path, rawurldecoded for special characters.
					// Xclass changed from: $path = rawurldecode(substr($absRef, strlen($siteUrl)));
					$path = $absRef;
					// Abs filepath, locked to relative path of this project.
					$filepath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($path);
					// Check file existence (in relative dir to this installation!)
					if ($filepath && @is_file($filepath)) {
						// If "magic image":
						$magicFolder = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier(
							$this->rteImageStorageDir()
						);
						if ($magicFolder instanceof \TYPO3\CMS\Core\Resource\Folder) {
							$magicFolderPath = $magicFolder->getPublicUrl();
							$pathPre = $magicFolderPath . 'RTEmagicC_';
							if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($path, $pathPre)) {
								// Find original file
								if ($attribArray['data-htmlarea-file-uid']) {
									$originalFileObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFileObject($attribArray['data-htmlarea-file-uid']);
								} else {
									// Backward compatibility mode
									$pI = pathinfo(substr($path, strlen($pathPre)));
									$filename = substr($pI['basename'], 0, -strlen(('.' . $pI['extension'])));
									$origFilePath = PATH_site . $magicFolderPath . 'RTEmagicP_' . $filename;
									if (@is_file($origFilePath)) {
										$originalFileObject = $magicFolder->addFile($origFilePath, $filename, 'changeName');
										$attribArray['data-htmlarea-file-uid'] = $originalFileObject->getUid();
									}
								}
								if (!empty($originalFileObject) && $originalFileObject instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
									/** @var $magicImageService \TYPO3\CMS\Core\Resource\Service\MagicImageService */
									$magicImageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Service\\MagicImageService');
									// Image dimensions of the current image
									$imageDimensions = @getimagesize($filepath);
									// Image dimensions as set on the img tag
									$imgTagDimensions = $this->getWHFromAttribs($attribArray);
									// If the dimensions have changed, we re-create the magic image
									if ($imgTagDimensions[0] != $imageDimensions[0] || $imgTagDimensions[1] != $imageDimensions[1]) {
										$imageConfiguration = array(
											'width' => $imgTagDimensions[0],
											'height' => $imgTagDimensions[1],
											'maxW' => 300,
											'maxH' => 1000
										);
										// TODO: Perhaps the existing magic image should be overridden?
										$magicImage = $magicImageService->createMagicImage($originalFileObject, $imageConfiguration, $this->rteImageStorageDir());
										if ($magicImage instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
											$filePath = $magicImage->getForLocalProcessing(FALSE);
											$imageInfo = @getimagesize($filePath);
											// Removing width and height from any style attribute
											$attribArray['style'] = preg_replace('/((?:^|)\\s*(?:width|height)\\s*:[^;]*(?:$|;))/si', '', $attribArray['style']);
											$attribArray['width'] = $imageInfo[0];
											$attribArray['height'] = $imageInfo[1];
											$attribArray['src'] = $this->siteURL() . substr($filePath, strlen(PATH_site));
											$params = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeAttributes($attribArray, 1);
											$imgSplit[$k] = '<img ' . $params . ' />';
										}
									}
								}
							} elseif ($this->procOptions['plainImageMode']) {
								// If "plain image" has been configured:
								// Image dimensions as set in the image tag, if any
								$curWH = $this->getWHFromAttribs($attribArray);
								if ($curWH[0]) {
									$attribArray['width'] = $curWH[0];
								}
								if ($curWH[1]) {
									$attribArray['height'] = $curWH[1];
								}
								// Removing width and heigth form style attribute
								$attribArray['style'] = preg_replace('/((?:^|)\\s*(?:width|height)\\s*:[^;]*(?:$|;))/si', '', $attribArray['style']);
								// Finding dimensions of image file:
								$fI = @getimagesize($filepath);
								// Perform corrections to aspect ratio based on configuration:
								switch ((string) $this->procOptions['plainImageMode']) {
								case 'lockDimensions':
									$attribArray['width'] = $fI[0];
									$attribArray['height'] = $fI[1];
									break;
								case 'lockRatioWhenSmaller':
									if ($attribArray['width'] > $fI[0]) {
										$attribArray['width'] = $fI[0];
									}
								case 'lockRatio':
									if ($fI[0] > 0) {
										$attribArray['height'] = round($attribArray['width'] * ($fI[1] / $fI[0]));
									}
									break;
								}
								// Compile the image tag again:
								$params = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeAttributes($attribArray, 1);
								$imgSplit[$k] = '<img ' . $params . ' />';
							}
						}
					}
				}
				// Convert abs to rel url
				if ($imgSplit[$k]) {
					$attribArray = $this->get_tag_attributes_classic($imgSplit[$k], 1);
					$absRef = trim($attribArray['src']);
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($absRef, $siteUrl)) {
						$attribArray['src'] = $this->relBackPath . substr($absRef, strlen($siteUrl));
						if (!isset($attribArray['alt'])) {
							$attribArray['alt'] = '';
						}
						// Must have alt-attribute for XHTML compliance.
						$imgSplit[$k] = '<img ' . \TYPO3\CMS\Core\Utility\GeneralUtility::implodeAttributes($attribArray, 1, 1) . ' />';
					}
				}
			}
		}
		return implode('', $imgSplit);
	}


}