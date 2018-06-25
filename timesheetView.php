<?php
require_once("header.php");//this is where session data is checked
global $resolution, $showWeekendDays, $startTime, $stopTime, $weekEndingDate, $minHours, $refid, $viewingMode, $variable, $submitted;
$variable = "false";

$db = new DB();
$viewingMode = "approve";
if(!isset($_GET["refid"])){
	error(_("Time sheet not found"));
}

$refid = intval($_GET["refid"]);
//checking if this user has access to the referenced timesheet
$oCredentials = new TimeSheetCredentials($refid);
if($oCredentials->isNull()){
	 error(_("Time sheet not found"));
}

if($oCredentials->isOwner()){
	$viewingMode = "userview";
}
elseif($oCredentials->isLineManager()){
	$viewingMode = "adminview";
}
elseif($_SESSION["user"]->isAdmin()){
	$viewingMode = "adminview";
}
elseif($oCredentials->isProjectManager()){
	$viewingMode = "approve";
}else{
	error(_("access denied"));
}
//setting variables related to the time sheet and its owner
$sql = "SELECT a.refid,a.fname,a.lname,a.minhours,b.submitted, b.enddate, b.guiresolution, b.showweekend, b.starttime, b.stoptime  
		FROM tbl_staff_lookup AS a,tbl_office_time_sheet AS b 
		WHERE a.refid = b.staffrefid AND b.refid = ".$refid;
$db->query($sql, Array('integer', 'text', 'text', 'float', 'boolean', 'text', 'float', 'boolean', 'text', 'text'));

$ownersName = $db->getElement("fname")." ".$db->getElement("lname");
$minHours = $db->getElement("minhours");
$weekEndingDate = $db->getElement("enddate");
$submitted = $db->getElement("submitted") ? "true" : "false";
$resolution = $db->getElement("guiresolution");
$showWeekendDays = $db->getElement("showweekend") ? "true" : "false";
$startTime = $db->getElement("starttime");
$stopTime = $db->getElement("stoptime");

$infoSectionContent = '';
if(strcmp($viewingMode,"userview")!=0) {
	$infoSectionContent = sprintf(_('This time sheet belongs to %s.'),'<span class="name">'.$ownersName.'</span>');
}
$submissionStatus = _('This time sheet has been <b>submitted</b>.');
if($submitted == "false") {
	$submissionStatus = _('This time sheet has <b>NOT</b> been <b>submitted</b>.');
}
//$infoSectionContent .= 'This time sheet has '.$submissionStatus.' been <b>submitted</b>.';
$infoSectionContent .= $submissionStatus;

$template = new Template(Array("subTitle" => _("time sheet"),
							"content" => "timesheet_.php",
							"hasInfoSection" => true, 
							"infoSectionContent" => $infoSectionContent,
							"scriptFiles" => Array(
									'yui/build/yahoo-dom-event/yahoo-dom-event.js',
									'yui/build/connection/connection.js',
									'yui/build/autocomplete/autocomplete.js',
									'js/ajaxRequestModule.js',
									'js/TimeSheetTableEntry.js',
									'js/TimeSheetTextEntry.js',
									'js/ColourObject.js',
									'js/DataHolders.js',
									'js/ErrorDisplay.js',
									"js/TimeSheet.js",
									"js/timesheetEditScripts.js")
						));
$template->display();
?>