﻿/* Faroese Translations */
/* Written by Sverri Mohr Olsen, sverrimo@gmail.com */
jQuery(function($){
	var langCode = 'fo';
	var calSettings = {
		closeText: 'Lat aftur',
		prevText: '&#x3c;Fyrra',
		nextText: 'Næsta&#x3e;',
		currentText: 'Í dag',
		monthNames: ['Januar','Februar','Mars','Apríl','Mei','Juni',
		'Juli','August','September','Oktober','November','Desember'],
		monthNamesShort: ['Jan','Feb','Mar','Apr','Mei','Jun',
		'Jul','Aug','Sep','Okt','Nov','Des'],
		dayNames: ['Sunnudagur','Mánadagur','Týsdagur','Mikudagur','Hósdagur','Fríggjadagur','Leyardagur'],
		dayNamesShort: ['Sun','Mán','Týs','Mik','Hós','Frí','Ley'],
		dayNamesMin: ['Su','Má','Tý','Mi','Hó','Fr','Le'],
		weekHeader: 'Vk',
		dateFormat: 'dd-mm-yy',
		firstDay: 0,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: ''
	};

	if ($.datepicker){
		$.datepicker.regional[langCode] = calSettings;
		$.datepicker.setDefaults($.datepicker.regional[langCode]);
	}

	if ($.datepick){
		$.datepick.regional[langCode] = calSettings;
		$.datepick.setDefaults($.datepick.regional[langCode]);
	}
});
