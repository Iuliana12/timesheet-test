<?php
require_once("header.php");
$nrOfWeeksAheadAllowed = 6;

if(!isset($status)){
	if(isset($_GET["status"])){
		$status = $_GET["status"];
	}else{
		$status = "";
	}
}

//if the admin is creating a new time sheet for someone else
if(isset($_GET["employee"]) && $_SESSION["user"]->isAdmin())
	$employee = intval($_GET["employee"]);
else
	$employee = $_SESSION["user"]->refid;

if(isset($_REQUEST["friday"])){
	$thisFridayTime = strtotime($_REQUEST["friday"]);
}
else{
	$dateAttribArray = getdate();
	if($dateAttribArray["wday"]== 5)
		$thisFridayTime = time();
	else
		$thisFridayTime = strtotime("next Friday");
}
$thisFriday = date("Y-m-d",$thisFridayTime);
$timeSheetWeekStart = _("Sat").' '.strftime("%d %b",strtotime("-6 days",$thisFridayTime));
$timeSheetWeekEnd = _("Fri").' '.strftime("%d %b",$thisFridayTime);
	
//implicit values
$vars = Array("resolution" => "30",
	"showWeekendDays" => false,
	"startTime" => "09:00",
	"stopTime" => "17:30",
	"status" => $status);
$db = new DB();
$sql = "SELECT showweekend, resolution, starttime, stoptime FROM tbl_staff_preferences WHERE staff_refid = ".$employee;
$db->query($sql, Array('boolean', 'float', 'text', 'text'));
if($db->numRows() > 0){
	//variables from DB as implicit
	$resolution = $db->getElement("resolution");
	if($resolution < 1){
		$resolution *= 60;
	}
	$showWeekendDays = formatBoolean($db->getElement("showweekend"),true,false);
	$startTime = $db->getElement("starttime");
	$stopTime = $db->getElement("stoptime");
	if ($startTime != '' && $stopTime != '') {
		$vars["resolution"] = $resolution;
		$vars["showWeekendDays"] = $showWeekendDays;
		$vars["startTime"] = $startTime;
		$vars["stopTime"] = $stopTime;
	}
}
$vars["employee"] = $employee;
$vars["thisFriday"] = $thisFriday;
$vars["timeSheetWeekStart"] = $timeSheetWeekStart;
$vars["timeSheetWeekEnd"] = $timeSheetWeekEnd;

$template = new Template(Array("subTitle" => _("new time sheet"),
								"content" => "timesheetNew_.php",
								"styleSheets" => Array("timesheets.css","yui/build/calendar/assets/calendar.css"),
								"scriptFiles" => Array(
                                    'yui/build/yahoo-dom-event/yahoo-dom-event.js',
                                    'yui/build/calendar/calendar-min.js',
                                    'js/newTimesheetScripts.js'),
								"vars" => $vars
							));
$template->display();
?>

