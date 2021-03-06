<?php
/*
	I.T. Web Experts, Rental Store v2
	http://www.itwebexperts.com

	Copyright (c) 2009 I.T. Web Experts

	This script and it's source is not redistributable
*/
// start profiling
set_time_limit(0);
if (isset($_GET['runProfile'])){
	xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
}

error_reporting(E_ALL & ~E_DEPRECATED);

	function onShutdown(){
		global $ExceptionManager;
		// This is our shutdown function, in 
		// here we can do any last operations
		// before the script is complete.
		
		if ($ExceptionManager->size() > 0){
			echo '<br /><div style="width:98%;margin-right:auto;margin-left:auto;">' . $ExceptionManager->output() . '</div>';
		}
	}
	register_shutdown_function('onShutdown');

	define('APPLICATION_ENVIRONMENT', 'admin');
	define('START_MEMORY_USAGE', memory_get_usage());
/* TO BE MOVED LATER -- BEGIN -- */
	define('USER_ADDRESS_BOOK_ENABLED', 'True');
	date_default_timezone_set('America/New_York');
/* TO BE MOVED LATER -- END -- */

// Start the clock for the page parse time log
	define('PAGE_PARSE_START_TIME', microtime());

require((isset($basePath) ? $basePath : '') . '../includes/classes/ConfigReader/Base.php');
require((isset($basePath) ? $basePath : '') . '../includes/classes/MainConfigReader.php');
require((isset($basePath) ? $basePath : '') . '../includes/classes/ExtensionConfigReader.php');
	require((isset($basePath) ? $basePath : '') . '../includes/classes/system_configuration.php');
	/*
	 * Load system path/database settings
	 */
	sysConfig::init();

/* Use sysConfig from here on */
include(sysConfig::getDirFsCatalog() . 'includes/conversionArrays.php');
require(sysConfig::getDirFsCatalog() . 'includes/classes/Profiler/Base.php');

// Define the project version
	sysConfig::set('PROJECT_VERSION', 'Sales Igniter E-Commerce System 1.0');

	$request_type = (getenv('HTTPS') == 'on') ? 'SSL' : 'NONSSL';

// Used in the "Backup Manager" to compress backups
	define('LOCAL_EXE_GZIP', '/usr/bin/gzip');
	define('LOCAL_EXE_GUNZIP', '/usr/bin/gunzip');
	define('LOCAL_EXE_ZIP', '/usr/local/bin/zip');
	define('LOCAL_EXE_UNZIP', '/usr/local/bin/unzip');

// include the list of project filenames
	require(sysConfig::getDirFsAdmin() . 'includes/filenames.php');

