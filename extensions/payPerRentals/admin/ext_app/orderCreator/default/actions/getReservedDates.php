<?php
	$pID_string = $_POST['products_id'];
	$purchaseTypeClasses = array();

	$purchaseTypeClasses = array();
	foreach($pID_string as $pElem){
		$product = new product($pElem);
		$purchaseTypeClasses[] = $product->getPurchaseType('reservation');
	}
	$isChecked= isset($_POST['hasOverride'])?true:false;
	$calendar = ReservationUtilities::getCalendar($pID_string, $purchaseTypeClasses, (isset($_POST['rental_qty'])?$_POST['rental_qty']:1), true,'catalog',array(),true, $isChecked);

	EventManager::attachActionResponse(array(
		'success' => true,
		'calendar' => $calendar
	), 'json');
?>