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
class FeaturedManagerCategoriesToGroups extends Doctrine_Record {

	public function setUp(){
		$this->setUpParent();
		
		$this->hasOne('Categories', array(
			'local' => 'categories_id',
			'foreign' => 'categories_id'
		));
		
		$this->hasOne('FeaturedManagerGroups', array(
			'local' => 'featured_group_id',
			'foreign' => 'featured_group_id'
		));
	}

	public function setUpParent(){
		$FeaturedGroups = Doctrine::getTable('FeaturedManagerGroups')->getRecordInstance();
		
		$FeaturedGroups->hasMany('FeaturedManagerCategoriesToGroups', array(
			'local' => 'featured_group_id',
			'foreign' => 'featured_group_id',
			'cascade' => array('delete')
		));

		$Products = Doctrine::getTable('Categories')->getRecordInstance();

		$Products->hasMany('FeaturedManagerCategoriesToGroups', array(
			'local' => 'categories_id',
			'foreign' => 'categories_id',
			'cascade' => array('delete')
		));
	}

	public function setTableDefinition(){
		$this->setTableName('featured_manager_categories_to_groups');
		
		$this->hasColumn('categories_id', 'integer', 4, array(
			'type' => 'integer',
			'length' => 4,
			'unsigned' => 0,
			'primary' => true,
			'autoincrement' => false,
		));
		
		$this->hasColumn('featured_group_id', 'integer', 4, array(
			'type' => 'integer',
			'length' => 4,
			'unsigned' => 0,
			'primary' => true,
			'autoincrement' => false,
		));
	}
}