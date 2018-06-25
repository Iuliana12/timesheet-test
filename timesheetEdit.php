<?php
require_once("header.php");

//VALUES NEEDED FROM THE db: minHours
//values that the user can edit but they have an implicit value taken from the DB: resolution, showWeekendDays, startTime, stopTime
//values always inputed by the user: weekEndingDate

global $resolution, $showWeekendDays, $startTime, $stopTime, $weekEndingDate, $minHours, $refid, $viewingMode, $variable, $submitted;
$nrOfWeeksAheadAllowed = 6;
$submitted = "false";
$transferred = "false";
$viewingMode = "edit";
$minHours=37.5;
$variable = "false";
$showWeekendDays = "false";
$transferred = false;
$db = new DB();

//this is the time sheet's reference id
if(isset($_GET["refid"])){
	$refid = intval($_GET["refid"]);
}
//if an administrator user is creating a new time sheet for another employee
if(isset($_POST["employee"]) && $_SESSION["user"]->isAdmin()){
	$employee = intval($_POST["employee"]);
	$_SESSION["adminCreatingTimeSheetFor"] = $employee;
}
else{
	$employee = $_SESSION["user"]->refid;
	unset($_SESSION["adminCreatingTimeSheetFor"]);
}

//variables from DB, stored in SESSION vars
$minHours = $_SESSION["user"]->minHours;
if($_SESSION["user"]->variable){
	$variable = "true";
}
//searching for the given date in the database
if(isset($_POST["weekendingdate"])){
	$weekEndingDate = strtolower(trim($_POST["weekendingdate"]));
	$sql = "SELECT refid FROM tbl_office_time_sheet WHERE staffrefid = ".$employee." AND enddate = ".$db->quote($weekEndingDate);
	$db->query($sql, Array('integer'));
	if($db->numRows() ==1){
		$refid = $db->getElement("refid");
	}
}
//if the refid is specified then we're editing an existing time sheet
if(isset($refid)){

	//checking if this user has access to the referenced timesheet
	$oCredentials = new TimeSheetCredentials($refid);
	if($oCredentials->isNull()){
		 error(_("Time sheet not found"));
	}
	if(!$oCredentials->canEdit()){
		 error($oCredentials->reasonForCanEditFalse);
	}
	$sql = "SELECT submitted, transferred, enddate, showweekend, guiresolution, starttime, stoptime
			FROM tbl_office_time_sheet WHERE refid = ".$refid;
	$db->query($sql,Array('boolean','boolean','text','boolean','float','text','text'));
	$weekEndingDate = $db->getElement("enddate");
	$submitted = formatBoolean($db->getElement("submitted"),"true","false");
  $transferred = formatBoolean($db->getElement("transferred"),true,false);
	$resolution = $db->getElement("guiresolution");
	$showWeekendDays = formatBoolean($db->getElement("showweekend"),"true","false");
	$startTime = $db->getElement("starttime");
	$stopTime = $db->getElement("stoptime");
	//if this is the case of an administrator editing this time sheet
	if(!$oCredentials->isOwner() && $_SESSION["user"]->isAdmin()){
		define("ADMINEDIT",true);
		//setting variables related to the owner of the timesheet
		$sql = "SELECT a.fname,a.lname,a.minhours,a.variable FROM tbl_staff_lookup AS a,tbl_office_time_sheet AS b
				WHERE a.refid = b.staffrefid AND b.refid = ".$refid;
		$db->query($sql, Array('text', 'text', 'float', 'boolean'));
		$ownersName = $db->getElement("fname")." ".$db->getElement("lname");
		$minHours = $db->getElement("minhours");
		$variable = $db->getElement("variable");
		$viewingMode = "adminedit";
	}
}
else//creating a new time sheet
{
	if(!isset($_POST["resolution"]) || !isset($_POST["starttime"]) || !isset($_POST["stoptime"]) || !isset($_POST["weekendingdate"])){
		require_once("timesheetNew.php");
		exit;
	}
	//variables from DB as implicit will be transfered via POST whether they were modified or not
	$resolution = intval(trim($_POST["resolution"]));
	if(isset($_POST["showweekend"]) && $_POST["showweekend"] == 'on') {
		$showWeekendDays="true";
	}
	$startTime = strtolower(trim($_POST["starttime"]));
	if(preg_match('/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/',$startTime)===FALSE)	{
		$status = _("The start time specified is invalid");
		require_once("timesheetNew.php");
		exit;
	}
	$stopTime= strtolower(trim($_POST["stoptime"]));
	if(preg_match('/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/',$stopTime)===FALSE){
		$status = _("The stop time specified is invalid");
		require_once("timesheetNew.php");
		exit;
	}
	//checking that stop time is not earlier than start time
	if ($startTime == $stopTime) {
		$status = _("Invalid time span chosen!");
		require_once("timesheetNew.php");
		exit;
	}
	$time1Arr = explode(":",$startTime);
	$time2Arr = explode(":",$stopTime);
	if (intval($time1Arr[0]) > intval($time2Arr[0]) || (intval($time1Arr[0]) == intval($time2Arr[0]) && intval($time1Arr[1]) > intval($time2Arr[1]) ) ) {
		if(!(intval($time2Arr[0]) == 0 && intval($time2Arr[1]) == 0 && intval($time1Arr[0]) > 0)){
			$status = _("Invalid time span chosen!");
			require_once("timesheetNew.php");
			exit;
		}
	}
	//variables that always need user's attention
	$weekEndingDate = strtolower(trim($_POST["weekendingdate"]));
	if(preg_match('/^([0-9]{4})-(0[1-9]|1[0-2])-([12][0-9]|0[1-9]|3[01])$/',$weekEndingDate)===FALSE){
		$status = _("The date you specified is invalid!");
		require_once("timesheetNew.php");
		exit;
	}
	//checking if the given date is a friday
	$attributes = getdate(strtotime($weekEndingDate));
	if($attributes["wday"] != 5){
		$status = _("Sorry, we need it to be a Friday");
		require_once("timesheetNew.php");
		exit;
	}
	$seconds = 60 * 60 * 24 * 7 * ($nrOfWeeksAheadAllowed-1);

	if(strtotime($weekEndingDate) > strtotime("next Friday")+$seconds){
		$status = _("Sorry, you can not edit that week's time sheet at this time");
		require_once("timesheetNew.php");
		exit;
	}
	if ( isset($_SESSION["adminCreatingTimeSheetFor"])) {
		define("ADMINEDIT",true);
		//setting variables related to the owner of the timesheet
		$sql = "SELECT fname,lname,minhours,variable FROM tbl_staff_lookup WHERE refid = ".$employee;
		$db->query($sql, Array('text', 'text', 'float', 'boolean'));
		if($db->numRows() == 0) {
			$status = _("An error has occured, please contact the IT department!");
			require_once("timesheetNew.php");
			exit;
		}
		$ownersName = $db->getElement("fname")." ".$db->getElement("lname");
		$minHours = $db->getElement("minhours");
		$variable = $db->getElement("variable");
		$viewingMode = "adminedit";
	}

	//saving user preferences for future use
	$values = Array("resolution" => $resolution,
					"starttime" => $startTime,
					"stoptime" => $stopTime,
					"showweekend" => $showWeekendDays );
	$where = "staff_refid = ".$employee;
	$db->autoExec('tbl_staff_preferences', $values, MDB2_AUTOQUERY_UPDATE, $where);
	if($db->getAffectedRows() == 0){
		//if the user has no preferences set yet
		$values ["staff_refid"] = $employee;
		$db->autoExec('tbl_staff_preferences', $values, MDB2_AUTOQUERY_INSERT);
	}
	$refid = -1;//refid needs to be negative to indicate the creation of a new one.
}

