<?php
set_time_limit(0);
update_extra();
require('includes/application_top.php');

function getColumnsToEncode($TableName, $collation){
	global $dbConn;

	$DBtableColumns = $dbConn->import->listTableColumns($TableName);

	$encodeColumns = array();
	$autoIncrementCol = null;
	foreach($DBtableColumns as $colName => $colSettings){
		if ($colSettings['autoincrement'] == 1){
			$autoIncrementCol = $colName;
		}

		if (!empty($colSettings['collation']) && $colSettings['collation'] != $collation){
			$encodeColumns[$colName] = $colSettings;
		}
	}

	return array(
		'autoIncrementCol' => $autoIncrementCol,
		'encodeCols' => $encodeColumns
	);
}

function getEncodeUpdateQueries($TableName, $EncodeInfo){
	$updateQueries = array();
	if (is_null($EncodeInfo['autoIncrementCol']) === false && !empty($EncodeInfo['encodeCols'])){
		$selectCols = array($EncodeInfo['autoIncrementCol']);
		foreach($EncodeInfo['encodeCols'] as $colName => $colInfo){
			$selectCols[] = $colName;
		}

		$Result = Doctrine_Manager::getInstance()
			->getCurrentConnection()
			->fetchAll('select ' . implode(', ', $selectCols) . ' from ' . $TableName);
		foreach($Result as $rInfo){
			$update = array();
			foreach($EncodeInfo['encodeCols'] as $colName => $colInfo){
				$encoded = Encoding::fixUTF8($rInfo[$colName]);
				if ($encoded !== false){
					$update[] = $colName . ' = "' . addslashes($encoded) . '"';
				}
			}

			if (!empty($update)){
				$updateQueries[] = 'update ' . $TableName . ' set ' . implode(', ', $update) . ' where ' . $EncodeInfo['autoIncrementCol'] . '=' . $rInfo[$EncodeInfo['autoIncrementCol']] . "\n";
			}
		}
	}
	return $updateQueries;
}

function processUpdateQueries($UpdateQueries){
	foreach($UpdateQueries as $query){
		Doctrine_Manager::getInstance()
			->getCurrentConnection()
			->exec($query);
	}
}


function checkModel($modelName, $charset, $collation){
	global $manager;
	$dbConn = $manager->getCurrentConnection();
	$tableObj = Doctrine_Core::getTable($modelName);
	$tableName = $tableObj->getTableName();
	$isOk = true;
	$isCharset = false;
	$info = array();
	$resLink = '';

	if ($dbConn->import->tableExists($tableName)){
		$tableObjRecord = $tableObj->getRecordInstance();
		$DBtableColumns = $dbConn->import->listTableColumns($tableName);
		$DBtableStatus = Doctrine_Manager::getInstance()
			->getCurrentConnection()
			->fetchAssoc('SHOW TABLE STATUS LIKE "' . $tableName . '"');

		$tableColumns = array();

		foreach($DBtableColumns as $k => $v){
			$tableColumns[strtolower($k)] = $v;
		}

		if ($DBtableStatus[0]['Collation'] != $collation){
			//changeTableCollation&table=' . $tableName . '&to=' . $charset . '&collate=' . $collation

			/*$EncodeInfo = getColumnsToEncode($tableName, $collation);
			$UpdateQueries = getEncodeUpdateQueries($tableName, $EncodeInfo);
			Doctrine_Manager::getInstance()
				->getCurrentConnection()
				->exec('alter table ' . $tableName . ' convert to character set ' . $charset . ' collate ' . $collation);

			if (!empty($UpdateQueries)){
				processUpdateQueries($UpdateQueries);
			}  */
		}

		$modelColumns = $tableObj->getColumns();
		$tableIsCharset = $isCharset;

		foreach($modelColumns as $colName => $colSettings){

			if ($colName == 'id') continue;

			if (array_key_exists($colName, $tableColumns) === false){
				$tableObj = Doctrine_Core::getTable($modelName);
				$colSettings = $tableObj->getColumnDefinition($colName);

				$dbConn->export->alterTable($tableObj->getTableName(), array(
						'add' => array(
							$colName => (array)$colSettings
						)
					));


			}else{
				if ($tableIsCharset === true && !empty($tableColumns[$colName]['collation']) && $tableColumns[$colName]['collation'] != $collation){

					//changeColumnCollation&table=' . $tableName . '&column=' . $colName . '&to=' . $charset . '&collate=' . $collation
				}
				if (
					$colSettings['type'] != $tableColumns[$colName]['type'] ||
					$colSettings['length'] != $tableColumns[$colName]['length']
				){
					if (
						($colSettings['type'] != 'timestamp') &&
						($colSettings['type'] != 'datetime') &&
						($colSettings['type'] != 'date') &&
						(($colSettings['type'] == 'clob' && empty($colSettings['length']) && is_null($tableColumns[$colName]['length'])) === false) &&
						(($colSettings['type'] == 'string' && $colSettings['length'] == '999' && is_null($tableColumns[$colName]['length'])) === false) &&						(($colSettings['type'] == 'text' && empty($colSettings['length']) && is_null($tableColumns[$colName]['length'])) === false)
					){
						//syncColumnSettings&model=' . $modelName . '&column=' . $colName
						/*
						 $tableObj = Doctrine_Core::getTable($modelName);
						$colSettings = $tableObj->getColumnDefinition($colName);

						$dbConn->export->alterTable($tableObj->getTableName(), array(
							'change' => array(
								$colName => array(
									'definition' => (array)$colSettings
								)
							)
						));
						  */
					}
				}
			}
		}
		foreach($tableColumns as $colName => $colSettings){
			if (array_key_exists($colName, $modelColumns) === false){
				//removeColumn &table=' . $tableName . '&column=' . $colName

				/*$success = Doctrine_Manager::getInstance()
					->getCurrentConnection()
					->exec('alter table ' . $tableName . ' drop ' . $colName);*/
			}
		}
	}else{

		Doctrine_Core::createTablesFromArray(array(
				$modelName
			));

		$tableObj = Doctrine_Core::getTable($modelName);
		if ($dbConn->import->tableExists($tableObj->getTableName())){


		}
	}

}

