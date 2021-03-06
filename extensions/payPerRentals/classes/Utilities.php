<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Utilities
 *
 * @author Stephen
 */
class ReservationUtilities {

	public static function getShippingDetails($method = null){
		$Quote = null;
		OrderShippingModules::loadModules();
		$ModuleQuote = OrderShippingModules::quote($method, 'zonereservation');
		if (isset($ModuleQuote[0]['methods']) && !empty($ModuleQuote[0]['methods'][0])){
			$Quote = $ModuleQuote[0]['methods'][0];
		}
		return $Quote;
	}

	public static function getEvent($evId = null){
		$Query = Doctrine_Query::create()
		->from('PayPerRentalEvents');

		if (is_null($evId) === false && is_numeric($evId) === false){
			$Query->andWhere('events_name = ?', $evId);
		}else{
			$Query->andWhere('events_id = ?', $evId);
		}

		if (is_null($evId) === false){
			$Result = $Query->fetchOne();
		}else{
			$Result = $Query->execute();
		}
		return $Result;
	}


	public static function addReservationProductToCart($productID, $rQty){
		global $ShoppingCart, $messageStack;
		//global variable with all the attributes per product which will get the POST[id] changed and then cleaned based on the product id
		$_POST['rental_qty'] = $rQty;

		$product = new product($productID);
		$purchaseTypeClass = $product->getPurchaseType('reservation');
		//if($purchaseTypeClass->hasInventory($rQty)){
			if(Session::exists('isppr_event_multiple_dates')){
				$datesArr = Session::get('isppr_event_multiple_dates');

				if(Session::exists('noInvDates')){
					$myNoInvDates = Session::get('noInvDates');
					if(isset($myNoInvDates[$productID]) && is_array($myNoInvDates[$productID]) && count($myNoInvDates[$productID]) > 0){

						foreach($myNoInvDates[$productID] as $iDate){
							foreach($datesArr as $k => $iDate1){
								if(strtotime($iDate1) == $iDate){
									unset($datesArr[$k]);
									break;
								}
							}
						}
					}
				}


				foreach($datesArr as $iDate){
					$_POST['start_date'] = $iDate;
					$_POST['end_date'] = $iDate;
					$_POST['event_date'] = $iDate;
					$ShoppingCart->addProduct($productID, 'reservation', $rQty);
				}
			} else{
				$ShoppingCart->addProduct($productID, 'reservation', $rQty);
			}
		/*} else{
			$messageStack->addSession('pageStack', 'Not enough inventory for one or multiple selected dates for the selected quantity');
		}*/
	}

	public static function addReservationProductToQueue($productID, $rQty){
		global $ShoppingCart, $messageStack, $userAccount;
		//global variable with all the attributes per product which will get the POST[id] changed and then cleaned based on the product id
		$_POST['rental_qty'] = $rQty;
		$product = new product($productID);
		$purchaseTypeClass = $product->getPurchaseType('reservation');
		//if($purchaseTypeClass->hasInventory($rQty)){
		if(Session::exists('isppr_event_multiple_dates')){
			$datesArr = Session::get('isppr_event_multiple_dates');
			if(Session::exists('noInvDates')){
				$myNoInvDates = Session::get('noInvDates');
				if(isset($myNoInvDates[$productID]) && is_array($myNoInvDates[$productID]) && count($myNoInvDates[$productID]) > 0){
					foreach($myNoInvDates[$productID] as $iDate){
						foreach($datesArr as $k => $iDate1){
							if(strtotime($iDate1) == $iDate){
								unset($datesArr[$k]);
								break;
							}
						}
					}
				}
			}
			foreach($datesArr as $iDate){
				$_POST['start_date'] = $iDate;
				$_POST['end_date'] = $iDate;
				$_POST['event_date'] = $iDate;
				$ShoppingCart->addProduct($productID, 'reservation', $rQty,null,true);
			}
		} else{
			$ShoppingCart->addProduct($productID, 'reservation', $rQty,null,true);
		}
		/*} else{
			$messageStack->addSession('pageStack', 'Not enough inventory for one or multiple selected dates for the selected quantity');
		}*/
	}
	public static function getPeriodTime($period, $type){
		if(isset($period) && is_numeric($period)){
			$QPayPerRentalTypes = Doctrine_Query::create()
			->from('PayPerRentalTypes')
			->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
			foreach($QPayPerRentalTypes as $iType){
				if($type == $iType['pay_per_rental_types_id']){
					 return $period * $iType['minutes'];
				}
			}
		}
		return 0;
	}

	public static function getPeriodType($type){
	   $QPayPerRentalTypes = Doctrine_Query::create()
		->from('PayPerRentalTypes')
		->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
		foreach($QPayPerRentalTypes as $iType){
			if($type == $iType['pay_per_rental_types_id']){
				 return $iType['pay_per_rental_types_name'];
			}
		}
		return '';
	}

	public static function getProductName($productId){
		$QProduct = Doctrine_Query::create()
		->from('Products p')
		->leftJoin('p.ProductsDescription pd')
		->where('p.products_id=?', $productId)
		->andWhere('pd.language_id=?', Session::get('languages_id'))
		->execute(array(), Doctrine_Core::HYDRATE_ARRAY);

		return $QProduct[0]['ProductsDescription'][0]['products_name'];
	}

