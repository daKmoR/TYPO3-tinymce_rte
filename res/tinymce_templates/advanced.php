<?php

/**
 * some usual TYPO3 stuff you might use $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS
 * additional nice things:
 * $LANG is set to the actual language of the current CE (so in a multilanguage site you don't need to worry about it)
 * $this->pageId // the current page (for BE only)
 * $this->templateId // what template is currently selected
 * $this->conf //the RTE config
 * $tinymce_rte // an instance of the tinymce_rte baseClass
 * 
 * Example on how to include it:
RTE.default.init.template_templates {
	 0 {
		title = TYPO3 mod
		description = Use an TYPO3 mod to get data easily into the the TinyMCE Template System
		include = EXT:tinymce_rte/res/tinymce_templates/advanced.php
	}
}
 * 
 * @author Thomas Allmer <thomas.allmer@webteam.at>
 *
 */
 
	$LANG->includeLLFile('EXT:tinymce_rte/res/tinymce_templates/locallang_advanced.xml');
	$this->content .= '<div style="border: 1px solid green; padding: 5px; margin: 10px 2px;">';
	$this->content .= $LANG->getLL('author') . ": " . ($BE_USER->user['realName'] ? $BE_USER->user['realName'] : $BE_USER->user['username']);
	$this->content .= $BE_USER->user['email'] ? '<br />' . $LANG->getLL('email') . ': <a href="mailto:'.$BE_USER->user['email'].'">'.$BE_USER->user['email'].'</a>' : '';
	
	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
		'uid, title',
		'pages',
		'',
		'',
		'',
		'5'
	);
	
	$this->content .= '<h3> List 5 pages:</h3>
		<table style="border: 1px solid #000;">
			<tr>
				<th>uid</th>
				<th>name</th>
			</tr>
	';
	while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {		 
		$is_admin = ($row['admin']) ? 'yes' : 'no';
		$this->content .= '<tr><td>' . $row['uid'] . '</td><td>' . $row['title'] . '</td></tr>';
	}	
	$this->content .= '</table>';
	$this->content .= '</div>';

?>