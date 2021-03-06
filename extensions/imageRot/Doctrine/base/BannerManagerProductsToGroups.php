<?php

/**
 * ProductsToCategories
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6401 2009-09-24 16:12:04Z guilhermeblanco $
 */
class BannerManagerProductsToGroups extends Doctrine_Record {

	public function setUp(){
		$this->setUpParent();
		
		$this->hasOne('Products', array(
			'local' => 'products_id',
			'foreign' => 'products_id'
		));
		
		$this->hasOne('BannerManagerGroups', array(
			'local' => 'banner_group_id',
			'foreign' => 'banner_group_id'
		));
	}

	public function setUpParent(){
		$BannerGroups = Doctrine::getTable('BannerManagerGroups')->getRecordInstance();
		
		$BannerGroups->hasMany('BannerManagerProductsToGroups', array(
			'local' => 'banner_group_id',
			'foreign' => 'banner_group_id',
			'cascade' => array('delete')
		));

		$Products = Doctrine::getTable('Products')->getRecordInstance();

		$Products->hasMany('BannerManagerProductsToGroups', array(
			'local' => 'products_id',
			'foreign' => 'products_id',
			'cascade' => array('delete')
		));
	}

	public function setTableDefinition(){
		$this->setTableName('banner_manager_products_to_groups');
		
		$this->hasColumn('products_id', 'integer', 4, array(
			'type' => 'integer',
			'length' => 4,
			'unsigned' => 0,
			'primary' => true,
			'autoincrement' => false,
		));
		
		$this->hasColumn('banner_group_id', 'integer', 4, array(
			'type' => 'integer',
			'length' => 4,
			'unsigned' => 0,
			'primary' => true,
			'autoincrement' => false,
		));
	}
}