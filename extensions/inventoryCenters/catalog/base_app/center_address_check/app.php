<?php
/*
	Inventory Centers Extension Version 1

	I.T. Web Experts, Rental Store v2
	http://www.itwebexperts.com

	Copyright (c) 2009 I.T. Web Experts

	This script and it's source is not redistributable
*/
	
	require('includes/functions/google_maps.php');
	require('includes/classes/json.php');

	if (Session::exists('addressCheck') === true){
		Session::remove('addressCheck');
	}
	
	$appContent = $App->getAppContentFile();
?>