function updateAllDbFields(){
	Doctrine_Core::loadAllModels();
	$Models = Doctrine_Core::getLoadedModels();
	sort($Models);

	$DatabaseTables = Doctrine_Manager::getInstance()
		->getCurrentConnection()
		->fetchAssoc('SHOW TABLES FROM ' . sysConfig::get('DB_DATABASE'));

	$tablesInDb = array();
	foreach($DatabaseTables as $tInfo){
		foreach($tInfo as $tableName){
			$tablesInDb[$tableName] = $tableName;
		}
	}

	foreach($Models as $mInfo){
		$ModelCheck = checkModel($mInfo, sysConfig::get('SYSTEM_CHARACTER_SET'), sysConfig::get('SYSTEM_CHARACTER_SET_COLLATION'));
		if (isset($tablesInDb[$ModelCheck['table_name']])){
			unset($tablesInDb[$ModelCheck['table_name']]);
		}
	}
}

function addMissingConfigs(){
	if(!class_exists('fileSystemBrowser')){
		require(sysConfig::getDirFsCatalog() . 'includes/classes/fileSystemBrowser.php');
	}
	$templates = new fileSystemBrowser(sysConfig::getDirFsCatalog()  . 'extensions/');
	$directories = $templates->getDirectories();

	foreach($directories as $dirInfo){

		$extension = $dirInfo['basename'];
		$extensionDir = sysConfig::getDirFsCatalog() . 'extensions/' . $extension . '/';

		$config = simplexml_load_file($extensionDir . 'data/base/configuration.xml', 'SimpleXMLElement', LIBXML_NOCDATA);
		$ConfigurationGroup = Doctrine_Core::getTable('ConfigurationGroup');

		$Group = Doctrine_Query::create()
			->select('configuration_group_id')
			->from('ConfigurationGroup')
			->where('configuration_group_title = ?', (string) $config->title)
			->fetchOne();
		if ($Group !== false){
			$groupID = $Group['configuration_group_id'];
			foreach((array) $config->Configuration as $configKey => $configSettings){
				$Qcheck = Doctrine_Query::create()
					->select('configuration_id')
					->from('Configuration')
					->where('configuration_key = ?', $configKey)
					->execute();
				if ($Qcheck->count() <= 0){
					$newConfig = new Configuration();
					$newConfig->configuration_key = (string) $configKey;
					$newConfig->configuration_title = (string) $configSettings->title;
					$newConfig->configuration_value = (string) $configSettings->value;
					$newConfig->configuration_description = (string) $configSettings->description;
					$newConfig->configuration_group_id = (int) $groupID;
					$newConfig->sort_order = (int) $configSettings->sort_order;

					if (isset($configSettings->use_function)){
						$newConfig->use_function = (string) $configSettings->use_function;
					}

					if (isset($configSettings->set_function)){
						$newConfig->set_function = (string) $configSettings->set_function;
					}
					$newConfig->save();
				}
				$Qcheck->free();
			}
		}
	}
}


function addConfiguration($key, $group, $title, $desc, $default, $func) {
	$Qcheck = Doctrine_Query::create()
		->select('configuration_id')
		->from('Configuration')
		->where('configuration_key = ?', $key)
		->execute();
	if ($Qcheck->count() <= 0) {
		$newConfig = new Configuration();
		$newConfig->configuration_key = $key;
		$newConfig->configuration_title = $title;
		$newConfig->configuration_value = $default;
		$newConfig->configuration_description = $desc;
		$newConfig->configuration_group_id = $group;
		$newConfig->sort_order = 11;
		$newConfig->set_function = $func;
		$newConfig->save();
	}
	$Qcheck->free();
}

function table_exists($tableName){

	if( mysql_num_rows( mysql_query("SHOW TABLES LIKE '" . $tableName . "'"))){
		return true;
	}else{
		return false;
	}
}

function add_extra_fields($table, $column, $column_attr = 'VARCHAR(255) NULL'){

	$configXml = simplexml_load_file('includes/configure.xml');
	$db = $configXml->config[7]->value;
	$link = mysql_connect($configXml->config[4]->value, $configXml->config[5]->value, $configXml->config[6]->value);
	if (! $link){
		die(mysql_error());
	}
	mysql_select_db($db , $link) or die("Select Error: ".mysql_error());
	if(table_exists($table)){
		$exists = false;
		$columns = mysql_query("show columns from $table");
		while($c = mysql_fetch_assoc($columns)){
			if($c['Field'] == $column){
				$exists = true;
				break;
			}
		}

		if(!$exists){
			mysql_query("ALTER TABLE `$table` ADD `$column`  $column_attr") or die("An error occured when running \n ALTER TABLE `$table` ADD `$column`  $column_attr \n" . mysql_error());
		}
	}

}

function update_extra_fields($table, $column, $column_attr = 'VARCHAR(255) NULL'){

	$configXml = simplexml_load_file('includes/configure.xml');
	$db = $configXml->config[7]->value;
	$link = mysql_connect($configXml->config[4]->value, $configXml->config[5]->value, $configXml->config[6]->value);
	if (! $link){
		die(mysql_error());
	}
	mysql_select_db($db , $link) or die("Select Error: ".mysql_error());
	if(table_exists($table)){
		$exists = false;
		$columns = mysql_query("show columns from $table");
		while($c = mysql_fetch_assoc($columns)){
			if($c['Field'] == $column){
				$exists = true;
				break;
			}
		}

		if(!$exists){
			mysql_query("ALTER TABLE `$table` CHANGE `$column` `$column` $column_attr") or die("An error occured when running \n ALTER TABLE `$table` ADD `$column`  $column_attr \n" . mysql_error());
		}
	}

}


function updatePagesDescription(){
	$db=sysConfig::get('DB_DATABASE');
	$link = mysql_connect(sysConfig::get('DB_SERVER'), sysConfig::get('DB_SERVER_USERNAME'), sysConfig::get('DB_SERVER_PASSWORD'));
	if (! $link){
		die(mysql_error());
	}
	mysql_select_db($db , $link) or die("Select Error: ".mysql_error());
	mysql_query("ALTER TABLE  `pages_description` CHANGE  `pages_html_text`  `pages_html_text` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL") or die('An error occured when running updating pages_description table'.mysql_error());
	mysql_query("ALTER TABLE  `pages_description` CHANGE  `pages_title`  `pages_title` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL") or die('An error occured when running updating pages_description table'.mysql_error());
}



