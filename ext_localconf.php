<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

// override TS_links_rte( )
$TYPO3_CONF_VARS['BE']['XCLASS']['t3lib/class.t3lib_parsehtml_proc.php'] = t3lib_extMgm::extPath($_EXTKEY).'class.ux_t3lib_parsehtml_proc.php';

// enable the RTE in the BE by default
if(!$TYPO3_CONF_VARS['BE']['RTEenabled']) $TYPO3_CONF_VARS['BE']['RTEenabled'] = 1;

// register the RTE to TYPO3
$TYPO3_CONF_VARS['BE']['RTE_reg'][$_EXTKEY] = array('objRef' => 'EXT:'.$_EXTKEY.'/class.tx_tinymce_rte_base.php:&tx_tinymce_rte_base');

// load default config from static
t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/static/pageTSConfig.ts">');


?>