	public static function getCalendar($productsId, $purchaseTypeClasses, $rQty = 1, $showShipping = true, $callType = 'catalog', $usableBarcodes = array(), $hasButton = true)
	{
		global $App;
        if(isset($_POST['freeTrialButton']) && $_POST['freeTrialButton'] == '1') {
            $freeTrialButton = $_POST['freeTrialButton'];
            $freeTrial = $_POST['freeTrial'];
        }
        elseif(isset($_GET['freeTrialButton']) && $_GET['freeTrialButton'] == '1') {
            $freeTrialButton = $_GET['freeTrialButton'];
            $freeTrial = $_GET['freeTrial'];
        }
		if($callType == 'catalog'){
			if($App->getEnv() == 'catalog'){
				$callLink = 'js_catalog_app_link(\'rType=ajax&appExt=payPerRentals&app=build_reservation&appPage=default\')';
				$callAction = 'getReservedDates';
			}else{
				$callLink = 'js_app_link(\'rType=ajax&appExt=orderCreator&app=default&appPage=new&action=getReservedDates\')';
				$callAction = '';
			}

		} else {
			$callLink = 'js_app_link(\'rType=ajax&appExt=orderCreator&app=default&appPage=new&action=loadReservationData\')';
			$callAction = '';
		}
		if($App->getEnv() == 'catalog'){
			$upsQuotes = 'js_catalog_app_link(\'appExt=payPerRentals&app=build_reservation&appPage=default&action=getUpsQuotes&products_id=\'+$(\'.pID\').val()+\'&qty=\'+$selfID.find(\'.rental_qty\').val())';
			$checkRes = 'js_catalog_app_link(\'rType=ajax&appExt=payPerRentals&app=build_reservation&freeTrialButton='.$freeTrialButton.'&freeTrial='.$freeTrial.'&appPage=default&action=checkRes\')';
		}else{
			$upsQuotes = 'js_app_link(\'appExt=orderCreator&app=default&appPage=new&action=getUpsQuotes&products_id=\'+$(\'.pID\').val()+\'&qty=\'+$selfID.find(\'.rental_qty\').val())';
			$checkRes = 'js_app_link(\'rType=ajax&appExt=orderCreator&app=default&freeTrialButton='.$freeTrialButton.'&freeTrial='.$freeTrial.'&appPage=new&action=checkRes\')';
		}

		$countryZones = 'js_catalog_app_link(\'appExt=payPerRentals&app=build_reservation&appPage=default&action=getCountryZones\')';
		if(!is_array($productsId)){
			$pID_string =  array();
			$pID_string[] = $productsId;
		}else{
			$pID_string = $productsId;
		}

		$purchaseTypeClass = $purchaseTypeClasses[0];
		$pprTable = Doctrine_Core::getTable('ProductsPayPerRental')->findOneByProductsId($pID_string[0]);//only for first product
		$QPeriods = Doctrine_Query::create()
		->from('ProductsPayPerPeriods')
		->whereIn('products_id', $pID_string)
		->andWhere('price > 0')
		->execute(array(), Doctrine_Core::HYDRATE_ARRAY);

		$semDates = array();

		$sDate = array();
		if (count($QPeriods)) {
			$QPeriodsNames = Doctrine_Query::create()
			->from('PayPerRentalPeriods')
			->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
			foreach ($QPeriods as $iPeriod) {
				$periodName = '';
				foreach ($QPeriodsNames as $periodNames) {
					if ($periodNames['period_id'] == $iPeriod['period_id']) {
						$periodName = $periodNames;
						break;
					}
				}
				if ($periodName != '') {
					$sDate['start_date'] = $periodName['period_start_date'];
					$sDate['end_date'] = $periodName['period_end_date'];
					$sDate['period_id'] = $iPeriod['period_id'];
					$sDate['period_name'] = $periodName['period_name'];
					$sDate['price'] = $iPeriod['price'];
					$semDates[] = $sDate;
				}
			}
		}
		/*end periods*/

		$allowHourly = (sysConfig::get('EXTENSION_PAY_PER_RENTALS_ALLOW_HOURLY') == 'True') ? true : false;
		$minTime = 60; //slotMinutes

        if(isset($_POST['freeTrialButton']) && $_POST['freeTrialButton'] == '1') {
            $freeOn = explode(',',$_POST['freeTrial']);
            $minRentalPeriod = ReservationUtilities::getPeriodTime($freeOn[0], $freeOn[1]) * 60 * 1000;
            $minRentalMessage = sysLanguage::get('PPR_ERR_AT_LEAST') . ' ' . $freeOn[0] . ' ' . ReservationUtilities::getPeriodType($freeOn[1]) . ' ' . sysLanguage::get('PPR_ERR_DAYS_RESERVED');
        }
        elseif(isset($_GET['freeTrialButton']) && $_GET['freeTrialButton'] == '1') {
            $freeOn = explode(',',$_GET['freeTrial']);
            $minRentalPeriod = ReservationUtilities::getPeriodTime($freeOn[0], $freeOn[1]) * 60 * 1000;
            $minRentalMessage = sysLanguage::get('PPR_ERR_AT_LEAST') . ' ' . $freeOn[0] . ' ' . ReservationUtilities::getPeriodType($freeOn[1]) . ' ' . sysLanguage::get('PPR_ERR_DAYS_RESERVED');
        }
        elseif (sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_GLOBAL_MIN_RENTAL_DAYS') == 'False') {
			$minRentalPeriod = ReservationUtilities::getPeriodTime($pprTable->min_period, $pprTable->min_type) * 60 * 1000;
			$minRentalMessage = sysLanguage::get('PPR_ERR_AT_LEAST') . ' ' . $pprTable->min_period . ' ' . ReservationUtilities::getPeriodType($pprTable->min_type) . ' ' . sysLanguage::get('PPR_ERR_DAYS_RESERVED');
		} else {
			$minRentalPeriod = (int)sysConfig::get('EXTENSION_PAY_PER_RENTALS_MIN_RENTAL_DAYS') * 24 * 60 * 60 * 1000;
			$minRentalMessage = sysLanguage::get('PPR_ERR_AT_LEAST') . ' ' . sysConfig::get('EXTENSION_PAY_PER_RENTALS_MIN_RENTAL_DAYS') . ' ' . 'Days' . ' ' . sysLanguage::get('PPR_ERR_DAYS_RESERVED');
		}

		$maxRentalPeriod = -1;
		$maxRentalMessage = '';
        if(isset($_POST['freeTrialButton']) && $_POST['freeTrialButton'] == '1') {
            $maxRentalPeriod = ReservationUtilities::getPeriodTime($freeOn[0], $freeOn[1]) * 60 * 1000;
            $maxRentalMessage = sysLanguage::get('PPR_ERR_MAXIMUM') . ' ' . $freeOn[0] . ' ' . ReservationUtilities::getPeriodType($freeOn[1]) . ' ' . sysLanguage::get('PPR_ERR_DAYS_RESERVED');
        }
        elseif(isset($_GET['freeTrialButton']) && $_GET['freeTrialButton'] == '1') {
            $maxRentalPeriod = ReservationUtilities::getPeriodTime($freeOn[0], $freeOn[1]) * 60 * 1000;
            $maxRentalMessage = sysLanguage::get('PPR_ERR_MAXIMUM') . ' ' . $freeOn[0] . ' ' . ReservationUtilities::getPeriodType($freeOn[1]) . ' ' . sysLanguage::get('PPR_ERR_DAYS_RESERVED');
        }
        elseif ($pprTable->max_period > 0) {
			$maxRentalPeriod = ReservationUtilities::getPeriodTime($pprTable->max_period, $pprTable->max_type) * 60 * 1000;
			$maxRentalMessage = sysLanguage::get('PPR_ERR_MAXIMUM') . ' ' . $pprTable->max_period . ' ' . ReservationUtilities::getPeriodType($pprTable->max_type) . ' ' . sysLanguage::get('PPR_ERR_DAYS_RESERVED');
		}

		$startTime = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
		//$endTime = mktime(0,0,0,date('m'), 1, date('Y')+3);

		//this part under a for i merge array
		$popArr = array();
		$bookings = array();
		$timeBookings = array();
		$isDisabled = false;
		$disabledBy = '""';
		foreach($pID_string as $nr => $pID_stringElem){

			$reservArr = array();
			$barcodesBooked = array();
			$bookingsF = $purchaseTypeClasses[$nr]->getBookedDaysArray(date('Y-m-d', $startTime), $rQty, &$reservArr, &$barcodesBooked, $usableBarcodes);
			if($bookingsF === false){
				$isDisabled = true;
				$disabledBy = '"'. ReservationUtilities::getProductName($pID_stringElem) . '"';
				$bookingsF = array();
			}
			for($i=0;$i<count($bookings);$i++){
				$popArr[] =  '"' .ReservationUtilities::getProductName($pID_stringElem) .'"';
			}
			$timeBookingsF = $purchaseTypeClasses[$nr]->getBookedTimeDaysArray(date('Y-m-d', $startTime), $rQty, $minTime, $reservArr, $barcodesBooked);

			$bookings = array_merge($bookings, $bookingsF);
			$timeBookings = array_merge($timeBookings, $timeBookingsF);

		}

		$maxShippingDays = -1;
		$shippingTable = '';
		if ($purchaseTypeClass->shippingIsNone() === false && $purchaseTypeClass->shippingIsStore() === false) {
			if($showShipping){
				$shippingTable = $purchaseTypeClass->buildShippingTable();
			}
			$maxShippingDays = $purchaseTypeClass->getMaxShippingDays(date('Y-m-d', $startTime));
		}
		/**
		 * Days Bookings
		 */
		$booked = array();
		$shippingDaysPadding = array();
		$shippingDaysArray = array();
		$paddingDays = array();
		foreach ($bookings as $iBook) {
			$booked[] = '"' . $iBook . '"';

			//period
			$op = 0;
			foreach ($semDates as $sDate) {
				if (strtotime($iBook) >= strtotime($sDate['start_date']) && strtotime($iBook) <= strtotime($sDate['end_date'])) {
					unset($semDates[$op]);
				}
				$op++;
			}
			$semDates = array_values($semDates);
			//end period
			$startTime = strtotime($iBook);
			for ($i = 0; $i <= $maxShippingDays; $i++) {
				$dateFormattedS = date('Y-n-j', strtotime('-' . $i . ' days', $startTime));
				$valDate = '"' . $dateFormattedS . '"';
				$valPos = array_search($valDate, $shippingDaysPadding);
				if ($valPos === false) {
					$shippingDaysPadding[] = $valDate;
					$shippingDaysArray[] = '"' . ($i) . '"';
					//period
					$op = 0;
					foreach ($semDates as $sDate) {
						if (strtotime($dateFormattedS) >= strtotime($sDate['start_date']) && strtotime($dateFormattedS) <= strtotime($sDate['end_date'])) {
							unset($semDates[$op]);
						}
						$op++;
					}
					$semDates = array_values($semDates);
					//end period
				} else {
					if ((int)substr($shippingDaysArray[$valPos], 1, strlen($shippingDaysArray[$valPos]) - 2) > $i) {
						$shippingDaysArray[$valPos] = '"' . $i . '"';
					}
				}
			}
			for ($i = 0; $i <= $maxShippingDays; $i++) {
				$dateFormattedS = date('Y-n-j', strtotime('+' . $i . ' days', $startTime));
				$valDate = '"' . $dateFormattedS . '"';
				$valPos = array_search($valDate, $shippingDaysPadding);
				if ($valPos === false) {
					$shippingDaysPadding[] = $valDate;
					$shippingDaysArray[] = '"' . ($i) . '"';
					//period
					$op = 0;
					foreach ($semDates as $sDate) {
						if (strtotime($dateFormattedS) >= strtotime($sDate['start_date']) && strtotime($dateFormattedS) <= strtotime($sDate['end_date'])) {
							unset($semDates[$op]);
						}
						$op++;
					}
					$semDates = array_values($semDates);
					//end period
				} else {
					if ((int)substr($shippingDaysArray[$valPos], 1, strlen($shippingDaysArray[$valPos]) - 2) > $i) {
						$shippingDaysArray[$valPos] = '"' . $i . '"';
					}
				}
			}
		}

		$disabledDays = sysConfig::explode('EXTENSION_PAY_PER_RENTALS_DISABLED_DAYS', ',');
		$startTimePadding = strtotime(date('Y-m-d'));

		$daysPadding =  (int)sysConfig::get('EXTENSION_PAY_PER_RENTALS_DATE_PADDING');
		foreach($pID_string as $pElem){
			/*$pClass = new Product($pElem);
			if($pClass->isNotAvailable()){
				$date1 = date('Y-m-d h:i:s');
				$date2 = $pClass->getAvailableDate();
				$diff = strtotime($date2) - strtotime($date1);
				$years = floor($diff / (365*60*60*24));
				$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
				$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
				if($days > $daysPadding){
					$daysPadding = $days;
				}
			} */
		}
		$endTimePadding = strtotime('+' . $daysPadding . ' days', $startTimePadding);
		while ($startTimePadding <= $endTimePadding) {
			$dateFormatted = date('Y-n-j', $startTimePadding);
			$paddingDays[] = '"' . $dateFormatted . '"';
			$startTimePadding += 60 * 60 * 24;
		}

		$QBlockedDates = Doctrine_Query::create()
		->from('PayPerRentalBlockedDates')
		->execute(array(), Doctrine_Core::HYDRATE_ARRAY);

		foreach ($QBlockedDates as $bInfo) {
			$startTimePaddingArr = array();
			$endTimePaddingArr = array();

			if ($bInfo['recurring'] == 0) {
				$startTimePaddingArr[] = strtotime($bInfo['block_start_date']);
				$endTimePaddingArr[] = strtotime($bInfo['block_end_date']);
				$i = 1;
			} else {
				$i = 0;
				while (true) {
					$bstartDate = strtotime('+' . $i . ' years', strtotime($bInfo['block_start_date']));
					$bendDate = strtotime('+' . $i . ' years', strtotime($bInfo['block_end_date']));
					$startTimePaddingArr[] = $bstartDate;
					$endTimePaddingArr[] = $bendDate;
					$i++;
					if (date('Y', $bendDate) - 3 > date('Y')) {
						break;
					}
				}

			}
			$j = 0;
			while ($j < $i) {
				$startTimePadding = $startTimePaddingArr[$j];
				$endTimePadding = $endTimePaddingArr[$j];
				while ($startTimePadding <= $endTimePadding) {
					$dateFormatted = date('Y-n-j', $startTimePadding);
					$paddingDays[] = '"' . $dateFormatted . '"';
					//period
					$op = 0;
					foreach ($semDates as $sDate) {
						if (strtotime($dateFormatted) >= strtotime($sDate['start_date']) && strtotime($dateFormatted) <= strtotime($sDate['end_date'])) {
							unset($semDates[$op]);
						}
						$op++;
					}
					$semDates = array_values($semDates);
					//end period
					$startTimePadding += 60 * 60 * 24;
				}
				$j++;
			}
		}

		/**
		 * Time Bookings
		 */
		$timeBooked = array();
		$timeBookedDate = array();
		$toRemoveStart = array();
		$toRemoveEnd = array();
		foreach ($timeBookings as $iBook) {
			$timeDateParse = date_parse($iBook);
			$postStartDate = isset($_POST['start_date'])?date_parse($_POST['start_date']):date_parse(date('Y-m-d'));
			$postEndDate = isset($_POST['end_date'])?date_parse($_POST['end_date']):date_parse(date('Y-m-d'));
			if(sysConfig::get('EXTENSION_PAY_PER_RENTALS_ENABLE_TIME_DROPDOWN') == 'True'){
				if($timeDateParse['year'] == $postStartDate['year'] && $timeDateParse['month'] == $postStartDate['month'] && $timeDateParse['day'] == $postStartDate['day']){
					$toRemoveStart[] = $timeDateParse['hour'];
				}
				if($timeDateParse['year'] == $postEndDate['year'] && $timeDateParse['month'] == $postEndDate['month'] && $timeDateParse['day'] == $postEndDate['day']){
					$toRemoveEnd[] = $timeDateParse['hour'];
				}
			}
			$stringStart = 'new Date(' . $timeDateParse['year'] . ',' . ($timeDateParse['month'] - 1) . ',' . $timeDateParse['day'] . ',' . $timeDateParse['hour'] . ',' . $timeDateParse['minute'] . ')';
			$stringEnd = 'new Date(' . $timeDateParse['year'] . ',' . ($timeDateParse['month'] - 1) . ',' . $timeDateParse['day'] . ',' . $timeDateParse['hour'] . ',' . ($timeDateParse['minute'] + 1) . ')';
			$timeBooked[] = "{title:'Not Available',start:" . $stringStart . ",end:" . $stringEnd . ", allDay:false}";
			$timeBookedDate[] = $stringStart;
		}
		 $oldIbookDate = '';
		foreach($timeBookings as $iBookDate){
			if(!empty($oldIbookDate)){
				$oldpostStartDate = date_parse($oldIbookDate);
			}else{
				$oldpostStartDate = '';
			}
			$postStartDate = date_parse($iBookDate);
			if($oldpostStartDate != '' && $oldpostStartDate['year'] == $postStartDate['year'] && $oldpostStartDate['month'] == $postStartDate['month'] && $oldpostStartDate['day'] == $postStartDate['day']){
				continue;
			}
			$toRemoveStart1 = array();
			foreach ($timeBookings as $iBook) {
				$timeDateParse = date_parse($iBook);
				if(sysConfig::get('EXTENSION_PAY_PER_RENTALS_ENABLE_TIME_DROPDOWN') == 'True'){
					if($timeDateParse['year'] == $postStartDate['year'] && $timeDateParse['month'] == $postStartDate['month'] && $timeDateParse['day'] == $postStartDate['day']){
						$toRemoveStart1[] = $timeDateParse['hour'];
					}
					$dayDisabled = true;
					$starttimeVal1 = (int) sysConfig::get('EXTENSION_PAY_PER_RENTALS_START_TIME');
					$endtimeVal1 = (int) sysConfig::get('EXTENSION_PAY_PER_RENTALS_END_TIME');
					for($iT=$starttimeVal1;$iT<=$endtimeVal1;$iT++){
						if(!in_array($iT,$toRemoveStart1)){
							$dayDisabled = false;
							break;
						}
					}
					if($dayDisabled){
						$booked[] = '"' . date('Y-n-j', strtotime($iBookDate)) . '"';
					}
				}
			}
			$oldIbookDate = $iBookDate;
		}
		//ob_start(array('ReservationUtilities','fatal_error_handler'));
		ob_start();
		?>
	<script>
	var bookedDates = [<?php echo implode(',', $booked);?>];
	var popArr = [<?php echo implode(',', $popArr);?>];
	var shippingDaysPadding = [<?php echo implode(',', $shippingDaysPadding);?>];
	var shippingDaysArray = [<?php echo implode(',', $shippingDaysArray);?>];
	var disabledDatesPadding = [<?php echo implode(',', $paddingDays);?>];
	var dayShortNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
	var disabledDays = ["<?php echo implode('","', $disabledDays);?>"];
	var disabledDates = [];
	var minRentalPeriod1 = <?php echo $minRentalPeriod;?>;
	var maxRentalPeriod = <?php echo $maxRentalPeriod;?>;
	var disabledWithMessage = <?php echo ((sysConfig::get('EXTENSION_PAY_PER_RENTALS_DISABLED_MESSAGE') == 'True')?'true':'false');?>;
	var minRentalPeriodMessage1 = '<?php echo $minRentalMessage;?>';
	var maxRentalPeriodMessage = '<?php echo $maxRentalMessage; ?>';
	var allowSelectionBefore = true;
	var allowSelectionAfter = true;
	var allowSelection = true;
	var allowSelectionMin = true;
	var allowSelectionMax = true;
	var isChecked = <?php echo (($isChecked == false)?'false':'true');?>;
	var startArray = [<?php echo implode(',', $timeBooked);?>];
	var bookedTimesArr = [<?php echo implode(',', $timeBookedDate);?>];
    var isCatalog = <?php echo ($App->getEnv() == 'catalog')?'true':'false';?>;
	var selected = '';
	var selectedDate;
	var days_before = 0;
	var days_after = 0;
	var isStart = false;
	//var autoChanged = false;
	var isHour = false;
	var isDisabled = <?php echo (($isDisabled === true)?'true':'false');?>;
	var disabledBy = <?php echo $disabledBy;?>;

	$(document).ready(function () {
		var $selfID = $('#reserv<?php echo $pID_string[0]; ?>');
		$selfID.parent().find('.inCart').hide();

		$selfID.find('.refreshCal').live('click', function() {
			if (selectedStartTimeTd != null) {
				selectedStartTimeTd.data('element').remove();
			}
			if (selectedEndTimeTd != null) {
				selectedEndTimeTd.data('element').remove();
			}
			selectedStartTime = null;
			selectedStartTimeTd = null;
			isTimeStart = false;
			selectedEndTime = null;
			selectedEndTimeTd = null; 
			<?php 
	if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_FORCE_START_DATE') == 'True') {
				?>
				$selfID.find('.datePicker').datepick('setDate', -1);
				var todayDate = new Date();
				selected = 'start';
				selectedDate = todayDate;
				isStart = true;
				var today_day = '';
				var today_month = '';

				if (todayDate.getDate() < 10) {
					today_day = '0' + todayDate.getDate();
				} else {
					today_day = todayDate.getDate();
				}

				if (todayDate.getMonth() < 10) {
					today_month = '0' + todayDate.getMonth();
				} else {
					today_month = todayDate.getMonth();
				}

				//this part won't work for shipping days before

				$selfID.find('.start_date').val(today_month + '/' + today_day + '/' + todayDate.getFullYear()).trigger('change');
				$selfID.find('.end_date').val('').trigger('change');
				
				

				<?php

			} else {
				?>

				if (selected == 'start') {
					$selfID.find('.datePicker').datepick('setDate', 0);
				} else if(selected == 'end') {
					$selfID.find('.datePicker').datepick('setDate', -1);
				}
				selected = '';
				selectedDate = '';
				isStart = false;
				allowSelectionBefore = true;
				allowSelectionAfter = true;
				allowSelection = true;
				allowSelectionMin = true;
				allowSelectionMax = true;
				$selfID.find('.start_date').val('').trigger('change');
				$selfID.find('.end_date').val('').trigger('change');
				$selfID.find('.calendarTime').hide();
				$selfID.parent().find('.priceQuote').html('');
				
				<?php

			}
			?>

		});

		if ($selfID.find('input[name=rental_shipping]').size() > 0 && $selfID.find('input[name=rental_shipping]:checked').size() == 0) {
			$selfID.find('input[name=rental_shipping]').each(function () {
				$(this).trigger('click');
			});
		}

		$selfID.find('.datePicker').datepick({
			useThemeRoller: true,
			<?php if($App->getEnv() == 'catalog'){ ?>
			minDate: '0',
			<?php } ?>
			dateFormat: '<?php echo getJsDateFormat();?>',
			rangeSelect: <?php echo ((sysConfig::get('EXTENSION_PAY_PER_RENTALS_FORCE_START_DATE') == 'True') ? 'false' : 'true');?>,
			rangeSeparator: ',',
			changeMonth: false,
			firstDay:0,
			changeYear: false,
			numberOfMonths: <?php echo sysConfig::get('EXTENSION_PAY_PER_RENTALS_NUMBER_OF_MONTHS_CALENDARS');?>,
			prevText: '<span class="ui-icon ui-icon-circle-triangle-w"></span>',
			prevStatus: '<?php echo sysLanguage::get('PPR_PREV_MONTH'); ?>',
			nextText: '<span class="ui-icon ui-icon-circle-triangle-e"></span>',
			nextStatus: '<?php echo sysLanguage::get('PPR_NEXT_MONTH'); ?>',
			clearText: '<?php echo sysLanguage::get('PPR_RESET'); ?>',
			clearStatus: '<?php echo sysLanguage::get('PPR_RESET_SELECTED'); ?>',
			initStatus: '<?php echo sysLanguage::get('PPR_SELECT_START_DATE'); ?>',
			showStatus: true,
			beforeShowDay: function (dateObj) {
				dateObj.setHours(0, 0, 0, 0);
				var dateFormatted = $.datepick.formatDate('yy-m-d', dateObj);
				today = new Date($.datepick.formatDate('yy-m-d', new Date()));
				/*if (today.getTime() > new Date(dateFormatted).getTime()){
					return [false, ''];
				}else */if ($.inArray(dayShortNames[dateObj.getDay()], disabledDays) > -1 && !isChecked) {
					return [false, 'ui-datepicker-disabled ui-datepicker-shipable', 'Disabled By Admin'];
				} else if ($.inArray(dateFormatted, bookedDates) > -1 && !isChecked || isDisabled == true) {
					return [false, 'ui-datepicker-reserved', 'Reserved for '+ ((isDisabled == false)?popArr[$.inArray(dateFormatted, bookedDates)]:disabledBy)];
				} else if ($.inArray(dateFormatted, disabledDatesPadding) > -1 && disabledWithMessage == false && !isChecked) {
					return [false, 'ui-datepicker-disabled', 'Disabled by Admin'];
				} else if ($.inArray(dateFormatted, shippingDaysPadding) > -1) {
					return [true, 'hasd dayto-' + shippingDaysArray[$.inArray(dateFormatted, shippingDaysPadding)], 'Available'];
				} else {
					if (disabledDates.length > 0) {
						for (var i = 0; i < disabledDates.length; i++) {
							var dateFrom = new Date();
							dateFrom.setFullYear(
									disabledDates[i][0][0],
									disabledDates[i][0][1] - 1,
									disabledDates[i][0][2]
									);
							dateFrom.setHours(0, 0, 0, 0);

							var dateTo = new Date();
							dateTo.setFullYear(
									disabledDates[i][1][0],
									disabledDates[i][1][1] - 1,
									disabledDates[i][1][2]
									);
							dateTo.setHours(0, 0, 0, 0);

							if (dateObj >= dateFrom && dateObj <= dateTo && !isChecked) {
								return [false, 'ui-datepicker-disabled', '<?php echo sysLanguage::get('PPR_DISABLED_BY_ADMIN'); ?>'];
							}
						}
					}
				}
				return [true, '', '<?php echo sysLanguage::get('PPR_AVAILABLE'); ?>'];
			},
			onHover: function (value, date, inst, curTd) {
				if (date == null) {
					$('.ui-datepicker-shipping-day-hover').removeClass('ui-datepicker-shipping-day-hover');
					$(curTd).removeClass('ui-datepicker-start_date');
				} else {
					$(curTd).addClass('ui-datepicker-start_date');
					var shippingDaysBefore = $selfID.find('input[name=rental_shipping]:checked').attr('days_before');
					var shippingDaysAfter = $selfID.find('input[name=rental_shipping]:checked').attr('days_after');
					var prevTD = $(curTd);
					var nextTD = $(curTd);

					allowSelectionBefore = true;
					allowSelectionAfter = true;

					if (!isStart) {
						//for (var i = 0; i < shippingDaysBefore; i++) {

						var sEnd = shippingDaysBefore;
						while(sEnd > 0){
							if (prevTD.prev().size() <= 0) {
								if (prevTD.find('a').html() == '1' || prevTD.html() == '1') {
									prevTD = prevTD.closest('.ui-datepicker-group').prev().find('td').filter(':not(.ui-datepicker-other-month)').last();
								} else {
									prevTD = prevTD.parent().prev().find('td').filter(':not(.ui-datepicker-other-month)').last();
								}
							} else {
								prevTD = prevTD.prev();
							}

							if (prevTD.hasClass('ui-datepicker-other-month')) {
								prevTD = prevTD.closest('.ui-datepicker-group').prev().find('td').filter(':not(.ui-datepicker-other-month)').last();
							}

							$('a', prevTD).addClass('ui-datepicker-shipping-day-hover');
							if (prevTD.hasClass('ui-state-disabled') && !prevTD.hasClass('ui-datepicker-shipable')) {
								allowSelectionBefore = false;
							}
							if (prevTD.hasClass('ui-state-disabled')){
								sEnd ++;
							}
							sEnd = sEnd - 1;
						}
					} else {
						var sEnd2 = shippingDaysAfter;
						while(sEnd2 > 0){
							if (nextTD.next().size() <= 0) {
								nextTD = nextTD.parent().next().find('td').first();
							} else {
								nextTD = nextTD.next();
							}

							if (nextTD.hasClass('ui-datepicker-other-month')) {
								nextTD = nextTD.closest('.ui-datepicker-group').next().find('td').filter(':not(.ui-datepicker-other-month)').first();
							}

							$('a', nextTD).addClass('ui-datepicker-shipping-day-hover');

							if (nextTD.hasClass('ui-state-disabled') && !nextTD.hasClass('ui-datepicker-shipable')) {
								allowSelectionAfter = false;
							}

							if (nextTD.hasClass('ui-state-disabled')){
								sEnd2 ++;
							}
							sEnd2 = sEnd2 - 1;
						}
					}

				}
			},
			onDayClick: function (date, inst, td) {

				var shippingLabel;
				var myclass = '';
				var sDay = 0;
				var words;
				var sDaysArr;
				var shippingDaysBefore = $selfID.find('input[name=rental_shipping]:checked').attr('days_before');
				var shippingDaysAfter = $selfID.find('input[name=rental_shipping]:checked').attr('days_after');
				var dateFormatted = $.datepick.formatDate('yy-m-d', date);
				if($.inArray(dateFormatted, disabledDatesPadding) > -1 && disabledWithMessage){
					alert('If you require service within 48 hours, please contact us directly at (877) 922-2337. Representatives are standing by and we make every effort to service your request.');
					return false;
				}else{
				if($selfID.find('input[name=rental_shipping]:checked').attr('min_rental')){
					minRentalPeriod = $selfID.find('input[name=rental_shipping]:checked').attr('min_rental');
					minRentalPeriodMessage = $('#'+minRentalPeriod).html();
				}else{
					minRentalPeriod = minRentalPeriod1;
					minRentalPeriodMessage = minRentalPeriodMessage1;
				}

				myclass = $(td).attr('class');
				if (myclass) {
					words = myclass.split(' ');
					sDay = 1000;
					for (var j = 0; j < words.length; j++) {
						if (words[j].indexOf('dayto') >= 0) {
							sDaysArr = words[j].split('-');
							sDay = parseInt(sDaysArr[1]);
							break;
						}
					}

					if (!isStart) {
						if (sDay - shippingDaysBefore <= 0) {
							allowSelectionBefore = false;
						}
					} else {
						if (sDay != 1000)
							if (shippingDaysAfter > sDay) {
								allowSelectionAfter = false;
							}
					}
				}
				if (selected == 'start') {
					allowSelection = true;
					for (var k = 0; k < bookedDates.length; k++) {
						bDateArr = bookedDates[k].split('-');
						bDate = new Date(parseInt(bDateArr[0]), parseInt(bDateArr[1]) - 1, parseInt(bDateArr[2]));
						if (selectedDate.getTime() <= bDate.getTime() && date.getTime() >= bDate.getTime()) {
							allowSelection = false;
						}
					}
					allowSelectionMin = true;
					if ((date.getTime() - selectedDate.getTime() + 24*60*60*1000) < ((minRentalPeriod))) {
						allowSelectionMin = false;
					}
					allowSelectionMax = true;
					if (((date.getTime() - selectedDate.getTime() + 24*60*60*1000) > (maxRentalPeriod)) && maxRentalPeriod != -1) {
						allowSelectionMax = false;
					}
				}

				//end check here
				if (allowSelectionMin == false && !isChecked) {
					alert(minRentalPeriodMessage);
					return false;
				}
				if (allowSelectionMax == false && !isChecked) {
					alert(maxRentalPeriodMessage);
					return false;
				}
				if (allowSelection == false && !isChecked) {
					alert('<?php echo sysLanguage::get('PPR_ERR_RESERVATION_BETWEEN'); ?>');
					return false;
				}
				if (allowSelectionBefore == false && !isChecked) {
					var shippingDaysBefore = $selfID.find('input[name=rental_shipping]:checked').attr('days_before');
					shippingLabel = $selfID.find('input[name=rental_shipping]:checked').parent().parent().find('td').first().html();
					alert('<?php echo sysLanguage::get('PPR_ERR_SHIP_METHOD'); ?> ' + shippingLabel + ', <?php echo sysLanguage::get('PPR_ERR_NEED_TO_ALLOW'); ?> ' + shippingDaysBefore + ' <?php echo sysLanguage::get('PPR_ERR_SHIP_DAYS_BEFORE_RESERVATION'); ?>');
					return false;
				}
				if (allowSelectionAfter == false && !isChecked) {
					var shippingDaysAfter = $selfID.find('input[name=rental_shipping]:checked').attr('days_after');
					shippingLabel = $selfID.find('input[name=rental_shipping]:checked').parent().parent().find('td').first().html();
					alert('<?php echo sysLanguage::get('PPR_ERR_SHIP_METHOD'); ?> ' + shippingLabel + ', <?php echo sysLanguage::get('PPR_ERR_NEED_TO_ALLOW'); ?> ' + shippingDaysBefore + ' <?php echo sysLanguage::get('PPR_ERR_SHIP_DAYS_AFTER_RESERVATION'); ?>');
					return false;
				}



				selected = (selected == '' || selected == 'end' ? 'start' : 'end');

				if (selected == 'start') {
					selectedDate = date;
					$selfID.find('.datePicker').datepick('option', 'initStatus', '<?php echo sysLanguage::get('PPR_SELECT_END_DATE'); ?>');
					$selfID.parent().find('.inCart').hide();

					days_before = $selfID.find('input[name=rental_shipping]:checked').attr('days_before');

					var prevTD = $(td);


						var sEnd = shippingDaysBefore;
						while(sEnd > 0){
							if (prevTD.prev().size() <= 0) {
								if (prevTD.find('a').html() == '1' || prevTD.html() == '1') {
									prevTD = prevTD.closest('.ui-datepicker-group').prev().find('td').filter(':not(.ui-datepicker-other-month)').last();
								} else {
									prevTD = prevTD.parent().prev().find('td').filter(':not(.ui-datepicker-other-month)').last();
								}
							} else {
								prevTD = prevTD.prev();
							}

							if (prevTD.hasClass('ui-datepicker-other-month')) {
								prevTD = prevTD.closest('.ui-datepicker-group').prev().find('td').filter(':not(.ui-datepicker-other-month)').last();
							}

							$('a', prevTD).addClass('ui-datepicker-shipping-day-hover');

							if (prevTD.hasClass('ui-state-disabled')){
								sEnd ++;
								days_before ++;
							}
							sEnd = sEnd - 1;
						}


				} else if (selected == 'end') {



					days_after = $selfID.find('input[name=rental_shipping]:checked').attr('days_after');
					var nextTD = $(td);
					var sEnd2 = $selfID.find('input[name=rental_shipping]:checked').attr('days_after');
						while(sEnd2 > 0){
							if (nextTD.next().size() <= 0) {
								nextTD = nextTD.parent().next().find('td').first();
							} else {
								nextTD = nextTD.next();
							}

							if (nextTD.hasClass('ui-datepicker-other-month')) {
								nextTD = nextTD.closest('.ui-datepicker-group').next().find('td').filter(':not(.ui-datepicker-other-month)').first();
							}

							$('a', nextTD).addClass('ui-datepicker-shipping-day-hover');

							if (nextTD.hasClass('ui-state-disabled') && !nextTD.hasClass('ui-datepicker-shipable')) {
								allowSelectionAfter = false;
							}

							if (nextTD.hasClass('ui-state-disabled')){
								sEnd2 ++;
								days_after ++;
							}
							sEnd2 = sEnd2 - 1;
						}
					$selfID.find('.datePicker').datepick('option', 'initStatus', '<?php echo sysLanguage::get('PPR_DATES_SELECTED'); ?>.<br /><?php echo sysLanguage::get('PPR_CLICK_RESTART_PROCESS'); ?>');
					var monthT = date.getMonth() + 1;
					var daysT = date.getDate();
					var daysTs = '';
					var monthTs = '';
					if (daysT < 10) {
						daysTs = '0' + daysT;
					} else {
						daysTs = daysT + '';
					}
					if (monthT < 10) {
						monthTs = '0' + monthT;
					} else {
						monthTs = monthT + '';
					}
					$selfID.find('.end_date').val(monthTs + '/' + daysTs + '/' + date.getFullYear()).trigger('change');
					$selfID.find('.days_before').val(days_before);
					$selfID.find('.days_after').val(days_after);
					var $this = $selfID.find('.datePicker');
					$sDate = new Date($selfID.find('.start_date').val());
					$eDate = new Date($selfID.find('.end_date').val());
					//alert($sDate + '   '+$eDate +' '+$('#start_date').val()+'  '+$('#end_date').val());
					if ($sDate.getTime() != $eDate.getTime()) {
						showAjaxLoader($this, 'xlarge');
						$.ajax({
							cache: false,
							dataType: 'json',
							type: 'post',
							url: <?php echo $checkRes;?>,
							data: $selfID.parent().find('.reservationTable *, .ui-widget-footer-box *, .pprButttons *').serialize(),
							success: function (data) {
								if (data.success == true) {
									$selfID.find('.shippingTable').html(data.htmlShipping);
									if ($selfID.find('input[name=rental_shipping]').size() > 0 && $selfID.find('input[name=rental_shipping]:checked').size() == 0) {
										$selfID.find('input[name=rental_shipping]').each(function () {
											$(this).trigger('click');
										});
									}
									$selfID.parent().find('.priceQuote').html($('.rental_qty').val()+'X ('+data.price + ' ' + data.message+') ='+data.totalPrice);
									$selfID.parent().find('.priceQuote').trigger('EventAfterPriceQuote');
									if(isCatalog){
										$selfID.parent().find('.inCart').show();
										$selfID.parent().find('.inCart').button();


									}

								} else if (data.success == 'not_supported') {
									$selfID.parent().find('.priceQuote').html(data.price);
								} else {
									alert('<?php echo sysLanguage::get('PPR_NOTICE_RESERVATION_NOT_AVAILABLE'); ?>.');
								}
								removeAjaxLoader($this);
							}
						});
					}
				} else {
					$selfID.find('.datePicker').datepick('option', 'initStatus', '<?php echo sysLanguage::get('PPR_SELECT_START_DATE'); ?>');
				}
			<?php
   		if ($allowHourly && sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_FULL_DAYS') == 'False' && (sysConfig::get('EXTENSION_ORDER_CREATOR_SHOW_TIMES') == 'False' && sysConfig::get('EXTENSION_PAY_PER_RENTALS_ENABLE_TIME_DROPDOWN') == 'False' || $App->getEnv() == 'catalog' && sysConfig::get('EXTENSION_PAY_PER_RENTALS_ENABLE_TIME_DROPDOWN') == 'False')) {
					?>
					$selfID.find('.calendarTime').show();
					$selfID.find('.calendarTime').fullCalendar('gotoDate', date);
					$sDate = new Date($selfID.find('.start_date').val());
					$eDate = new Date($selfID.find('.end_date').val());

					if ($sDate.getTime() != $eDate.getTime() || selected == 'end') {
						if (selectedStartTimeTd != null) {
							selectedStartTimeTd.data('element').remove();
						}
						if (selectedEndTimeTd != null) {
							selectedEndTimeTd.data('element').remove();
						}
					}
					if ($sDate.getTime() != $eDate.getTime()) {
						$selfID.find('.calendarTime').hide();
					}
					<?php

				}
				?>
			}
			},
			onSelect: function (value, date, inst) {
				var dates = value.split(',');
			<?php
   	if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_FORCE_START_DATE') == 'True') {
					?>
					if ($selfID.find('.start_date').val() == '' && isStart) {
						$selfID.find('.start_date').val(dates[0]).trigger('change');
						$selfID.find('.end_date').val(dates[1]).trigger('change');

						$selfID.find('.days_before').val(days_before);
						$selfID.find('.days_after').val(days_after);

						isStart = false;
						if (dates[0] != dates[1]) {
							$selfID.find('.datePicker').datepick('option', 'maxDate', null);
						} else {
							isStart = true;
						}
					} else {
						var todayDate = new Date();
						$selfID.find('.end_date').val(dates[0]).trigger('change');
						$selfID.find('.days_before').val(days_before);
						$selfID.find('.days_after').val(days_after);
						selected = 'start';
						selectedDate = todayDate;
						isStart = true;

					}
					<?php

				} elseif(sysConfig::get('EXTENSION_PAY_PER_RENTALS_END_DATE_SAME_START_DATE') == 'True'){
					?>
					if ($selfID.find('.start_date').val() == '' && isStart) {
					} else {
						$selfID.find('.start_date').val(dates[0]).trigger('change');
						$selfID.find('.end_date').val(dates[0]).trigger('change');
						$selfID.find('.days_before').val(days_before);
						$selfID.find('.days_after').val(days_after);
						$selfID.find('.datePicker').datepick('option', 'maxDate', null);
						isStart = false;
						selected = 'end';
						$('.end_time').trigger('change');
					}
			<?php
				} else {
					?>
					var dates = value.split(',');
					$selfID.find('.start_date').val(dates[0]).trigger('change');
					$selfID.find('.end_date').val(dates[1]).trigger('change');
					$selfID.find('.days_before').val(days_before);
					$selfID.find('.days_after').val(days_after);
					isStart = false;
					if (dates[0] != dates[1]) {
						$selfID.find('.datePicker').datepick('option', 'maxDate', null);
					} else {
						isStart = true;
					}
		   if(isStart == true){
		   var $this = $selfID.find('.datePicker');
		   showAjaxLoader($this, 'xlarge');
		   $.ajax({
			   cache: false,
			   dataType: 'json',
			   type: 'post',
			   url: <?php echo $checkRes;?>,
			   data: $selfID.parent().find('.reservationTable *, .pprButttons *').serialize(),
			   success: function (data) {
				   if (data.success == true) {
					   removeAjaxLoader($this);
					   $selfID.find('.shippingTable').html(data.htmlShipping);
					   if($('#googleAddress').size() > 0){
						   $selfID.find('.shipSelect').hide();
					   }
					   if ($selfID.find('input[name=rental_shipping]').size() > 0 && $selfID.find('input[name=rental_shipping]:checked').size() == 0) {
						   $selfID.find('input[name=rental_shipping]').each(function () {
							   $(this).trigger('click');
						   });
					   }
					   $selfID.parent().find('.priceQuote').html($('.rental_qty').val()+'X ('+data.price + ' ' + data.message+') ='+data.totalPrice);
					   if(isCatalog){
						   $selfID.parent().find('.inCart').show();
						   $selfID.parent().find('.inCart').button();
					   }
					   if($selfID.find('.timesInputs').size() > 0 && $selfID.find('.timesInputs').html() != data.timeInputs){
						   $selfID.find('.timesInputs').html(data.timeInputs);
						   $selfID.find('.start_time').trigger('change');
					   }
				   } else if (data.success == 'not_supported') {
					   $selfID.parent().find('.priceQuote').html(data.price);
				   } else {
					   alert('<?php echo sysLanguage::get('PPR_NOTICE_RESERVATION_NOT_AVAILABLE'); ?>.');
				   }
			   }
		   });
		   }
					<?php
				}
				?>
			}
		});
			<?php
   	if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_FORCE_START_DATE') == 'True') {
			?>
			var todayDate = new Date();
			selected = 'start';
			selectedDate = todayDate;
			isStart = true;
			var today_day = '';
			var today_month = '';

			if (todayDate.getDate() < 10) {
				today_day = '0' + todayDate.getDate();
			} else {
				today_day = todayDate.getDate();
			}

			if (todayDate.getMonth() < 10) {
				today_month = '0' + (todayDate.getMonth() + 1);
			} else {
				today_month = todayDate.getMonth() + 1;
			}

			$selfID.find('.start_date').val(today_month + '/' + today_day + '/' + todayDate.getFullYear()).trigger('change');
			<?php

		}
		?>

