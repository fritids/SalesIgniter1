<?php

/**
 * ProductsDescription
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6401 2009-09-24 16:12:04Z guilhermeblanco $
 */
class ProductsDescription extends Doctrine_Record {
	
	public function setUp(){
		$this->setUpParent();
		$this->setAttribute(Doctrine_Core::ATTR_COLL_KEY, 'language_id');
		
		$this->hasOne('Products', array(
			'local' => 'products_id',
			'foreign' => 'products_id'
		));
		
		$this->hasOne('Languages', array(
			'local' => 'language_id',
			'foreign' => 'languages_id'
		));
	}
	
	public function setUpParent(){
	}
	
	public function setTableDefinition(){
		$this->setTableName('products_description');
		
		$this->hasColumn('products_description_id', 'integer', 4, array(
			'type' => 'integer',
			'length' => 4,
			'unsigned' => 0,
			'primary' => true,
			'autoincrement' => true
		));
		
		$this->hasColumn('products_id', 'integer', 4, array(
			'type' => 'integer',
			'length' => 4,
			'unsigned' => 0,
			'primary' => true,
			'autoincrement' => false
		));
		
		$this->hasColumn('language_id', 'integer', 4, array(
			'type' => 'integer',
			'length' => 4,
			'unsigned' => 0,
			'primary' => true,
			'default' => '1',
			'autoincrement' => false
		));
		
		$this->hasColumn('products_name', 'string', 64, array(
			'type' => 'string',
			'length' => 64,
			'fixed' => false,
			'primary' => false,
			'default' => '',
			'notnull' => true,
			'autoincrement' => false
		));
		
		$this->hasColumn('products_sname', 'string', 64, array(
			'type' => 'string',
			'length' => 64,
			'fixed' => false,
			'primary' => false,
			'default' => '',
			'notnull' => true,
			'autoincrement' => false
		));
		
		$this->hasColumn('products_description', 'string', null, array(
			'type' => 'string',
			'fixed' => false,
			'primary' => false,
			'notnull' => false,
			'autoincrement' => false
		));
		
		$this->hasColumn('products_url', 'string', 255, array(
			'type' => 'string',
			'length' => 255,
			'fixed' => false,
			'primary' => false,
			'notnull' => false,
			'autoincrement' => false
		));
		
		$this->hasColumn('products_viewed', 'integer', 4, array(
			'type' => 'integer',
			'length' => 4,
			'unsigned' => 0,
			'primary' => false,
			'default' => '0',
			'notnull' => false,
			'autoincrement' => false
		));
		
		$this->hasColumn('products_head_title_tag', 'string', 80, array(
			'type' => 'string',
			'length' => 80,
			'fixed' => false,
			'primary' => false,
			'notnull' => false,
			'autoincrement' => false
		));
		
		$this->hasColumn('products_head_desc_tag', 'string', null, array(
			'type' => 'string',
			'fixed' => false,
			'primary' => false,
			'notnull' => false,
			'autoincrement' => false
		));
		
		$this->hasColumn('products_head_keywords_tag', 'string', null, array(
			'type' => 'string',
			'fixed' => false,
			'primary' => false,
			'notnull' => false,
			'autoincrement' => false
		));
		
		$this->hasColumn('products_seo_url', 'string', 100, array(
			'type' => 'string',
			'length' => 100,
			'fixed' => false,
			'primary' => false,
			'default' => '',
			'notnull' => true,
			'autoincrement' => false
		));
	}
	
	public function newLanguageProcess($fromLangId, $toLangId){
		$Qdescription = Doctrine_Query::create()
		->from('ProductsDescription')
		->where('language_id = ?', (int) $fromLangId)
		->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
		foreach($Qdescription as $Record){
			$toTranslate = array(
				'name'         => $Record['products_name'],
				'description'  => $Record['products_description'],
				'htc_title'    => $Record['products_head_title_tag'],
				'htc_desc'     => $Record['products_head_desc_tag'],
				'htc_keywords' => $Record['products_head_keywords_tag']
			);
			
			EventManager::notify('ProductsDescriptionNewLanguageProcessBeforeTranslate', $toTranslate);
			
			$translated = sysLanguage::translateText($toTranslate, (int) $toLangId, (int) $fromLangId);
			
			$newDesc = new ProductsDescription();
			$newDesc->products_id = $Record['products_id'];
			$newDesc->language_id = (int) $toLangId;
			$newDesc->products_name = $translated['name'];
			$newDesc->products_sname = $Record['products_sname'];
			$newDesc->products_description = $translated['description'];
			$newDesc->products_viewed = 0;
			$newDesc->products_head_title_tag = $translated['htc_title'];
			$newDesc->products_head_desc_tag = $translated['htc_desc'];
			$newDesc->products_head_keywords_tag = $translated['htc_keywords'];
			$newDesc->products_seo_url = $Record['products_seo_url'];
			
			EventManager::notify('ProductsDescriptionNewLanguageProcessBeforeSave', $newDesc);
			
			$newDesc->save();
		}
	}

	public function cleanLanguageProcess($existsId){
		Doctrine_Query::create()
		->delete('ProductsDescription')
		->whereNotIn('language_id', $existsId)
		->execute();
	}

	public function deleteLanguageProcess($langId){
		Doctrine_Query::create()
		->delete('ProductsDescription')
		->where('language_id = ?', (int) $langId)
		->execute();
	}
}