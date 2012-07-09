<?php

/**
 * CustomersMembership
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6401 2009-09-24 16:12:04Z guilhermeblanco $
 */
class CustomersMembership extends Doctrine_Record {
	
	public function setUp(){
		$this->hasOne('Customers', array(
			'local' => 'customers_id',
			'foreign' => 'customers_id'
		));
	}
	
	public function preInsert($event){
		$this->membership_date = date('Y-m-d h:i:s');
	}

	public function setTableDefinition(){
		$this->setTableName('customers_membership');
		
		$this->hasColumn('customers_id', 'integer', 4, array(
		'type' => 'integer',
		'length' => 4,
		'unsigned' => 0,
		'primary' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('plan_id', 'integer', 4, array(
		'type' => 'integer',
		'length' => 4,
		'unsigned' => 0,
		'primary' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('plan_name', 'string', 64, array(
		'type' => 'string',
		'length' => 64,
		'fixed' => false,
		'primary' => false,
		'notnull' => false,
		'autoincrement' => false,
		));
		$this->hasColumn('plan_price', 'decimal', 15, array(
		'type' => 'decimal',
		'length' => 15,
		'unsigned' => 0,
		'primary' => false,
		'notnull' => false,
		'autoincrement' => false,
		'scale' => 4,
		));
		$this->hasColumn('plan_tax_class_id', 'integer', 4, array(
		'type' => 'integer',
		'length' => 4,
		'unsigned' => 0,
		'primary' => false,
		'notnull' => false,
		'autoincrement' => false,
		));
		$this->hasColumn('ismember', 'enum', 1, array(
		'type' => 'enum',
		'length' => 1,
		'fixed' => false,
		'values' =>
		array(
		0 => 'M',
		1 => 'U',
		),
		'primary' => false,
		'default' => 'U',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('activate', 'enum', 1, array(
		'type' => 'enum',
		'length' => 1,
		'fixed' => false,
		'values' =>
		array(
		0 => 'Y',
		1 => 'N',
		),
		'primary' => false,
		'default' => 'N',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('canceled', 'integer', 1, array(
		'type' => 'integer',
		'length' => 1,
		'unsigned' => 0,
		'primary' => false,
		'default' => '0',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('membership_date', 'date', null, array(
		'type' => 'date',
		'primary' => false,
		'default' => '0000-00-00',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('next_bill_date', 'date', null, array(
		'type' => 'date',
		'primary' => false,
		'default' => '0000-00-00',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('free_trial_flag', 'string', 3, array(
		'type' => 'string',
		'length' => 3,
		'fixed' => false,
		'primary' => false,
		'default' => 'N',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('free_trial_ends', 'date', null, array(
		'type' => 'date',
		'primary' => false,
		'default' => '0000-00-00',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('cancel_date', 'date', null, array(
		'type' => 'date',
		'primary' => false,
		'default' => '0000-00-00',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('card_num', 'string', 64, array(
		'type' => 'string',
		'length' => 64,
		'fixed' => false,
		'primary' => false,
		'notnull' => false,
		'autoincrement' => false,
		));
		$this->hasColumn('card_cvv', 'string', 64, array(
		'type' => 'string',
		'length' => 64,
		'fixed' => false,
		'primary' => false,
		'notnull' => false,
		'autoincrement' => false,
		));
		$this->hasColumn('exp_date', 'string', 64, array(
		'type' => 'string',
		'length' => 64,
		'fixed' => false,
		'primary' => false,
		'notnull' => false,
		'autoincrement' => false,
		));
		$this->hasColumn('payment_method', 'string', 255, array(
		'type' => 'string',
		'length' => 255,
		'fixed' => false,
		'primary' => false,
		'default' => '',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('rental_address_id', 'integer', 4, array(
		'type' => 'integer',
		'length' => 4,
		'unsigned' => 0,
		'primary' => false,
		'default' => '0',
		'notnull' => true,
		'autoincrement' => false,
		));
		$this->hasColumn('subscr_id', 'string', 100, array(
		'type' => 'string',
		'length' => 100,
		'fixed' => false,
		'primary' => false,
		'notnull' => false,
		'autoincrement' => false,
		));
		$this->hasColumn('payment_term', 'string', 2, array(
		'type' => 'string',
		'length' => 2,
		'fixed' => true,
		'primary' => false,
		'default' => 'M',
		'notnull' => true,
		'autoincrement' => false,
		));
        $this->hasColumn('auto_billing', 'integer', 11, array(
        'type' => 'integer',
        'length' => 11,
        'unsigned' => 0,
        'primary' => false,
        'notnull' => false,
        'autoincrement' => false,
        ));
	}
}