<?php

/**
 * RentedQueue
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6401 2009-09-24 16:12:04Z guilhermeblanco $
 */
class RentedQueue extends Doctrine_Record {
	
	public function setUp(){
		$this->setUpParent();
		
		$this->hasOne('Customers', array(
			'local' => 'customers_id',
			'foreign' => 'customers_id'
		));
		
		$this->hasOne('Products', array(
			'local' => 'products_id',
			'foreign' => 'products_id'
		));
		
		$this->hasOne('ProductsInventoryBarcodes', array(
			'local' => 'products_barcode',
			'foreign' => 'barcode_id'
		));
	}
	
	public function setUpParent(){
		$Customers = Doctrine_Core::getTable('Customers')->getRecordInstance();
		$Products = Doctrine_Core::getTable('Products')->getRecordInstance();
		
		$Customers->hasMany('RentedQueue', array(
			'local' => 'customers_id',
			'foreign' => 'customers_id',
			'cascade' => array('delete')
		));
		
		$Products->hasMany('RentedQueue', array(
			'local' => 'products_id',
			'foreign' => 'products_id',
			'cascade' => array('delete')
		));
	}
	
	public function preInsert($event){
		$this->date_added = date('Y-m-d');
	}
	
	public function preUpdate($event){
	}
	
	public function setTableDefinition(){
		$this->setTableName('rented_queue');
		
		$this->hasColumn('customers_queue_id', 'integer', 4, array(
		'type' => 'integer',
		'length' => 4,
		'unsigned' => 0,
		'primary' => true,
		'autoincrement' => true,
		));
		$this->hasColumn('customers_id', 'integer', 4, array(
		'type' => 'integer',
		'length' => 4,
		'unsigned' => 0,
		'primary' => false,
		'default' => '0',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('products_id', 'integer', 4, array(
		'type' => 'integer',
		'length' => 4,
		'unsigned' => 0,
		'primary' => false,
		'default' => '0',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('date_added', 'timestamp', null, array(
		'type' => 'timestamp',
		'primary' => false,
		'default' => '0000-00-00 00:00:00',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('shipment_date', 'timestamp', null, array(
		'type' => 'timestamp',
		'primary' => false,
		'default' => '0000-00-00 00:00:00',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('arrival_date', 'timestamp', null, array(
		'type' => 'timestamp',
		'primary' => false,
		'default' => '0000-00-00 00:00:00',
		'notnull' => false,
		'autoincrement' => false,
		));
		$this->hasColumn('return_date', 'timestamp', null, array(
		'type' => 'timestamp',
		'primary' => false,
		'default' => '0000-00-00 00:00:00',
		'notnull' => false,
		'autoincrement' => false,
		));
		$this->hasColumn('products_barcode', 'string', 50, array(
		'type' => 'string',
		'length' => 50,
		'fixed' => false,
		'primary' => false,
		'default' => '0',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('broken', 'integer', 1, array(
		'type' => 'integer',
		'length' => 1,
		'unsigned' => 0,
		'primary' => false,
		'default' => '0',
		'notnull' => true,
		'autoincrement' => false,
		));
	}
}