<?php
	$Qcurrencies = Doctrine_Query::create()
	->select('*')
	->from('CurrenciesTable')
	->orderBy('title');
	
	EventManager::notify('CurrencyListingQueryBeforeExecute', &$Qcurrencies);
	
	$tableGrid = htmlBase::newElement('grid')
	->usePagination(true)
	->setPageLimit((isset($_GET['limit']) ? (int)$_GET['limit']: 25))
	->setCurrentPage((isset($_GET['page']) ? (int)$_GET['page'] : 1))
	->setQuery($Qcurrencies);

	$tableGrid->addHeaderRow(array(
		'columns' => array(
			array('text' => sysLanguage::get('TABLE_HEADING_CURRENCY_NAME')),
			array('text' => sysLanguage::get('TABLE_HEADING_CURRENCY_CODES')),
			array('text' => sysLanguage::get('TABLE_HEADING_CURRENCY_VALUE')),
			array('text' => sysLanguage::get('TABLE_HEADING_ACTION'))
		)
	));
	
	$Result = &$tableGrid->getResults();
	if ($Result){
		$allGetParams = tep_get_all_get_params(array('action', 'cID'));
		foreach($Result as $currency){
			$currencyId = $currency['currencies_id'];
			$currencyTitle = $currency['title'];
			$currencyCode = $currency['code'];
			$currencyValue = $currency['value'];
			
			if ((!isset($_GET['cID']) || $_GET['cID'] == $currencyId) && !isset($cInfo) && substr($action, 0, 3) != 'new'){
				$cInfo = new objectInfo($currency);
			}
			
			$arrowIcon = htmlBase::newElement('icon')->setType('info')
			->setHref(itw_app_link($allGetParams . 'cID=' . $currencyId));
			
			$addCls = '';
			$onClickLink = itw_app_link($allGetParams . 'cID=' . $currencyId);
			if (isset($cInfo) && $currencyId == $cInfo->currencies_id){
				$addCls = 'ui-state-default';
				$onClickLink .= '&action=edit';
				$arrowIcon->setType('circleTriangleEast');
			}

			if (sysConfig::get('DEFAULT_CURRENCY') == $currencyCode){
				$currencyTitle = '<b>' . $currencyTitle . ' (' . sysLanguage::get('TEXT_DEFAULT') . ')</b>';
		    }

			$tableGrid->addBodyRow(array(
				'addCls'  => $addCls,
				'click'   => 'document.location=\'' . $onClickLink . '\'',
				'columns' => array(
					array('text' => $currencyTitle),
					array('text' => $currencyCode),
					array('text' => number_format($currencyValue, 8), 'align' => 'right'),
					array('text' => $arrowIcon->draw(), 'align' => 'right')
				)
			));
		}
	}
	
	$infoBox = htmlBase::newElement('infobox');

	switch($action){
		case 'new':
		case 'edit':
			$infoBox->setHeader('<b>' . (isset($cInfo) ? sysLanguage::get('TEXT_INFO_HEADING_EDIT_CURRENCY') : TEXT_INFO_HEADING_NEW_CURRENCY) . '</b>');

			$infoBox->setForm(array(
				'name'   => 'currencies',
				'action' => itw_app_link($allGetParams . 'action=save' . (isset($cInfo) ? '&cID=' . $cInfo->currencies_id : ''))
			));
			
			$saveButton = htmlBase::newElement('button')->setType('submit')->usePreset('save');
			$cancelButton = htmlBase::newElement('button')->usePreset('cancel')
			->setHref(itw_app_link($allGetParams . 'cID=' . $cInfo->currencies_id));
			
			$infoBox->addButton($saveButton)->addButton($cancelButton);
				
			$infoBox->addContentRow((isset($cInfo) ? sysLanguage::get('TEXT_INFO_EDIT_INTRO') : TEXT_INFO_INSERT_INTRO));

			$titleInput = htmlBase::newElement('input')->setName('title');
			$codeInput = htmlBase::newElement('input')->setName('code');
			$symbolLeftInput = htmlBase::newElement('input')->setName('symbol_left');
			$symbolRightInput = htmlBase::newElement('input')->setName('symbol_right');
			$decimalPointInput = htmlBase::newElement('input')->setName('decimal_point');
			$thousandsPointInput = htmlBase::newElement('input')->setName('thousands_point');
			$decimalPlacesInput = htmlBase::newElement('input')->setName('decimal_places');
			$valueInput = htmlBase::newElement('input')->setName('value');
			
			if (isset($cInfo)){
				$titleInput->setValue($cInfo->title);
				$codeInput->setValue($cInfo->code);
				$symbolLeftInput->setValue($cInfo->symbol_left);
				$symbolRightInput->setValue($cInfo->symbol_right);
				$decimalPointInput->setValue($cInfo->decimal_point);
				$thousandsPointInput->setValue($cInfo->thousands_point);
				$decimalPlacesInput->setValue($cInfo->decimal_places);
				$valueInput->setValue($cInfo->value);
			}
			
			$formTable = htmlBase::newElement('formTable')->setAddClass('main');
			$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_TITLE'), $titleInput);
			$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_CODE'), $codeInput);
			$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_SYMBOL_LEFT'), $symbolLeftInput);
			$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_SYMBOL_RIGHT'), $symbolRightInput);
			$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_DECIMAL_POINT'), $decimalPointInput);
			$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_THOUSANDS_POINT'), $thousandsPointInput);
			$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_DECIMAL_PLACES'), $decimalPlacesInput);
			$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_VALUE'), $valueInput);
			
			if ($cInfo->code != DEFAULT_CURRENCY){
				$defaultInput = htmlBase::newElement('checkbox')->setName('default')->setLabel(sysLanguage::get('TEXT_INFO_SET_AS_DEFAULT'));
				$formTable->addRow($defaultInput);
			}
			
			$infoBox->addContentRow($formTable);
			break;
		case 'delete':
			$infoBox->setHeader('<b>' . sysLanguage::get('TEXT_INFO_HEADING_DELETE_CURRENCY') . '</b>');
			$infoBox->setForm(array(
				'name'   => 'reviews',
				'action' => itw_app_link($allGetParams . 'action=deleteConfirm&cID=' . $cInfo->currencies_id)
			));

			$cancelButton = htmlBase::newElement('button')->usePreset('cancel')
			->setHref(itw_app_link($allGetParams . 'cID=' . $cInfo->currencies_id));
			
			if ($cInfo->code == DEFAULT_CURRENCY){
				$infoBox->addContentRow(sysLanguage::get('ERROR_REMOVE_DEFAULT_CURRENCY'));
			}else{
				$deleteButton = htmlBase::newElement('button')->setType('submit')->usePreset('delete');
				$infoBox->addButton($deleteButton);
			
				$infoBox->addContentRow(sysLanguage::get('TEXT_INFO_DELETE_INTRO'));
				$infoBox->addContentRow('<b>' . $cInfo->title . '</b>');
			}

			$infoBox->addButton($cancelButton);
			break;
		default:
			if (isset($cInfo)){
				$infoBox->setButtonBarLocation('top');
				$infoBox->setHeader('<b>' . $cInfo->title . '</b>');

				$editButton = htmlBase::newElement('button')->usePreset('edit')
				->setHref(itw_app_link($allGetParams . 'action=edit&cID=' . $cInfo->currencies_id));
				
				$infoBox->addButton($editButton);
				
				if ($cInfo->code != DEFAULT_CURRENCY){
					$deleteButton = htmlBase::newElement('button')->usePreset('delete')
					->setHref(itw_app_link($allGetParams . 'action=delete&cID=' . $cInfo->currencies_id));
					
					$infoBox->addButton($deleteButton);
				}
				
				$formTable = htmlBase::newElement('formTable')->setAddClass('main');
				$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_TITLE'), $cInfo->title);
				$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_CODE'), $cInfo->code);
				$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_SYMBOL_LEFT'), $cInfo->symbol_left);
				$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_SYMBOL_RIGHT'), $cInfo->symbol_right);
				$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_DECIMAL_POINT'), $cInfo->decimal_point);
				$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_THOUSANDS_POINT'), $cInfo->thousands_point);
				$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_DECIMAL_PLACES'), $cInfo->decimal_places);
				$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_LAST_UPDATED'), tep_date_short($cInfo->last_updated));
				$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_VALUE'), number_format($cInfo->value, 8));
				$formTable->addRow(sysLanguage::get('TEXT_INFO_CURRENCY_EXAMPLE'), $currencies->format('30', false, DEFAULT_CURRENCY) . ' = ' . $currencies->format('30', true, $cInfo->code));

				$infoBox->addContentRow($formTable);
			}
			break;
	}
