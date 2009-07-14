<?php
	if (!defined ('TYPO3_MODE')) die ('Access denied.');

	// remove of default RTE Fullsize as TinyMCE's fullsize is much better [brought to you by Peter Klein]
	t3lib_div::loadTCA('tt_content');
	unset($TCA['tt_content']['columns']['bodytext']['config']['wizards']['RTE']);
?>