		$selfID.find('.rental_qty').change(function () {
			var $calLoader = $selfID.find('.datePicker');
			showAjaxLoader($calLoader, 'xlarge');
			$.ajax({
				cache: false,
				dataType: 'json',
				type: 'post',
				url: <?php echo $callLink; ?>,
				data: 'action=<?php echo $callAction;?>&' + $selfID.parent().find('.reservationTable *, .ui-widget-footer-box *, .pprButttons *').serialize(),
				success: function (data) {
					if (data.success == true) {
						removeAjaxLoader($calLoader);
						$selfID.parent().html(data.calendar);
						$('#getQuotes').button();
						$selfID.parent().find('.inCart').hide();
						$selfID.parent().find('.start_date').val('');
						$selfID.parent().find('.end_date').val('');
						$calLoader.trigger('EventAfterLoadedCalendar');
					}
				}
			});
		});
		/*this part down will need some testing*/
		$selfID.find('.selected_period').change(function() {

			if ($(this).val() != '' && $(this).val() != null) {
				var selectedPeriod = $(this);
				var startDateString = $selfID.find('.selected_period option:selected').attr('start_date');
				var endDateString = $selfID.find('.selected_period option:selected').attr('end_date');
				$selfID.find('.start_date').val(startDateString.substr(0, startDateString.length - 9)).trigger('change');
				$selfID.find('.end_date').val(endDateString.substr(0, endDateString.length - 9)).trigger('change');
				$selfID.find('.days_before').val(days_before);
				$selfID.find('.days_after').val(days_after);
				hasDisabled = false;
				var attrDis = $selfID.find('.selected_period').attr('disabled');
				if (typeof attrDis !== 'undefined' && attrDis !== false) {
					$selfID.find('.selected_period').removeAttr('disabled');
					hasDisabled = true;
				}
				showAjaxLoader(selectedPeriod, 'xlarge');
				$.ajax({
					cache: false,
					dataType: 'json',
					type: 'post',
					url: <?php echo $checkRes;?>,
					data: $selfID.parent().find('.reservationTable *, .ui-widget-footer-box *, .pprButttons *').serialize(),//+'&price='+price,//isSemester=1&
					success: function (data) {
						if (data.success == true) {
							$selfID.find('.shippingTable').html(data.htmlShipping);
							if($('#googleAddress').size() > 0){
								$selfID.find('.shipSelect').hide();
							}
							if ($selfID.find('input[name=rental_shipping]').size() > 0 && $selfID.find('input[name=rental_shipping]:checked').size() == 0) {
								$selfID.find('input[name=rental_shipping]').each(function () {
									$(this).trigger('click');
								});
							}
							$selfID.parent().find('.priceQuote').html($('.rental_qty').val()+'X ('+data.price + ' ' + data.message+') ='+data.totalPrice);
							if(isCatalog){
								$selfID.parent().find('.inCart').show();
								$selfID.parent().find('.inCart').button();


							}
						} else if (data.success == 'not_supported') {
							$selfID.parent().find('.priceQuote').html(data.price);
						} else {
							alert('<?php echo sysLanguage::get('PPR_NOTICE_RESERVATION_NOT_AVAILABLE'); ?>.');
						}
						if(hasDisabled){
							$('.selected_period').attr('disabled','disabled');
						}
						removeAjaxLoader(selectedPeriod);
					}
				});
			} else {
				$selfID.parent().find('.priceQuote').html('');
				$selfID.parent().find('.inCart').hide();
			}
		});
		var isTimeStart = false;
		var selectedStartTimeTd = null;
		var selectedEndTimeTd = null;
		var selectedStartTime = null;
		var selectedEndTime = null;

