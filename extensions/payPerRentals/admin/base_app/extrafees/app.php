<?php
	$appContent = $App->getAppContentFile();
	$App->addJavascriptFile('admin/rental_wysiwyg/ckeditor.js');
$App->addJavascriptFile('ext/jQuery/ui/jquery.ui.tabs.js');
	if (isset($_GET['tfID'])){
		$App->setInfoBoxId($_GET['tfID']);
	}
?>