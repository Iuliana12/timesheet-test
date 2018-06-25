<?php
require_once("header.php");
if(!$_SESSION["user"]->isAdmin()){
	error(_("access denied"));
	exit;
}
$costCentres = Array();
$db = new DB();
$sql = 'SELECT DISTINCT cost_centre FROM tbl_staff_lookup WHERE employed = true AND cost_centre IS NOT NULL';
$db->query($sql);
for ($i = 0; $i < $db->numRows(); ++$i){
	$cost_centre = htmlspecialchars($db->getElement("cost_centre"));
	$costCentres[] = $cost_centre;
 	$db->nextRow();
}
$template = new Template(Array("subTitle" => _("employees"),
								"content" => "employees_.php",
								"styleSheets" => Array("timesheets.css","yui/build/calendar/assets/calendar.css"),
								"scriptFiles" => Array('yui/build/yahoo-dom-event/yahoo-dom-event.js',
                                    'yui/build/calendar/calendar-min.js',
                                    'js/ajaxRequestModule.js',
                                    'js/employeesScripts.js'),
								"vars" => Array("costCentres" => $costCentres)));
$template->display();
?>