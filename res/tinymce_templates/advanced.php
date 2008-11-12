<?php
/**
 * Example of using PHP/TYPO3 userfunction in the TinyMCE Templates plugin 
 *
 * !!! REMEMBER: this file NEEDS to be placed inside typo3conf (or any folder inside) !!!
 *
 *  # Example of usage in TSConfig:
 *  RTE.default.init {
 *    theme_advanced_buttons3 := addToList(template)
 *    template_templates {
 *      0 {
 *        title = PHP based template/content
 *        src = typo3conf/ext/tinymce_rte/res/tinymce_templates/advanced.php
 *        description = Inserts name and email of current TYPO3 BE user
 *      }
 *    }
 *  }
 *
 * @author	Thomas Allmer <thomas.allmer@webteam.at>
 */

	unset($MCONF);
	// you can set the MOD and BACK path on your own. If not set they will be auto-configured...
	$TYPO3_MOD_PATH = "";
	$BACK_PATH = "";

	// try to auto-configure the MOD_PATH and BACK_PATH
	if (!$TYPO3_MOD_PATH) $TYPO3_MOD_PATH = autoMOD_PATH();
	define('TYPO3_MOD_PATH', '../' . $TYPO3_MOD_PATH);
	if (!$BACK_PATH) $BACK_PATH = autoBACK_PATH();
	
	require_once ( $BACK_PATH . 'init.php' );
	require_once ( $BACK_PATH . 'template.php' );
	global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
	$LANG->includeLLFile('EXT:tinymce_rte/res/tinymce_templates/advanced.xml');
	
	/*	HERE STARTS YOUR CONTENT
	*	this is an easy example and should just give you an idea of what is possible....
	*/
	
	$content  = '<div style="border: 1px solid green; padding: 5px; margin: 10px 2px;">';
	$content .= $LANG->getLL('author') . ": " . ($BE_USER->user['realName'] ? $BE_USER->user['realName'] : $BE_USER->user['username']);
	$content .= $BE_USER->user['email'] ? '<br />' . $LANG->getLL('email') . ': <a href="mailto:'.$BE_USER->user['email'].'">'.$BE_USER->user['email'].'</a>' : '';
	$content .= '</div>';	
	
	echo $content;
	
	/*	FUNCTIONS FOR AUTOCONFIGURE
	*	you might want to keep them
	*/	
	
	function autoMOD_PATH() {
		$TYPO3_MOD_PATH = dirname(__FILE__) . '/';
		if ( strpos($TYPO3_MOD_PATH, '\\') !== true )
			$TYPO3_MOD_PATH = str_replace('\\', '/', $TYPO3_MOD_PATH);
		if ( strpos($TYPO3_MOD_PATH, 'typo3conf') !== true )
			$TYPO3_MOD_PATH = substr($TYPO3_MOD_PATH, strpos($TYPO3_MOD_PATH, 'typo3conf'));
		return $TYPO3_MOD_PATH;
	}
	
	function autoBACK_PATH() {
		$count = count_chars(TYPO3_MOD_PATH);
		for($i = 0; $i < $count[47]-1; $i++)
			$BACK_PATH .= '../';
		$BACK_PATH .= 'typo3/';
		return $BACK_PATH;
	}
	
?>