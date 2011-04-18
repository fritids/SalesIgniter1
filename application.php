<?php
/*
	I.T. Web Experts, Rental Store v2
	http://www.itwebexperts.com

	Copyright (c) 2009 I.T. Web Experts

	This script and it's source is not redistributable
*/

	require('includes/application_top.php');
	
	$action = (isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : ''));
	
	require($App->getAppFile());
		
	if (!empty($action)){
		EventManager::notify('ApplicationActionsBeforeExecute', &$action);
		
		$actionFiles = $App->getActionFiles($action);
		foreach($actionFiles as $file){
			require($file);
		}
		
		EventManager::notify('ApplicationActionsAfterExecute');
	}
	
	EventManager::notify('ApplicationTemplateBeforeInclude');

	if (isset($_GET['dialog']) && $_GET['dialog'] == 'true'){
		require(sysConfig::getDirFsCatalog() . 'templates/' . Session::get('tplDir') . '/popup.tpl.php');
	}else{
		require(sysConfig::getDirFsCatalog() . 'templates/' . Session::get('tplDir') . '/main_page.tpl.php');
	}
	
	EventManager::notify('ApplicationTemplateAfterInclude');

	require(sysConfig::getDirFsCatalog() . 'includes/application_bottom.php');
?>