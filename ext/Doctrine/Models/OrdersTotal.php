<?php

/**
 * OrdersTotal
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6401 2009-09-24 16:12:04Z guilhermeblanco $
 */
class OrdersTotal extends Doctrine_Record {

	public function setUp(){
		parent::setUp();
		$this->setUpParent();
	}

	public function setUpParent(){
	}

	public function setTableDefinition(){
		$this->setTableName('orders_total');
		
		$this->hasColumn('orders_total_id', 'integer', 4, array(
		'type' => 'integer',
		'length' => 4,
		'unsigned' => 1,
		'primary' => true,
		'autoincrement' => true,
		));
		$this->hasColumn('orders_id', 'integer', 4, array(
		'type' => 'integer',
		'length' => 4,
		'unsigned' => 0,
		'primary' => false,
		'default' => '0',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('title', 'string', 255, array(
		'type' => 'string',
		'length' => 255,
		'fixed' => false,
		'primary' => false,
		'default' => '',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('text', 'string', 255, array(
		'type' => 'string',
		'length' => 255,
		'fixed' => false,
		'primary' => false,
		'default' => '',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('value', 'decimal', 15, array(
		'type' => 'decimal',
		'length' => 15,
		'unsigned' => 0,
		'primary' => false,
		'default' => '0.0000',
		'notnull' => true,
		'autoincrement' => false,
		'scale' => 4,
		));
		$this->hasColumn('module_type', 'string', 32, array(
		'type' => 'string',
		'length' => 32,
		'fixed' => false,
		'primary' => false,
		'default' => '',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('module', 'string', 32, array(
		'type' => 'string',
		'length' => 32,
		'fixed' => false,
		'primary' => false,
		'default' => '',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('method', 'string', 64, array(
		'type' => 'string',
		'length' => 64,
		'fixed' => false,
		'primary' => false,
		'default' => '',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('sort_order', 'integer', 4, array(
		'type' => 'integer',
		'length' => 4,
		'unsigned' => 0,
		'primary' => false,
		'default' => '0',
		'notnull' => true,
		'autoincrement' => false,
		));
	}
}