function updateConfiguration($key, $group, $title, $desc, $default, $func) {
	$Qcheck = Doctrine_Query::create()
		->select('configuration_id')
		->from('Configuration')
		->where('configuration_key = ?', $key)
		->fetchOne();

	if ($Qcheck) {
		if($title != -1){
			$Qcheck->configuration_title = $title;
		}
		if($default != -1){
			$Qcheck->configuration_value = $default;
		}
		if($desc != -1){
			$Qcheck->configuration_description = $desc;
		}
		if($group != -1){
			$Qcheck->configuration_group_id = $group;
		}
		$Qcheck->sort_order = 11;
		if($func != -1){
			$Qcheck->set_function = $func;
		}
		$Qcheck->save();
	}
}
function addStatus($status_name) {
	$Qstatus = Doctrine_Query::create()
		->select('s.orders_status_id, sd.orders_status_name')
		->from('OrdersStatus s')
		->leftJoin('s.OrdersStatusDescription sd')
		->where('sd.language_id = ?', (int) Session::get('languages_id'))
		->andWhere('sd.orders_status_name=?', $status_name)
		->orderBy('s.orders_status_id')
		->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
	if (count($Qstatus) <= 0) {
		$Status = new OrdersStatus();
		$Description = &$Status->OrdersStatusDescription;
		foreach (sysLanguage::getLanguages() as $lInfo) {
			$Description[$lInfo['id']]->language_id = $lInfo['id'];
			$Description[$lInfo['id']]->orders_status_name = $status_name;
		}
		$Status->save();
	}
}

function addInfoPage($page_name, $page_text) {
	$QPages = Doctrine_Query::create()
		->from('Pages p')
		->leftJoin('p.PagesDescription pd')
		->andWhere('p.page_key = ?', $page_name)
		->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
	if (count($QPages) <= 0) {
		$Page = new Pages();
		$Page->page_type = 'page';
		$Page->page_key = $page_name;
		$Description = $Page->PagesDescription;
		foreach (sysLanguage::getLanguages() as $lInfo) {
			$Description[$lInfo['id']]->language_id = $lInfo['id'];
			$Description[$lInfo['id']]->pages_title = $page_name;
			$Description[$lInfo['id']]->pages_html_text = $page_text;
		}
		$Page->save();
	}
}

function addEmailTemplateVariables($variableName,$event, $is_conditional = 0, $condition_check = ''){
	$emailTemplates = Doctrine_Core::getTable('EmailTemplates')->findOneByEmailTemplatesEvent($event);
	if($emailTemplates){
		$EmailTemplatesVariables = Doctrine_Core::getTable('EmailTemplatesVariables');
		$EmailTemplatesVariableCheck = $EmailTemplatesVariables->findOneByEmailTemplatesIdAndEventVariable($emailTemplates->email_templates_id,$variableName);
		if(!$EmailTemplatesVariableCheck){
			$emailTemplatesVariable = new EmailTemplatesVariables();
			$emailTemplatesVariable->email_templates_id = $emailTemplates->email_templates_id;
			$emailTemplatesVariable->event_variable = $variableName;
			$emailTemplatesVariable->is_conditional = $is_conditional;
			$emailTemplatesVariable->condition_check = $condition_check;
			$emailTemplatesVariable->save();
		}
	}
}


function installPDFInfobox($boxPath, $className, $extName = null){
	$moduleDir = sysConfig::getDirFsCatalog() . $boxPath;
	if (is_dir($moduleDir . 'Doctrine/base/')){
		Doctrine_Core::createTablesFromModels($moduleDir . 'Doctrine/base/');
	}

	$className = 'PDFInfoBox' . ucfirst($className);
	if (!class_exists($className)){
		require($moduleDir . 'pdfinfobox.php');
	}
	$class = new $className;

	$Infobox = new PDFTemplatesInfoboxes();
	$Infobox->box_code = $class->getBoxCode();
	$Infobox->box_path = $boxPath;
	if (!is_null($extName)){
		$Infobox->ext_name = $extName;
	}
	$Infobox->save();
}

function installInfobox($boxPath, $className, $extName = null){
	$moduleDir = sysConfig::getDirFsCatalog() . $boxPath;
	if (is_dir($moduleDir . 'Doctrine/base/')){
		Doctrine_Core::createTablesFromModels($moduleDir . 'Doctrine/base/');
	}

	$className = 'InfoBox' . ucfirst($className);
	if(file_exists($moduleDir . 'infobox.php')){
		if (!class_exists($className)){
			require($moduleDir . 'infobox.php');
		}
		$class = new $className;

		$Infobox = new TemplatesInfoboxes();
		$Infobox->box_code = $class->getBoxCode();
		$Infobox->box_path = $boxPath;
		if (!is_null($extName)){
			$Infobox->ext_name = $extName;
		}
		$Infobox->save();
	}
}

function addLayoutToPage($app, $appPage, $extName, $layoutId){
	$TemplatePages = Doctrine_Core::getTable('TemplatePages');
	if (!is_null($extName)){
		$Page = $TemplatePages->findOneByApplicationAndPageAndExtension($app, $appPage, $extName);
	}else{
		$Page = $TemplatePages->findOneByApplicationAndPage($app, $appPage);
	}

	if (!$Page){
		$Page = $TemplatePages->create();
		$Page->layout_id = $layoutId;
		$Page->application = $app;
		$Page->page = $appPage;
		if (!is_null($extName)){
			$Page->extension = $extName;
		}
	}elseif ($Page->count() > 0){
		$Page->layout_id .= ',' . $layoutId;
	}
	$Page->save();
}


function importPDFLayouts(){
	$PDFTemplateLayouts = Doctrine_Core::getTable('PDFTemplateLayouts');
	$PDFTemplatesInfoboxes = Doctrine_Core::getTable('PDFTemplatesInfoboxes');
	$Qcount = Doctrine_Query::create()
		->from('PDFTemplateManagerLayouts')
		->execute(array(), Doctrine_Core::HYDRATE_ARRAY);

	if(count($Qcount) == 0){
		require(sysConfig::getDirFsCatalog() . 'ext/pdfLayouts/installDataInvoice.php');
	}

}

function tep_get_languages() {
	$languages_query =  Doctrine_Manager::getInstance()
		->getCurrentConnection()
		->fetchAssoc('select languages_id, name, code, image, directory from languages where status = "1" order by sort_order');
	$languages_array = array();
	foreach($languages_query as $languages){
		$languages_array[] = array('id' => $languages['languages_id'],
			'name' => $languages['name'],
			'code' => $languages['code'],
			'image' => $languages['image'],
			'directory' => $languages['directory']);
	}

	return $languages_array;
}