		$selfID.find('.calendarTime').fullCalendar({
			header: {
				left:   '',
				center: '',
				right:  ''
			},
			theme: true,
			allDaySlot:false,
			slotMinutes:<?php echo $minTime;?>,
			editable: false,
			disableDragging: true,
			disableResizing: true,
			minTime:'<?php echo sysConfig::get('EXTENSION_PAY_PER_RENTALS_START_TIME');?>',
			maxTime:'<?php echo sysConfig::get('EXTENSION_PAY_PER_RENTALS_END_TIME');?>',
			defaultView: 'agendaDay',
			height: 296,
			events: startArray,
			dayClick: function(date, allDay, jsEvent, view) {
				if (isTimeStart == false) {
					isTimeStart = true;
					if (selectedStartTimeTd != null) {
						selectedStartTimeTd.data('element').remove();
					}
					if (selectedEndTimeTd != null) {
						selectedEndTimeTd.data('element').remove();
					}
					selectedStartTimeTd = $(this);
					selectedEndTime = null;
					selectedEndTimeTd = null;

					selectedStartTime = new Date(date);
					$el = $('<span></span>').html('Selected Start Time');
					$el.css('background-color', 'red');
					$el.css('color', 'white');
					selectedStartTimeTd.find('div').first().remove();
					selectedStartTimeTd.append($el);
					selectedStartTimeTd.data('element', $el);

					if (selectedStartTime.getDate() < 10) {
						today_day = '0' + selectedStartTime.getDate();
					} else {
						today_day = selectedStartTime.getDate();
					}

					if (selectedStartTime.getMonth() < 10) {
						today_month = '0' + (selectedStartTime.getMonth() + 1);
					} else {
						today_month = selectedStartTime.getMonth() + 1;
					}

					$selfID.find('.start_date').val(today_month + '/' + today_day + '/' + selectedStartTime.getFullYear() + ' ' + selectedStartTime.getHours() + ':' + selectedStartTime.getMinutes() + ':00').trigger('change');
				} else {
					if (selectedStartTime < new Date(date)) {

						var allowSelectionTime = true;
						for (var k = 0; k < bookedTimesArr.length; k++) {
							if (selectedStartTime.getTime() <= bookedTimesArr[k].getTime() && date.getTime() >= bookedTimesArr[k].getTime()) {
								allowSelectionTime = false;
							}
						}
						var allowSelectionMinTime = true;
						if ((date.getTime() - selectedStartTime.getTime() + 24*60*60*1000) < ((minRentalPeriod))) {
							allowSelectionMinTime = false;
						}
						var allowSelectionMaxTime = true;
						if (((date.getTime() - selectedStartTime.getTime() + 24*60*60*1000) > (maxRentalPeriod)) && maxRentalPeriod != -1) {
							alert(date.getTime() - selectedStartTime.getTime());
							allowSelectionMaxTime = false;
						}


						//end check here
						if (allowSelectionMinTime == false) {
							alert(minRentalPeriodMessage);
							return false;
						}
						if (allowSelectionMaxTime == false) {
							alert(maxRentalPeriodMessage);
							return false;
						}
						if (allowSelectionTime == false) {
							alert('<?php echo sysLanguage::get('PPR_ERR_RESERVATION_BETWEEN'); ?>');
							return false;
						}

						isTimeStart = false;
						selectedEndTimeTd = $(this);
						selectedEndTime = new Date(date);
						$el = $('<span></span>').html('Selected End Time');
						$el.css('background-color', 'red');
						$el.css('color', 'white');
						selectedEndTimeTd.find('div').first().remove();
						selectedEndTimeTd.append($el);
						selectedEndTimeTd.data('element', $el);

						if (selectedEndTime.getDate() < 10) {
							today_day = '0' + selectedEndTime.getDate();
						} else {
							today_day = selectedEndTime.getDate();
						}

						if (selectedEndTime.getMonth() < 10) {
							today_month = '0' + (selectedEndTime.getMonth() + 1);
						} else {
							today_month = selectedEndTime.getMonth() + 1;
						}

						$selfID.find('.end_date').val(today_month + '/' + today_day + '/' + selectedEndTime.getFullYear() + ' ' + selectedEndTime.getHours() + ':' + selectedEndTime.getMinutes() + ':00').trigger('change');
						$selfID.find('.days_before').val(days_before);
						$selfID.find('.days_after').val(days_after);
						var $this = $selfID.find('.datePicker');

						showAjaxLoader($this, 'xlarge');
						$.ajax({
							cache: false,
							dataType: 'json',
							type: 'post',
							url: <?php echo $checkRes;?>,
							data: $selfID.parent().find('.reservationTable *, .ui-widget-footer-box *, .pprButttons *').serialize(),
							success: function (data) {
								if (data.success == true) {
									removeAjaxLoader($this);
									$selfID.find('.shippingTable').html(data.htmlShipping);
									if($selfID.find('.timesInputs').size() > 0){
										$selfID.find('.timesInputs').html(data.timeInputs);
									}
									if($('#googleAddress').size() > 0){
										$selfID.find('.shipSelect').hide();
									}
									if ($selfID.find('input[name=rental_shipping]').size() > 0 && $selfID.find('input[name=rental_shipping]:checked').size() == 0) {
										$selfID.find('input[name=rental_shipping]').each(function () {
											$(this).trigger('click');
										});
									}
									$selfID.parent().find('.priceQuote').html($('.rental_qty').val()+'X ('+data.price + ' ' + data.message+') ='+data.totalPrice);
									if(isCatalog){
										$selfID.parent().find('.inCart').show();
										$selfID.parent().find('.inCart').button();

									}
								} else if (data.success == 'not_supported') {
									$selfID.parent().find('.priceQuote').html(data.price);
								} else {
									alert('<?php echo sysLanguage::get('PPR_NOTICE_RESERVATION_NOT_AVAILABLE'); ?>.');
								}

							}
						});
					}
					//reset selected td;
				}

			}
		});

