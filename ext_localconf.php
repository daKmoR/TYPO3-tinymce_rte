<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

// override TS_links_rte( ), TS_images_db()
$TYPO3_CONF_VARS['BE']['XCLASS']['t3lib/class.t3lib_parsehtml_proc.php'] = t3lib_extMgm::extPath($_EXTKEY).'class.ux_t3lib_parsehtml_proc.php';

// XCLASSes the "User settings" module, so that the browser is reloaded if language is changed.
$TYPO3_CONF_VARS['BE']['XCLASS']['ext/setup/mod/index.php'] = t3lib_extMgm::extPath($_EXTKEY).'ux_index.php';

// enable the RTE in the BE by default
if(!$TYPO3_CONF_VARS['BE']['RTEenabled']) $TYPO3_CONF_VARS['BE']['RTEenabled'] = 1;

// register the RTE to TYPO3
$TYPO3_CONF_VARS['BE']['RTE_reg'][$_EXTKEY] = array('objRef' => 'EXT:'.$_EXTKEY.'/class.tx_tinymce_rte_base.php:&tx_tinymce_rte_base');

// load default PageTS config from static
t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/static/pageTSConfig.ts">');

// Get extension configuration
$extConf = unserialize($_EXTCONF);

// set default if value is undefined (for backward compatibility as most users will have it set empty)
if( $extConf['pageLoadConfigFile'] === '' ) {
	$extConf['pageLoadConfigFile'] = 'EXT:tinymce_rte/static/pageLoad.ts';
}
// load mandatory pageLoadConfigFile
t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:' . $extConf['pageLoadConfigFile'] . '">');

if ( $extConf['loadConfig'] !== '' ) {
	t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:' . $extConf['loadConfig'] . '">');
}

// load default SetupTS config from static
t3lib_extMgm::addTypoScript($_EXTKEY,'setup','<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/static/setupTSConfig.ts">',43);

//add linkhandler for "record"
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['record'] = 'EXT:tinymce_rte/hooks/class.tx_tinymce_rte_handler.php:&tx_tinymce_rte_handler';

// Enable preStartPageHook hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preStartPageHook'][] = 'EXT:' . $_EXTKEY . '/hooks/class.tx_tinymce_rte_header.php:&tx_tinymce_rte_header->preStartPageHook';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['EXT:feeditadvanced/view/class.tx_feeditadvanced_adminpanel.php']['addIncludes'][] = 'EXT:' . $_EXTKEY . '/hooks/class.tx_tinymce_rte_feeditadv.php:&tx_tinymce_rte_feeditadv';

// user function to force typolink creation of every link
if (!class_exists('user_tinymce_rte', false)) {
	class user_tinymce_rte {
		function isNotAnchor($content,$conf) {
			return preg_match('/\s*href\s*=\s*"[^"]+"\s*/i', $content) ? 1 : 0;
		}
		function getHref($content,$conf) {
			if (preg_match('/\s*href\s*=\s*"([^"]+)"\s*/i', $content, $regs))
				$content = htmlspecialchars_decode($regs[1]);
			return $content;
		}
		function getATagParams($content,$conf) {
			return preg_replace('/\s*href\s*=\s*"[^"]+"\s*/i', ' ', $content);
		}
	}
}

?>