function updateCategoriesSEOUrls(){
	$QCategories = Doctrine_Query::create()
		->from('Categories c')
		->leftJoin('c.CategoriesDescription cd')
		->execute();
	$languages = tep_get_languages();
	foreach($QCategories as $Category){
		$CategoriesDescription =& $Category->CategoriesDescription;
		for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
			$lID = $languages[$i]['id'];
			if($CategoriesDescription[$lID]->categories_seo_url == ''){
				$CategoriesDescription[$lID]->categories_seo_url = makeUniqueCategory($Category->categories_id, tep_friendly_seo_url($CategoriesDescription[$lID]->categories_name), true);
			}else{
				$CategoriesDescription[$lID]->categories_seo_url = makeUniqueCategory($Category->categories_id, tep_friendly_seo_url($CategoriesDescription[$lID]->categories_seo_url), true);
			}
		}
		$Category->save();
	}

}


function addEmailTemplate($name, $event, $attach, $subject, $content){
	$emailTemplates = Doctrine_Core::getTable('EmailTemplates')->findOneByEmailTemplatesEvent($event);
	if(!$emailTemplates){
		$emailTemplate = new EmailTemplates;
		$emailTemplate->email_templates_name = $name;
		$emailTemplate->email_templates_event = $event;
		if(!empty($attach)){
			$emailTemplate->email_templates_attach = $attach;
		}
		$emailTemplate->save();
		$emailTemplateDescription = new EmailTemplatesDescription;
		$emailTemplateDescription->email_templates_id = $emailTemplate->email_templates_id;
		$emailTemplateDescription->email_templates_subject = $subject;
		$emailTemplateDescription->email_templates_content = $content;
		$emailTemplateDescription->language_id = Session::get('languages_id');

		$emailTemplateDescription->save();
	}
}

function update_extra(){
	add_extra_fields('admin','admin_override_password',"VARCHAR( 40 ) NOT NULL DEFAULT  ''");
	add_extra_fields('admin','admins_stores'," text NOT NULL");
	add_extra_fields('admin','admins_main_store',"int(11) NOT NULL");
	add_extra_fields('admin','admin_simple_admin',"int(1) NOT NULL default '0'");
	add_extra_fields('admin','admin_favs_id',"int(11) NOT NULL");
	add_extra_fields('languages','forced_default',"int(1) NOT NULL default '0'");
	add_extra_fields('configuration','configuration_group_key',"VARCHAR( 200 ) NOT NULL DEFAULT  ''");
}

