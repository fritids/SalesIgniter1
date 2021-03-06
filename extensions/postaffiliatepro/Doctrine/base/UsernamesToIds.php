<?php

/**
 * CustomersStreamingViews
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6401 2009-09-24 16:12:04Z guilhermeblanco $
 */
class UsernamesToIds extends Doctrine_Record {
	
	public function setUp(){
		parent::setUp();
		//$this->setUpParent();

	}
	
	public function setUpParent(){
		/*$Customers = Doctrine::getTable('Customers')->getRecordInstance();
		
		$Customers->hasMany('CustomersToOrders', array(
			'local' => 'customers_id',
			'foreign' => 'customers_id',
			'cascade' => array('delete')
		));*/
		
	}

	public function setTableDefinition(){
		$this->setTableName('usernames_to_ids');

		$this->hasColumn('ids', 'string', 250, array(
		'type' => 'string',
		'length' => 250,
		'primary' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('username', 'string', null, array(
				'type' => 'string',
				'length' => null
		));
		$this->hasColumn('customers_email_address', 'string', null, array(
				'type' => 'string',
				'length' => null
		));

	}
}