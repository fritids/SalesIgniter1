/*
	Info Pages Extension Version 1
	
	I.T. Web Experts, Rental Store v2
	http://www.itwebexperts.com

	Copyright (c) 2009 I.T. Web Experts

	This script and it's source is not redistributable
*/
$(document).ready(function (){
	$('.makeFCK').ckeditor(function (){
	}, {
		//filebrowserBrowseUrl: DIR_WS_ADMIN + 'rentalwysiwyg/editor/filemanager/browser/default/browser.php'
		filebrowserBrowseUrl: DIR_WS_ADMIN + 'rental_wysiwyg/filemanager/index.php'
	});
	
	$('#languageTabs').tabs();
});