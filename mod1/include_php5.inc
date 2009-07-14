<?php
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'] as $classData) {
					$processObject = &t3lib_div::getUserObj($classData);
					
						if(!($processObject instanceof t3lib_browseLinksHook)) {
							throw new UnexpectedValueException('$processObject must implement interface t3lib_browseLinksHook', 1195039394);
						}
	
					$parameters = array();
					$processObject->init($this, $parameters);
					$this->hookObjects[] = $processObject;
				}
			}
?>