// include the list of project database tables
	require(sysConfig::getDirFsAdmin() . 'includes/database_tables.php');

	require(sysConfig::getDirFsCatalog() . 'ext/Doctrine.php');
	spl_autoload_register(array('Doctrine_Core', 'autoload'));
	spl_autoload_register(array('Doctrine_Core', 'modelsAutoload'));
	$manager = Doctrine_Manager::getInstance();
	//$manager->setAttribute(Doctrine_Core::ATTR_AUTOLOAD_TABLE_CLASSES, true);
	$manager->setAttribute(Doctrine_Core::ATTR_MODEL_LOADING, Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
	Doctrine_Core::setModelsDirectory(sysConfig::getDirFsCatalog() . 'ext/Doctrine/Models'); 
	//Doctrine_Core::loadModels(sysConfig::getDirFsCatalog() . 'ext/Doctrine/Models');

	//$profiler = new Doctrine_Connection_Profiler();
	
	$connString = 'mysql://' . sysConfig::get('DB_SERVER_USERNAME') . ':' . sysConfig::get('DB_SERVER_PASSWORD') . '@' . sysConfig::get('DB_SERVER') . '/' . sysConfig::get('DB_DATABASE');
	$conn = Doctrine_Manager::connection($connString, 'mainConnection');
	/*$cacheDriver = new Doctrine_Cache_Apc();
	$conn->setAttribute(Doctrine_Core::ATTR_QUERY_CACHE, $cacheDriver);
	$conn->setAttribute(Doctrine_Core::ATTR_RESULT_CACHE, $cacheDriver);*/

	//$conn->setListener($profiler);

	/*$cacheConnection = Doctrine_Manager::connection(new PDO('sqlite::memory:'), 'cacheConnection');
	$cacheDriver = new Doctrine_Cache_Db(array(
		'connection' => $conn,
		'tableName'  => 'DoctrineCache'
	));
	$conn->setAttribute(Doctrine_Core::ATTR_QUERY_CACHE, $cacheDriver);
	$conn->setAttribute(Doctrine_Core::ATTR_RESULT_CACHE, $cacheDriver);
	$conn->setAttribute(Doctrine_Core::ATTR_RESULT_CACHE_LIFESPAN, 3600);*/
$conn->setCharset(sysConfig::get('SYSTEM_CHARACTER_SET'));
$conn->setCollate(sysConfig::get('SYSTEM_CHARACTER_SET_COLLATION'));
$manager->setCurrentConnection('mainConnection');
Doctrine_Manager::connection()->setAttribute(Doctrine_Core::ATTR_AUTO_FREE_QUERY_OBJECTS, true );
Doctrine_Manager::getInstance()
	->getCurrentConnection()
	->exec('SET SQL_BIG_SELECTS=1');
	define('CURRENCY_SERVER_PRIMARY', 'oanda');
	define('CURRENCY_SERVER_BACKUP', 'xe');
	require(sysConfig::getDirFsCatalog() . 'includes/classes/dataAccess.php');
	new dataAccess();
	require(sysConfig::getDirFsCatalog() . 'includes/functions/database.php');

	sysConfig::load();

require(sysConfig::getDirFsCatalog() . 'includes/classes/MultipleInheritance.php');
require(sysConfig::getDirFsCatalog() . 'includes/classes/Importable/Installable.php');
require(sysConfig::getDirFsCatalog() . 'includes/classes/Importable/SortedDisplay.php');
require(sysConfig::getDirFsCatalog() . 'includes/classes/htmlBase.php');
require(sysConfig::getDirFsCatalog() . 'includes/classes/exceptionManager.php');
$ExceptionManager = new ExceptionManager;
set_error_handler(array($ExceptionManager, 'addError'));
set_exception_handler(array($ExceptionManager, 'add'));


	require(sysConfig::getDirFsAdmin() . 'includes/classes/navigation_history.php');
	require(sysConfig::getDirFsAdmin() . 'includes/functions/general.php');
	require(sysConfig::getDirFsAdmin() . 'includes/functions/html_output.php');
	require(sysConfig::getDirFsCatalog() . 'includes/classes/email_events.php');
	require(sysConfig::getDirFsAdmin() . 'includes/functions/password_funcs.php');
	require(sysConfig::getDirFsAdmin() . 'includes/classes/logger.php');

	require(sysConfig::getDirFsCatalog() . 'includes/classes/user/membership.php');
	require(sysConfig::getDirFsCatalog() . 'includes/classes/user/address_book.php');
	require(sysConfig::getDirFsCatalog() . 'includes/classes/user.php');

require(sysConfig::getDirFsCatalog() . 'includes/classes/eventManager/Manager.php');
require(sysConfig::getDirFsCatalog() . 'includes/classes/system_modules_loader.php');
require(sysConfig::getDirFsCatalog() . 'includes/classes/ModuleInstaller.php');
require(sysConfig::getDirFsCatalog() . 'includes/classes/ModuleBase.php');
require(sysConfig::getDirFsCatalog() . 'includes/classes/ModuleConfigReader.php');

require(sysConfig::getDirFsCatalog() . 'includes/modules/pdfinfoboxes/PDFInfoBoxAbstract.php');
require(sysConfig::getDirFsCatalog() . 'includes/modules/orderShippingModules/modules.php');
require(sysConfig::getDirFsCatalog() . 'includes/modules/orderPaymentModules/modules.php');
require(sysConfig::getDirFsCatalog() . 'includes/modules/orderTotalModules/modules.php');
	require(sysConfig::getDirFsCatalog() . 'includes/classes/application.php');
	require(sysConfig::getDirFsCatalog() . 'includes/classes/extension.php');

	$App = new Application((isset($_GET['app']) ? $_GET['app'] : ''), (isset($_GET['appPage']) ? $_GET['appPage'] : ''));
	if ($App->isValid() === false) die('No valid application found.');

	$appExtension = new Extension;
	$appExtension->preSessionInit();

	require(sysConfig::getDirFsCatalog() . 'includes/classes/session.php');
	Session::init(); /* Initialize the session */
	require(sysConfig::getDirFsCatalog() . 'includes/classes/message_stack.php');
	$messageStack = new messageStack;
	$appExtension->postSessionInit();
	require(sysConfig::getDirFsCatalog() . 'includes/classes/system_language.php');
	sysLanguage::init();

	$appExtension->loadExtensions();

$appExtension->initApplicationPlugins();

	if (isset($_GET['verifyModels'])){
		$dirObj = new DirectoryIterator(sysConfig::getDirFsCatalog() . 'ext/Doctrine/Models/');
		foreach($dirObj as $mInfo){
			if ($mInfo->isDot() || $mInfo->isDir()) continue;
			$App->checkModel($mInfo->getBasename('.php'));
		}
	}

	$App->loadLanguageDefines();


	require(sysConfig::getDirFsAdmin() . 'includes/classes/object_info.php');
	require(sysConfig::getDirFsAdmin() . 'includes/classes/mime.php');
	require(sysConfig::getDirFsAdmin() . 'includes/classes/email.php');


	if (sysConfig::exists('DEFAULT_CURRENCY', true) === false){
		$messageStack->add('footerStack', sysLanguage::get('ERROR_NO_DEFAULT_CURRENCY_DEFINED'), 'error');
	}

	if (function_exists('ini_get') && ((bool)ini_get('file_uploads') == false) ) {
		$messageStack->add('footerStack', sysLanguage::get('WARNING_FILE_UPLOADS_DISABLED'), 'warning');
	}
	if (Session::exists('navigationAdmin') === false){
		Session::set('navigationAdmin', new navigationHistory);
	}
	$navigation = &Session::getReference('navigationAdmin');
	$navigation->add_current_page();
	

	require(sysConfig::getDirFsCatalog() . 'includes/classes/system_permissions.php');

	if ($App->getAppName() != 'login' && basename($_SERVER['PHP_SELF']) != 'stylesheet.php' && basename($_SERVER['PHP_SELF']) != 'javascript.php') {
		sysPermissions::checkLoggedIn();
		sysPermissions::loadPermissions();
		
		if ($App->getAppPage() != 'noAccess'){
			$accessPermitted = sysPermissions::adminAccessAllowed(
				$App->getAppName(),
				$App->getAppPage(),
				(isset($_GET['appExt']) ? $_GET['appExt'] : null)
			);
			if ($accessPermitted === false){
				tep_redirect(itw_app_link(null, 'index', 'noAccess'));
			}
		}
		
		if ($App->getAppName() != 'database_manager' && Session::exists('DatabaseError')){
			//$messageStack->addSession('pageStack', 'There are database errors that must be fixed before you can use the administration area, They are hilighted red below', 'error');
			//tep_redirect(itw_app_link(null, 'database_manager', 'default'));
		}
	}

class PagerLayoutWithArrows extends Doctrine_Pager_Layout {
	private $myType = '';

	public function setMyType($val){
		$this->myType = $val;
	}

	public function getMyType(){
		return $this->myType;
	}


	public function display($options = array(), $return = false){
		if(empty($this->myType)){
			$this->myType = sysLanguage::get('TEXT_PAGER_TYPE');
		}
		$pager = $this->getPager();
		$str = '';

		// First page
		$this->addMaskReplacement('page', '&laquo;', true);
		$options['page_number'] = $pager->getFirstPage();
		$str .= $this->processPage($options);

		// Previous page
		$this->addMaskReplacement('page', '&lsaquo;', true);
		$options['page_number'] = $pager->getPreviousPage();
		$str .= $this->processPage($options);

		// Pages listing
		$this->removeMaskReplacement('page');
		$str .= parent::display($options, true);

		// Next page
		$this->addMaskReplacement('page', '&rsaquo;', true);
		$options['page_number'] = $pager->getNextPage();
		$str .= $this->processPage($options);

		// Last page
		$this->addMaskReplacement('page', '&raquo;', true);
		$options['page_number'] = $pager->getLastPage();
		$str .= $this->processPage($options);

		$str .= '&nbsp;&nbsp;<b>' . $pager->getFirstIndice() . ' - ' . $pager->getLastIndice() . ' ('.sysLanguage::get('TEXT_PAGER_OF').' ' . $pager->getNumResults() . ' ' . $this->myType. ')</b>';
		// Possible wish to return value instead of print it on screen
		if ($return) {
			return $str;
		}
		echo $str;
	}
}

	require(sysConfig::getDirFsCatalog() . 'includes/classes/ftp/base.php');
?>