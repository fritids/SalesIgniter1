<?php
class productInventoryAttribute_barcode {
	public function __construct($invData, $aID = false){
		$this->aID_string = null;
		if($aID !== false){
			$this->aID_string = attributesUtil::getAttributeString($aID);
		}
		if (isset($_POST['id'][$invData['type']])){
			$this->aID_string = attributesUtil::getAttributeString($_POST['id'][$invData['type']]);
		}
		$this->invData = $invData;
		$this->invUnavailableStatus = array(
			'B', //Broken
			//'O', //Out
			'P'  //Purchased
		);
	}

	public function hasInventory(){

		$Qcheck = Doctrine_Query::create()
		->select('count(ib.barcode_id) as total')
		->from('ProductsInventoryBarcodes ib')
		->where('ib.inventory_id = ?', $this->invData['inventory_id'])
		->andWhereNotIn('ib.status', $this->invUnavailableStatus);
		
		if (is_null($this->aID_string) === false){
			$attributePermutations = attributesUtil::permutateAttributesFromString($this->aID_string);
			
			$Qcheck->andWhereIn('ib.attributes', $attributePermutations);
		}
		
		EventManager::notify('ProductInventoryBarcodeHasInventoryQueryBeforeExecute', $this->invData, &$Qcheck);

		$Result = $Qcheck->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
		return ($Result[0]['total'] > 0);
	}

	public function getInventoryItemCount(){
		$count = 0;

		$invItems = $this->getInventoryItems();
		foreach($invItems as $invItem){
			$addTotal = true;

			EventManager::notify('ProductInventoryBarcodeGetItemCount', &$this->invData, &$invItem, &$addTotal);

			if ($addTotal === true){
				$count++;
			}
		}
		return $count;
	}

	public function getTotalInventoryItemCount(){
		$Qcheck = Doctrine_Query::create()
				->from('ProductsInventoryBarcodes ib')
				->where('ib.inventory_id = ?', $this->invData['inventory_id'])
				->andWhereNotIn('ib.status', $this->invUnavailableStatus);
		if (is_null($this->aID_string) === false){
			$attributePermutations = attributesUtil::permutateAttributesFromString($this->aID_string);
			$Qcheck->andWhereIn('ib.attributes', $attributePermutations);
		}
		EventManager::notify('ProductInventoryBarcodeGetInventoryItemsQueryBeforeExecute', $this->invData, &$Qcheck);
		$Result = $Qcheck->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
		return count($Result);
	}
	public function updateStock($orderId, $orderProductId, &$cartProduct){
		$aID_string = attributesUtil::getAttributeString($cartProduct->getInfo('attributes'));
		$attributePermutations = attributesUtil::permutateAttributesFromString($aID_string);
		
		if ($this->invData['type'] == 'new' || $this->invData['type'] == 'used'){
			$Qcheck = Doctrine_Query::create()
			->select('ib.barcode_id')
			->from('ProductsInventoryBarcodes ib')
			->where('ib.status = ?', 'A')
			->andWhere('ib.inventory_id = ?', $this->invData['inventory_id'])
			->andWhereIn('ib.attributes', $attributePermutations)
			->limit('1');
			
			EventManager::notify('ProductInventoryBarcodeUpdateStockQueryBeforeExecute', $this->invData, &$Qcheck);
			
			$Result = $Qcheck->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
			if ($Result){
				$Barcode = Doctrine_Core::getTable('ProductsInventoryBarcodes')->find($Result[0]['barcode_id']);
				$Barcode->status = 'P';
				$Barcode->save();
				
				Doctrine_Query::create()
				->update('OrdersProducts')
				->set('barcode_id', '?', (int)$Barcode->barcode_id)
				->where('orders_products_id = ?', (int)$orderProductId)
				->execute();
			}
		}
	}

	public function addStockToCollection(&$Product, &$CollectionObj){
		global $Editor;
		if ($this->invData['type'] == 'new' || $this->invData['type'] == 'used' || $this->invData['type'] == 'reservation'){
			$pInfo = $Product->getPInfo();
			$Qcheck = Doctrine_Query::create()
			->select('ib.barcode_id')
			->from('ProductsInventoryBarcodes ib')
			->where('ib.status = ?', 'A')
			->andWhere('ib.inventory_id = ?', $this->invData['inventory_id']);
			if(isset($pInfo['usableBarcodes']) && count($pInfo['usableBarcodes']) > 0){
				$Qcheck->andWhereIn('ib.barcode_id', $pInfo['usableBarcodes']);
			}
			$Qcheck->limit('1');

			if (is_null($this->aID_string) === false){
				$attributePermutations = attributesUtil::permutateAttributesFromString($this->aID_string);

				$Qcheck->andWhereIn('ib.attributes', $attributePermutations);
			}
			EventManager::notify('ProductInventoryBarcodeUpdateStockQueryBeforeExecute', $this->invData, &$Qcheck);

			$Result = $Qcheck->execute();
			if ($Result){
				$CollectionObj->barcode_id = (int) $Result[0]->barcode_id;
				$CollectionObj->ProductsInventoryBarcodes->status = 'P';
			}else{
				$Editor->addErrorMessage('There is no inventory for the estimate. Please reselect.');
			}
		}
	}

	public function getInventoryItems(){
		$barcodes = array();

		$Qcheck = Doctrine_Query::create()
		->from('ProductsInventoryBarcodes ib')
		->where('ib.inventory_id = ?', $this->invData['inventory_id'])
		->andWhereNotIn('ib.status', $this->invUnavailableStatus);
		
		if (is_null($this->aID_string) === false){
			$attributePermutations = attributesUtil::permutateAttributesFromString($this->aID_string);
			$Qcheck->andWhereIn('ib.attributes', $attributePermutations);
		}
		
		EventManager::notify('ProductInventoryBarcodeGetInventoryItemsQueryBeforeExecute', $this->invData, &$Qcheck);
		
		$Result = $Qcheck->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
		if ($Result){
			foreach($Result as $i => $barcode){
				$barcodes[$i] = array(
					'id'           => $barcode['barcode_id'],
					'barcode'      => $barcode['barcode'],
					'inventory_id' => $barcode['inventory_id'],
					'status'       => $barcode['status']
				);
				
				EventManager::notify('ProductInventoryBarcodeGetInventoryItemsArrayPopulate', $barcode, &$barcodes[$i]);
			}
		}
		return $barcodes;
	}
}
?>