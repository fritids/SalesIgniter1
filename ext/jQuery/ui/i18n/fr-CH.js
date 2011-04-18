/* Swiss-French Translations. */
/* Written Martin Voelkle (martin.voelkle@e-tc.ch). */
jQuery(function($){
	var langCode = 'fr-CH';
	var calSettings = {
		closeText: 'Fermer',
		prevText: '&#x3c;Pr�c',
		nextText: 'Suiv&#x3e;',
		currentText: 'Courant',
		monthNames: ['Janvier','F�vrier','Mars','Avril','Mai','Juin',
		'Juillet','Ao�t','Septembre','Octobre','Novembre','D�cembre'],
		monthNamesShort: ['Jan','F�v','Mar','Avr','Mai','Jun',
		'Jul','Ao�','Sep','Oct','Nov','D�c'],
		dayNames: ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
		dayNamesShort: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'],
		dayNamesMin: ['Di','Lu','Ma','Me','Je','Ve','Sa'],
		weekHeader: 'Sm',
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
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