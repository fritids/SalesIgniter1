<?php
/*
	Products Attributes Extension Version 1

	I.T. Web Experts, Rental Store v2
	http://www.itwebexperts.com

	Copyright (c) 2009 I.T. Web Experts

	This script and it's source is not redistributable
*/

class ProductsOptionsGroups extends Doctrine_Record {

	public function setUp(){
		$this->hasMany('ProductsOptionsToProductsOptionsGroups', array(
			'local' => 'products_options_groups_id',
			'foreign' => 'products_options_groups_id',
			'cascade' => array('delete')
		));
	}
	
	public function setTableDefinition(){
		$this->setTableName('products_options_groups');
		
		$this->hasColumn('products_options_groups_id', 'integer', 4, array(
			'type'          => 'integer',
			'length'        => 4,
			'unsigned'      => 0,
			'primary'       => true,
			'autoincrement' => true,
		));
		
		$this->hasColumn('products_options_groups_name', 'string', 128, array(
			'type'          => 'string',
			'length'        => 128,
			'fixed'         => false,
			'primary'       => false,
			'default'       => '',
			'notnull'       => true,
			'autoincrement' => false,
		));
	}
}
?>