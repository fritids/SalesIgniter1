<?php
/*
	Inventory Centers Extension Version 1
	
	I.T. Web Experts, Rental Store v2
	http://www.itwebexperts.com

	Copyright (c) 2009 I.T. Web Experts

	This script and it's source is not redistributable
*/

class inventoryCenters_admin_products_default extends Extension_inventoryCenters {

	public function __construct(){
		parent::__construct();
	}
	
	public function load(){
		global $appExtension;
		if ($this->enabled === false) return;

			EventManager::attachEvents(array(
				'ProductsDefaultAddFilterOptions',
				'ProductListingQueryBeforeExecute'
			), null, $this);
	}
	
	public function ProductsDefaultAddFilterOptions(){
		$searchForm = htmlBase::newElement('form')
		->attr('name', 'search')
		->attr('action', itw_app_link(null, null, null, 'SSL'))
		->attr('method', 'get');
		
		$submitb = htmlBase::newElement('button')
		->setType('submit')
		->setText('GO')
		->setName('submitb');
		
		$searchField = htmlBase::newElement('selectbox')
		->setName('search_inv_center');
		$searchField->addOption('-1','None');

		$Qinv = Doctrine_Query::create()
		->from('ProductsInventoryCenters')
		->execute(array(),Doctrine_Core::HYDRATE_ARRAY);
		if ($Qinv){
			foreach($Qinv as $qi){
				$searchField->addOption($qi['inventory_center_id'], $qi['inventory_center_name']);
			}
		}
		
		if (isset($_GET['search_inv_center'])){
			$searchField->selectOptionByValue($_GET['search_inv_center']);
		}
		
		$searchForm->append($searchField)->append($submitb);
		return $searchForm->draw();
	}
	
	public function ProductListingQueryBeforeExecute(&$Qproducts){
		if (isset($_GET['search_inv_center'])) {
			$search = (int)$_GET['search_inv_center'];
			if ($search > 0){
				$Qproducts->leftJoin('p.ProductsInventory pi')
				->leftJoin('pi.ProductsInventoryBarcodes pib')
				->leftJoin('pib.ProductsInventoryBarcodesToInventoryCenters piq')
				->leftJoin('piq.ProductsInventoryCenters pic')
				->andWhere('pic.inventory_center_id = ?', $search)
				->andWhere('pi.use_center = ?', '1');
			}
			//add a new condition to see if inventory center has inventory?
		}
	}
}
?>