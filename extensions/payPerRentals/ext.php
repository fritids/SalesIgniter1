<?php
/*
  Pay Per Rentals Version 1

  I.T. Web Experts, Rental Store v2
  http://www.itwebexperts.com

  Copyright (c) 2009 I.T. Web Experts

  This script and it's source is not redistributable
 */
class Extension_payPerRentals extends ExtensionBase {

	public function __construct(){
		parent::__construct('payPerRentals');
	}

	public function init(){
		global $App, $appExtension, $typeNames, $inventoryTypes;
		if ($this->isEnabled() === false)
			return;

		$typeNames['reservation'] = 'Reservation';
		$inventoryTypes['reservation'] = 'Reservation';

		EventManager::attachEvents(array(
			'ApplicationTopActionCheckPost',
			'OrderQueryBeforeExecute',
			'OrderClassQueryFillProductArray',
			'ProductInventoryBarcodeGetItemCount',
			'ProductInventoryQuantityGetItemCount',
			'ApplicationTopAction_reserve_now',
			'BoxMarketingAddLink',
			'OrderBeforeSendEmail',
            'ProductBeforeTaxAddress',
			'NewProductAddBarcodeListingHeader',
			'NewProductAddBarcodeListingBody',
			'ApplicationTopAction_add_reservation_product',
			'CouponEditPurchaseTypeBeforeOutput',
			'CouponEditBeforeSave',
			'ShoppingCartFind',
			'ShoppingCartFindKey',
			'UpdateTotalsCheckout',
			'BeforeShowShippingOrderTotals',
			'OrderTotalShippingProcess',
			'ShippingMethodCheckBeforeConstruct',
			'CouponsPurchaseTypeRestrictionCheck'
		), null, $this);

		if ($appExtension->isCatalog()){
			EventManager::attachEvent('ProductSearchQueryBeforeExecute', null, $this);
			EventManager::attachEvent('ProductListingQueryBeforeExecute', null, $this);
		}else{
			EventManager::attachEvent('OrderInfoAddBlock', null, $this);
			EventManager::attachEvent('OrderShowExtraPackingData', null, $this);
		}

		/*
		 * Shopping Cart Actions --BEGIN--
		 */
		require(dirname(__FILE__) . '/classEvents/ShoppingCart.php');
		$eventClass = new ShoppingCart_payPerRentals();
		$eventClass->init();

		require(dirname(__FILE__) . '/classEvents/ShoppingCartProduct.php');
		$eventClass = new ShoppingCartProduct_payPerRentals();
		$eventClass->init();

		require(dirname(__FILE__) . '/classEvents/ShoppingCartDatabase.php');
		$eventClass = new ShoppingCartDatabase_payPerRentals();
		$eventClass->init();

		require(dirname(__FILE__) . '/classes/Utilities.php');
		/*load google api key per store*/
		$multiStore = $appExtension->getExtension('multiStore');
		if ($multiStore !== false && $multiStore->isEnabled() === true){
			$QstoreInfo = Doctrine_Query::create()
			->select('google_key')
			->from('Stores')
			->where('stores_id = ?', $multiStore->getStoreId())
			->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
			Session::set('google_key', $QstoreInfo[0]['google_key']);
		}else{
			Session::set('google_key', sysConfig::get('EXTENSION_PAY_PER_RENTALS_GOOGLE_MAPS_API_KEY'));
		}
	}

	public function UpdateTotalsCheckout(){
		global $onePageCheckout, $ShoppingCart;
		$weight = 0;
		$selectedMethod = '';
		$Module = OrderShippingModules::getModule('zonereservation', true);
		if($Module->getType() == 'Order' && sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_ONE_SHIPPING_METHOD') == 'False'){
			$totalPrice = 0;
			foreach($ShoppingCart->getProducts() as $cartProduct) {
						$cost = 0;
						if ($cartProduct->hasInfo('reservationInfo') === true){
							$reservationInfo1 = $cartProduct->getInfo('reservationInfo');
							if(isset($reservationInfo1['shipping']) && isset($reservationInfo1['shipping']['module']) && $reservationInfo1['shipping']['module'] == 'zonereservation'){
								$selectedMethod = $reservationInfo1['shipping']['id'];
								$weight += $cartProduct->getWeight();

								if(isset($reservationInfo1['shipping']['cost'])){
									$cost = $reservationInfo1['shipping']['cost'];
								}
							}
						}
				$totalPrice += $cartProduct->getFinalPrice(true) * $cartProduct->getQuantity() - $cost * $cartProduct->getQuantity();
			}


			$quotes = array($Module->quote($selectedMethod, $weight, $totalPrice));


			$onePageCheckout->onePage['info']['reservationshipping'] = array(
									'id'     => 'zonereservation_' .(isset($quotes[0]['methods'][0]['id'])?$quotes[0]['methods'][0]['id']:''),
									'module' => 'zonereservation',
									'method' => 'zonereservation',
									'title'  => isset($quotes[0]['methods'][0]['title'])?$quotes[0]['methods'][0]['title']:'',
									'cost'   => isset($quotes[0]['methods'][0]['cost'])?$quotes[0]['methods'][0]['cost']:''
			);
		}
	}

