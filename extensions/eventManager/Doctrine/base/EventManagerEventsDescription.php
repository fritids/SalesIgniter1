<?php

/**
 * PhotoGalleryCategoriesDescription
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6401 2009-09-24 16:12:04Z guilhermeblanco $
 */
class EventManagerEventsDescription extends Doctrine_Record {

	public function setUp(){
		$this->setUpParent();
		
		$this->setAttribute(Doctrine::ATTR_COLL_KEY, 'language_id');
		
		$this->hasOne('EventManagerEvents', array(
			'local' => 'events_id',
			'foreign' => 'events_id'
		));
	}
	
	public function setUpParent(){
		$EventManagerEvents = Doctrine_Core::getTable('EventManagerEvents')->getRecordInstance();

		$EventManagerEvents->hasMany('EventManagerEventsDescription', array(
			'local' => 'events_id',
			'foreign' => 'events_id',
			'cascade' => array('delete')
		));
	}
	
	public function setTableDefinition(){
		$this->setTableName('event_manager_events_description');
		
		$this->hasColumn('events_id', 'integer', 4, array(
			'type'          => 'integer',
			'length'        => 4,
			'unsigned'      => 0,
			'primary'       => false,
			'autoincrement' => false
		));
		
		$this->hasColumn('language_id', 'integer', 4, array(
			'type'          => 'integer',
			'length'        => 4,
			'unsigned'      => 0,
			'primary'       => false,
			'default'       => '1',
			'autoincrement' => false
		));

		$this->hasColumn('events_title', 'string', 255, array(
			'type'          => 'string',
			'length'        => 255,
			'fixed'         => false,
			'primary'       => false,
			'default'       => '',
			'notnull'       => true,
			'autoincrement' => false
		));

		$this->hasColumn('events_description_text', 'string', null, array(
			'type'          => 'string',
			'fixed'         => false,
			'primary'       => false,
			'notnull'       => false,
			'autoincrement' => false
		));


	}
	public function newLanguageProcess($fromLangId, $toLangId){
		$Qdescription = Doctrine_Query::create()
		->from('EventManagerEventsDescription')
		->where('language_id = ?', (int) $fromLangId)
		->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
		foreach($Qdescription as $Record){
			$toTranslate = array(
				'events_title' => $Record['events_title'],
				'events_description_text' => $Record['events_description_text']
			);

			EventManager::notify('EventManagerEventsDescriptionNewLanguageProcessBeforeTranslate', $toTranslate);

			$translated = sysLanguage::translateText($toTranslate, (int) $toLangId, (int) $fromLangId);

			$newDesc = new EventManagerEventsDescription();
			$newDesc->events_id = $Record['events_id'];
			$newDesc->language_id = (int) $toLangId;
			$newDesc->events_title = $translated['events_title'];
			$newDesc->events_description_text = $translated['events_description_text'];

			EventManager::notify('EventManagerEventsDescriptionNewLanguageProcessBeforeSave', $newDesc);

			$newDesc->save();
		}
	}

	public function cleanLanguageProcess($existsId){
		Doctrine_Query::create()
		->delete('EventManagerEventsDescription')
		->whereNotIn('language_id', $existsId)
		->execute();
	}

	public function deleteLanguageProcess($langId){
		Doctrine_Query::create()
		->delete('EventManagerEventsDescription')
		->where('language_id = ?', (int) $langId)
		->execute();
	}
}