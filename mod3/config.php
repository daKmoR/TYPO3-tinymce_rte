<?php

	unset($MCONF);
	require ('conf.php');
	require ($BACK_PATH.'init.php');
	
	$pageId = 0;
	if( t3lib_div::_GP('pageId') ) {
		$pageId = t3lib_div::_GP('pageId');
	}
	$pageTSconfig = t3lib_BEfunc::getPagesTSconfig( $pageId );
	
	$config = $pageTSconfig['RTE.']['default.']['spellcheck.'];
	
	// General settings
	$config['general.engine'] = $config['general.']['engine'];
	
	//$config['general.engine'] = 'PSpell';
	//$config['general.engine'] = 'PSpellShell';
	//$config['general.remote_rpc_url'] = 'http://some.other.site/some/url/rpc.php';

	// PSpell settings
	$config['PSpell.mode'] = $config['PSpell.']['mode'];
	$config['PSpell.spelling'] = $config['PSpell.']['spelling'];
	$config['PSpell.jargon'] = $config['PSpell.']['jargon'];
	$config['PSpell.encoding'] = $config['PSpell.']['encoding'];

	// PSpellShell settings
	$config['PSpellShell.mode'] = $config['PSpellShell.']['mode'];
	$config['PSpellShell.aspell'] = $config['PSpellShell.']['aspell'];
	$config['PSpellShell.tmp'] = $config['PSpellShell.']['tmp'];

	// Windows PSpellShell settings
	//$config['PSpellShell.aspell'] = '"c:\Program Files\Aspell\bin\aspell.exe"';
	//$config['PSpellShell.tmp'] = 'c:/temp';
?>