		$selfID.find('.end_time').live('change', function(){
			if($selfID.find('.start_date').val() != '' && $selfID.find('.end_date').val() != ''){
				var $this = $selfID.find('.datePicker');

				showAjaxLoader($this, 'xlarge');
				$.ajax({
					cache: false,
					dataType: 'json',
					type: 'post',
					url: <?php echo $checkRes;?>,
					data: $selfID.parent().find('.reservationTable *, .pprButttons *').serialize(),
					success: function (data) {
						if (data.success == true) {
							removeAjaxLoader($this);
							$selfID.find('.shippingTable').html(data.htmlShipping);
							if($('#googleAddress').size() > 0){
								$selfID.find('.shipSelect').hide();
							}
							if ($selfID.find('input[name=rental_shipping]').size() > 0 && $selfID.find('input[name=rental_shipping]:checked').size() == 0) {
								$selfID.find('input[name=rental_shipping]').each(function () {
									$(this).trigger('click');
								});
							}
							$selfID.parent().find('.priceQuote').html($('.rental_qty').val()+'X ('+data.price + ' ' + data.message+') ='+data.totalPrice);
							if(isCatalog){
								$selfID.parent().find('.inCart').show();
								$selfID.parent().find('.inCart').button();

							}
						} else if (data.success == 'not_supported') {
							$selfID.parent().find('.priceQuote').html(data.price);
						} else {
							alert('<?php echo sysLanguage::get('PPR_NOTICE_RESERVATION_NOT_AVAILABLE'); ?>.');
						}

					}
				});
			}
		});
		$selfID.find('.start_time').live('change', function(){
			if($selfID.find('.start_date').val() != '' && $selfID.find('.end_date').val() != ''){
				var $this = $selfID.find('.datePicker');

				showAjaxLoader($this, 'xlarge');
				$.ajax({
					cache: false,
					dataType: 'json',
					type: 'post',
					url: <?php echo $checkRes;?>,
					data: $selfID.parent().find('.reservationTable *, .pprButttons *').serialize(),
					success: function (data) {
						if (data.success == true) {
							removeAjaxLoader($this);
							$selfID.find('.shippingTable').html(data.htmlShipping);
							if($('#googleAddress').size() > 0){
								$selfID.find('.shipSelect').hide();
							}
							if ($selfID.find('input[name=rental_shipping]').size() > 0 && $selfID.find('input[name=rental_shipping]:checked').size() == 0) {
								$selfID.find('input[name=rental_shipping]').each(function () {
									$(this).trigger('click');
								});
							}
							$selfID.parent().find('.priceQuote').html($('.rental_qty').val()+'X ('+data.price + ' ' + data.message+') ='+data.totalPrice);
							if(isCatalog){
								$selfID.parent().find('.inCart').show();
								$selfID.parent().find('.inCart').button();

							}
						} else if (data.success == 'not_supported') {
							$selfID.parent().find('.priceQuote').html(data.price);
						} else {
							alert('<?php echo sysLanguage::get('PPR_NOTICE_RESERVATION_NOT_AVAILABLE'); ?>.');
						}

					}
				});
			}
		});

        $selfID.find('.semRow').hide();

        $selfID.find('input[name="cal_or_semester"]').change(function(){
			if($(this).val() == '1'){
				$selfID.find('.dateRow').show();
				$selfID.find('.semRow').hide();
				$selfID.find('.dateSelectedCalendar').show();
				$selfID.find('.selected_period').attr('name','sem');
			}else{
				$selfID.find('.dateRow').hide();
				$selfID.find('.dateSelectedCalendar').hide();
				$selfID.find('.semRow').show();
				$selfID.find('.selected_period').attr('name','semester_name');
			}
        });



		if($('.pricingTable table tr').size() == 0){

			$selfID.find('.shippingInfoDiv').hide();
			$selfID.find('.iscal').hide();
			$selfID.find('.iscal').prev().hide();
			$selfID.find('.issem').trigger('click');
			$selfID.find('input[name="cal_or_semester"]').trigger('change');

			if($selfID.find('.selected_period option').size() == 2){
				$selfID.find('.selected_period').attr('disabled','disabled');
			}

			$selfID.find('.selected_period option:selected').removeAttr('selected');
			$selfID.find('.selected_period option').each(function(){
				if($(this).val() != ''){
					$(this).attr('selected', 'selected');
				}
			});
			$selfID.find('.selected_period').trigger('change');
		}

		$selfID.find('.calendarTime').hide();
		if($('#googleAddress').size() > 0){
			$selfID.find('.shipSelect').hide();
		}
		<?php
   		if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_UPS_RESERVATION') == 'True' && sysConfig::get('EXTENSION_PAY_PER_RENTALS_CHECK_GOOGLE_ZONES_BEFORE') == 'False') {
			?>
			var $calLoader2 = $selfID.find('.datePicker');

			$('#getQuotes').click(function(){
			 showAjaxLoader($('#getQuotes'), 'xlarge');
			 $('#shipMethods').hide();
			 $.ajax({
					cache:false,
					url: <?php echo $upsQuotes;?>,
					type: 'post',
					data: 'rental_qty='+$selfID.find('.rental_qty').val()+'&street_address='+$('#street_address').val() + '&state='+$('#state').val() +'&city='+$('#city').val() +'&postcode1='+$('#postcode1').val() +'&postcode2='+$('#postcode2').val() +'&country='+$('#countryDrop').val() + '&iszip=' + $('#zipAddress').is(":visible"),
					dataType: 'json',
					success: function (data) {

						removeAjaxLoader($('#getQuotes'));
						if(data.success == true){
							if (data.nr == 0){
								$('#zipAddress').hide();
								$('#fullAddress').show();
								removeAjaxLoader($calLoader2);
								showAjaxLoader($calLoader2,'noloader');
								$calLoader2.datepick('option', 'initStatus', '');
							} else{
								$('#shipMethods').show();
								$('#rowquotes').html(data.html);
								$('#zipAddress').show();
								$('#fullAddress').hide();
								$calLoader2.datepick('option', 'initStatus', 'Please select a start date');
								removeAjaxLoader($calLoader2);
							}
						}
						//foreach data.quotesid make them visible
						//the same for data.quotescosts
					}
			 });
		});

		$('#countryDrop').change(function (){
			var $stateColumn = $('#stateCol');
			//showAjaxLoader($stateColumn);
			$.ajax({
				cache: true,
				url: <?php echo $countryZones;?>,
				data: 'cID=' + $(this).val(),
				dataType: 'html',
				success: function (data){
					//removeAjaxLoader($stateColumn);
					$('#stateCol').html(data);
				}
			})
		});
		$(window).load(function () {
			showAjaxLoader($calLoader2, 'noloader');
		});

		$('#countryDrop').val('223').trigger('change');
		$('#fullAddress').hide();
		$('#shipMethods').hide();
		$('#zipAddress').show();