	public function ShippingMethodCheckBeforeConstruct(&$isEnabled){
		global $ShoppingCart, $App;
		if(isset($ShoppingCart) && is_object($ShoppingCart) && $App->getAppName() == 'checkout'){
			$isEnabled = false;
			foreach($ShoppingCart->getProducts() as $cartProduct){
				if($cartProduct->hasInfo('reservationInfo') == false){
					$isEnabled = true;
					break;
				}
			}
		}
	}

	public function OrderTotalShippingProcess(&$totalShippingCost, &$shippingmodulesInfo){
		global $ShoppingCart;
		$hasShipping = false;
		$totalPrice = 0;
		foreach($ShoppingCart->getProducts() as $cartProduct){
			$totalPrice += $cartProduct->getFinalPrice()* $cartProduct->getQuantity();
		}
		foreach($ShoppingCart->getProducts() as $cartProduct){
			if ($cartProduct->getPurchaseType() != 'reservation'){
				continue;
			}

			$pInfo = $cartProduct->getInfo();
			$pID = $cartProduct->getIdString();
			$uniqID = $cartProduct->getUniqID();
			$reservationInfo = $pInfo['reservationInfo'];
			if (isset($reservationInfo['shipping']) && $reservationInfo['shipping'] !== false){
				$shippingInfo = $reservationInfo['shipping'];
				$Module = OrderShippingModules::getModule($shippingInfo['module'], true);

				if($Module->getType() == 'Order' && sysConfig::get('EXTENSION_PAY_PER_RENTALS_SHOW_SHIPPING') == 'False'){
					if(sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_ONE_SHIPPING_METHOD') == 'False'){
						$totalShippingCost += $shippingInfo['cost'];
						$hasShipping = true;
						$shippingmodulesInfo .= 'Shipping('.$shippingInfo['title'].')';
					}else{
						if(isset($shippingInfo['free_delivery_over']) && $shippingInfo['free_delivery_over'] != -1 && $shippingInfo['free_delivery_over'] <= $totalPrice){
							$shippingInfo['cost'] = 0;
							$shippingmodulesInfo = 'Free delivery';
						}else{
							$shippingmodulesInfo = 'Shipping('.$shippingInfo['title'].')';
						}
						$totalShippingCost = $shippingInfo['cost'] - 1;
						$hasShipping = true;

					}
				}
			}
		}
		if($hasShipping){
			$totalShippingCost += 1;
		}
	}
	
	public function CouponEditPurchaseTypeBeforeOutput(&$checkbox, $name, $Coupon){
		if ($name == 'reservation'){
			$periodBox = htmlBase::newElement('selectbox')
			->setName('min_reservation_period')
			//->addOption('h', 'Hour(s)');
			->addOption('d', 'Day(s)')
			->addOption('w', 'Week(s)')
			->addOption('m', 'Month(s)')
			->addOption('y', 'Year(s)')
			->selectOptionByValue($Coupon->min_reservation_period);
			
			$timeBox = htmlBase::newElement('input')
			->attr('size', '4')
			->setName('min_reservation_time')
			->val($Coupon->min_reservation_time);
			
			$checkbox = '<table cellpadding="0" cellspacing="0" border="0">' . 
				'<tr>' . 
					'<td>' . $checkbox . '</td>' . 
					'<td>&nbsp;Min Length:</td>' . 
					'<td>&nbsp;' . $timeBox->draw() . ' ' . $periodBox->Draw() . '</td>' . 
				'</tr>' . 
			'</table>';
		}
	}
	
