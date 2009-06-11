<?php
class ux_SC_mod_user_setup_index extends SC_mod_user_setup_index {
	
	function printContent()	{
		$this->content = preg_replace('/top\.TYPO3ModuleMenu\.refreshMenu\(\);/si', 'top.location = "'.t3lib_div::getIndpEnv('TYPO3_SITE_URL').'typo3/backend.php";', $this->content);
		echo $this->content;
	}
	
}
?>
