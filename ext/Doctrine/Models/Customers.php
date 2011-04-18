<?php

/**
 * Customers
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6401 2009-09-24 16:12:04Z guilhermeblanco $
 */
class Customers extends Doctrine_Record {
	
	public function setUp(){
		$this->hasMany('AddressBook', array(
			'local' => 'customers_id',
			'foreign' => 'customers_id',
			'cascade' => array('delete')
		));

		$this->hasMany('Orders', array(
			'local' => 'customers_id',
			'foreign' => 'customers_id',
			'cascade' => array('delete')
		));
		
		$this->hasOne('CustomersMembership', array(
			'local' => 'customers_id',
			'foreign' => 'customers_id',
			'cascade' => array('delete')
		));
	}
	
	public function setTableDefinition(){
		$this->setTableName('customers');
		
		$this->hasColumn('customers_id', 'integer', 4, array(
			'type' => 'integer',
			'length' => 4,
			'unsigned' => 0,
			'primary' => true,
			'autoincrement' => true,
		));
		$this->hasColumn('language_id', 'integer', 4, array(
			'type' => 'integer',
			'length' => 4,
			'unsigned' => 0,
			'primary' => false,
			'autoincrement' => false,
		));
		$this->hasColumn('customers_gender', 'string', 1, array(
			'type' => 'string',
			'length' => 1,
			'fixed' => true,
			'primary' => false,
			'default' => '',
			'notnull' => true,
			'autoincrement' => false,
		));
		$this->hasColumn('customers_firstname', 'string', 32, array(
			'type' => 'string',
			'length' => 32,
			'fixed' => false,
			'primary' => false,
			'default' => '',
			'notnull' => true,
			'autoincrement' => false,
		));
		$this->hasColumn('customers_lastname', 'string', 32, array(
			'type' => 'string',
			'length' => 32,
			'fixed' => false,
			'primary' => false,
			'default' => '',
			'notnull' => true,
			'autoincrement' => false,
		));
		$this->hasColumn('customers_dob', 'date', null, array(
			'type' => 'date',
			'primary' => false,
			'default' => '0000-00-00',
			'notnull' => true,
			'autoincrement' => false,
		));
		$this->hasColumn('customers_email_address', 'string', 96, array(
			'type' => 'string',
			'length' => 96,
			'fixed' => false,
			'primary' => false,
			'default' => '',
			'notnull' => true,
			'autoincrement' => false,
		));
		$this->hasColumn('customers_default_address_id', 'integer', 4, array(
			'type' => 'integer',
			'length' => 4,
			'unsigned' => 0,
			'primary' => false,
			'notnull' => false,
			'autoincrement' => false,
		));
		$this->hasColumn('customers_telephone', 'string', 32, array(
			'type' => 'string',
			'length' => 32,
			'fixed' => false,
			'primary' => false,
			'default' => '',
			'notnull' => true,
			'autoincrement' => false,
		));
		$this->hasColumn('customers_fax', 'string', 32, array(
			'type' => 'string',
			'length' => 32,
			'fixed' => false,
			'primary' => false,
			'notnull' => false,
			'autoincrement' => false,
		));
		$this->hasColumn('customers_password', 'string', 40, array(
			'type' => 'string',
			'length' => 40,
			'fixed' => false,
			'primary' => false,
			'default' => '',
			'notnull' => true,
			'autoincrement' => false,
		));
		$this->hasColumn('customers_newsletter', 'string', 1, array(
			'type' => 'string',
			'length' => 1,
			'fixed' => true,
			'primary' => false,
			'notnull' => false,
			'autoincrement' => false,
		));
	}
}