	public function CouponEditBeforeSave($Coupon){
		if (isset($_POST['restrict_to_purchase_type']) && in_array('reservation', $_POST['restrict_to_purchase_type'])){
			$Coupon->min_reservation_time = $_POST['min_reservation_time'];
			$Coupon->min_reservation_period = $_POST['min_reservation_period'];
		}
	}
	
	public function CouponsPurchaseTypeRestrictionCheck($cartProduct, $Coupon, &$success){
		if ($success === true && $cartProduct->getPurchaseType() == 'reservation'){
			$minTime = $Coupon['min_reservation_time'];
			$minPeriod = $Coupon['min_reservation_period'];
			
			$resInfo = $cartProduct->getInfo('reservationInfo');
			$startParsed = date_parse($resInfo['start_date']);
			$endParsed = date_parse($resInfo['end_date']);
			
			$startTime = mktime(
				$startParsed['hour'],
				$startParsed['minute'],
				$startParsed['second'],
				$startParsed['month'],
				$startParsed['day'],
				$startParsed['year']
			);
			$endTime = mktime(
				$endParsed['hour'],
				$endParsed['minute'],
				$endParsed['second'],
				$endParsed['month'],
				$endParsed['day'],
				$endParsed['year']
			);
			
			$timeDiff = $endTime - $startTime;
			
			switch($minPeriod){
				case 'h':
					$checkTime = floor($timeDiff/(60*60));
					break;
				case 'd':
					$checkTime = floor($timeDiff/(60*60*24));
					break;
				case 'w':
					$checkTime = floor($timeDiff/(60*60*24*7));
					break;
				case 'm':
					$checkTime = floor($timeDiff/(60*60*24*30));
					break;
				case 'y':
					$checkTime = floor($timeDiff/(60*60*24*365));
					break;
			}

			if ($minTime > $checkTime){
				$success = false;
			}
		}
	}

	public function ApplicationTopActionCheckPost(&$action){
		if (isset($_POST['reserve_now']) || (isset($_GET['action']) && $_GET['action'] == 'reserve_now')){
			$action = 'reserve_now';
		}elseif (isset($_POST['add_reservation_product']) || (isset($_GET['action']) && $_GET['action'] == 'add_reservation_product')){
			$action = 'add_reservation_product';
		}
	}