function update_configs(){

	require(sysConfig::getDirFsCatalog() . 'includes/classes/ftp/base.php');
	$Ftp = new SystemFTP();
	$Ftp->connect();
	$Ftp->makeWritable(sysConfig::getDirFsCatalog().'images');
	$Ftp->makeWritable(sysConfig::getDirFsCatalog().'cache');
	$Ftp->makeWritable(sysConfig::getDirFsCatalog().'cache/admin/javascript');
	$Ftp->makeWritable(sysConfig::getDirFsCatalog().'cache/admin/stylesheet');
	$Ftp->makeWritable(sysConfig::getDirFsCatalog().'cache/catalog/javascript');
	$Ftp->makeWritable(sysConfig::getDirFsCatalog().'cache/catalog/stylesheet');
	$Ftp->makeWritable(sysConfig::getDirFsCatalog().'temp');
	$Ftp->createDir(sysConfig::getDirFsCatalog().'temp/pdf');
	$Ftp->createDir(sysConfig::getDirFsCatalog().'images/templates');
	$Ftp->makeWritable(sysConfig::getDirFsCatalog().'temp/pdf');
	$Ftp->makeWritable(sysConfig::getDirFsCatalog().'images/templates');

	$Ftp->makeWritable(sysConfig::getDirFsCatalog().'extensions/imageRot/images');
	$Ftp->makeWritable(sysConfig::getDirFsCatalog().'extensions/pdfPrinter/images');
	$Ftp->makeWritable(sysConfig::getDirFsCatalog().'templates');


	require(sysConfig::getDirFsCatalog() . 'includes/classes/fileSystemBrowser.php');
	$templates = new fileSystemBrowser(sysConfig::getDirFsCatalog()  . 'templates/');
	$directories = $templates->getDirectories();

	foreach($directories as $dirInfo){
		$Ftp->makeWritable(sysConfig::getDirFsCatalog().'templates/'.$dirInfo['basename'].'/images');
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(sysConfig::getDirFsCatalog().'includes/languages'),
		RecursiveIteratorIterator::SELF_FIRST);

	$iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);

	foreach($iterator as $file) {
		if(($file->isDir() || $file->isFile())) {
			$Ftp->makeWritable($file->getRealpath());
		}
	}

	$Ftp->disconnect();

}
function updateModules(){
	//Add Installed Key For Modules That Are Enabled
	//--BEGIN--
	$ResultSet = Doctrine_Manager::getInstance()
		->getCurrentConnection()
		->fetchAssoc('select modules_id from modules_configuration where configuration_key LIKE "MODULE_%_STATUS"');
	foreach($ResultSet as $kInfo){
		Doctrine_Manager::getInstance()
			->getCurrentConnection()
			->exec('insert into modules_configuration (configuration_key, configuration_value, modules_id) values ("INSTALLED", "True", "' . $kInfo['modules_id'] . '")');
	}
	//Add Installed Key For Modules That Are Enabled
	//--END--

	//Update Configuration Keys That Are Known To Be Common
	//--BEGIN--
	Doctrine_Manager::getInstance()
		->getCurrentConnection()
		->exec('update modules_configuration set configuration_key = "CHECKOUT_METHOD" where configuration_key LIKE "%_CHECKOUT_METHOD"');
	Doctrine_Manager::getInstance()
		->getCurrentConnection()
		->exec('update modules_configuration set configuration_key = "ORDER_STATUS_ID" where configuration_key LIKE "%_ORDER_STATUS_ID"');
	Doctrine_Manager::getInstance()
		->getCurrentConnection()
		->exec('update modules_configuration set configuration_key = "ZONE" where configuration_key LIKE "%_ZONE"');
	Doctrine_Manager::getInstance()
		->getCurrentConnection()
		->exec('update modules_configuration set configuration_key = "DISPLAY_ORDER" where configuration_key LIKE "%_SORT_ORDER"');
	Doctrine_Manager::getInstance()
		->getCurrentConnection()
		->exec('update modules_configuration set configuration_key = "STATUS" where configuration_key LIKE "%_STATUS"');
	Doctrine_Manager::getInstance()
		->getCurrentConnection()
		->exec('update modules_configuration set configuration_key = "TAX_CLASS" where configuration_key LIKE "%_TAX_CLASS"');
	//Update Configuration Keys That Are Known To Be Common
	//--END--

	//Update Module Types From Old
	//--BEGIN--
	Doctrine_Manager::getInstance()
		->getCurrentConnection()
		->exec('update modules set modules_type = "orderPayment" where modules_type = "order_payment"');
	Doctrine_Manager::getInstance()
		->getCurrentConnection()
		->exec('update modules set modules_type = "orderShipping" where modules_type = "order_shipping"');
	Doctrine_Manager::getInstance()
		->getCurrentConnection()
		->exec('update modules set modules_type = "orderTotal" where modules_type = "order_total"');
	//Update Module Types From Old
	//--END--

	//Delete all configuration entries that are the same value as the xml configuration files
	// --BEGIN--
	$Directory = new DirectoryIterator(sysConfig::getDirFsCatalog() . 'includes/configs/');
	foreach($Directory as $ConfigFile){
		if ($ConfigFile->isDot() || $ConfigFile->isDir()) continue;

		$Configuration = simplexml_load_file(
			$ConfigFile->getPathname(),
			'SimpleXMLElement',
			LIBXML_NOCDATA
		);
		$keys = array();
		foreach($Configuration->tabs->children() as $tInfo){
			foreach($tInfo->configurations->children() as $ConfigKey => $Config){
				$key = (string) $ConfigKey;
				$value = (string) $Config->value;

				$ResultSet = Doctrine_Manager::getInstance()
					->getCurrentConnection()
					->fetchAssoc('select configuration_value from configuration where configuration_key = "' . $key . '"');
				if ($ResultSet && sizeof($ResultSet) > 0){
					/*if ($value == $ResultSet[0]['configuration_value']){
						$keys[] = '"' . $key . '"';
					}else{*/
						Doctrine_Manager::getInstance()
							->getCurrentConnection()
							->exec('update configuration set configuration_group_key = "' . (string)$Configuration->key . '" where configuration_key = "' . $key . '"');
					//}
				}else{
					$Configuration1 = Doctrine_Core::getTable('Configuration')->findAll();
					$ConfigGroup = new MainConfigReader((string)$Configuration->key);
					//foreach($_POST['configuration'] as $k => $v){
					$Configuration1[$key]->configuration_group_key = $ConfigGroup->getKey();
					$Configuration1[$key]->configuration_key = $key;
					/*if (is_array($value)){
							$Config = $ConfigGroup->getConfig($key);
							$Configuration[$key]->configuration_value = implode($Config->getGlue(), $value);
					}else{*/
						$Configuration1[$key]->configuration_value = $value;
						$Configuration1->save();
					//}

					//}
				}

			}
		}

		/*if (!empty($keys)){
			Doctrine_Manager::getInstance()
				->getCurrentConnection()
				->exec('delete from configuration where configuration_key in(' . implode(',', $keys) . ')');
		} */
	}


	$Directory = new DirectoryIterator(sysConfig::getDirFsCatalog() . 'extensions/');
	foreach($Directory as $ConfigFile){
		if ($ConfigFile->isDot() || $ConfigFile->isFile()) continue;

		$Configuration = simplexml_load_file(
			$ConfigFile->getPathname() . '/data/base/configuration.xml',
			'SimpleXMLElement',
			LIBXML_NOCDATA
		);
		$keys = array();
		foreach($Configuration->tabs->children() as $tInfo){
			foreach($tInfo->configurations->children() as $ConfigKey => $Config){
				$key = (string) $ConfigKey;
				$value = (string) $Config->value;

				$ResultSet = Doctrine_Manager::getInstance()
					->getCurrentConnection()
					->fetchAssoc('select configuration_value from configuration where configuration_key = "' . $key . '"');
				if ($ResultSet && sizeof($ResultSet) > 0){
					/*if ($value == $ResultSet[0]['configuration_value']){
						$keys[] = '"' . $key . '"';
					}else{*/
						Doctrine_Manager::getInstance()
							->getCurrentConnection()
							->exec('update configuration set configuration_group_key = "' . (string)$Configuration->key . '" where configuration_key = "' . $key . '"');
					//}
				}else{
					/*$Configuration1 = Doctrine_Core::getTable('Configuration')
						->findByConfigurationGroupKey((string)$Configuration->key);
					if(strpos($key,'_INSTALLED') === false && strpos($key,'_ENABLED') === false){
						$ExtConfig = new ExtensionConfigReader((string)$Configuration->key);
						//foreach($_POST['configuration'] as $k => $v){
							$Configuration1[$key]->configuration_group_key = $ExtConfig->getKey();
							$Configuration1[$key]->configuration_key = $key;
							$Configuration1[$key]->configuration_value = $value;
						//}
						$Configuration1->save();
					}*/
				}
			}
		}

		/*if (!empty($keys)){
			Doctrine_Manager::getInstance()
				->getCurrentConnection()
				->exec('delete from configuration where configuration_key in(' . implode(',', $keys) . ')');
		} */
	}
	//Delete all configuration entries that are the same value as the xml configuration files
	// --END--

	//Add Installed Key For Extensions That Are Enabled
	//--BEGIN--
	$ResultSet = Doctrine_Manager::getInstance()
		->getCurrentConnection()
		->fetchAssoc('select configuration_key, configuration_value, configuration_group_key from configuration where configuration_key LIKE "EXTENSION_%_ENABLED"');
	foreach($ResultSet as $kInfo){
		$newKey = preg_replace('/_ENABLED/', '_INSTALLED', $kInfo['configuration_key']);
		$ResultSet1 = Doctrine_Manager::getInstance()
			->getCurrentConnection()
			->fetchAssoc('select configuration_key, configuration_value, configuration_group_key from configuration where configuration_key LIKE "EXTENSION_%_INSTALLED" AND configuration_group_key = "'.$kInfo['configuration_group_key'].'"');
		if(!$ResultSet1 || sizeof($ResultSet1) <= 0){
		Doctrine_Manager::getInstance()
			->getCurrentConnection()
			->exec('insert into configuration (configuration_key, configuration_value, configuration_group_key) values ("' . $newKey . '", "True", "' . $kInfo['configuration_group_key'] . '")');
		}
	}
	//Add Installed Key For Extensions That Are Enabled
	//--END--

}