if(defined("ADMINEDIT")){
	$infoSectionContent = _("This time sheet belongs to ").'<span class="name">'.$ownersName.'</span>';
}
else
	$infoSectionContent = _('Click inside the table to select your worked intervals');
if($transferred){
    $infoSectionContent .= '<b><span class="highlightRed"> '._("WARNING ")._("You are editing a TRANSFERRED time sheet!").'</span></b>';
}
$infoSectionContent .= '
				<div id="invoice_list">
					'._("Common unfunded invoice codes").'
					<ul>
						<li><span class="code">HOL</span> - <span class="descriprion">'._("holidays").'</span></li>
						<li><span class="code">BH</span> - <span class="descriprion">'._("<b>BANK</b> holidays").'</span></li>
						<li><span class="code">TOIL</span> - <span class="descriprion">'._("time off in lieu").'</span></li>
						<li><span class="code">SICK</span> - <span class="descriprion">'._("time taken off because of sickness").'</span></li>
						<li><span class="code">APPOINT</span> - <span class="descriprion">'._("Doctor/Hospital appointment - max 2hours").'</span></li>
						<li><span class="code">UNPAID</span> - <span class="descriprion">'._("unpaid time taken off").'</span></li>
						<li><span class="code">ABS</span> - <span class="descriprion">'._("unmotivated absence").'</span></li>
						<li><span class="code">TRAIN</span> - <span class="descriprion">'._("training").'</span></li>
						<li><span class="code">INDUCT</span> - <span class="descriprion">'._("induction time").'</span></li>
						<li><span class="code">PMHOL</span> - <span class="descriprion">'._("paternity or maternity leave").'</span></li>
						<li><span class="code">ACCM</span> - <span class="descriprion">'._("work time accident").'</span></li>
						<li><span class="code">SICKM</span> - <span class="descriprion">'._("work related sickness").'</span></li>
					</ul>
				</div>';
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