?>
 <div class="pageHeading"><?php echo sysLanguage::get('HEADING_TITLE');?></div>
 <br />
 <div style="width:75%;float:left;">
  <div class="ui-widget ui-widget-content ui-corner-all" style="width:99%;margin-right:5px;margin-left:5px;">
   <div style="width:99%;margin:5px;">
   <?php echo $tableGrid->draw();?>
   <?php if (empty($action)){ ?>
   <table border="0" width="100%" cellspacing="0" cellpadding="2">
    <tr>
     <td><?php
      if (CURRENCY_SERVER_PRIMARY){
      	echo htmlBase::newElement('button')->usePreset('update')->setText(sysLanguage::get('TEXT_BUTTON_UPDATE_CURRENCIES'))
      	->setHref(itw_app_link($allGetParams . 'action=updateExchange&cID=' . $cInfo->currencies_id))
      	->draw();
      }
     ?></td>
     <td align="right"><?php
      echo htmlBase::newElement('button')->usePreset('insert')->setText(sysLanguage::get('TEXT_BUTTON_NEW_CURRENCY'))
      ->setHref(itw_app_link($allGetParams . 'action=new'))
      ->draw();
     ?></td>
    </tr>
   </table>
   <?php } ?>
   </div>
  </div>
 </div>
 <div style="width:25%;float:right;"><?php echo $infoBox->draw();?></div>