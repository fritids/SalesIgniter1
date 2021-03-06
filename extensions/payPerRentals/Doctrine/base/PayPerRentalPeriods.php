<?php
/*
	Inventory Centers Extension Version 1
	
	I.T. Web Experts, Rental Store v2
	http://www.itwebexperts.com

	Copyright (c) 2009 I.T. Web Experts

	This script and it's source is not redistributable
*/

class PayPerRentalPeriods extends Doctrine_Record {
	
	public function setUp(){
	}

	public function setUpParent(){
	}
	 
	public function setTableDefinition(){
		$this->setTableName('pay_per_rental_periods');
		
		$this->hasColumn('period_id', 'integer', 4, array(
			'type'          => 'integer',
			'length'        => 4,
			'unsigned'      => 0,
			'primary'       => true,
			'autoincrement' => true,
		));

		$this->hasColumn('period_start_date', 'datetime', null, array(
			'type'          => 'datetime',
			'primary'       => false,
			'notnull'       => true,
			'autoincrement' => false
		));

		$this->hasColumn('period_end_date', 'datetime', null, array(
			'type'          => 'datetime',
			'primary'       => false,
			'notnull'       => true,
			'autoincrement' => false
		));

		$this->hasColumn('period_name', 'string', 128, array(
			'type'          => 'string',
			'length'        => 128,
			'fixed'         => false,
			'primary'       => false,
			'notnull'       => true,
			'autoincrement' => false,
		));


		$this->hasColumn('period_details', 'string', null, array(
			'type'          => 'string',
			'fixed'         => false,
			'primary'       => false,
			'notnull'       => true,
			'autoincrement' => false,
		));

	}
}