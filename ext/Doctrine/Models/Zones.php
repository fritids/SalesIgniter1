<?php

/**
 * Zones
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6401 2009-09-24 16:12:04Z guilhermeblanco $
 */
class Zones extends Doctrine_Record {
	
	public function setUp(){
		$this->setUpParent();
	}
	
	public function setUpParent(){
		$AddressBook = Doctrine::getTable('AddressBook')->getRecordInstance();
		$Countries = Doctrine::getTable('Countries')->getRecordInstance();
		
		$AddressBook->hasOne('Zones', array(
			'local' => 'entry_zone_id',
			'foreign' => 'zone_id'
		));
		
		$Countries->hasMany('Zones', array(
			'local' => 'countries_id',
			'foreign' => 'zone_country_id'
		));
	}
	
	public function setTableDefinition(){
		$this->setTableName('zones');
		
		$this->hasColumn('zone_id', 'integer', 4, array(
			'type' => 'integer',
			'length' => 4,
			'unsigned' => 0,
			'primary' => true,
			'autoincrement' => true,
		));
		
		$this->hasColumn('zone_country_id', 'integer', 4, array(
			'type' => 'integer',
			'length' => 4,
			'unsigned' => 0,
			'primary' => false,
			'default' => '0',
			'notnull' => true,
			'autoincrement' => false,
		));
		
		$this->hasColumn('zone_code', 'string', 32, array(
			'type' => 'string',
			'length' => 32,
			'fixed' => false,
			'primary' => false,
			'default' => '',
			'notnull' => true,
			'autoincrement' => false,
		));
		
		$this->hasColumn('zone_name', 'string', 32, array(
			'type' => 'string',
			'length' => 32,
			'fixed' => false,
			'primary' => false,
			'default' => '',
			'notnull' => true,
			'autoincrement' => false,
		));
	}
	
	public function getZoneName($zoneId, $countryId){
		$Query = Doctrine_Query::create()
		->select('zone_name')
		->from('Zones')
		->where('zone_id = ?', $zoneId)
		->andWhere('zone_country_id = ?', $countryId)
		->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
		return $Query[0]['zone_name'];
	}
}