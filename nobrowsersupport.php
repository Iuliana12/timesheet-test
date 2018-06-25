<?php
require_once 'functions.php';
setLocales();
$text1 = _("We are sorry, but you are currently using a non standards-supporting browser!<br/>Time Recording System has been tested successfully under Firefox, Chrome and Opera.");
$text2 = _("Please download the latest Firefox  browser from ");

$content = '
<div id="mainFormContainer" class="mainFormTextLabel1" style="width:100%;height:100%;text-transform: none">
	'.$text1.'
	<br/>
	'.$text2.' <a href="http://www.mozilla.com/en-US/firefox/" target="_blank">'._('here').'</a>.
   	<br/>
	<br/>
</div>
';
require_once('classes/Template.php');
$template = new Template(Array(
	"subTitle" => _("ERROR"),
	"content" => $content,
	"printContent" => true,
	"hasMenu" => false));
$template->display();
?>

