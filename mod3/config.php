<?php

	unset($MCONF);
	require ('conf.php');
	require ($BACK_PATH.'init.php');
	
	$RTEsetup = $GLOBALS["BE_USER"]->getTSConfig("RTE",t3lib_BEfunc::getPagesTSconfig("")); 
	$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup["properties"],"","");
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
