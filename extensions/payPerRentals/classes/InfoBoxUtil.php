<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of InfoBoxUtil
 *
 * @author Stephen
 */
class ReservationInfoBoxUtil {

	public static function showInfoboxBefore($settings = null, $hasButton = true){

		$templateFile = 'beforeInfobox.tpl';
		$templateDir = sysConfig::getDirFsCatalog() . 'extensions/payPerRentals/catalog/ext_app/infoboxes/template/';
		if (is_null($settings) === false && isset($settings['template_file'])){
			$templateFile = $settings['template_file'];
		}
		if (is_null($settings) === false && isset($settings['template_dir'])){
			$templateDir = $settings['template_dir'];
		}
		$infobeforeTemplate = new Template($templateFile, $templateDir);

		$oneVar = '';

		$infobeforeTemplate->setVars(array(
			'formD' => self::getPprForm($hasButton)
		));

		return $infobeforeTemplate->parse();
	}

	public static function addPPrChildren($child, $currentPath, &$ulElement){
		global $current_category_id;
		$currentPath .= '_' . $child['categories_id'];

		$childLinkEl = htmlBase::newElement('a')
				->addClass('ui-widget ui-widget-content ui-corner-all cats')
				->css('border-color', 'transparent')
				->html('<span class="ui-icon ui-icon-triangle-1-e ui-icon-categories-bullet" style="vertical-align:middle;"></span><span style="display:inline-block;vertical-align:middle;">' . $child['CategoriesDescription'][Session::get('languages_id')]['categories_name'] . '</span>')
				->attr('rel', $currentPath);
		//->setHref(itw_app_link('cPath=' . $currentPath, 'index', 'default'));

		if ($child['categories_id'] == $current_category_id){
			$childLinkEl->addClass('selected');
		}

		$Qchildren = Doctrine_Query::create()
		->select('c.categories_id, cd.categories_name, c.parent_id, c.ppr_show_in_menu')
		->from('Categories c')
		->leftJoin('c.CategoriesDescription cd')
		->where('c.parent_id = ?', $child['categories_id'])
		->andWhere('cd.language_id = ?', (int)Session::get('languages_id'))
		->orderBy('c.sort_order, cd.categories_name');

		EventManager::notify('CategoryQueryBeforeExecute', $Qchildren);

		$currentParentChildren = $Qchildren->execute()->toArray(true);

		$children = false;
		if ($currentParentChildren){
			$childLinkEl
				->html(
					'<span style="float:right;" class="ui-icon ui-icon-triangle-1-e"></span>' .
					'<span style="line-height:1.5em;">' .
					'<span class="ui-icon ui-icon-triangle-1-e ui-icon-categories-bullet" style="vertical-align:middle;"></span>' .
					'<span style="vertical-align:middle;">' .
					$child['CategoriesDescription'][Session::get('languages_id')]['categories_name'] .
					'</span>' .
					'</span>');

			$children = htmlBase::newElement('list')
					->addClass('ui-widget ui-widget-content ui-corner-all ui-menu-flyout')
					->css('display', 'none');
			foreach($currentParentChildren as $childInfo){
				if ($childInfo['pp_show_in_menu'] == 1){
					self::addPPRChildren($childInfo, $currentPath, &$children);
				}
			}
		}

		$liElement = htmlBase::newElement('li')
				->append($childLinkEl);
		if ($children){
			$liElement->append($children);
		}
		$ulElement->addItemObj($liElement);
	}

