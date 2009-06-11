<?php

/**
 * some usual TYPO3 stuff you might use $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS
 * additional nice things:
 * $LANG is set to the actual language of the current CE (so in a multilanguage site you don't need to worry about it)
 * $this->pageId // the current page
 * $this->templateId // what template is currently selected
 * $this->conf //the RTE config
 * $this->pageTSconfig //complete pageTSconfig
 * $this->tinymce_rte // an instance of the tinymce_rte baseClass
 * for example if you need the complete setupTSconfig do
 *  $setupTSconfig = $this->tinymce_rte->getSetupTS( $this->pageId );
 * 
 * Example on how to include it:
RTE.default.init.template_templates {
	 10 {
		title = TYPO3 mod
		description = Use an TYPO3 mod to get data easily into the the TinyMCE Template System
		include = EXT:tinymce_rte/res/tinymce_templates/advanced.php
	}
}
 * 
 * @author Thomas Allmer <thomas.allmer@webteam.at>
 *
 */
	
	$this->content .= print_r(t3lib_div::_GET(), true);

?>