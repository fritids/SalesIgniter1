<?php
class OrderTotalCoupon extends OrderTotalModuleBase
{
	public $credit_class;
	public $include_shipping;
	public $include_tax;
	public $calculate_tax;
	public $tax_class;
	public $user_prompt;
	public $header;
	public $deduction;

	public function __construct() {
		/*
		 * Default title and description for modules that are not yet installed
		 */
		$this->setTitle('Discount Coupons');
		$this->setDescription('Discount Coupon');

		$this->init('coupon');

		if ($this->isInstalled() === true){
			$this->credit_class = true;
			$this->include_shipping = $this->getConfigData('MODULE_ORDER_TOTAL_COUPON_INC_SHIPPING');
			$this->include_tax = $this->getConfigData('MODULE_ORDER_TOTAL_COUPON_INC_TAX');
			$this->calculate_tax = $this->getConfigData('MODULE_ORDER_TOTAL_COUPON_CALC_TAX');
			$this->tax_class = $this->getConfigData('MODULE_ORDER_TOTAL_COUPON_TAX_CLASS');
			$this->user_prompt = '';
			$this->header = $this->getConfigData('MODULE_ORDER_TOTAL_COUPON_HEADER');
		}
	}

	public function getDiscount(){
		$order_total = $this->get_order_total();
		$discountAmount = $this->calculate_credit($order_total);
		$taxDiscountAmount = 0.0; //Fred
		$this->deduction = $discountAmount;
		if ($this->calculate_tax != 'None'){ //Fred - changed from 'none' to 'None'!
			$taxDiscountAmount = $this->calculate_tax_deduction($order_total, $this->deduction, $this->calculate_tax);
		}
		return $taxDiscountAmount + $discountAmount;
	}
	public function process() {
		global $order, $onePageCheckout;

		$order_total = $this->get_order_total();
		$discountAmount = $this->calculate_credit($order_total);
		$taxDiscountAmount = 0.0; //Fred
		$this->deduction = $discountAmount;
		if ($this->calculate_tax != 'None'){ //Fred - changed from 'none' to 'None'!
			$taxDiscountAmount = $this->calculate_tax_deduction($order_total, $this->deduction, $this->calculate_tax);
		}

		if ($discountAmount > 0){

			if(Session::exists('cc_id_trial_days') && Session::get('cc_id_trial_days') > 0 && $onePageCheckout->isMembershipCheckout()){
				Session::set('cc_id_trial', $discountAmount + $taxDiscountAmount);
				$this->addOutput(array(
						'title' => $this->getTitle() . ':' . $this->coupon_code . ':',
						'text' => '<b>-' . $this->formatAmount($discountAmount + $taxDiscountAmount) . ' for '.Session::get('cc_id_trial_days').' days</b>',
						'value' => 0
				));
			}else{
				$minModule = OrderTotalModules::getModule('minorder', true);
				if($order->info['subtotal'] -($discountAmount + $taxDiscountAmount) >= $minModule->getConfigData('MODULE_ORDER_TOTAL_MINORDER_AMOUNT')){
				$order->info['tax'] -= $taxDiscountAmount;
				if($order->info['total'] - $discountAmount > 0){
					$order->info['total'] -= $discountAmount;
				}else{
					$order->info['total'] = 0;
				}
				$this->addOutput(array(
						'title' => $this->getTitle() . ':' . $this->coupon_code . ':',
						'text' => '<b>-' . $this->formatAmount($discountAmount + $taxDiscountAmount) . '</b>',
						'value' => -($discountAmount + $taxDiscountAmount)
					));
				}
			}

		}
	}

	public function selection_test() {
		return false;
	}

	public function pre_confirmation_check($order_total) {
		return $this->calculate_credit($order_total);
	}

	public function use_credit_amount() {
		return $output_string;
	}