	public static function getPprForm($hasButton = true, $hasHeaders = false){
		global $appExtension, $currencies, $cPath, $cPath_array, $tree, $categoriesString, $current_category_id, $App;

		$getv = '';
		if (isset($_GET['cPath'])){
			$getv = "&cPath=" . $_GET['cPath'];
		}
		$pprform = htmlBase::newElement('form')
		->attr('name', 'selectPPR')
		->attr('id', 'sd')
		->attr('action', itw_app_link('appExt=payPerRentals&action=setBefore' . $getv, 'build_reservation', 'default'))
		->attr('method', 'post');

		$pickupt = htmlBase::newElement('p')
		->html('Pickup Zone')
		->addClass('pickp');
		$br = htmlBase::newElement('br');
		$pickup = htmlBase::newElement('selectbox')
		->setName('pickup')
		->addClass('myf')
		->attr('id', 'pickupz');

		if (Session::exists('isppr_inventory_pickup') && (Session::get('isppr_inventory_pickup') != '') && Session::get('isppr_selected') == true){
			$pickup->selectOptionByValue(Session::get('isppr_inventory_pickup'));
		}


		$dropofft = htmlBase::newElement('p')
		->html('DropOff Zone')
		->addClass('pickp');

		$dropoff = htmlBase::newElement('selectbox')
		->setName('dropoff')
		->addClass('myg')
		->attr('id', 'dropoffz');
		$dropoff->addOption('0', 'Same as above');

		if (Session::exists('isppr_inventory_dropoff') && (Session::get('isppr_inventory_dropoff') != '') && Session::get('isppr_selected') == true){
			$dropoff->selectOptionByValue(Session::get('isppr_inventory_dropoff'));
		}

		$invCentExt = $appExtension->getExtension('inventoryCenters');
		$myfinv = 0;
		if ($invCentExt !== false && $invCentExt->isEnabled() === true){
			$Qinventory = Doctrine_Query::create()
			->select('p.*')
			->from('ProductsInventoryCenters p')
			->orderBy('p.inventory_center_name')
			->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
			if ($Qinventory){
				foreach($Qinventory as $qinv){

					if ($myfinv == 0){
						$myfinv = $qinv['inventory_center_id'];
					}
					$attr = array(
						array(
							'name' => 'days',
							'value' => $qinv['inventory_center_min_rental_days']
						)
					);
					$pickup->addOptionWithAttributes($qinv['inventory_center_id'], $qinv['inventory_center_name'], $attr);
					$dropoff->addOption($qinv['inventory_center_id'], $qinv['inventory_center_name']);
				}
			}
		}
		$separator1 = htmlBase::newElement('div');
		if ($hasHeaders === true){
			$separator1->addClass('ui-my-header ui-corner-top');
		}
		$separatort = htmlBase::newElement('div');
		if ($hasHeaders === true){
			$separatort->addClass('ui-my-header-text');
			$separatort->html('1. Select Destinations');
		}
		$container_dest = htmlBase::newElement('div');
		if ($hasHeaders === true){
			$container_dest->addClass('ui-my-content');
		}
		$separator1->append($separatort);
		$pickText = htmlBase::newElement('a')
		->text('More Info')
		->addClass('myf1')
		->attr('href', itw_app_link('appExt=inventoryCenters&inv_id=' . $myfinv, 'show_inventory', 'default'));

		$dropText = htmlBase::newElement('a')
		->text('More Info')
		->addClass('myg1')
		->attr('href', itw_app_link('appExt=inventoryCenters', 'show_inventory', 'default'));

		if ($invCentExt !== false && $invCentExt->isEnabled() === true){
			if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_CHOOSE_PICKUP') == 'True'){
				$container_dest->append($pickupt)->append($pickup)->append($pickText);
			}
			if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_CHOOSE_DROPOFF') == 'True'){
				$container_dest->append($dropofft)->append($dropoff)->append($dropText)->append($br);
			}
			$pprform->append($separator1)->append($container_dest);
		}

		$separator2 = htmlBase::newElement('div');
		if ($hasHeaders === true){
			$separator2->addClass('ui-my-header');
		}
		$separatort2 = htmlBase::newElement('div');
		if ($hasHeaders === true){
			$separatort2->addClass('ui-my-header-text');
			$separatort2->html('2. Select Dates');
		}
		$separator2->append($separatort2);
		$container_dates = htmlBase::newElement('div');
		if ($hasHeaders === true){
			$container_dates->addClass('ui-my-content');
		}
		$starttime = (int) sysConfig::get('EXTENSION_PAY_PER_RENTALS_START_TIME');
		$endtime = (int) sysConfig::get('EXTENSION_PAY_PER_RENTALS_END_TIME');

		$dst = htmlBase::newElement('span')
		->addClass('start_text')
		->html(sysLanguage::get('TEXT_ENTRY_PICKUP_DATE'));

		$dateStart = htmlBase::newElement('input')
		->addClass('picker')
		->setName('dstart')
		->setId('dstart');

		$est = htmlBase::newElement('span')
		->addClass('end_text')
		->html(sysLanguage::get('TEXT_ENTRY_RETURN_DATE'));

		$dateEnd = htmlBase::newElement('input')
		->addClass('picker')
		->setName('dend')
		->setId('dend');

		if (Session::exists('isppr_date_start') && (Session::get('isppr_date_start') != '') && Session::get('isppr_selected') == true){
			$dd = explode(' ', Session::get('isppr_date_start'));
			$dateStart->val(date(sysLanguage::getDateFormat(), strtotime($dd[0])));
		}

		if (Session::exists('isppr_date_end') && (Session::get('isppr_date_end') != '') && Session::get('isppr_selected') == true){
			$dd = explode(' ', Session::get('isppr_date_end'));
			$dateEnd->val(date(sysLanguage::getDateFormat(), strtotime($dd[0])));
		}
		$hst = htmlBase::newElement('span')
		->addClass('start_time_text')
		->html(sysLanguage::get('TEXT_ENTRY_PICKUP_TIME'));

		$hourStart = htmlBase::newElement('selectbox')
		->setName('hstart')
		->addClass('pickers')
		->addClass('myf2')
		->attr('id', 'hstart');

		if (Session::exists('isppr_hour_starts') && (Session::get('isppr_hour_starts') != '') && Session::get('isppr_selected') == true){
			$hourStart->selectOptionByValue(Session::get('isppr_hour_starts'));
		}
		$hen = htmlBase::newElement('span')
		->addClass('end_time_text')
		->html(sysLanguage::get('TEXT_ENTRY_RETURN_TIME'));

		$hourEnd = htmlBase::newElement('selectbox')
		->setName('hend')
		->addClass('pickers')
		->addClass('myf2')
		->attr('id', 'hend');

		if (Session::exists('isppr_hour_ends') && (Session::get('isppr_hour_ends') != '') && Session::get('isppr_selected') == true){
			$hourEnd->selectOptionByValue(Session::get('isppr_hour_ends'));
		}
		$pageURL = 'http';
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on"){
			$pageURL .= "s";
		}
		$pageURL .= "://";
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		$htmlPageUrl = htmlBase::newElement('input')
		->setType('hidden')
		->setName('url')
		->setValue($pageURL);

		$submitb = htmlBase::newElement('button')
		->setType('submit')
		->usePreset('save')
		->addClass('rentbbut')
		->setName('submitb');

		if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_SHOW_VIEW_ALL_BUTTON') == 'True'){
			if (Session::exists('isppr_selected') && Session::get('isppr_selected') == true){
				$submitb->setText(sysLanguage::get('TEXT_BUTTON_UPDATE'));
				$pprform->append($htmlPageUrl);
			}else{
				$submitb->setText(sysLanguage::get('TEXT_BUTTON_SUBMIT'));
			}
		}else{

			$Qcategories = Doctrine_Query::create()
			->select('c.categories_id, cd.categories_name, c.parent_id, c.ppr_show_in_menu')
			->from('Categories c')
			->leftJoin('c.CategoriesDescription cd')
			->where('c.parent_id = ?', '0')
			->andWhere('(c.categories_menu = "infobox" or c.categories_menu = "both")')
			->andWhere('cd.language_id = ?', (int)Session::get('languages_id'))
			->orderBy('c.sort_order, cd.categories_name');

			EventManager::notify('CategoryQueryBeforeExecute', $Qcategories);

			$Result = $Qcategories->execute(array(), Doctrine::HYDRATE_ARRAY);

			$headerMenuContainer = htmlBase::newElement('div')
			->attr('id', 'categoriesPPRBoxMenu');

			$headMenuContainer = htmlBase::newElement('div')
			->html("Select Category")
			->addClass('ui-widget-header')
			->attr('id', 'headPPRBoxMenu');
			$headerMenuContainer->append($headMenuContainer);

			if ($Result){
				foreach($Result as $idx => $cInfo){
					$categoryId = $cInfo['categories_id'];
					$parentId = $cInfo['parent_id'];
					$categoryName = $cInfo['CategoriesDescription'][0]['categories_name'];
					if ($cInfo['ppr_show_in_menu'] == 1){
						$headerEl = htmlBase::newElement('h3');
						if (isset($cPath_array) && $cPath_array[0] == $categoryId){
							$headerEl->addClass('currentCategory');
						}
						$headerEl->html($categoryName);

						$Qchildren = Doctrine_Query::create()
						->select('c.categories_id, cd.categories_name, c.parent_id, c.ppr_show_in_menu')
						->from('Categories c')
						->leftJoin('c.CategoriesDescription cd')
						->where('c.parent_id = ?', $categoryId)
						->andWhere('cd.language_id = ?', (int)Session::get('languages_id'))
						->orderBy('c.sort_order, cd.categories_name');

						EventManager::notify('CategoryQueryBeforeExecute', &$Qchildren);
						$currentChildren = $Qchildren->execute();

						$flyoutContainer = htmlBase::newElement('div');
						$ulElement = htmlBase::newElement('list');
						if ($currentChildren->count() > 0){
							foreach($currentChildren->toArray() as $child){
								if ($child['ppr_show_in_menu'] == 1){
									self::addPPRChildren($child, $categoryId, &$ulElement);
								}
							}
						}else{
							$childLinkEl = htmlBase::newElement('a')
									->addClass('ui-widget ui-widget-content ui-corner-all cats')
									->css('border-color', 'transparent')
									->html('<span class="ui-icon ui-icon-triangle-1-e ui-icon-categories-bullet" style="vertical-align:middle;"></span><span class="ui-categories-text" style="vertical-align:middle;">'.sysLanguage::get('INFOBOX_CATEGORIES_VIEW_PRODUCTS').'</span>')
									->attr('rel', $categoryId);

							$liElement = htmlBase::newElement('li')
									->append($childLinkEl);
							$ulElement->addItemObj($liElement);
						}
						$flyoutContainer->append($ulElement);

						$headerMenuContainer->append($headerEl)->append($flyoutContainer);
					}
				}
			}
		}

		$starttime = explode(":", $starttime);
		$i = $starttime[0];
		if (isset($starttime[1])){
			$min = $starttime[1];
		}else{
			$min = 0;
		}
		if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_TIME_INCREMENT') == '1/2'){
			$time_increment = 30;
		}else{
			$time_increment = 60;
		}

		$endtime = explode(":", $endtime);

		$et = $endtime[0];

		if (isset($endtime[1])){
			$etm = $endtime[1];
		}else{
			$etm = 0;
		}

		$timezone = str_replace('GMT ','',sysConfig::get('EXTENSION_PAY_PER_RENTALS_GMT'));
		$timezone = str_replace('GMT ','',sysConfig::get('EXTENSION_PAY_PER_RENTALS_GMT'));

		$offset=(int)$timezone*60*60; //converting 5 hours to seconds.

		$curHour = (int)gmdate('G', time()+$offset);//a config for setting store GMT hour

		//$curHour = 14;
		$min1 = 0;
		Session::set('isppr_curDate', gmdate('m/d/Y',time()+$offset));
		Session::set('isppr_nextDay','0');
		if ($time_increment < (int) date('i')) {
			if($curHour + 1 < 24){
				$curHour += 1;
				$min1 = 0;
			}else{
				Session::set('isppr_nextDay','1');
			}
		} else {
			if($time_increment == 60){
				if($curHour + 1 < 24){
					$curHour += 1;
					$min1 = 0;
				}else{
					Session::set('isppr_nextDay','1');
				}
			}else{
				//Session::set('isppr_curMin','30');
				$min1 = 30;
			}
		}

		if($curHour > $et){
			Session::set('isppr_nextDay','1');
		}

		$endtime1 = mktime($et, $etm, 0, date("m"), date("d"), date("Y"));
		$next_date1 = mktime($curHour, $min, 0, date("m"), date("d"), date("Y"));
		$j = $curHour;
		$issafe = true;
		$hourCurDays = '';
		$hourCurDaye = '';
		while($issafe){

			if ($next_date1 >= $endtime1)	break;

			$mt1 = date("g:i A", $next_date1);
			if (Session::exists('isppr_hour_starts') && (Session::get('isppr_hour_starts') == $j) && Session::get('isppr_selected') == true){
				$hourCurDays .= '<option selected="selected" value="'.$j.'">'.$mt1.'</option>';
			}else{
				$hourCurDays .= '<option value="'.$j.'">'.$mt1.'</option>';
			}
			if (Session::exists('isppr_hour_ends') && (Session::get('isppr_hour_ends') == $j) && Session::get('isppr_selected') == true){
				$hourCurDaye .= '<option selected="selected" value="'.$j.'">'.$mt1.'</option>';
			}else{
				$hourCurDaye .= '<option value="'.$j.'">'.$mt1.'</option>';
			}
			//$hourStart->addOption($j, $mt);
			//$hourEnd->addOption($j, $mt);
			$j++;
			$min1 = $min1 + $time_increment;
			$next_date1 = mktime($curHour, $min1, 0, date("m"), date("d"), date("Y"));
		}
		Session::set('isppr_selectOptionscurdays', $hourCurDays);
		Session::set('isppr_selectOptionscurdaye', $hourCurDaye);

		$endtime = mktime($et, $etm, 0, date("m"), date("d"), date("Y"));
		$next_date = mktime($i, $min, 0, date("m"), date("d"), date("Y"));

		$j = $i;
		$issafe = true;
		$hourCurDays = '';
		$hourCurDaye = '';
		while($issafe){

			if ($next_date >= $endtime)	break;

			$mt = date("g:i A", $next_date);
			if(strtotime($dateStart->val()) == strtotime(Session::get('isppr_curDate')) && $j >= $curHour || (strtotime($dateStart->val()) > strtotime(Session::get('isppr_curDate')))){
				$hourStart->addOption($j, $mt);
			}
			if(strtotime($dateEnd->val()) == strtotime(date('m/d/Y', strtotime($timezone.' hours'))) && $j >= $curHour || (strtotime($dateEnd->val()) > strtotime(date('m/d/Y', strtotime($timezone.' hours'))))){
				$hourEnd->addOption($j, $mt);
			}
			if (Session::exists('isppr_hour_starts') && (Session::get('isppr_hour_starts') == $j) && Session::get('isppr_selected') == true){
				$hourCurDays .= '<option selected="selected" value="'.$j.'">'.$mt.'</option>';
			}else{
				$hourCurDays .= '<option value="'.$j.'">'.$mt.'</option>';
			}
			if (Session::exists('isppr_hour_ends') && (Session::get('isppr_hour_ends') == $j) && Session::get('isppr_selected') == true){
				$hourCurDaye .= '<option selected="selected" value="'.$j.'">'.$mt.'</option>';
			}else{
				$hourCurDaye .= '<option value="'.$j.'">'.$mt.'</option>';
			}
			$j++;
			$min = $min + $time_increment;
			$next_date = mktime($i, $min, 0, date("m"), date("d"), date("Y"));
		}

		Session::set('isppr_selectOptionsnormaldays', $hourCurDays);
		Session::set('isppr_selectOptionsnormaldaye', $hourCurDaye);

		$shipt = htmlBase::newElement('p')
		->html('<span style="color:#D31820;float:left;">2. Select Level of Service</span><a style="float:right;margin-right:10px;"href="' . itw_app_link('appExt=infoPages', 'show_page', 'help_level_service') . '" onclick="popupWindow(\'' . itw_app_link('appExt=infoPages&dialog=true', 'show_page', 'help_level_service', 'SSL') . '\',\'400\',\'300\');return false;"><span class="helpicon"></span></a><br style="clear:both;"/>')
		->addClass('shipp');

		$br = htmlBase::newElement('br');
		$shipb = htmlBase::newElement('selectbox')
		->setName('ship_method')
		->addClass('shipf')
		->attr('id', 'shipz');

		$firstShippingMethod = 0;
		if (Session::exists('isppr_shipping_method') && tep_not_null(Session::get('isppr_shipping_method')) && Session::get('isppr_selected') == true){
			$shipb->selectOptionByValue(Session::get('isppr_shipping_method'));
			$firstShippingMethod = Session::get('isppr_shipping_method');
		}

		if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_UPS_RESERVATION') == 'False'){
			$Module = OrderShippingModules::getModule('zonereservation');
		} else{
			$Module = OrderShippingModules::getModule('upsreservation');
		}
		$quotes = $Module->quote();
		$min_days = 1000;
		for($i = 0, $n = sizeof($quotes['methods']); $i < $n; $i++){
			if ((int) $quotes['methods'][$i]['days_before'] < $min_days){
				$min_days = (int) $quotes['methods'][$i]['days_before'];
			}
		}

		$eventt = htmlBase::newElement('p')
		->html('<span style="color:#D31820">1. Select Event</span>')
		->addClass('eventp');

		$br = htmlBase::newElement('br');
		$eventb = htmlBase::newElement('selectbox')
		->setName('event')
		->addClass('eventf')
		->attr('id', 'eventz');
		$eventb->addOption('0', 'Select your event');

		$firstEvent = 0;
		if (Session::exists('isppr_event') && tep_not_null(Session::get('isppr_event')) && Session::get('isppr_selected') == true){
			$eventb->selectOptionByValue(Session::get('isppr_event'));
			$firstEvent = Session::get('isppr_event');
		}

		$shipb->addOption('0', 'Select Level of Service');

		$min_date =  date("Y-m-d h:i:s", mktime(date("h"),date("i"),date("s"),date("m"),date("d")/*+$min_days*/,date("Y")));
		$Qevent = Doctrine_Query::create()
		->from('PayPerRentalEvents')
		->where('events_date > ?', $min_date)
		->orderBy('events_date')
		->execute(array(), Doctrine_Core::HYDRATE_ARRAY);


		if($Qevent){
			foreach($Qevent as $eInfo){

				$shippingArrA = explode(',', $eInfo['shipping']);
				$start_dateA = strtotime($eInfo['events_date']);
				$starting_dateA = date("Y-m-d h:i:s", mktime(date("h",$start_dateA),date("i",$start_dateA), date("s",$start_dateA), date("m",$start_dateA), date("d",$start_dateA), date("Y",$start_dateA)));
				for($i=0, $n=sizeof($quotes['methods']); $i<$n; $i++){
					$days = $quotes['methods'][$i]['days_before'];
					$next_day = mktime(date("h"),date("i"),date("s"),date("m"),date("d")+$days,date("Y"));
					if(/*$next_day < strtotime($starting_dateA) &&*/ in_array($quotes['methods'][$i]['id'], $shippingArrA)){
						if($firstEvent == $eInfo['events_id'] || $firstEvent == 0){
							$firstEvent = $eInfo['events_id'];
							$shippingArr = explode(',', $eInfo['shipping']);
							$start_date = strtotime($eInfo['events_date']);
							$starting_date = date("Y-m-d h:i:s", mktime(date("h",$start_date),date("i",$start_date), date("s",$start_date), date("m",$start_date), date("d",$start_date), date("Y",$start_date)));
						}
						$eventb->addOption($eInfo['events_id'],$eInfo['events_name']);
						break;
					}
				}
			}
		}
		if(!isset($starting_date)){
			$starting_date =  date("Y-m-d h:i:s");
		}
		for($i=0, $n=sizeof($quotes['methods']); $i<$n; $i++){
			$days = $quotes['methods'][$i]['days_before'];
			$next_day = mktime(date("h"),date("i"),date("s"),date("m"),date("d")+$days,date("Y"));
			if($next_day < strtotime($starting_date) && in_array($quotes['methods'][$i]['id'], $shippingArr)){
				if($firstShippingMethod == 0){
					$firstShippingMethod = $quotes['methods'][$i]['id'];
				}
				$shipb->addOption($quotes['methods'][$i]['id'], $quotes['methods'][$i]['title'] . ' (' . $currencies->format($quotes['methods'][$i]['cost']) . ')');
			}
		}

		$separator1ev = htmlBase::newElement('div');
		$separatortev = htmlBase::newElement('div');
		$container_destev = htmlBase::newElement('div');
		$separator1ev->append($separatortev);
		$evText = htmlBase::newElement('a')
		->text('More Info')
		->addClass('myev1')
		->attr('href',itw_app_link('appExt=payPerRentals&ev_id='.$firstEvent,'show_event','default'));

		$separator1sh = htmlBase::newElement('div');
		$separatortsh = htmlBase::newElement('div');
		$container_destsh = htmlBase::newElement('div');
		$separator1sh->append($separatortsh);

		$shText = htmlBase::newElement('a')
		->text('More Info')
		->addClass('mysh1')
		->attr('href',itw_app_link('appExt=payPerRentals&sh_id='.$firstShippingMethod,'show_shipping','default'));

		if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_EVENTS') == 'True'){
			$container_destev->append($eventt)
			->append($eventb)
			->append($evText);
			$pprform->append($separator1ev)
			->append($container_destev);
		}else{
			$container_destshB = htmlBase::newElement('div')
			->addClass('destB');
			$container_destshBD = htmlBase::newElement('div')
			->addClass('destBD');
			$container_destshBD->append($dst)
			->append($dateStart);
			$container_destshBT = htmlBase::newElement('div')
				->addClass('destBT');
			if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_ENABLE_TIME') == 'True'){
				$container_destshBT->append($hst);
				$container_destshBT->append($hourStart);
			}
			$brClear = htmlBase::newElement('br')
			->addClass('brClear');

			$container_destshB->append($container_destshBD)
			->append($container_destshBT)
			->append($brClear);
			$container_dates->append($container_destshB);
			$container_rethB = htmlBase::newElement('div')
			->addClass('retB');
			$container_rethBD = htmlBase::newElement('div')
			->addClass('retBD');
			$brClear1 = htmlBase::newElement('br')
			->addClass('brClear1');

			$container_rethBD->append($est)
			->append($dateEnd);

			$container_rethBT = htmlBase::newElement('div')
			->addClass('retBT');

			if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_ENABLE_TIME') == 'True'){
				$container_rethBT->append($hen);
				$container_rethBT->append($hourEnd);
			}

			$container_rethB->append($container_rethBD)
			->append($container_rethBT)
			->append($brClear1);

			$container_dates->append($container_rethB);
			$pprform->append($separator2)
			->append($container_dates);
		}

		$container_qty = htmlBase::newElement('div')
		->addClass('qtyContainer');

		$qtyText = htmlBase::newElement('span')
		->addClass('qty_text')
		->html(sysLanguage::get('TEXT_QTY'));

		$qtyInput = htmlBase::newElement('input')
		->addClass('qtypicker')
		->setName('qty')
		->setId('qtypicker');
		if(Session::exists('isppr_product_qty')){
			$qtyInput->setValue(Session::get('isppr_product_qty'));
		}  else{
			$qtyInput->setValue('1');
		}
		$container_qty->append($qtyText)->append($qtyInput);
		$pprform->append($container_qty);

		//here is for the level of service or reservation shipping methods
		if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_SHIP') == 'True'){
			$container_destsh->append($shipt)->append($shipb)->append($shText);
			$pprform->append($separator1sh)->append($container_destsh);
		}
		if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_SHOW_VIEW_ALL_BUTTON') == 'True' && $hasButton){
			$pprform->append($submitb);
		}else{
			if (Session::exists('isppr_selected') && Session::get('isppr_selected') == true && (isset($_GET['cPath']) || strpos($App->getAppLocation('relative'), 'categoriesPages') > 0) && $hasButton){
				$submitb->setText(sysLanguage::get('TEXT_BUTTON_UPDATE'));
				$pprform->append($submitb);
			}
			if ($hasButton){
				$pprform->append($headerMenuContainer);
			}
		}
		 $viewAllHtml = htmlBase::newElement('span')
			 			->html('<div style="text-align:center;font-size:.8em;font-weight:bold;margin:.5em;" ><a class="cats" rel="-1" href="' . itw_app_link(null, 'products', 'all', 'NONSSL') . '">' . sysLanguage::get('INFOBOX_CATEGORIES_ALL_PRODUCTS') . '</a></div>');
		 if ($hasHeaders == false && $hasButton == false){
		 	$pprform->append($viewAllHtml);
		 }
		return $pprform->draw();
	}
}
?>