		<?php

		}
		?>

		$('.start_date, .end_date, .start_time, .end_time').live('change',function(){
			var dd1 = new Date($('.dateSelectedCalendar').find('.start_date').val());
			var dd2 = new Date($('.dateSelectedCalendar').find('.end_date').val());

			if(dd1 == 'Invalid Date' || dd2 == 'Invalid Date'){
				$('.dateQuotes').html('');
			}else{
				var dd1_day = '';
				var dd2_day = '';
				if(dd1.getDate() < 10){
					dd1_day = '0'+dd1.getDate();
				}else{
					dd1_day = dd1.getDate();
				}
				if(dd2.getDate() < 10){
					dd2_day = '0'+dd2.getDate();
				}else{
					dd2_day = dd2.getDate();
				}
				var hours1 = '';
				var hours2 = '';
				if($('.dateSelectedCalendar').find('.start_time')){
					hours1 = $('.dateSelectedCalendar').find('.start_time').val();
				}
				if($('.dateSelectedCalendar').find('.end_time')){
					hours2 = $('.dateSelectedCalendar').find('.end_time').val();
				}
				//$('.dateQuotes').html(m_names[dd1.getMonth()] + ' ' +dd1_day + ', ' +dd1.getFullYear() + ' '+hours1 +'&nbsp;&nbsp;&nbsp;To&nbsp;&nbsp;&nbsp;' + m_names[dd2.getMonth()] + ' ' +dd2_day + ', ' +dd2.getFullYear() + ' '+ hours2);
				$('.dateQuotes').html(m_names[dd1.getMonth()] + ' ' +dd1_day + ', ' +dd1.getFullYear() +'&nbsp;&nbsp;&nbsp;To&nbsp;&nbsp;&nbsp;' + m_names[dd2.getMonth()] + ' ' +dd2_day + ', ' +dd2.getFullYear());
			}
		});
		var $priceText = $('<div class="priceText"><?php echo sysLanguage::get('TEXT_DATES_PRICE');?> </div>');

		$('.priceQuote').wrap('<div class="divPriceWrap"></div>');
		var $divPrice = $('.divPriceWrap');
		$divPrice.addClass('ui-widget ui-widget-content ui-corner-all ui-state-error');
		$priceText.insertBefore($divPrice);
		$('.divPriceWrap, .priceText').wrapAll('<div class="wrapPrice"></div>');

		var $dateText = $('<div class="dateText"><?php echo sysLanguage::get('TEXT_DATES_SELECTED');?> </div>');
		var $divDate = $('<div class="dateQuotesWrap"></div>');
		var $dateQuotes = $('<span class="dateQuotes"></span>');
		$divDate.addClass('ui-widget ui-widget-content ui-corner-all ui-state-error');
		$divDate.append($dateQuotes);
		$divDate.insertBefore($('.wrapPrice'));
		$dateText.insertBefore($('.dateQuotesWrap'));
		$('.dateText, .dateQuotesWrap').wrapAll('<div class="wrapDates"></div>');
		<?php if ($App->getEnv() == 'catalog'){?>
		$('.shippingDiv').css('top','-'+($('.periodsInsurance').height()+12)+'px');
		<?php }else{?>
			//$('.shippingDiv').css('top','-'+($('.periodsInsurance').height()+12)+'px');
		<?php }?>
		$('.dateRow').css('top','-'+($('.periodsInsurance').height()+12-30-$('.maxPeriod').height()-$('.minPeriod').height())+'px');
		$('.pprButttons').css('top','-'+($('.periodsInsurance').height()+12-45)+'px');
		$('.pprButttons_wrapper').css('height',(100)+'px');
		$('.pprButttons').css('height',(100)+'px');
		$('.pprButttons_wrapper').css('width','100%');
		$('.pprButttons_wrapper').css('margin-bottom','50px');
		//if(isCatalog){
			//$('.refreshCal').css('top',($('.pprButttons').position().top + 162)+'px');
		/*} else{
		$('.refreshCal').css('top',($('.pprButttons').position().top + 115)+'px');
		}*/
		var firstColumnWidth = $('.calendarTable').width()*7/100;
		var secColumnWidth = $('.calendarTable').width()*80/100;

		$('.pprButttons').css('left',firstColumnWidth+'px');
		//$('.refreshCal').css('left',(1.4*firstColumnWidth)+'px');
		$('.calendarTable').css('padding-bottom', '40px');
		$('.hasOverride').live('change', function(){
			    var isChecked = $(this).is(':checked');
				var $calLoader = $selfID.find('.datePicker');
				showAjaxLoader($calLoader, 'xlarge');
				$.ajax({
					cache: false,
					dataType: 'json',
					type: 'post',
					url: <?php echo $callLink; ?>,
					data: 'action=<?php echo $callAction;?>&' + $selfID.parent().find('.reservationTable *, .ui-widget-footer-box *, .pprButttons *').serialize(),
					success: function (data) {
						if (data.success == true) {
							removeAjaxLoader($calLoader);
							$selfID.parent().html(data.calendar);

							$('#getQuotes').button();
							$selfID.parent().find('.inCart').hide();
							$selfID.parent().find('.start_date').val('');
							$selfID.parent().find('.end_date').val('');
							$calLoader.trigger('EventAfterLoadedCalendar');
						}
					}
				});
		});
	});
	</script>
	<style>
		.ui-datepicker-group {
			margin: .2em;
		}

		.ui-datepicker-header {
			padding: 0;
			text-align: center;
		}

		.ui-datepicker-header span {
			margin: .5em;
		}

		.ui-datepicker .ui-datepicker-prev, .ui-datepicker .ui-datepicker-next {
			top: 0px;
		}

		.fc-event-time {
			display: none !important;
		}

		.fc-event {
			width: 460px !important;
		}

		.ui-datepicker-status {
			margin: .5em;
			text-align: center;
			font-weight: bold;
		}

		.fc-minor {

		}

		.dateRow{
			position:relative;
		}

		.pricingTable{
			float:right;
			font-size:13px;
			width:25%;
		}

		.minPeriod{
			width:100%;
			font-size:13px;
			margin-bottom:5px;
		}
		.maxPeriod{
			width:100%;
			font-size:13px;
			margin-bottom:5px;
		}

		.pprButttons_wrapper{
			position:relative;
			display: inline-block;
		}

		.pprButttons{
			position:absolute;
		}


		.insurancePrice{
			width:100%;
			font-size:13px;
			margin-bottom:10px
		}

		.quantityDiv div{
			width:25%;
			font-size:13px;
		}
		.shippingInfoDiv{
			float:left;
			width:25%;
			font-size:11px;
		}
		<?php if ($App->getEnv() == 'catalog'){?>
		.shippingDiv{
			width:50%;
			position:relative;
			left:-4%;
			font-size:11px;
			display: inline-block;

		}
	<?php }else{?>
	.shippingDiv{
		width:50%;
		position:relative;
		top:4%;
		left:25%;
		font-size:11px;
		display: inline-block;

	}
	<?php }?>
		.estimatedPricing{
			display:none;
		}
		.divPriceWrap, .dateQuotesWrap{
		    min-height: 25px;
			height:auto !important;
			height: 25px;
			border:0;
			width:350px;
			font-size:11px !important;
			padding:0 !important;
			line-height:25px;
			display:inline-block;
			text-align: center;
			margin-top:10px !important;
		}
		.datesInputs{
			display:none;
		}
		.refreshCal_wrapper{
			position:relative;
		}
		.refreshCal{
			position:absolute;
			top:120px;
			left:20%;
		}
		.priceText{
			display:inline-block;
			line-height:50px;vertical-align:top;
			margin-right:10px;
		}
		.wrapPrice{
			text-align:right;
			padding-left:31px;
		}
		.dateText{
			display:inline-block;
			line-height:50px;vertical-align:top;
			margin-right:10px;
		}
		.wrapDates{
			text-align:right;
		}
		
		.ui-datepicker-current label{
			display:none;
		}
		.ui-datepicker-current{
			line-height:18px;
			height:20px;
		}
		.datePicker{
			padding-left:0px;
		}

		.calendarTime {
			width: 540px;
		}

		.fc-agenda-body td.ui-state-default {
			cursor: pointer;
		}

		.datePicker {
		}

		.inCart{
			position:relative;
			top:26px;
			left:50%;
		}

		.ui-datepicker {
			display: block;
		}

		.ui-datepicker-shipping-day-hover, .ui-datepicker-shipping-day-hover-info {
			background: #F7C8D3;
		}
		.ui-datepicker-start_date_info{
			background: #3a6036;
		}

		.datePicker .ui-state-active {
			background: #CACEE6;
		}
	</style>
	<div id="reserv<?php echo $pID_string[0]; ?>" class="reservationTable">
		<div class="quantityDiv">
            <div>
	            <?php echo sysLanguage::get('ENTRY_QUANTITY');?><input type="text" size="3" class="rental_qty" name="rental_qty" value="<?php echo $rQty;?>">
            </div>
        </div>
		<?php
		if($App->getEnv() == 'catalog'){
			if(sysConfig::get('EXTENSION_PAY_PER_RENTALS_INSURE_ALL_PRODUCTS_AUTO') == 'True'){
			?>
			<input type="hidden" class="hasInsurance" name="hasInsurance" value="1">
				<?php
			}
		}else{
		?>
		<div class="insuranceDiv">
            <div>
	            <?php echo sysLanguage::get('ENTRY_INSURANCE');?><input type="checkbox" class="hasInsurance" name="hasInsurance" value="1">
            </div>
        </div>
		<?php
		}
        ?>
		<?php
		if($App->getEnv() == 'admin'){
		?>
			<div class="overrideInventory">
				<div>
					<?php echo sysLanguage::get('ENTRY_OVERRIDE');?><input type="checkbox" class="hasOverride" name="hasOverride" value="1" <?php echo ($isChecked == true?'checked="checked"':'')?>>
				</div>
			</div>
		<?php
		}
		?>
		<div class="shippingInfoDiv">
			<div colspan="2">
				<table cellpadding="0" cellspacing="3" border="0" width="100%">
				<tr>
				<td style="width:10px;height:10px;" class="ui-datepicker-reserved ui-state-disabled">&nbsp;</td>
				<td style="font-size:.8em"> - <?php echo sysLanguage::get('PPR_INFO_UNAVAILABLE_DAYS')?></td>
				</tr>
			    <tr>
				<td style="width:10px;height:10px;" class="ui-datepicker-start_date_info">&nbsp;</td>
				<td style="font-size:.8em"> - <?php echo sysLanguage::get('PPR_INFO_SELECTED_DAYS')?></td>
			    </tr>

			<?php if ($purchaseTypeClass->shippingIsNone() === false && $purchaseTypeClass->shippingIsStore() === false){ ?>
				<tr>
				<td style="width:10px;height:10px;" class="ui-datepicker-shipping-day-hover-info">&nbsp;</td>
				<td style="font-size:.8em"> - <?php echo sysLanguage::get('PPR_INFO_SHIPPING_DAYS')?></td>
		        </tr>
			<?php } ?>

                </table>
			</div>
		</div>
     <?php
     if ($purchaseTypeClass->shippingIsNone() === false && $purchaseTypeClass->shippingIsStore() === false){
	  ?>
		<div class="shippingDiv"><div>
	<?php
     	echo $shippingTable;
	?>
		</div></div>
	<?php
     }
     ?>
	 <div class="semestersDiv">
		 <div>
            <?php
		    	echo $purchaseTypeClass->buildSemesters($semDates);
	        ?>
		 </div>
	 </div>
	 <br style="clear:both"/>

	 <div class="dateRow">
      <div><table cellpadding="3" cellspacing="0" border="0" width="100%">
       <tr>
        <td valign="top"><div class="datePicker"></div>
		<div class="calendarTime">

		</div>

		</td>
       </tr>
      </table></div>
     </div>

     <div class="dateSelectedCalendar">

      <div class="datesInputs"><?php echo sysLanguage::get('ENTRY_RENTAL_DATES_SELECTED');?>
      <input type="text" name="start_date" class="start_date" value="<?php echo (isset($rInfo) ? $rInfo['reservationInfo']['start_date'] : '');?>" readonly="readonly">
      <?php echo sysLanguage::get('PAYPERRENTALS_TO');?>
		<input type="text" name="end_date" class="end_date" value="<?php echo (isset($rInfo) ? $rInfo['reservationInfo']['end_date'] : '');?>" readonly="readonly">
		<input type="hidden" name="days_before" class="days_before" value="<?php echo (isset($rInfo['reservationInfo']['days_before']) ? $rInfo['reservationInfo']['days_before'] : '');?>"> <input type="hidden" name="days_after" class="days_after" value="<?php echo (isset($rInfo['reservationInfo']['days_after']) ? $rInfo['reservationInfo']['days_after'] : '');?>">
	  </div>
     <?php
		if(sysConfig::get('EXTENSION_PAY_PER_RENTALS_ENABLE_TIME_DROPDOWN') == 'True'){
			if(sysConfig::get('EXTENSION_PAY_PER_RENTALS_ENABLE_TIME_FEES') == 'True' && Session::exists('current_store_id')){
				?>
					 <div class="timesInputs"><?php echo sysLanguage::get('ENTRY_RENTAL_TIMES_SELECTED');?>
					<?php
				$multiStore = $appExtension->getExtension('multiStore');
				if ($multiStore !== false && $multiStore->isEnabled() === true){
					$QTimeFees = Doctrine_Query::create()
							->from('StoresTimeFees')
							->where('stores_id = ?', Session::get('current_store_id'))
							->orderBy('timefees_id')
							->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
				}else{
					$QTimeFees = Doctrine_Query::create()
							->from('PayPerRentalTimeFees')
							->orderBy('timefees_id')
							->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
				}
				$hourStart = htmlBase::newElement('selectbox')
						->setName('start_time')
						->addClass('start_time');
				$hourEnd = htmlBase::newElement('selectbox')
						->setName('end_time')
						->addClass('end_time');
				foreach($QTimeFees as $timeFee){
					if(!empty($timeFee['timefees_name']) && $timeFee['timefees_name'] != 'not applicable' && $timeFee['timefees_name'] != 'not available'){
						//echo '<b>'.$timeFee['timefees_name'].' - Cost: '.$currencies->format($timeFee['timefees_fee']).''.'</b><br/>';
						$hourStart->addOption($timeFee['timefees_start'].':00:00',$timeFee['timefees_name'].' - Cost: '.$currencies->format($timeFee['timefees_fee']));
						$hourEnd->addOption($timeFee['timefees_start'].':00:00',$timeFee['timefees_name'].' - Cost: '.$currencies->format($timeFee['timefees_fee']));
					}
				}
						 echo sysLanguage::get('EXTENSION_PAY_PER_RENTALS_START_TIME'). $hourStart->draw();
						 echo '&nbsp;&nbsp;&nbsp;&nbsp;'.sysLanguage::get('EXTENSION_PAY_PER_RENTALS_END_TIME').$hourEnd->draw();
						 ?>
			</div>
					<?php
			}else{
		?>
	  <div class="timesInputs"><?php echo sysLanguage::get('ENTRY_RENTAL_TIMES_SELECTED');?>
          <?php
		      $starttimeVal = (int) sysConfig::get('EXTENSION_PAY_PER_RENTALS_START_TIME');
		      $endtimeVal = (int) sysConfig::get('EXTENSION_PAY_PER_RENTALS_END_TIME');

		      if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_TIME_INCREMENT') == '1/2'){
			      $time_increment = 30;
		      }else{
			      $time_increment = 60;
		      }

		      $hourStart = htmlBase::newElement('selectbox')
			  ->setName('start_time')
		      ->addClass('start_time');
		      $hourEnd = htmlBase::newElement('selectbox')
			  ->setName('end_time')
			  ->addClass('end_time');

		      for($i=$starttimeVal;$i<=$endtimeVal;$i++){
				  $notAddStart = false;
				  $notAddEnd = false;
				  if(in_array($i, $toRemoveStart)){
					  $notAddStart = true;
				  }
				  if(in_array($i, $toRemoveStart)){
					  $notAddEnd = true;
				  }
			    if((int)$i < 10){
			        $val = '0'.$i.':00:00';
					if($i > 12){
						$am = ' PM';
						$j = $i % 12;
					}elseif($i == 12){
						$am = 'PM';
						$j = '12';
					}
					else{
						$am = ' AM';
						$j = $i;
					}
				    $myVal = '0'.$j.':00'. $am;
			    }else{
				    $val = $i.':00:00';
					if($i > 12){
						$am = ' PM';
						$j = $i % 12;
					}elseif($i == 12){
						$am = ' PM';
						$j = '12';
					}
					else{
						$am = ' AM';
						$j = $i;
					}
				    $myVal = $j.':00'.$am;
			    }
				if(!$notAddStart){
			    $hourStart->addOption($val, $myVal);
				}
				if(!$notAddEnd){
			    $hourEnd->addOption($val, $myVal);
				}

			    if($time_increment == 30 && $i<$endtimeVal){
				    if((int)$i < 10){
					    $val = '0'.$i.':30:00';
					    $myVal = '0'.$i.':30';
				    }else{
					    $val = $i.':30:00';
					    $myVal = $i.':30';
				    }

				    $hourStart->addOption($val, $myVal);
				    $hourEnd->addOption($val, $myVal);
			    }


		      }
			echo sysLanguage::get('EXTENSION_PAY_PER_RENTALS_START_TIME_DELIVERY'). $hourStart->draw();
		  	echo '&nbsp;&nbsp;&nbsp;&nbsp;'.sysLanguage::get('EXTENSION_PAY_PER_RENTALS_END_TIME_PICKUP').$hourEnd->draw();
	 ?>
	  </div>
     <?php
	      }
	      }
	      ?>

	     <div class="refreshCal_wrapper">
	  <?php
        echo htmlBase::newElement('button')
             ->addClass('refreshCal')
             ->setName('refreshCal')
             ->setText(sysLanguage::get('PPR_CALENDAR_RESET'))
             ->draw();
      ?>
	     </div>
     </div>
	</div>
		<div class="pprButttons_wrapper">
	<div class="pprButttons">
			<?php
	   $pprButtons = '<span class="estimatedPricing">' . sysLanguage::get('TEXT_ESTIMATED_PRICING') . '</span>' . '<span class="priceQuote"></span>'.'&nbsp;&nbsp;&nbsp;';

	   foreach($pID_string as $nr => $pElem){
	        $pprButtons .= '<input type="hidden" name="products_id[]" class="pID" value="' . $pElem . '">';
	   }

	   foreach($usableBarcodes as $bElem){
	        $pprButtons .= '<input type="hidden" name="barcode" value="' . $bElem . '">';
	   }

	   $pprButtons .= $purchaseTypeClass->getHiddenFields();
	   if($hasButton){
		   if(sysConfig::get('EXTENSION_PAY_PER_RENTAL_ALLOW_MEMBERSHIP') == 'False'){
		   $pprButtons .= htmlBase::newElement('div')
		   ->addClass('inCart')
		   ->css(array(
			   'display'   => 'inline-block',
			   'width' => '150px'
		   ))
		   ->html(sysLanguage::get('TEXT_BUTTON_IN_CART'))
		   ->draw();
		   }else{
		   		$pprButtons .= htmlBase::newElement('div')
			  	->addClass('inCart inQueue')
			   	->css(array(
				   'display'   => 'inline-block',
				   'width' => '150px'
			   	))
			   	->html(sysLanguage::get('TEXT_BUTTON_IN_QUEUE'))
			   	->draw();
		   }
	   }

		echo $pprButtons;
			?>
		</div>
		</div>
			<?php
   		$calendar = ob_get_contents();
		ob_end_clean();
		//ob_end_flush();
		return $calendar;
	}

	public static function getMaxShippingDays($productId, $start, $allowOverbooking = false){

		$maxDays = 0;
		if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_ALLOW_OVERBOOKING') == 'False' && $allowOverbooking === false){

			$Qcheck = Doctrine_Query::create()
			->select('MAX(shipping_days_before) as max_before, MAX(shipping_days_after) as max_after')
			->from('OrdersProductsReservation opr')
			->leftJoin('opr.ProductsInventoryBarcodes ib')
			->leftJoin('ib.ProductsInventory i')
			->where('i.products_id = ?', $productId)
			->andWhereIn('opr.rental_state', array('reserved', 'out'))
			->andWhere('opr.parent_id IS NULL')
			->andWhere('DATE_ADD(end_date, INTERVAL shipping_days_after DAY) >= ?', $start);

			EventManager::notify('OrdersProductsReservationListingBeforeExecute', &$Qcheck);

			$Qcheck = $Qcheck->execute(array(), Doctrine_Core::HYDRATE_ARRAY);

			if($Qcheck[0]['max_before'] > $Qcheck[0]['max_after']){
				$maxDays = $Qcheck[0]['max_before'];
			}else{
				$maxDays = $Qcheck[0]['max_after'];
			}
		}
		return $maxDays;
	}

	public static function getMyReservations($productId, $start, $allowOverbooking = false, $usableBarcodes = array()){

		$reservArr = array();
		if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_ALLOW_OVERBOOKING') == 'False' && $allowOverbooking === false){

			$Qcheck = Doctrine_Query::create()
			->from('OrdersProductsReservation opr')
			->leftJoin('opr.ProductsInventoryBarcodes ib')
			->leftJoin('ib.ProductsInventory i')
			->where('i.products_id = ?', $productId)
			->andWhereIn('opr.rental_state', array('reserved', 'out'))
			->andWhere('DATE_ADD(end_date, INTERVAL shipping_days_after DAY) >= ?', $start);

			if(count($usableBarcodes) > 0){
				$Qcheck->andWhereIn('ib.barcode_id', $usableBarcodes);
			}

			EventManager::notify('OrdersProductsReservationListingBeforeExecuteUtilities', &$Qcheck);

			$Qcheck = $Qcheck->execute(array(), Doctrine_Core::HYDRATE_ARRAY);

			foreach($Qcheck as $iReservation){
					$reservationArr = array();

					$startDateArr = date_parse($iReservation['start_date']);
					$endDateArr = date_parse($iReservation['end_date']);

					$startTime = mktime($startDateArr['hour'],$startDateArr['minute'],$startDateArr['second'],$startDateArr['month'],$startDateArr['day']-$iReservation['shipping_days_before'],$startDateArr['year']);
					$endTime = mktime($endDateArr['hour'],$endDateArr['minute'],$endDateArr['second'],$endDateArr['month'],$endDateArr['day']+$iReservation['shipping_days_after'],$endDateArr['year']);

					$dateStart = date('Y-n-j', $startTime);
					$timeStart = date('G:i', $startTime);

					$dateEnd = date('Y-n-j', $endTime);
					$timeEnd = date('G:i', $endTime);

					if($timeStart == '0:00' || (sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_FULL_DAYS') == 'True')){
						$reservationArr['start'] = $dateStart;
					}else{
						$reservationArr['start_time'] = $timeStart;
						$reservationArr['start_date'] = $dateStart;
						$reservationArr['end_time'] = '23:59';
						$reservationArr['end_date'] = $dateStart;
						$nextStartTime = strtotime('+1 day', strtotime($dateStart));
						$prevEndTime = strtotime('-1 day', strtotime($dateEnd));
						if( $nextStartTime <= $prevEndTime){
							$reservationArr['start'] = date('Y-n-j', $nextStartTime);
						}
					}

					if($timeEnd == '0:00' || (sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_FULL_DAYS') == 'True')){
						$reservationArr['end'] = $dateEnd;
					}else{
						if(!isset($reservationArr['start_time'])){
							$reservationArr['start_time'] = '0:00';
						}
						$reservationArr['start_date'] = $dateEnd;
						$reservationArr['end_time'] = $timeEnd;
						$reservationArr['end_date'] = $dateEnd;
						$nextStartTime = strtotime('+1 day', strtotime($dateStart));
						$prevEndTime = strtotime('-1 day', strtotime($dateEnd));
						if( $nextStartTime <= $prevEndTime){
							$reservationArr['end'] = date('Y-n-j', $prevEndTime);
						}
					}

				    $reservationArr['barcode'] = $iReservation['barcode_id'];//if barcode_id is null or 0 this means is quantity and check will be made with the total qty at some point.
					$reservationArr['qty'] = 1;

					$reservArr[] = $reservationArr;
			}
		}

		return $reservArr;
	}

	public static function CheckBooking($settings){
		$returnVal = 0;
		if(isset($settings['start_date']) && isset($settings['end_date'])){
			$Qcheck = Doctrine_Query::create();

			if ($settings['item_type'] == 'barcode'){
				$Qcheck->select('barcode_id');
			}else{
				$Qcheck->select('quantity_id');
			}

			$Qcheck->from('OrdersProductsReservation');

			if ($settings['item_type'] == 'barcode'){
				$Qcheck->where('barcode_id = ?', $settings['item_id']);
			}else{
				$Qcheck->where('quantity_id = ?', $settings['item_id']);
			}

			$Qcheck->andWhere('
					(
						(
							(CAST("' . date('Y-m-d H:i:s', $settings['start_date']) . '" as DATETIME)
								between
									DATE_SUB(CAST(start_date as DATETIME), INTERVAL shipping_days_before DAY)
										AND
									DATE_ADD(CAST(end_date as DATETIME), INTERVAL shipping_days_after DAY)
							)
						AND TRUE)
								OR
						(
							(CAST("' . date('Y-m-d H:i:s', $settings['end_date']) . '" as DATETIME)
								between
									DATE_SUB(CAST(start_date as DATETIME), INTERVAL shipping_days_before DAY)
										AND
									DATE_ADD(CAST(end_date as DATETIME), INTERVAL shipping_days_after DAY)
							)
						AND TRUE)
								OR
						(
							(
							CAST("' . date('Y-m-d H:i:s', $settings['start_date']) . '" as DATETIME) <= DATE_SUB(CAST(start_date as DATETIME), INTERVAL shipping_days_before DAY)
								AND
							CAST("' . date('Y-m-d H:i:s', $settings['end_date']) . '" as DATETIME) >= DATE_ADD(CAST(end_date as DATETIME), INTERVAL shipping_days_after DAY)
							)
						AND TRUE)
					AND TRUE)
				AND TRUE');

			if ($settings['item_type'] == 'barcode'){
				$Qcheck->andWhere('(rental_state = "reserved" or rental_state = "out")');
			}else{
				$Qcheck->andWhere('rental_state = ?', 'out');
			}
			//echo 'ddd'. $Qcheck->getSqlQuery();
			EventManager::notify('ReservationCheckQueryBeforeExecute', &$Qcheck, $settings);

			$Result = $Qcheck->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
			$returnVal = ($Result ? sizeof($Result) : 0);

			EventManager::notify('ReservationCheckQueryAfterExecute', &$Result, $settings, &$returnVal);
		}
		return $returnVal;
	}

	public static function returnReservation($bID, $status, $comment, $lost, $broken){
		global $appExtension,$currencies, $messageStack;
        $consumption = false;
        $subtotal = 0;
        $total = 0;

		$Qcheck = Doctrine_Query::create()
		->select('orders_products_id')
		->from('OrdersProductsReservation')
		->where('orders_products_reservations_id = ?', $bID)
		->execute(array(), Doctrine_Core::HYDRATE_ARRAY);

		if (isset($Qcheck[0]['orders_products_id']) && is_null($Qcheck[0]['orders_products_id']) === false){
			$ReservationQuery = Doctrine_Query::create()
			->from('Orders o')
            ->leftJoin('o.OrdersTotal ot')
			->leftJoin('o.Customers c')
			->leftJoin('o.OrdersAddresses oa')
			->leftJoin('o.OrdersProducts op')
			->leftJoin('op.OrdersProductsReservation opr')
			->where('opr.orders_products_reservations_id = ?', $bID)
			->andWhere('oa.address_type = ?', 'customer')
			->andWhere('parent_id IS NULL');
		}else{
			$ReservationQuery = Doctrine_Query::create()
			->from('OrdersProductsReservation opr')
			->where('opr.orders_products_reservations_id = ?', $bID);
		}
		
		$ReservationQuery->leftJoin('opr.ProductsInventoryBarcodes ib')
		->leftJoin('ib.ProductsInventory ibi')
		->leftJoin('opr.ProductsInventoryQuantity iq')
		->leftJoin('iq.ProductsInventory iqi');
		
		if ($appExtension->isInstalled('inventoryCenters') && $appExtension->isEnabled('inventoryCenters')){
			$extInventoryCenters = $appExtension->getExtension('inventoryCenters');
			if ($extInventoryCenters->stockMethod == 'Store'){
				$ReservationQuery->leftJoin('ib.ProductsInventoryBarcodesToStores b2s')
				->leftJoin('b2s.Stores');
			}else{
				$ReservationQuery->leftJoin('ib.ProductsInventoryBarcodesToInventoryCenters b2c')
				->leftJoin('b2c.ProductsInventoryCenters');
			}
		}
		
		$Reservation = $ReservationQuery->execute();
		foreach($Reservation as $oInfo){
			if (isset($oInfo->OrdersProducts)){
				$Products = $oInfo->OrdersProducts;
				$sendEmail = true;
			}else{
				$Products = $oInfo;
				$sendEmail = false;
			}
			foreach($Products as $pInfo){
				if (isset($pInfo->OrdersProductsReservation)){
                    $purchase_type = $pInfo['purchase_type'];
					$Reservations = $pInfo->OrdersProductsReservation;
				}else{
					$Reservations = array($pInfo);
				}
				foreach($Reservations as $oprInfo){
                    if(!class_exists('Product')){
                        require(sysConfig::getDirFsCatalog() . 'includes/classes/product.php');
                    }
					$product = new Product($pInfo->products_id);

                    $reservationId = $oprInfo->orders_products_reservations_id;
					$trackMethod = $oprInfo->track_method;
					$oprInfo->broken = $broken;
					//$oprInfo->lost = $lost;

                    if($purchase_type == 'reservation'){
                        $purchaseTypeClass = $product->getPurchaseType('reservation');
                        if($purchaseTypeClass->consumptionAllowed() === '1'){
                            $consumption = true;
                            if ($oprInfo->rental_state == 'out'){
                                $now = date('Y-m-d H:i:s');
                                $prices = $purchaseTypeClass->getReservationPrice($oprInfo->start_date,$now,$oprInfo,'', (sysConfig::get('EXTENSION_PAY_PER_RENTALS_INSURE_ALL_PRODUCTS_AUTO') == 'True'));
                                $oprInfo->end_date = $now;
                                $oprInfo->OrdersProducts->products_price = $prices['price'];
                                $oprInfo->OrdersProducts->final_price = $prices['totalPrice'];
                                $oprInfo->rental_state = 'returned';
                                $oprInfo->date_returned = $now;
                                $subtotal += $prices['price'];
                                $total += $prices['totalPrice'];
                                $oprInfo->ProductsInventoryBarcodes->status = $status;
                            }else{
                                $subtotal +=  $oprInfo->OrdersProducts->products_price;
                                $total += $oprInfo->OrdersProducts->final_price;
                            }
                        }
                    }

					if (!empty($comment)){
						if ($reservationId == 'barcode'){
							$oprInfo->ProductsInventoryBarcodes->ProductsInventoryBarcodesComments[]->comments = $comment;
						}elseif ($reservationId == 'quantity'){
							$oprInfo->ProductsInventoryQuantity->ProductsInventoryQuantitysComments[]->comments = $comment;
						}
					}

					if (isset($extInventoryCenters)){
						$invCenterChanged = false;
						if (isset($_POST['inventory_center'][$reservationId])){
							$invCenter = $_POST['inventory_center'][$reservationId];
							if ($trackMethod == 'barcode'){
								if ($extInventoryCenters->stockMethod == 'Store'){
									$Barcode = $oprInfo->ProductsInventoryBarcodes->ProductsInventoryBarcodesToStores;
									if ($Barcode->inventory_store_id != $invCenter){
										$Barcode->inventory_store_id = $invCenter;
										$invCenterChanged = true;
									}
								}else{
									$Barcode = $oprInfo->ProductsInventoryBarcodes->ProductsInventoryBarcodesToInventoryCenters;
									if ($Barcode->inventory_center_id != $invCenter){
										$Barcode->inventory_center_id = $invCenter;
										$invCenterChanged = true;
									}
								}
							}elseif ($trackMethod == 'quantity'){
								$Quantity = $oprInfo->ProductsInventoryQuantity;
								if ($extInventoryCenters->stockMethod == 'Store'){
									if ($Quantity->inventory_store_id != $invCenter){
										$Qupdate = Doctrine_Query::create()
										->update('ProductsInventoryQuantity')
										->where('inventory_store_id = ?', $invCenter)
										->andWhere('inventory_id = ?', $Quantity->inventory_id);
										if ($status == 'B' || $status == 'L'){
											$Qupdate->set('broken = broken+1');
										}else{
											$Qupdate->set('available = available+1');
										}
										$Qupdate->execute();
										$invCenterChanged = true;
									}
								}else{
									if ($Quantity->inventory_center_id != $invCenter){
										$Qupdate = Doctrine_Query::create()
										->update('ProductsInventoryQuantity')
										->where('inventory_center_id = ?', $invCenter)
										->andWhere('inventory_id = ?', $Quantity->inventory_id);
										if ($status == 'B' || $status == 'L'){
											$Qupdate->set('broken = broken+1');
										}else{
											$Qupdate->set('available = available+1');
										}
										$Qupdate->execute();
										$invCenterChanged = true;
									}
								}
							}
						}
					}else{
						if ($trackMethod == 'barcode'){
							$oprInfo->ProductsInventoryBarcodes->status = $status;
						}elseif ($trackMethod == 'quantity'){
							$oprInfo->ProductsInventoryQuantity->qty_out--;
							if ($status == 'B' || $status == 'L'){
								$oprInfo->ProductsInventoryQuantity->broken++;
							}else{
								$oprInfo->ProductsInventoryQuantity->available++;
							}
						}
					}

					if ($sendEmail === true){
						$emailEvent = new emailEvent('reservation_returned', $oInfo->Customers->language_id);
						if (date('Y-m-d h:i:s') > $oprInfo->end_date){
							$dateArr = date_parse($oprInfo->end_date);
							$days_late = (mktime(0, 0, 0) - mktime(0, 0, 0, $dateArr['month'], $dateArr['day'], $dateArr['year'])) / (60 * 60 * 24);
						}else{
							$days_late = 0;
						}
						$emailEvent->setVars(array(
							'days_late' => $days_late,
							'full_name' => $oInfo->OrdersAddresses['customer']->entry_name,
							'email_address' => $oInfo->customers_email_address,
							'rented_product' => $pInfo->products_name
						));
					    if(sysConfig::get('EXTENSION_PAY_PER_RENTALS_SEND_EMAIL_RETURN') == 'True'){
							$emailEvent->sendEmail(array(
								'email' => $oInfo->customers_email_address,
								'name' => $oInfo->OrdersAddresses['customer']->entry_name
							));
						}
					}
				}
			}

            if (isset($oInfo->OrdersTotal) and $consumption === True){
                $Totals =  $oInfo->OrdersTotal;
                foreach($Totals as $totInfo){
                    $tax = $total - $subtotal;
                    if($totInfo['module'] == 'subtotal'){
                        $totInfo['value'] = $subtotal;
                        $totInfo['text'] = $currencies->currencies[DEFAULT_CURRENCY]['symbol_left']. ' '.$subtotal;
                    }
                    if($totInfo['module'] == 'tax'){
                        $totInfo['value'] = $tax;
                        $totInfo['text'] = $currencies->currencies[DEFAULT_CURRENCY]['symbol_left']. ' '.$tax;
                    }
                    if($totInfo['module'] == 'total'){
                        $totInfo['value'] = $total;
                        $totInfo['text'] = $currencies->currencies[DEFAULT_CURRENCY]['symbol_left']. ' '.$total;
                    }
                }

            }
		}
		$Reservation->save();
	}
	public static function inventoryCenterAddon($hasHeaders, $hasGeographic = true, $showPickup = true, $showDropoff, $hasLP){
			global $appExtension;
			$invCentExt = $appExtension->getExtension('inventoryCenters');
			$pprform = htmlBase::newElement('div')
			->addClass('invCenter');
			if ($invCentExt !== false && $invCentExt->isEnabled() === true){
				$pickupt = htmlBase::newElement('p')
				->html(sysLanguage::get('TEXT_PICKUP_ZONE'))
				->addClass('pickp');
				$br = htmlBase::newElement('br');
				$pickup = htmlBase::newElement('selectbox')
				->setName('pickup')
				->addClass('myf pickupz changer');

				if($showPickup === false){
					$pickup->css(array(
						'display'   => 'none'
					));
				}

				$pickup->addOption('select',sysLanguage::get('TEXT_PLEASE_SELECT'));


				$dropofft = htmlBase::newElement('p')
				->html(sysLanguage::get('TEXT_DROPOFF_ZONE'))
				->addClass('pickp');

				$dropoff = htmlBase::newElement('selectbox')
				->setName('dropoff')
				->addClass('myg changer dropoffz');
				$dropoff->addOption('0', sysLanguage::get('TEXT_SAME_AS_ABOVE'));

				if($showDropoff === false){
					$dropoff->css(array(
						'display'   => 'none'
					));
				}

				$continentt = htmlBase::newElement('p')
				->html(sysLanguage::get('TEXT_CONTINENT'))
				->addClass('continent');
				$br = htmlBase::newElement('br');
				$continent = htmlBase::newElement('selectbox')
				->setName('continent')
				->addClass('changer continent');
				$continent->addOption('select',sysLanguage::get('TEXT_PLEASE_SELECT_ALL_CONTINENTS'));

				$countryt = htmlBase::newElement('p')
				->html(sysLanguage::get('TEXT_COUNTRY'))
				->addClass('country');
				$br = htmlBase::newElement('br');
				$country = htmlBase::newElement('selectbox')
				->setName('country')
				->addClass('changer country');
				$country->addOption('select',sysLanguage::get('TEXT_PLEASE_SELECT'));

				$statet = htmlBase::newElement('p')
				->html(sysLanguage::get('TEXT_STATE'))
				->addClass('state');
				$br = htmlBase::newElement('br');
				$state = htmlBase::newElement('selectbox')
				->setName('state')
				->addClass('changer state');
				$state->addOption('select',sysLanguage::get('TEXT_PLEASE_SELECT'));

				$cityt = htmlBase::newElement('p')
				->html(sysLanguage::get('TEXT_CITY'))
				->addClass('city');
				$br = htmlBase::newElement('br');
				$city = htmlBase::newElement('selectbox')
				->setName('city')
				->addClass('changer city');
				$city->addOption('select',sysLanguage::get('TEXT_PLEASE_SELECT'));


				$continentsArr = array();
				$countriesArr = array();
				$statesArr = array();
				$citiesArr = array();
				$countriesArrNames = array();

				$continentsArr[] = 'Africa';
				$continentsArr[] = 'Asia';
				$continentsArr[] = 'Australasia';
				$continentsArr[] = 'Caribbean Islands';
				$continentsArr[] = 'Central America';
				$continentsArr[] = 'Europe';
				$continentsArr[] = 'North America';
				$continentsArr[] = 'Pacific Islands';
				$continentsArr[] = 'South America';

				$Qinventory = Doctrine_Query::create()
				->select('p.*')
				->from('ProductsInventoryCenters p')
				->orderBy('p.inventory_center_sort_order');

				if (Session::exists('isppr_inventory_dropoff') && (Session::get('isppr_inventory_dropoff') != '')){
					$dropoff->selectOptionByValue(Session::get('isppr_inventory_dropoff'));
				}
				/*if (Session::exists('isppr_inventory_pickup') && (Session::get('isppr_inventory_pickup') != '')){
					$pickup->selectOptionByValue(Session::get('isppr_inventory_pickup'));
				}*/

				if (Session::exists('isppr_inventory_pickup') && (Session::get('isppr_inventory_pickup') != '')){
					$pickup->selectOptionByValue(Session::get('isppr_inventory_pickup'));
				}

				if($hasLP == true && sysConfig::get('EXTENSION_INVENTORY_CENTERS_USE_LP') == 'True' && Session::exists('isppr_inventory_lp')){
					$pickup->selectOptionByValue(Session::get('isppr_inventory_lp'));
				}
				if(Session::exists('isppr_continent') && (Session::get('isppr_continent') != '')){
					$continent->selectOptionByValue(Session::get('isppr_continent'));
					$QinventoryCountry = Doctrine_Query::create()
					->select('p.*')
					->from('ProductsInventoryCenters p')
					->where('p.inventory_center_continent=?', Session::get('isppr_continent'))
					->orderBy('p.inventory_center_country')
					->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
					if(count($QinventoryCountry) > 0){
						foreach($QinventoryCountry as $qcountry){
							$f = true;
							$multiStore = $appExtension->getExtension('multiStore');
							if ($multiStore !== false && $multiStore->isEnabled() === true){
								$storeArr = explode(';', $qcountry['inventory_center_stores']);

								if(in_array(Session::get('current_store_id'), $storeArr)){
									$f = true;
								}else{
									$f = false;
								}
							}

							if (!in_array($qcountry['inventory_center_country'], $countriesArr) && $qcountry['inventory_center_country'] != '' && $f) {
								$countriesArrNames[] = tep_get_country_name($qcountry['inventory_center_country']);
								$countriesArr[] = $qcountry['inventory_center_country'];
							}
						}
					}else{
						Session::set('isppr_country', '');
						Session::set('isppr_state', '');
						Session::set('isppr_city', '');

					}
				}
				if(Session::exists('isppr_country') && (Session::get('isppr_country') != '')){
					$country->selectOptionByValue(Session::get('isppr_country'));
					$QinventoryStates = Doctrine_Query::create()
					->select('p.*')
					->from('ProductsInventoryCenters p')
					->where('p.inventory_center_country=?', Session::get('isppr_country'))
					->orderBy('p.inventory_center_state')
					->execute(array(), Doctrine_Core::HYDRATE_ARRAY);

					if(count($QinventoryStates) > 0){
						foreach($QinventoryStates as $qstate){
							$f = true;
							$multiStore = $appExtension->getExtension('multiStore');
							if ($multiStore !== false && $multiStore->isEnabled() === true){
								$storeArr = explode(';', $qstate['inventory_center_stores']);

								if(in_array(Session::get('current_store_id'), $storeArr)){
									$f = true;
								}else{
									$f = false;
								}
							}
							if (!in_array($qstate['inventory_center_state'], $statesArr) && !empty($qstate['inventory_center_state']) && $f) {
								$statesArr[] = $qstate['inventory_center_state'];
							}
						}
					}else{
						Session::set('isppr_state', '');
						Session::set('isppr_city', '');
					}
				}
				if(Session::exists('isppr_state') && (Session::get('isppr_state') != '')){
					$state->selectOptionByValue(Session::get('isppr_state'));
					$QinventoryCity = Doctrine_Query::create()
					->select('p.*')
					->from('ProductsInventoryCenters p')
					->where('p.inventory_center_state=?', Session::get('isppr_state'))
					->orderBy('p.inventory_center_city')
					->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
					if(count($QinventoryCity) > 0){
						foreach($QinventoryCity as $qcity){
							$f = true;
							$multiStore = $appExtension->getExtension('multiStore');
							if ($multiStore !== false && $multiStore->isEnabled() === true){
								$storeArr = explode(';', $qcity['inventory_center_stores']);

								if(in_array(Session::get('current_store_id'), $storeArr)){
									$f = true;
								}else{
									$f = false;
								}
							}
							if (!in_array($qcity['inventory_center_city'], $citiesArr) && !empty($qcity['inventory_center_city']) && $f) {
								$citiesArr[] = $qcity['inventory_center_city'];
							}
						}
					}else{
						Session::set('isppr_city', '');
					}
				}
				if(Session::exists('isppr_city') && (Session::get('isppr_city') != '')){
					$city->selectOptionByValue(Session::get('isppr_city'));
					$Qinventory->andWhere('p.inventory_center_city=?', Session::get('isppr_city'));
				}

				$Qinventory = $Qinventory->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
				$myfinv = 0;
				$curInv = 0;
				foreach ($Qinventory as $qinv) {

					if ($myfinv == 0) {
						$myfinv = $qinv['inventory_center_id'];
					}
					$attr = array(
						array(
							'name' => 'days',
							'value' => $qinv['inventory_center_min_rental_days']
						)
					);
					if($hasLP == false){
					$pickup->addOptionWithAttributes($qinv['inventory_center_id'], $qinv['inventory_center_name'], $attr);
					}else{
						$QLP = Doctrine_Query::create()
						->from('InventoryCentersLaunchPoints')
						->where('inventory_center_id = ?', $qinv['inventory_center_id'])
						->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
						foreach($QLP as $iLP){
							$pickup->addOption($iLP['lp_name'], $iLP['lp_name']);
						}
					}
					$dropoff->addOption($qinv['inventory_center_id'], $qinv['inventory_center_name']);
					$curInv++;
				}

				if ($curInv == 1) {
					$continent->selectOptionByValue($qinv['inventory_center_continent']);
					$country->selectOptionByValue($qinv['inventory_center_country']);
					$city->selectOptionByValue($qinv['inventory_center_city']);
					$state->selectOptionByValue($qinv['inventory_center_state']);
					//$pickup->selectOptionByValue($qinv['inventory_center_id']);
				}

				foreach($continentsArr as $continentItem){
					$continent->addOption($continentItem, $continentItem);
				}
				sort($statesArr);
				foreach($statesArr as $stateItem){
					$state->addOption($stateItem, $stateItem);
				}
				array_multisort($countriesArrNames, $countriesArr);

				foreach($countriesArr as $k => $countryItem){
					$country->addOption($countryItem, $countriesArrNames[$k]);
				}
				sort($citiesArr);
				foreach($citiesArr as $cityItem){
					$city->addOption($cityItem, $cityItem);
				}

				$separator1 = htmlBase::newElement('div');
				if ($hasHeaders === true){
					$separator1->addClass('ui-my-header ui-corner-top');
				}
				$separatort = htmlBase::newElement('div');
				if ($hasHeaders === true){
					$separatort->addClass('ui-my-header-text');
					$separatort->html(sysLanguage::get('TEXT_SELECT_DESTINATION'));
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

				$lpText = htmlBase::newElement('a')
				->addClass('icon1 mylp1')
				->css(array(
					'display' => 'inline-block'
				));
				$dropText = htmlBase::newElement('a')
				->text('More Info')
				->addClass('myg1')
				->attr('href', itw_app_link('appExt=inventoryCenters', 'show_inventory', 'default'));

				if($hasGeographic){
					if (sysConfig::get('EXTENSION_INVENTORY_CENTERS_SHOW_CONTINENT_ON_PPR_INFOBOX') == 'True'){
						$container_dest->append($continentt)->append($continent);
					}
					if (sysConfig::get('EXTENSION_INVENTORY_CENTERS_SHOW_COUNTRY_ON_PPR_INFOBOX') == 'True'){
						$container_dest->append($countryt)->append($country);
					}
					if (sysConfig::get('EXTENSION_INVENTORY_CENTERS_SHOW_STATE_ON_PPR_INFOBOX') == 'True'){
						$container_dest->append($statet)->append($state);
					}
					if (sysConfig::get('EXTENSION_INVENTORY_CENTERS_SHOW_CITY_ON_PPR_INFOBOX') == 'True'){
						$container_dest->append($cityt)->append($city);
					}
				}

				if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_CHOOSE_PICKUP') == 'True'){
					$container_dest->append($pickupt)->append($pickup)->append($pickText)->append($lpText);
				}
				if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_CHOOSE_DROPOFF') == 'True'){
					$container_dest->append($dropofft)->append($dropoff)->append($dropText)->append($br);
				}
				$htmlHasHeaders = htmlBase::newElement('input')
				->setType('hidden')
				->setName('hasHeaders')
				->setValue($hasHeaders);

				$pprform->append($separator1)->append($container_dest)->append($htmlHasHeaders);
			}
			return $pprform;
		}

}
?>