<?php

/**
 * CustomerGroups
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6401 2009-09-24 16:12:04Z guilhermeblanco $
 */
class ProductGallery extends Doctrine_Record {
	
	public function setUp(){
		parent::setUp();
		$this->setUpParent();
		$this->hasOne('Products', array(
			'local' => 'products_id',
			'foreign' => 'products_id'
		));
	}

	public function setUpParent(){
		$Products = Doctrine::getTable('Products')->getRecordInstance();

		$Products->hasOne('ProductGallery', array(
			'local' => 'products_id',
			'foreign' => 'products_id',
			'cascade' => array('delete')
		));
	}

	public function setTableDefinition(){
		$this->setTableName('products_gallery');

		$this->hasColumn('products_gallery_id', 'integer', 4, array(
			'type'          => 'integer',
			'length'        => 4,
			'unsigned'      => 0,
			'primary'       => true,
			'autoincrement' => true
		));
		$this->hasColumn('products_id', 'integer', 4, array(
			'type'          => 'integer',
			'length'        => 4,
			'unsigned'      => 0,
			'primary'       => false,
			'autoincrement' => false
		));
		$this->hasColumn('file_name', 'string', 255, array(
			'type'          => 'string',
			'length'        => 255,
			'fixed'         => false,
			'primary'       => false,
			'notnull'       => false,
			'autoincrement' => false
		));

		$this->hasColumn('comments', 'string', null, array(
			'type'          => 'string',
			'length'        => null,
			'fixed'         => false,
			'primary'       => false,
			'notnull'       => false,
			'autoincrement' => false
		));
	}
}