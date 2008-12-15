<?php

	unset($MCONF);
	require ('conf.php');
	require ($BACK_PATH.'init.php');
	require_once(t3lib_extMgm::extPath('tinymce_rte').'class.tx_tinymce_rte_base.php');	
	
	$thisConfig['loadConfig'] = 'EXT:tinymce_rte/static/full.ts';
	
	$tinymce_rte = t3lib_div::makeInstance('tx_tinymce_rte_base');
	$thisConfig = $tinymce_rte->init( $thisConfig );
	$thisConfig = $thisConfig['spellcheck.'];
	
	// General settings
	$config['general.engine'] = $thisConfig['general.']['engine'];
	
	//$config['general.engine'] = 'PSpell';
	//$config['general.engine'] = 'PSpellShell';
	//$config['general.remote_rpc_url'] = 'http://some.other.site/some/url/rpc.php';

	// PSpell settings
	$config['PSpell.mode'] = $thisConfig['PSpell.']['mode'];
	$config['PSpell.spelling'] = $thisConfig['PSpell.']['spelling'];
	$config['PSpell.jargon'] = $thisConfig['PSpell.']['jargon'];
	$config['PSpell.encoding'] = $thisConfig['PSpell.']['encoding'];

	// PSpellShell settings
	$config['PSpellShell.mode'] = $thisConfig['PSpellShell.']['mode'];
	$config['PSpellShell.aspell'] = $thisConfig['PSpellShell.']['aspell'];
	$config['PSpellShell.tmp'] = $thisConfig['PSpellShell.']['tmp'];

	// Windows PSpellShell settings
	//$config['PSpellShell.aspell'] = '"c:\Program Files\Aspell\bin\aspell.exe"';
	//$config['PSpellShell.tmp'] = 'c:/temp';
?>
