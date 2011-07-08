<?php
/*
	Related Products Version 1

	I.T. Web Experts, Rental Store v2
	http://www.itwebexperts.com

	Copyright (c) 2009 I.T. Web Experts

	This script and it's source is not redistributable
*/

class Extension_packages extends ExtensionBase {

	public function __construct(){
		parent::__construct('packages');
	}

	public function init(){
		global $App, $appExtension, $typeNames, $inventoryTypes;
		if ($this->enabled === false){
			return;
		}

		$typeNames['package'] = 'Package';
		$inventoryTypes['package'] = 'Package';

		EventManager::attachEvents(array(
		), null, $this);
	}

}
?>