<?php global $status;
require_once('header.php');
if(isset($_SESSION["user"])){
	$location = 'timesheetList';
	require_once($location.".php");
}
else{
	$template = new Template(Array("subTitle" => _("notice"),
									"content" => '<br/><br/>'.$status,
									"printContent" => true,
									"styleSheets" => Array("timesheets.css")
	));
	$template->display();
}
?>