	public function credit_selection() {
		global $language;
		$selection_string = '';
		$selection_string .= '' . "\n";
		$selection_string .= ' </tr><tr><td class="main">' . "\n";
		$selection_string .= sysLanguage::get('TEXT_ENTER_GV_CODE') . tep_draw_input_field('gv_redeem_code') . ' and click ';
		$image_submit = htmlBase::newElement('button')->setType('submit')
			->setText(sysLanguage::get('IMAGE_REDEEM_VOUCHER'))
			->attr('onclick', 'return check_form(), submitFunction()')->addClass('contbutton')->draw();
		$selection_string .= '';
		$selection_string .= '' . $image_submit . '</td>';
		$selection_string .= '';
		$selection_string .= '' . "\n";
		return $selection_string;
	}
	public function calculate_credit($amount) {
		global $ShoppingCart, $onePageCheckout, $order;

		$totalDiscount = 0;
		if (Session::exists('cc_id') === true){
			$Qcoupon = Doctrine_Query::create()
				->from('Coupons')
				->where('coupon_id = ?', (int)Session::get('cc_id'))
				->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
			if ($Qcoupon){
				$Coupon = $Qcoupon[0];
				$this->coupon_code = $Coupon['coupon_code'];
				$couponAmount = $Coupon['coupon_amount'];
				if ($Coupon['coupon_type'] == 'S'){
					$couponAmount = $order->info['shipping_cost'];

					if ($Coupon['coupon_amount'] > 0){
						$couponAmount = $order->info['shipping_cost'] + $Coupon['coupon_amount'];
					}
				}
				$couponMinOrder = $Coupon['coupon_minimum_order'];

				if ($couponMinOrder <= $this->get_order_total()){
					if (!empty($Coupon['restrict_to_products']) || !empty($Coupon['restrict_to_purchase_type'])){
						if($onePageCheckout->isMembershipCheckout()) {
							$success = false;
							if (!empty($Coupon['restrict_to_purchase_type'])){
								$allowedPurchaseTypes = explode(',', $Coupon['restrict_to_purchase_type']);
								if(strstr($Coupon['restrict_to_purchase_type'],',')){
									$allowedPurchaseTypes = explode(',',$Coupon['restrict_to_purchase_type']);
								} else {
									$allowedPurchaseTypes = array($Coupon['restrict_to_purchase_type']);
								}
								$success = (is_array($allowedPurchaseTypes) && in_array('membership', $allowedPurchaseTypes));
							} else {
								$success = true;
							}
							if ($success && $Coupon['coupon_type'] == 'P'){

								$priceDiscount = round($order->products[0]['final_price']*10)/10*$couponAmount/100;
								$totalDiscount = $priceDiscount;
							}else{
								$totalDiscount = $couponAmount;
							}
							if($Coupon['number_days_membership'] > 0){
								Session::set('cc_id_trial_days', $Coupon['number_days_membership']);
							}
						} else {
							foreach($ShoppingCart->getProducts() as $cartProduct){
								$productPrice = ($cartProduct->getFinalPrice() * $cartProduct->getQuantity());

							if (!empty($Coupon['restrict_to_purchase_type'])){
								$purchaseTypes = explode(',', $Coupon['restrict_to_purchase_type']);
								$purchaseType = $cartProduct->getPurchaseType();

								$success = in_array($purchaseType, $purchaseTypes);
								EventManager::notify('CouponsPurchaseTypeRestrictionCheck', $cartProduct, $Coupon, &$success);

								if ($success === true){
									if ($Coupon['coupon_type'] == 'P'){
										$priceDiscount = round($productPrice * 10) / 10 * $couponAmount / 100;
										$totalDiscount += $priceDiscount;
									}
									else {
										$totalDiscount = $couponAmount;
									}
								}
							}
							elseif (!empty($Coupon['restrict_to_products'])) {
								$productIds = explode(',', $Coupon['restrict_to_products']);
								foreach($productIds as $pID){
									if ($pID == (int)$cartProduct->getIdString()){
										if ($Coupon['coupon_type'] == 'P'){
											$priceDiscount = round($productPrice * 10) / 10 * $couponAmount / 100;
											$totalDiscount += $priceDiscount;
										}
										else {
											$totalDiscount = $couponAmount;
										}
									}
								}
							}
					}
						}
					}
					else {
						if ($Coupon['coupon_type'] != 'P'){
							$totalDiscount = $couponAmount;
						}
						else {
							$totalDiscount = $amount * $couponAmount / 100;
						}
					}
				}
			}
			if ($totalDiscount > $amount){
				$totalDiscount = $amount;
			}
		}
		return $totalDiscount;
	}

