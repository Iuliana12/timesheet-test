<?php
require_once 'functions.php';
setLocales();

$content = '
<div id="mainFormContainer" class="mainFormTextLabel1" style="width:100%;height:100%;text-transform: none">
	'. _("An error has been reported: ").'
	<br/><br/>
	<span class="highlightRed">
	'.$message.'
	</span>
	<br/><br/>
	<input type="button" value="'._("go back").'" onclick="window.back()"/>
   	<br/><br/>
</div>
';
require_once('classes/Template.php');
$template = new Template(Array("subTitle" => _("ERROR"),
	"content" => $content,
	"printContent" => true,
	"hasMenu" => false));
$template->display();
?>