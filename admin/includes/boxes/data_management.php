<?php
/*
	Sales Igniter E-Commerce System
	Version: 1.0
	
	I.T. Web Experts
	http://www.itwebexperts.com
	
	Copyright (c) 2010 I.T. Web Experts
	
	This script and its source are not distributable without the written conscent of I.T. Web Experts
*/

	$contents = array(
		'text' => 'Data Import/Export',
		'link' => false,
		'children' => array()
	);
	
	if (sysPermissions::adminAccessAllowed('data_manager') === true){
		if (sysPermissions::adminAccessAllowed('data_manager', 'default') === true){
			$contents['children'][] = array(
				'link' => itw_app_link(null, 'data_manager', 'default', 'SSL'),
				'text' => 'Products'
			);
		}
		
		if (sysPermissions::adminAccessAllowed('data_manager', 'barcodePopulate') === true){
			$contents['children'][] = array(
				'link' => itw_app_link(null, 'data_manager', 'barcodePopulate', 'SSL'),
				'text' => 'Product Inventory'
			);
		}
	}

	EventManager::notify('BoxDataManagementAddLink', &$contents);
?>