function updateAddressFormat(){
	//Update address formats for new formatting
	//--BEGIN--
	$ResultSet = Doctrine_Manager::getInstance()
		->getCurrentConnection()
		->fetchAssoc('select * from address_format');
	foreach($ResultSet as $aInfo){
		$newFormat = str_replace('$cr', "\n", $aInfo['address_format']);
		$newFormat = str_replace('$streets', '$street_address', $newFormat);
		Doctrine_Manager::getInstance()
			->getCurrentConnection()
			->exec('update address_format set address_format = "' . $newFormat . '" where address_format_id = "' . $aInfo['address_format_id'] . '"');
	}
	//Update address formats for new formatting
	//--END--
}

function updateHTaccess(){
	global $ftpConn;
	$folderList = explode(';', sysConfig::get('HTACCESS_FOLDERS'));
	$ftpCmd = ftp_chmod($ftpConn, octdec('0777'), '.htaccess');
	if (!$ftpCmd){
		echo 'Error chmod 777 .htaccess';
	}
	$htaccessPath = sysConfig::getDirFsCatalog(). '.htaccess';
	$htaccessFile = file_get_contents($htaccessPath);
	$htaccessFileContents = explode("\n", $htaccessFile);
	$pos = array_search('RewriteRule ^installAuto($|/) - [L]',$htaccessFileContents);
	if($pos !== false){
		foreach($folderList as $folder){
			if(!empty($folder)){
				array_splice($htaccessFileContents,$pos,0, 'RewriteRule ^'.$folder.'($|/) - [L]');
			}
		}
	}

	file_put_contents($htaccessPath, implode("\n", $htaccessFileContents));
	$ftpCmd = ftp_chmod($ftpConn, octdec('0644'), '.htaccess');
	if (!$ftpCmd){
		echo 'Error chmod 644 .htaccess';
	}
}

