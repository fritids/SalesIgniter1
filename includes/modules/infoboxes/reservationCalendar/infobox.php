<?php
/*
	I.T. Web Experts, Rental Store v2
	http://www.itwebexperts.com

	Copyright (c) 2009 I.T. Web Experts

	This script and it's source is not redistributable
*/

class InfoBoxReservationCalendar extends InfoBoxAbstract {

	public function __construct(){
		global $App;
		$this->init('reservationCalendar');
	}

	public function show(){
			global $appExtension;
			ob_start();
			require(sysConfig::getDirFsCatalog() . 'extensions/payPerRentals/catalog/base_app/build_reservation/pages/default.php');
			echo '<script type="text/javascript" src="'.sysConfig::getDirWsCatalog() . 'extensions/payPerRentals/catalog/base_app/build_reservation/javascript/default.js'.'"></script>';
			$pageHtml = ob_get_contents();
			ob_end_clean();
			$this->setBoxContent($pageHtml);
			return $this->draw();
	}
}
?>