<?php
class productListing_membershipRental {
	public function sortColumns(){
		$selectSortKeys = array(

		);
		return $selectSortKeys;
	}

	public function show(&$productClass, &$purchaseTypesCol){
		global $rentalQueue;
		$purchaseTypeClass = $productClass->getPurchaseType('rental');

		if (is_null($purchaseTypeClass) === false && $purchaseTypeClass->hasInventory()){
			$rentNowButton = htmlBase::newElement('button')
			->setText(sysLanguage::get('TEXT_BUTTON_IN_QUEUE'));
			if($productClass->isBox()){
				$rentNowButton->setHref(itw_app_link(tep_get_all_get_params(array('action')) . 'action=add_queue_all&products_id=' . $productClass->getID()), true);
			}else{
				$rentNowButton->setHref(itw_app_link(tep_get_all_get_params(array('action')) . 'action=rent_now&products_id=' . $productClass->getID()), true);
			}

			if ($rentalQueue->in_queue($productClass->getID()) === true){
				$rentNowButton->disable();				
			}


			
			return $rentNowButton->draw();
		}
		return false;
	}
}
?>