function updateToolsConfiguration(){
	/*//addConfiguration('SHOW_MANUFACTURER_ON_PRODUCT_INFO', 1, 'Show manufacturer name on product Info', 'Show manufacturer name on product Info', 'false', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('SHOW_PRODUCTS_FROM_SUBCATEGORIES', 1, 'Show all the products from all the subcategories of the current category', 'Show all the products from all the subcategories of the current category', 'false', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('SHOW_SUBCATEGORIES', 1, 'Show subcategories of the current category', 'Show subcategories of the current category', 'true', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('ONEPAGE_CHECKOUT_SHIPPING_ADDRESS', 7575, 'Show shipping address on checkout', 'Show shipping address on checkout', 'true', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('PRODUCT_INFO_SHOW_MODEL', 1, 'Show model on product info', 'Show model on product Info', 'true', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('CUSTOMER_CHANGE_SEND_NOTIFICATION_EMAIL_DEFAULT', 1, 'Send customer email when a change is made to his account set as default', 'Send customer email set as default', 'true', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('CHECK_STOCK_NEW_USED', 1, 'Check Stock for new and used purchase types', 'Check Stock for new and used purchase types', 'true', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('ORDERS_STATUS_CANCELLED_ID', 1, 'Order Status cancel ID', 'Order Status cancel ID', '7', 'tep_cfg_pull_down_order_status_list(');
	//addConfiguration('ORDERS_STATUS_WAITING_ID', 1, 'Order Status Waiting for Confirmation ID', 'Order Status Waiting for Confirmation ID', '6', 'tep_cfg_pull_down_order_status_list(');
	//addConfiguration('ORDERS_STATUS_APPROVED_ID', 1, 'Order Status Order Approved ID', 'Order Status order Approved ID', '8', 'tep_cfg_pull_down_order_status_list(');
	//addConfiguration('ORDERS_STATUS_PROCESSING_ID', 1, 'Order Status order Processing ID', 'Order Status order Processing ID', '1', 'tep_cfg_pull_down_order_status_list(');
	//addConfiguration('ORDERS_STATUS_DELIVERED_ID', 1, 'Order Status order Delivered ID', 'Order Status order Delivered ID', '3', 'tep_cfg_pull_down_order_status_list(');
	//addConfiguration('ORDERS_STATUS_ESTIMATE_ID', 1, 'Order Status order Estimate ID', 'Order Status order estimate ID', '9', 'tep_cfg_pull_down_order_status_list(');
	//addConfiguration('ORDERS_STATUS_SHIPPED_ID', 1, 'Order Status Order Shipped ID', 'Order Status order Shipped ID', '10', 'tep_cfg_pull_down_order_status_list(');
	//addConfiguration('ACCOUNT_FISCAL_CODE_REQUIRED', 5, 'Fiscal Code required', 'Fiscal Code required', 'false', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('ACCOUNT_VAT_NUMBER_REQUIRED', 5, 'VAT Number required', 'VAT Number required', 'false', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('ACCOUNT_CITY_BIRTH_REQUIRED', 5, 'City of birth required', 'City of birth required', 'false', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('ACCOUNT_NEWSLETTER', 5, 'Enable newsletter subscription', 'Enable newsletter subscription', 'false', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('ENABLE_HTML_EDITOR', 1, 'Use wysiwyg editor for product description', 'Use wysiwyg editor to edit product description', 'true', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('SHOW_ENLAGE_IMAGE_TEXT', 1, 'Show enlarge image text on product info page', 'Show enlarge image text on product info page', 'true', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('PRODUCT_LISTING_TYPE', 8, 'Use rows or columns for product listing', 'Use rows or columns for product listing', 'row', "tep_cfg_select_option(array('row', 'column'),");
	//addConfiguration('PRODUCT_LISTING_ALLOW_PRODUCT_SORTER', 8, 'Allow sort products', 'Allow sort products', 'True', "tep_cfg_select_option(array('True', 'False'),");

	//addConfiguration('PRODUCT_LISTING_TOTAL_WIDTH', 8, 'When using columns for product listing content area width to use when calculating image width', 'When using columns for product listing content area width to use when calculating image width', '600', "");
	//addConfiguration('PRODUCT_LISTING_PRODUCTS_COLUMNS', 8, 'When using columns for product listing number of products to display in a row', 'When using columns for product listing number of products to display in a row', '4', "");
	//addConfiguration('CATEGORIES_MAX_WIDTH', 8, 'Max width for the subcategories images', 'Max width for the subcategories images', '170', "");
	//addConfiguration('CATEGORIES_MAX_HEIGHT', 8, 'Max height for the subcategories images', 'Max height for the subcategories images', '170', "");
	//addConfiguration('PRODUCT_LISTING_PRODUCTS_LIMIT', 8, 'Number of products to list per page', 'Number of products to list per page (max 25)', '12', "");
	//addConfiguration('PRODUCT_LISTING_SELECT_MULTIPLES', 8, 'Can add to cart multiple items', 'Can add to cart multiple items?', 'false', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('PRODUCT_LISTING_PRODUCTS_LIMIT_ARRAY', 8, 'Results Per Page drop down values', 'Results Per Page drop down values (comma seperated example:<br>12,24,48,96)', '12,24,48,96', "");
	//addConfiguration('TOOLTIP_DESCRIPTION_ENABLED', 8, 'Enable product image tooltip description for products listing?', 'Enable product image tooltip description for products listing?', 'true', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('TOOLTIP_DESCRIPTION_BUTTONS', 8, 'Show buttons in product image tooltip description for products listing?', 'Show buttons in product image tooltip description for products listing?', 'true', "tep_cfg_select_option(array('true', 'false'),");

	//addConfiguration('RENTAL_DAYS_CUSTOMER_PAST_DUE', 16, 'How many days past due the customer is allowed to rent and receive items', 'How many days past due the customer is allowed to rent and receive items', '3', '');
	//updateConfiguration('DIR_WS_TEMPLATES_DEFAULT', -1, -1, -1, -1, "tep_cfg_pull_down_template_list(");
	//addConfiguration('SHOW_COMMENTS_CHECKOUT', 1, 'Show comments on checkout', 'Show comments on checkout page', 'true', "tep_cfg_select_option(array('true', 'false'),");

	//addConfiguration('ACCOUNT_COMPANY_REQUIRED',5, 'Company required', 'Company required','false',"tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('ACCOUNT_SUBURB_REQUIRED',5, 'Suburb required', 'Suburb required','false',"tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('ACCOUNT_GENDER_REQUIRED',5, 'Gender required', 'Gender required','false',"tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('ACCOUNT_DOB_REQUIRED',5, 'DOB required', 'DOB required','false',"tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('ACCOUNT_STATE_REQUIRED',5, 'State required', 'State required','false',"tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('ACCOUNT_VAT_NUMBER',5, 'VAT Number', 'VAT Number','false',"tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('ACCOUNT_TELEPHONE',5, 'Telephone Number', 'Telephone Number','false',"tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('ACCOUNT_FISCAL_CODE',5, 'Fiscal Code', 'Fiscal Code','false',"tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('ACCOUNT_CITY_BIRTH', 5, 'City of birth', 'City of birth', 'false', "tep_cfg_select_option(array('true', 'false'),");

	//addConfiguration('BARCODE_TYPE', 1, 'Choose barcode type to use in the store', 'Choose barcode type to use in the store', 'Code 39', "tep_cfg_select_option(array('Code 128B', 'Code 39 Extended', 'QR', 'Code 39', 'Code 25', 'Code 25 Interleaved'),");
	//addConfiguration('SHOW_IP_ADDRESS_ORDERS_DETAILS', 1, 'Show IP address on orders details page', 'Show IP address on orders details page', 'true', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('SHOW_PACKING_SLIP_BUTTONS', 1, 'Show packing slip buttons', 'Show packing slip buttons', 'true', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('BARCODES_INVENTORY_TYPES', 1, 'List of barcodes inventory types', 'List of barcodes inventory types separated by ;', '', '');

	//addConfiguration('SITE_MAINTENANCE_MODE', 1, 'Site In maintenance mode', 'Site in maintenance mode', 'false', "tep_cfg_select_option(array('true', 'false'),");
	//addConfiguration('IP_LIST_MAINTENANCE_ENABLED', 1, 'List of IP enabled in maintenance mode', 'List of IP enabled in maintenance mode separated by ;', '', '');


	//addConfiguration('REQUEST_PICKUP_BEFORE_DAYS', 16, 'How many days to not show on the request pickup or delivery', 'How many days to not show on the request pickup or delivery', '2', '');
	//addConfiguration('SHOW_STANDARD_CREATE_ACCOUNT', 1, 'Show standard create account tab on account login', 'Show standard create account tab on account login', 'true', "tep_cfg_select_option(array('true', 'false'),");

	//addConfiguration('GOOGLE_API_SERVER_KEY', 1, 'Google API Server Key', 'The server key created through googles apis page', '', '');
	//addConfiguration('GOOGLE_API_BROWSER_KEY', 1, 'Google API Browser Key', 'The browser key created through googles apis page', '', '');
    */


	$EmailTemplatesVariables = Doctrine_Core::getTable('EmailTemplatesVariables');
	$EmailTemplatesVariableCheck = $EmailTemplatesVariables->findOneByEmailTemplatesIdAndEventVariable(17,'adminEditLink');

	if($EmailTemplatesVariableCheck == false)
	{
		$Variable = new EmailTemplatesVariables();
		$Variable->event_variable = 'adminEditLink';
		$Variable->is_conditional = '0';
		$Variable->email_templates_id = '17';
		$Variable->save();
	}

	addEmailTemplate('(Admin) Estimate Success','estimate_success','','Estimate Receipt','{$store_name} -
------------------------------------------------------------------
Estimate Order ID: {$order_id}
Estimate Invoice URL: {$invoice_link}
Date Ordered: {$date_ordered}
<!-- if ($rental_city)
Rental City/Town: {$rental_city}
Delivery Depot: {$delivery_depot}
-->

{$order_comments}

Products Ordered:
------------------------------------------------------------------
{$ordered_products}
------------------------------------------------------------------
{$orderTotals}

<!-- if ($shipping_address)
Shipping Address
------------------------------------------------------------------
{$shipping_address}

-->
Billing Address
------------------------------------------------------------------
{$billing_address}


Payment Method
------------------------------------------------------------------
{$paymentTitle}
<!-- if ($po_number)
{$po_number}
-->
<!-- if ($payment_footer)
{$payment_footer}
-->

{$terms}');
	addEmailTemplateVariables('order_id','estimate_success');
	addEmailTemplateVariables('invoice_link','estimate_success');
	addEmailTemplateVariables('date_ordered','estimate_success');
	addEmailTemplateVariables('ordered_products','estimate_success');
	addEmailTemplateVariables('orderTotals','estimate_success');
	addEmailTemplateVariables('billing_address','estimate_success');
	addEmailTemplateVariables('paymentTitle','estimate_success');
	addEmailTemplateVariables('terms','estimate_success');

	addEmailTemplate('Return Reminders','return_reminder','','Return Reminder Alert','Hello {$firstname},<br/><br/>The following products are to be returned {$rented_list}<br/><br/>Regards,<br/>{$store_owner}');
	addEmailTemplateVariables('firstname','return_reminder');
	addEmailTemplateVariables('email_address','return_reminder');
	addEmailTemplateVariables('rented_list','return_reminder');

	addEmailTemplate('Shipment Due Reminders','ship_reminder','','Shipment Due Reminder','Hello {$firstname},<br/><br/>The following products are due to be shipped {$rented_list}<br/><br/>Regards,<br/>{$store_owner}');
	addEmailTemplateVariables('firstname','ship_reminder');
	addEmailTemplateVariables('rented_list','ship_reminder');

	addEmailTemplateVariables('order_has_streaming_or_download','order_success', '1', 'order_has_streaming_or_download');
	addEmailTemplateVariables('customerFirstName','membership_activated_admin');
	addEmailTemplateVariables('customerLastName','membership_activated_admin');
	addEmailTemplateVariables('currentPlanPackageName','membership_activated_admin');
	addEmailTemplateVariables('currentPlanMembershipDays','membership_activated_admin');
	addEmailTemplateVariables('currentPlanNumberOfTitles','membership_activated_admin');
	addEmailTemplateVariables('currentPlanFreeTrial','membership_activated_admin');
	addEmailTemplateVariables('currentPlanPrice','membership_activated_admin');
	addEmailTemplateVariables('previousPlanPackageName','membership_activated_admin');
	addEmailTemplateVariables('previousPlanMembershipDays','membership_activated_admin',1);
	addEmailTemplateVariables('previousPlanNumberOfTitles','membership_activated_admin');
	addEmailTemplateVariables('previousPlanFreeTrial','membership_activated_admin');
	addEmailTemplateVariables('previousPlanPrice','membership_activated_admin');

	addEmailTemplateVariables('customerFirstName','membership_upgraded_admin');
	addEmailTemplateVariables('customerLastName','membership_upgraded_admin');
	addEmailTemplateVariables('currentPlanPackageName','membership_upgraded_admin');
	addEmailTemplateVariables('currentPlanMembershipDays','membership_upgraded_admin');
	addEmailTemplateVariables('currentPlanNumberOfTitles','membership_upgraded_admin');
	addEmailTemplateVariables('currentPlanFreeTrial','membership_upgraded_admin');
	addEmailTemplateVariables('currentPlanPrice','membership_upgraded_admin');
	addEmailTemplateVariables('previousPlanPackageName','membership_upgraded_admin');
	addEmailTemplateVariables('previousPlanMembershipDays','membership_upgraded_admin',1);
	addEmailTemplateVariables('previousPlanNumberOfTitles','membership_upgraded_admin');
	addEmailTemplateVariables('previousPlanFreeTrial','membership_upgraded_admin');
	addEmailTemplateVariables('previousPlanPrice','membership_upgraded_admin');

	addStatus('Waiting Confirmation');
	addStatus('Cancelled');
	addStatus('Approved');
	addStatus('Estimate');
	addStatus('Shipped');
	addInfoPage('maintenance_page','<div style="margin:0 auto;text-align:center;"><img src="'.sysConfig::getDirWsCatalog().'images/logo.png" /> <p style="font-size:30px;">This Site Is Under Maintenance</p> </div>');

	updatePagesDescription();
	updateCategoriesSEOUrls();
	updateAddressFormat();
	updateHTaccess();
	importPDFLayouts();

	Doctrine_Query::create()
	->update('TemplatesInfoboxes')
	->set('box_path', '?', 'extensions/imageRot/catalog/infoboxes/banner/')
	->set('ext_name', '?', 'imageRot')
	->where('box_code = ?', 'banner')
	->execute();

	add_extra_fields('modules_shipping_zone_reservation_methods','weight_rates','TEXT NULL');
	add_extra_fields('modules_shipping_zone_reservation_methods','min_rental_number'," INT( 1 ) NOT NULL DEFAULT  '0'");
	add_extra_fields('modules_shipping_zone_reservation_methods','min_rental_type'," INT( 1 ) NOT NULL DEFAULT  '0'");
	update_extra_fields('orders','terms','TEXT');
	add_extra_fields('modules_shipping_zone_reservation_methods','method_zipcode','TEXT NULL');
	add_extra_fields('modules_shipping_zone_reservation_methods','free_delivery_over'," FLOAT(15,2) NOT NULL DEFAULT  '-1'");
}

function updateTemplates(){
	//$TemplateLayouts = Doctrine_Core::getTable('TemplateLayouts');
	$TemplatePages = Doctrine_Core::getTable('TemplatePages');
	$TemplatesInfoboxes = Doctrine_Core::getTable('TemplatesInfoboxes');
	$TemplatesInfoboxesToTemplates = Doctrine_Core::getTable('TemplatesInfoboxesToTemplates');
	$QhasCodeGeneration = Doctrine_Query::create()
		->from('TemplateManagerTemplatesConfiguration')
		->where('configuration_key = ?', 'NAME')
		->andWhere('configuration_value = ?','codeGeneration')
		->execute(array(), Doctrine_Core::HYDRATE_ARRAY);


	if(file_exists(sysConfig::getDirFsCatalog() . 'templates/codeGeneration/installData.php') && !isset($QhasCodeGeneration[0])){
		require(sysConfig::getDirFsCatalog() . 'templates/codeGeneration/installData.php');
	}
}

function run_updates(){
	global $appExtension;
	updateAllDbFields();
	update_configs();
	//addMissingConfigs();
	updateModules();
	updateTemplates();
	updateToolsConfiguration();
}


$ftpConn = ftp_connect(sysConfig::get('SYSTEM_FTP_SERVER'));
if ($ftpConn === false){
	die('Error ftp_connect');
}
else {
	$ftpCmd = ftp_login($ftpConn,sysConfig::get('SYSTEM_FTP_USERNAME') , sysConfig::get('SYSTEM_FTP_PASSWORD'));
	if (!$ftpCmd){
		die('Error ftp_login');
	}
}



$ftpCmd = ftp_chdir($ftpConn, 'public_html');
if (!$ftpCmd){
	die('Error ftp_chdir public_html');
}

run_updates();

ftp_close($ftpConn);
?>
Configuration Updated. Ignore errors, if any.<br/>
Don't forget to update layout configurations with the categories and infopages.
Don't forget to reinstall all modules if this is an upgrade.
<?php
	require('includes/application_bottom.php');
?>