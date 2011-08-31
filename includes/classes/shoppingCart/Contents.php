<?php
/*
	Rental Store Version 2

	I.T. Web Experts
	http://www.itwebexperts.com

	Copyright (c) 2009 I.T. Web Experts

	This script and it's source is not redistributable
*/

	class ShoppingCartContents implements Countable, Iterator, Serializable {
		private $contents = array();
		private $position;
		private $dbUtil;
		
		public function __construct(){
			$this->position = 0;
			$this->dbUtil = new ShoppingCartDatabaseActions;
		}
		
		public function count(){
			return sizeof($this->contents);
		}
		
		public function rewind(){
			$this->position = 0;
		}
		
		public function current(){
			return $this->contents[$this->position];
		}
		
		public function key(){
			return $this->position;
		}
		
		public function next(){
			++$this->position;
		}
		
		public function valid(){
			return (isset($this->contents[$this->position]));
		}
		
		public function serialize(){
			return serialize($this->contents);
		}
		
		public function unserialize($data){
			$this->contents = unserialize($data);
			$this->dbUtil = new ShoppingCartDatabaseActions;
		}
		
		public function getContents(){
			return $this->contents;
		}
		
		public function remove($pID_string, $purchaseType){
			$key = $this->findKey($pID_string, $purchaseType);
			if ($key !== false){
				$this->removeKey($key);
			}
		}
		
		public function add(ShoppingCartProduct &$cartProduct, $runDbAction = true){
			$pID_string = $cartProduct->getIdString();
			$purchaseType = $cartProduct->getPurchaseType();
			$pInfo = $cartProduct->getInfo();
			
			$check = $this->findProductAsKey($cartProduct, $purchaseType);
			if ($check !== false){
				$action = 'update';
				$this->contents[$check] =& $cartProduct;
			}else{
				$action = 'insert';
				$this->contents[] =& $cartProduct;
			}
			
			EventManager::notify('AddToContentsBeforeProcess', &$pID_string, $cartProduct);
		
			if ($runDbAction === true){
				$this->dbUtil->runAction($action, $cartProduct);
			}

			EventManager::notify('AddToContentsAfterProcess', &$pID_string, $cartProduct);

			$this->cleanUp();
		}
		
		private function removeKey($key){
			$cartProduct = $this->contents[$key];
			$pID_string = $cartProduct->getIdString();
			$purchaseType = $cartProduct->getPurchaseType();
			
			$cartProduct->purchaseTypeClass->processRemoveFromCart();

			unset($this->contents[$key]);
			$this->contents = array_values($this->contents);
			
			$this->dbUtil->runAction('delete', $cartProduct);
		}
		
		private function cleanUp(){
			foreach($this->contents as $key => $cartProduct){
				if ($cartProduct->getQuantity() < 1){
					$this->removeKey($key);
				}
			}
		}
		
		public function &find($pID_string, $purchaseType = null){
			foreach($this->contents as $cartProduct){
				if($cartProduct->getUniqID() == $pID_string){
					return $cartProduct;
				}
			}
			return false;
		}

		public function findProductAsKey($cartProduct, $purchaseType = false){
			foreach($this->contents as $key => $iProduct){
				if ($iProduct->getIdString() == $cartProduct->getIdString()){
					if (($purchaseType == false)){
						return $key;
					}elseif ($iProduct->getPurchaseType() == $purchaseType){
						$returnVal = true;
						EventManager::notify('ShoppingCartFindKey', $iProduct, &$cartProduct, &$returnVal);
						if($returnVal){
							return $key;
						}
					}
				}
			}
			return false;

		}

		public function findProduct($pid, $purchaseType = false){

			foreach($this->contents as $key => $iProduct){
				if ($iProduct->getIdString() == $pid){
					if (($purchaseType == false)){
						return $iProduct;
					}elseif ($iProduct->getPurchaseType() == $purchaseType){
						$returnVal = true;
						EventManager::notify('ShoppingCartFind', $iProduct, &$returnVal);
						if($returnVal){
							return $iProduct;
						}
					}
				}
			}
			return false;

		}
		
		private function findKey($pID_string, $purchaseType = false){
			foreach($this->contents as $key => $cartProduct){
				if($cartProduct->getUniqID() == $pID_string){
					return $key;
				}
			}
			return false;
		}
	}
?>