	public function calculate_tax_deduction($amount, $discountAmount, $method) {
		global $userAccount, $order, $ShoppingCart, $onePageCheckout;

		if (Session::exists('cc_id')){
			$Qcoupon = Doctrine_Query::create()
				->from('Coupons')
				->where('coupon_id = ?', (int)Session::get('cc_id'))
				->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
			if ($Qcoupon){
				$Coupon = $Qcoupon[0];
				$totalPrice = 0;
				if ($Coupon['coupon_type'] != 'S'){
					$valid_array = array();
					if ($Coupon['restrict_to_products'] || $Coupon['restrict_to_purchase_type']){
						$valid_product = false;
						foreach($ShoppingCart->getProducts() as $cartProduct){
							$valid_product = false;
							$productId = (int)$cartProduct->getIdString();
							$taxClassId = $cartProduct->getTaxClassId();
							$purchaseType = $cartProduct->getPurchaseType();
							$productPrice = $cartProduct->getFinalPrice();
							$productQty = $cartProduct->getQuantity();

							if (!empty($Coupon['restrict_to_products'])){
								$productIds = explode(',', $Coupon['restrict_to_products']);
								if (in_array($productId, $productIds)){
									$valid_product = true;
								}
							}

							if (!empty($Coupon['restrict_to_purchase_type'])){
								$purchaseTypes = explode(',', $Coupon['restrict_to_purchase_type']);

								$success = in_array($purchaseType, $purchaseTypes);
								EventManager::notify('CouponsPurchaseTypeRestrictionCheck', $cartProduct, $Coupon, &$success);

								if ($success === true){
									$valid_product = true;
								}
							}

							if ($valid_product === true){
								$finalPrice = $productPrice * $productQty;
								$valid_array[] = array(
									'product_id' => $productId,
									'products_price' => $finalPrice,
									'products_tax_class' => $taxClassId
								);
								$totalPrice += $finalPrice; // changed
							}
						}

						if (sizeof($valid_array) > 0){
							if ($Coupon['coupon_type'] == 'P'){
								$ratio = $Coupon['coupon_amount'] / 100;
							}
							else {
								$ratio = $discountAmount / $totalPrice;
							}

							if ($Coupon['coupon_type'] == 'S'){
								$ratio = 1;
							}

							if ($method == 'Credit Note'){
								$tax_rate = tep_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
								$tax_desc = tep_get_tax_description($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
								if ($Coupon['coupon_type'] == 'P'){
									$totalDiscount = $discountAmount / (100 + $tax_rate) * $tax_rate;
								}
								else {
									$totalDiscount = $order->info['tax_groups'][$tax_desc] * $discountAmount / 100;
								}
								$order->info['tax_groups'][$tax_desc] -= $totalDiscount;
								$order->info['total'] -= $totalDiscount;
								$order->info['tax'] -= $totalDiscount;
							}
							else {
								for($i = 0; $i < sizeof($valid_array); $i++){
									$tax_rate = tep_get_tax_rate($valid_array[$i]['products_tax_class'], $order->delivery['country']['id'], $order->delivery['zone_id']);
									$tax_desc = tep_get_tax_description($valid_array[$i]['products_tax_class'], $order->delivery['country']['id'], $order->delivery['zone_id']);
									if ($tax_rate > 0){
										$totalDiscount = ($valid_array[$i]['products_price'] * $tax_rate) / 100 * $ratio;
										$order->info['tax_groups'][$tax_desc] -= $totalDiscount;
										$order->info['total'] -= $totalDiscount;
										$order->info['tax'] -= $totalDiscount;
									}
								}
							}
						}
					}
					else {
						if ($Coupon['coupon_type'] == 'F'){
							$totalDiscount = 0;
							if ($method == 'Credit Note'){
								$tax_rate = tep_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
								$tax_desc = tep_get_tax_description($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
								$totalDiscount = $discountAmount / (100 + $tax_rate) * $tax_rate;
								$order->info['tax_groups'][$tax_desc] -= $totalDiscount;
							}
							else {
								reset($order->info['tax_groups']);
								while(list($key, $value) = each($order->info['tax_groups'])){
									$ratio1 = $discountAmount / ($amount - $order->info['tax_groups'][$key]); ////debug
									$tax_rate = tep_get_tax_rate_from_desc($key);
									$net = $tax_rate * $order->info['tax_groups'][$key];
									if ($net > 0){
										$taxGroupTotal = $order->info['tax_groups'][$key] * $ratio1;
										$totalDiscount += $taxGroupTotal;
										$order->info['tax_groups'][$key] = $order->info['tax_groups'][$key] - $taxGroupTotal;
									}
								}
							}
							$order->info['total'] -= $totalDiscount;
							$order->info['tax'] -= $totalDiscount;
						}
						elseif ($Coupon['coupon_type'] == 'P') {
							$totalDiscount = 0;
							if ($method == 'Credit Note'){
								$tax_desc = tep_get_tax_description($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
								$totalDiscount = $order->info['tax_groups'][$tax_desc] * $discountAmount / 100;
								$order->info['tax_groups'][$tax_desc] -= $totalDiscount;
							}
							else {
								reset($order->info['tax_groups']);
								while(list($key, $value) = each($order->info['tax_groups'])){
									$taxGroupTotal = 0;
									$tax_rate = tep_get_tax_rate_from_desc($key);
									$net = $tax_rate * $order->info['tax_groups'][$key];
									if ($net > 0){
										$taxGroupTotal = $order->info['tax_groups'][$key] * $Coupon['coupon_amount'] / 100;
										$totalDiscount += $taxGroupTotal;
										$order->info['tax_groups'][$key] = $order->info['tax_groups'][$key] - $taxGroupTotal;
									}
								}
							}
							$order->info['total'] -= $totalDiscount;
							$order->info['tax'] -= $totalDiscount;
						}
					}
				}
			}
		}
		return $totalDiscount;
	}

	public function update_credit_account($cartProduct) {
		return false;
	}

	public function apply_credit() {
		global $insert_id, $userAccount;
		//$cc_id = $_SESSION['cc_id']; //Fred commented out, do not use $_SESSION[] due to backward comp. Reference the global var instead.
		if ($this->deduction != 0){
			$query = ("insert into coupon_redeem_track (coupon_id, redeem_date, redeem_ip, customer_id, order_id) values ('" . Session::get('cc_id') . "', now(), '" . $_SERVER['REMOTE_ADDR'] . "', '" . $userAccount->getCustomerId() . "', '" . $insert_id . "')");
			Doctrine_Manager::getInstance()
			->getCurrentConnection()
			->exec($query);
		}
		Session::remove('cc_id');
	}

	public function get_order_total() {
		global $order, $ShoppingCart, $userAccount, $onePageCheckout;

		$order_total = $order->info['total'];
		// Check if gift voucher is in cart and adjust total
		foreach($ShoppingCart->getProducts() as $cartProduct){
			if (preg_match('/^GIFT/', $cartProduct->getModel())){
				if ($this->include_tax == 'False'){
					$gv_amount = $cartProduct->getFinalPrice() * $cartProduct->getQuantity();
				}
				else {
					$gv_amount = ($cartProduct->getFinalPrice(true)) * $cartProduct->getQuantity();
				}
				$order_total -= $gv_amount;
			}
		}
		if ($this->include_tax == 'False'){
			$order_total = $order_total - $order->info['tax'];
		}
		if ($this->include_shipping == 'False'){
			$order_total = $order_total - $order->info['shipping_cost'];
		}
		/*if ($this->include_tax == 'False' && $this->include_shipping == 'False'){
			$order_total = $order->info['subtotal'];
		} */
		// OK thats fine for global coupons but what about restricted coupons
		// where you can only redeem against certain products/categories.
		// and I though this was going to be easy !!!
		if (Session::exists('cc_id')){
			$Qcoupon = Doctrine_Query::create()
				->from('Coupons')
				->where('coupon_id = ?', (int)Session::get('cc_id'))
				->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
			if ($Qcoupon){
				$Coupon = $Qcoupon[0];
				if (!empty($Coupon['restrict_to_purchase_type'])){
					$types = explode(',', $Coupon['restrict_to_purchase_type']);
					$totalPrice = 0;
					if($onePageCheckout->isMembershipCheckout()) {
						if(is_array($types) && in_array('membership', $types)) {
							$totalPrice = $order->products[0]['final_price'];
						}

					} else {
						foreach($ShoppingCart->getProducts() as $cartProduct){
							if (in_array($cartProduct->getPurchaseType(), $types)){
								$totalPrice += $cartProduct->getFinalPrice(($this->include_tax == 'True')) * $cartProduct->getQuantity();
								if($cartProduct->hasInfo('reservationInfo')){
									if($this->include_shipping == 'False'){
										$pInfo = $cartProduct->getInfo('reservationInfo');
										$totalPrice -= $pInfo['shipping']['cost'];
									}
								}
							}
						}
					}
					$order_total = $totalPrice;
				}
				if(!empty($Coupon['products_excluded'])){
					$products = explode(',', $Coupon['products_excluded']);
					foreach($ShoppingCart->getProducts() as $cartProduct){
						$pInfo = $cartProduct->getInfo();
						$pId = str_replace($pInfo['aID_string'],'', $pInfo['id_string']);
						if(in_array($pId, $products)){
							$order_total -= $cartProduct->getFinalPrice(($this->include_tax == 'True')) * $cartProduct->getQuantity();
						}
					}
				}
			}
		}
		return $order_total;
	}

	// START added by Rigadin in v5.13, needed to show module errors on checkout_payment page
	public function get_error() {
		if (isset($_GET['error'])){
			$error_req = $_GET['error'];
		}
		if (isset($_GET['amp;error'])){
			$error_req = $_GET['amp;error'];
		}
		$error = array('title' => sysLanguage::get('MODULE_ORDER_TOTAL_COUPON_TEXT_ERROR'),
			'error' => stripslashes(urldecode($error_req)));

		return $error;
	}
	// END added by Rigadin
}

?>