	public function ProductInventoryQuantityGetItemCount(&$invData, &$invItem, &$addTotal){
		if ($invData['type'] == 'reservation'){
			$today = date('Y-n-j', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
			$plusFive = date('Y-n-j', mktime(0, 0, 0, date('m'), date('d') + 5, date('Y')));

			$Qreserved = Doctrine_Query::create()
			->select('count(orders_products_reservations_id) as total')
			->from('OrdersProducts op')
			->leftJoin('op.OrdersProductsReservation opr')
			->where('op.products_id = ?', $invData['products_id'])
			->andWhere('opr.track_method = ?', 'quantity')
			->andWhere('((opr.start_date between CAST("' . $today . '" as DATE) and CAST("' . $plusFive . '" as DATE)) or (opr.end_date between CAST("' . $today . '" as DATE) and CAST("' . $plusFive . '" as DATE)))');

			EventManager::notify('OrdersProductsReservationListingBeforeExecute', &$Qreserved);

			$Qreserved = $Qreserved->execute();
			if ($Qreserved && $Qreserved[0]->total > $invItem['available']){
				$addTotal = false;
			}
		}
	}

	public function ProductInventoryBarcodeGetItemCount(&$invData, &$invItem, &$addTotal){
		/*if ($invData['type'] == 'reservation'){
			$today = date('Y-n-j', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
			$plusFive = date('Y-n-j', mktime(0, 0, 0, date('m'), date('d') + 5, date('Y')));

			$Qreserved = Doctrine_Query::create()
			->select('orders_products_reservations_id')
			->from('OrdersProductsReservation opr')
			->where('barcode_id = ?', $invItem['id'])
			->andWhere('track_method = ?', 'barcode')
			->andWhere('((start_date between CAST("' . $today . '" as DATE) and CAST("' . $plusFive . '" as DATE)) or (end_date between CAST("' . $today . '" as DATE) and CAST("' . $plusFive . '" as DATE)))');

			EventManager::notify('OrdersProductsReservationListingBeforeExecute', &$Qreserved);
			$Qreserved = $Qreserved->fetchOne();

			if ($Qreserved){
				$addTotal = false;
			}
		} */
	}

	public function OrderQueryBeforeExecute(&$Qorder){
		$Qorder->leftJoin('op.OrdersProductsReservation opr')
		->leftJoin('opr.ProductsInventoryBarcodes pib')
		->leftJoin('opr.ProductsInventoryQuantity piq');
	}


	public function ApplicationTopAction_reserve_now(){
		global $messageStack, $appExtension;

		if (Session::exists('post_array') && isset($_POST['is_change_address'])){
			$_POST = array_merge($_POST, Session::get('post_array'));
			Session::remove('post_array');
		}

		$pID = (int) tep_get_prid($_GET['products_id']);
		$product = new product($pID);
		$purchaseType = $product->getPurchaseType('reservation');
	
		if ($purchaseType->hasInventory() === false){
			$messageStack->addSession('pageStack', 'This product has no inventory for reservations', 'error');
			tep_redirect(itw_app_link(tep_get_all_get_params(array('action', 'appExt')), 'product', 'info'));
		}

		$extAttributes = $appExtension->getExtension('attributes');
		if ($extAttributes && $extAttributes->isEnabled() === true) {
			if (attributesUtil::productHasAttributes($pID, 'reservation')) {
				if (!isset($_POST) || !isset($_POST[$extAttributes->inputKey]) || !isset($_POST[$extAttributes->inputKey]['reservation'])) {
					$messageStack->addSession('pageStack', 'This product has attributes to select', 'warning');
					tep_redirect(itw_app_link('products_id=' . $pID, 'product', 'info'));
				}
			}
		}

		if (empty($_POST)){
			tep_redirect(itw_app_link(tep_get_all_get_params(array('action', 'appExt')) . 'appExt=payPerRentals', 'build_reservation', 'default'));
		}
	}

	public function ApplicationTopAction_add_reservation_product(){
		if(is_array($_POST['products_id'])){
			$productID = $_POST['products_id'][0];
		}else{
			$productID = $_POST['products_id'];
		}
		$qty = $_POST['rental_qty'];
		if(sysConfig::get('EXTENSION_PAY_PER_RENTALS_ENABLE_TIME_DROPDOWN') == 'True'){
			if(isset($_POST['start_time']) && isset($_POST['end_time'])){
				$_POST['start_date'] .= ' '.$_POST['start_time'];
				$_POST['end_date'] .= ' '.$_POST['end_time'];
			}
		}
		ReservationUtilities::addReservationProductToCart($productID, $qty);
		tep_redirect(itw_app_link(null, 'shoppingCart', 'default'));
	}

	public function ShoppingCartFind(&$cartProduct, &$returnVal){
		if(sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_ONE_SHIPPING_METHOD') == 'True'){
			$returnVal = true;
		}else{
			if($cartProduct->hasInfo('reservationInfo') && isset($_POST['start_date']) && isset($_POST['end_date'])){
					$pInfo = $cartProduct->getInfo('reservationInfo');
					if(($pInfo['start_date'] == $_POST['start_date']) && ($pInfo['end_date'] == $_POST['end_date'])){
						$returnVal = true;
					}else{
						$returnVal = false;
					}
			}
		}

	}

	public function ShoppingCartFindKey($iProduct, &$cartProduct, &$returnVal){
		if(sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_ONE_SHIPPING_METHOD') == 'True'){
			$returnVal = true;
		}else{
			if($cartProduct->hasInfo('reservationInfo')){
				$returnVal = false;
					if($iProduct->hasInfo('reservationInfo')){
						$pInfo = $cartProduct->getInfo('reservationInfo');
						$qInfo = $iProduct->getInfo('reservationInfo');
						if(($pInfo['start_date'] == $qInfo['start_date']) && ($pInfo['end_date'] == $qInfo['end_date'])){
							$returnVal = true;
						}
					}

			}
		}

	}

	public function ShoppingCartAddFields(&$qty, $purchaseType, $cartProduct){
		$gInfo = $cartProduct->getInfo();
		$resInfo = $gInfo['reservationInfo'];
		foreach($resInfo as $item => $val){
			if($item != 'quantity'){
				$qty .= tep_draw_hidden_field('cart_quantity['.$cartProduct->getUniqID().']['.$purchaseType.']['.$item.']', $val, 'size="4" class="'.$item.'_shop"');
			}
		}

	}

	public function OrderClassQueryFillProductArray(&$pInfo, &$product){
		$Reservations = $pInfo['OrdersProductsReservation'];
		if (sizeof($Reservations) > 0){
			$mainReservation = false;
			foreach($Reservations as $rInfo){
				if (is_null($rInfo['parent_id'])){
					$mainReservation = $rInfo;
					break;
				}
			}

			if ($mainReservation !== false){
				if ($mainReservation['track_method'] == 'barcode'){
					$product['barcode_number'] = $mainReservation['ProductsInventoryBarcodes']['barcode'];
				}

				$product['reservationInfo'] = array(
					'start_date' => $mainReservation['start_date'],
					'end_date' => $mainReservation['end_date'],
					'insurance' => $mainReservation['insurance'],
					'quantity' =>  (isset($mainReservation['rental_qty'])?$mainReservation['rental_qty']:1)
				);

				if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_EVENTS') == 'True'){
					$product['reservationInfo']['event_date'] = $mainReservation['event_date'];
				    $product['reservationInfo']['event_name'] = $mainReservation['event_name'];
				    if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_GATES') == 'True'){
					    $product['reservationInfo']['event_gate'] = $mainReservation['event_gate'];
				    }
			    }
				$product['reservationInfo']['semester_name'] = $mainReservation['semester_name'];

				if (isset($mainReservation['shipping_method']) && !empty($mainReservation['shipping_method'])){
					$product['reservationInfo']['shipping'] = array(
						'title' => $mainReservation['shipping_method_title'],
						'cost' => $mainReservation['shipping_cost'],
						'id' => $mainReservation['shipping_method'],
						'days_before' => $mainReservation['shipping_days_before'],
						'days_after' => $mainReservation['shipping_days_after']
					);
				}

				EventManager::notify('Extension_payPerRentalsOrderClassQueryFillProductArray', &$mainReservation, &$product);
			}else{
				$product['name'] .= '<br />NO VALID ROOT RESERVATION!';
			}
		}
	}

	public function BoxMarketingAddLink(&$contents){
		if (sysPermissions::adminAccessAllowed('default_orders', 'show_reports', 'payPerRentals') === true){
			$contents['children'][] = array(
				'link'       => itw_app_link('appExt=payPerRentals','show_reports','default_orders','SSL'),
				'text' => 'Rental Order Reports'
			);
		}
		if (sysPermissions::adminAccessAllowed('default', 'reservations_reports', 'payPerRentals') === true){
			$contents['children'][] = array(
				'link'       => itw_app_link('appExt=payPerRentals','reservations_reports','default','SSL'),
				'text' => 'Rental Inventory Report'
			);
		}
	        if (sysPermissions::adminAccessAllowed('default_orders', 'consumption_report', 'payPerRentals') === true){
        	    	$contents['children'][] = array(
                	'link'       => itw_app_link('appExt=payPerRentals','consumption_report','default_orders','SSL'),
                	'text' => 'Consumption Product Report'
            		);
        	}
		if (sysPermissions::adminAccessAllowed('default', 'quantity_reports', 'payPerRentals') === true){
			$contents['children'][] = array(
				'link'       => itw_app_link('appExt=payPerRentals','quantity_reports','default','SSL'),
				'text' => 'Quantity Inventory Report'
			);
		}
	}

	public function BeforeShowShippingOrderTotals(&$orderTotals){
		global $ShoppingCart, $order, $userAccount;

		if(sysConfig::get('EXTENSION_PAY_PER_RENTALS_SHOW_SHIPPING') == 'False' && isset($ShoppingCart)){
			$shippingCost = 0;
			foreach($ShoppingCart->getProducts() as $cartProduct){
				$resInfo = $cartProduct->getInfo('reservationInfo');
				if(isset($resInfo['shipping'])){
					$shippingCost += $resInfo['shipping']['cost'];

				}
			}
			$order->info['total'] += $shippingCost;
			$addressBook =& $userAccount->plugins['addressBook'];
			if ($addressBook->entryExists('delivery') === true){
				$deliveryAddress = $addressBook->getAddress('delivery');
			}else{
				$deliveryAddress = $addressBook->getAddress('billing');
			}
			$module = OrderShippingModules::getModule('zonereservation');
			$taxClassId = $module->getTaxClass();
			if ($taxClassId > 0) {
				$shipping_tax = tep_get_tax_rate($taxClassId, $deliveryAddress['entry_country_id'], $deliveryAddress['entry_zone_id']);
				$shipping_tax_description = tep_get_tax_description($taxClassId, $deliveryAddress['entry_country_id'], $deliveryAddress['entry_zone_id']);		
				$order->info['tax'] += tep_calculate_tax($shippingCost, $shipping_tax);
				$order->info['tax_groups']["$shipping_tax_description"] += tep_calculate_tax($shippingCost, $shipping_tax);
				$order->info['total'] += tep_calculate_tax($shippingCost, $shipping_tax);

				if (sysConfig::get('DISPLAY_PRICE_WITH_TAX') == 'true'){
					$shippingCost += tep_calculate_tax($shippingCost, $shipping_tax);
				}
			}
			$orderTotals->addOutput(array(
					'module' => 'zonereservation',
					'method' => 'zonereservation',
					'title'  =>  'Shipping:',
					'text'   => $orderTotals->formatAmount($shippingCost),
					'value'  => $shippingCost
			));
		}
	}

	public function ProductListingQueryBeforeExecute(&$Qproducts){
		$Qproducts->leftJoin('p.ProductsPayPerRental pppr');
		$Qproducts->leftJoin('pppr.PricePerRentalPerProducts ppprp');
		$Qproducts->leftJoin('p.PayPerRentalHiddenDates pprhd');
		if(Session::exists('isppr_date_start') && (Session::get('isppr_date_start') != '') && Session::exists('isppr_date_end') && (Session::get('isppr_date_end') != '')){
			//i update hidden_start_dates for every run
			$QHiddenDatesUpdateStart = Doctrine_Query::create()
			->from('PayPerRentalHiddenDates')
			->where('hidden_start_date < ?', date('Y-m-d'))
			->andWhere('hidden_end_date < ?', date('Y-m-d'))
			->execute();
			foreach($QHiddenDatesUpdateStart as $iHidden){
				$iHidden->hidden_start_date = date('Y-m-d', strtotime('+1 year', strtotime($iHidden->hidden_start_date)));
				$iHidden->hidden_end_date = date('Y-m-d', strtotime('+1 year', strtotime($iHidden->hidden_end_date)));
				$iHidden->save();
			}


			$Qproducts->andWhere('hidden_start_date is null OR
								  hidden_end_date is null OR
								  hidden_start_date NOT BETWEEN CAST("'.date('Y-m-d',strtotime(Session::get('isppr_date_start'))).'" AS DATE) AND CAST("'.date('Y-m-d', strtotime(Session::get('isppr_date_end'))).'" AS DATE) AND
								  hidden_end_date NOT BETWEEN CAST("'.date('Y-m-d',strtotime(Session::get('isppr_date_start'))).'" AS DATE) AND CAST("'.date('Y-m-d', strtotime(Session::get('isppr_date_end'))).'" AS DATE) AND
			                      CAST("'.date('Y-m-d', strtotime(Session::get('isppr_date_end'))).'" AS DATE) NOT BETWEEN hidden_start_date AND hidden_end_date
			');
		}
	}

	public function ProductSearchQueryBeforeExecute(&$Qproducts){
		$Qproducts->leftJoin('p.ProductsPayPerRental pppr')
			->leftJoin('pppr.PricePerRentalPerProducts ppprp');
		global $currencies;
		if (isset($_GET['pprfrom']) && is_array($_GET['pprfrom'])){
			//if (isset($_GET['pfrom']) && is_array($_GET['pfrom']) && $_GET['debug'] == 1){
			if ($currencies->is_set(Session::get('currency'))){
				$rate = $currencies->get_value(Session::get('currency'));
			}
			foreach($_GET['pprfrom'] as $k => $v){
				if (isset($rate)){
					$v = $v / $rate;
					if (isset($_GET['pprto'][$k])){
						$_GET['pprto'][$k] = $_GET['pprto'][$k] / $rate;
					}
				}
				$queryAddString = '(ppprp.price > ' . (double)$v;
				if (isset($_GET['pprto'][$k])){
					$queryAddString .= ' AND ppprp.price <= ' . (double)$_GET['pprto'][$k];
				}
				$queryAddString .= ')';

				$queryAdd[] = $queryAddString;
			}
			$priceFiltersCheck = Doctrine_Query::create()
				->select('pppr.products_id')
				->from('ProductsPayPerRental pppr')
				->leftJoin('pppr.PricePerRentalPerProducts ppprp')
				->where('(' . implode(' or ', $queryAdd) . ')')
				->fetchArray();
			if(count($priceFiltersCheck) > 0) {
				foreach($priceFiltersCheck as $priceFilterProductID){
					$priceFilters[$priceFilterProductID['products_id']] = $priceFilterProductID['products_id'];
				}
				$Qproducts->andWhere('p.products_id in (' . implode(', ', $priceFilters) . ')');
			}
		}
	}

	public function NewProductAddBarcodeListingBody(&$bInfo, &$currentBarcodesTableBody){

		$currentBarcodesTableBody[1]['text'] = $currentBarcodesTableBody[1]['text'] . "<a href='" . itw_app_link('appExt=payPerRentals&product_id=' . $_GET['pID'] . '&barcode_id=' . $bInfo['barcode_id'], 'show_reports', 'default_orders') . "'>&nbsp;&nbsp;<img src='images/money-icon.png' alt='Rental Orders Report'/></a>";
	}

	public function NewProductAddBarcodeListingHeader(&$currentBarcodesTableHeaders){
		
	}

	 public function ProductBeforeTaxAddress(&$zoneId, &$countryId, $product, $order, $userAccount){
        $pInfo = $product->getInfo();
        if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_EVENTS') == 'True'){
            $shippingTitles = explode(',', sysConfig::get('EXTENSION_PAY_PER_RENTALS_TAX_PER_EVENT_ADDRESS'));
            if (isset($pInfo['reservationInfo']['shipping']['title']) && !empty($pInfo['reservationInfo']['shipping']['title'])){
                if (in_array($pInfo['reservationInfo']['shipping']['title'], $shippingTitles)){
                    $eventsTable = Doctrine_Core::getTable('PayPerRentalEvents')->findOneByEventsName($pInfo['reservationInfo']['event_name']);
                    $zoneId = $eventsTable->events_zone_id;
                    $countryId = $eventsTable->events_country_id;
                }
            }
        }
    }
	public function OrderBeforeSendEmail(&$order, &$emailEvent, &$products_ordered, &$sendVaribles){
			global $currencies, $appExtension;
			$Qorders = Doctrine_Query::create()
			->from('Orders o')
			->leftJoin('o.OrdersProducts op')
			->leftJoin('op.OrdersProductsReservation ops')
			->where('o.orders_id =?', $order['orderID'])
			->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
		    	$order_products_arranged = '';
			if(count($Qorders) > 0){
				if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_EVENTS') == 'True' && sysConfig::get('EXTENSION_PAY_PER_RENTALS_SHOW_EVENT_EMAIL') == 'True'){
					if (isset($Qorders[0]['OrdersProducts'][0]['OrdersProductsReservation'][0]['event_name'])){
						$evInfo = ReservationUtilities::getEvent($Qorders[0]['OrdersProducts'][0]['OrdersProductsReservation'][0]['event_name']);
						$details = $evInfo['events_details'];
						if(sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_GATES') == 'True'){
							$details .= 'Event Gate: '. $Qorders[0]['OrdersProducts'][0]['OrdersProductsReservation'][0]['event_gate'];
						}
						$emailEvent->setVar('event_description', $details);
					}
				}
				if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_SHOW_TERMS_EMAIL') == 'True'){
					$emailEvent->setVar('terms', $Qorders[0]['terms']);
				}
				$table = '<table style="width:100%" width="100%"><tr><td>Item</td><td>Qty</td><td>Arrival</td><td>Departure</td><td>Total</td></tr>';
				foreach($Qorders as $iOrder){
					foreach($iOrder['OrdersProducts'] as $iOrderProducts){
						$isReservation = false;
						foreach($iOrderProducts['OrdersProductsReservation'] as $iReservation){
							$isReservation = true;
							if(sysConfig::get('EXTENSION_PAY_PER_RENTALS_ENABLE_TIME_FEES') == 'True'){
								$parseDateStart = date_parse($iReservation['start_date']);
								$parseDateEnd = date_parse($iReservation['end_date']);
								$deliveryHour = $parseDateStart['hour'];
								$pickupHour = $parseDateEnd['hour'];
								$QStore = Doctrine_Query::create()
								->from('OrdersToStores')
								->where('orders_id = ?', $iOrder['orders_id'])
								->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
								$storeId = $QStore[0]['stores_id'];
							}else{
								//the calculation will need to be done in checkres(actually in getReservationPrice) but again it will be problems because this should be a tax per order
								$pickupHour = 0;
								$deliveryHour = 0;
								$storeId = 0;
							}
							$multiStore = $appExtension->getExtension('multiStore');
							if ($multiStore !== false && $multiStore->isEnabled() === true){
								$QTimeFees = Doctrine_Query::create()
										->from('StoresTimeFees')
										->where('stores_id = ?', $storeId)
										->orderBy('timefees_id')
										->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
							}else{
								$QTimeFees = Doctrine_Query::create()
										->from('PayPerRentalTimeFees')
										->orderBy('timefees_id')
										->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
							}
							$timeSlotStart = '';
							$timeSlotEnd = '';
							if(count($QTimeFees) > 0){
								$timeSlotStart = $QTimeFees[0]['timefees_name'];
								$timeSlotEnd = $QTimeFees[0]['timefees_name'];
								foreach($QTimeFees as $timeFee){
									if((int)$pickupHour >= (int)$timeFee['timefees_start'] && (int)$pickupHour <= (int)$timeFee['timefees_end']){
										$timeSlotEnd = $timeFee['timefees_name'];
									}
									if((int)$deliveryHour >= (int)$timeFee['timefees_start'] && (int)$deliveryHour <= (int)$timeFee['timefees_end']){
										$timeSlotStart = $timeFee['timefees_name'];
									}
								}
							}
							$table .= '<tr>';
							$table .= '<td>'.$iOrderProducts['products_name'].'</td>';
							$table .= '<td>'.$iOrderProducts['products_quantity'].'</td>';
							$table .= '<td>'.date('m/d/Y',strtotime($iReservation['start_date'])).'<br/>'.$timeSlotStart.'</td>';//date('H:i:s',strtotime($iReservation['end_date']))
							$table .= '<td>'.date('m/d/Y',strtotime($iReservation['end_date'])).'<br/>'.$timeSlotEnd.'</td>';//date('H:i:s',strtotime($iReservation['start_date']))
							$table .= '<td>'.$currencies->format(($iOrderProducts['final_price']*$iOrderProducts['products_quantity'])).'</td>';
							$table .= '</tr>';
						}
						if(!$isReservation){
							$table .= '<tr>';
							$table .= '<td>'.$iOrderProducts['products_name'].'</td>';
							$table .= '<td>'.$iOrderProducts['products_quantity'].'</td>';
							$table .= '<td>'.'not applicable'.'</td>';//date('H:i:s',strtotime($iReservation['end_date']))
							$table .= '<td>'.'not applicable'.'</td>';//date('H:i:s',strtotime($iReservation['start_date']))
							$table .= '<td>'.$currencies->format(($iOrderProducts['final_price']*$iOrderProducts['products_quantity'])).'</td>';
							$table .= '</tr>';
						}
					}
				}
				$table .= '</table>';
				$order_products_arranged = $table;
			}
			$emailEvent->setVar('order_products_arranged', $order_products_arranged);

		}

		public function OrderShowExtraPackingData(&$order){			
			$QOrder = Doctrine_Query::create()
			->from('Orders o')
			->leftJoin('o.OrdersProducts op')
			->leftJoin('op.OrdersProductsReservation ops')
			->where('o.orders_id = ?', $_GET['oID'])
			->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
			if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_EVENTS') == 'True'){
				$evInfo = ReservationUtilities::getEvent($QOrder[0]['OrdersProducts'][0]['OrdersProductsReservation'][0]['event_name']);
				$htmlEventDetails = '<br/><br/><b>Event Details:</b><br/>' .  trim($evInfo['events_details']);
				if (sysConfig::get('EXTENSION_PAY_PER_RENTALS_USE_GATES') == 'True'){
					$htmlEventDetails .= '<br/><br/><b>Event Gate:</b><br/>' .  trim($QOrder[0]['OrdersProducts'][0]['OrdersProductsReservation'][0]['event_gate']);
				}


				$htmlTermsDetails = $QOrder[0]['terms'];
			}

			if (isset($htmlEventDetails)){
				echo $htmlEventDetails;
			}
			echo '<br/>';
			if (isset($htmlTermsDetails)){
				echo $htmlTermsDetails;
			}
		}
		public function OrderInfoAddBlock($orderId){
		return
			'<div class="ui-widget ui-widget-content ui-corner-all" style="padding:1em;">' .
				'<table cellpadding="3" cellspacing="0">' .
					'<tr>' .
						'<td><table cellpadding="3" cellspacing="0">' .
							'<tr>
							 <td class="main" valign="top">' . '<a target="_blank" href="'.itw_app_link('oID='.$orderId,'orders','printTerms').'">Print Terms and Conditions The Client Agreed</a></td>
							</tr>
						</table></td>' .
					'</tr>' .
				'</table>' .
			'</div>';
	}

	
}
?>