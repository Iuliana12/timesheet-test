<?php
require_once("header.php");
$db = new DB();
if(!$_SESSION["user"]->isAdmin()){
	error(_("access denied"));
	exit;
}

$cost_centre_addon = $fav_cost_centre = $date = $fav_location = $location_addon = "";
if (isset($_GET["cost_centre"])) {
	$fav_cost_centre = trim($_GET["cost_centre"]);
}
if ($fav_cost_centre != '-' && $fav_cost_centre != '') {
	$cost_centre_addon = "AND A.cost_centre = ".$db->quote($fav_cost_centre);	
}
if (isset($_GET["location"])) {
	$fav_location = trim($_GET["location"]);
	if ($fav_location == '') {
		$fav_location = '-';
	}
}
if ($fav_location != '-' && $fav_location != '') {
	$location_addon = "AND A.location = ".$db->quote($fav_location);	
}
if(isset($_GET["date"])){
	$thisFridayTime = strtotime($_GET["date"]);
}
else{
	$dateAttribArray = getdate();
	if($dateAttribArray["wday"]== 5)
		$thisFridayTime = time();
	else
		$thisFridayTime = strtotime("last Friday");
}
$thisFriday = date("Y-m-d",$thisFridayTime);

header("Content-type: text/csv; charset=UTF-8");
header("Content-disposition: attachment ; filename = SubmissionStatus-".$fav_cost_centre."-".date("d/m/Y",$thisFridayTime).".csv");


$sql = "SELECT ".$db->function->concat('A.lname',$db->quote(' '),'A.fname')." AS name,A.minhours,
		SUM(C.hours) AS hours,
		SUM(CASE WHEN C.ratetype = ".$db->quote('1')." THEN C.hours ELSE 0 END) AS lieu,
		SUM(CASE WHEN C.ratetype = ".$db->quote('2')." THEN C.hours ELSE 0 END) AS charged,
		SUM(CASE WHEN C.ratetype = ".$db->quote('3')." THEN C.hours ELSE 0 END) AS sundaycharged,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('TOIL')." THEN C.hours ELSE 0 END) AS toil,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('HOL')." THEN C.hours ELSE 0 END) AS hols,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('SICK')." THEN C.hours ELSE 0 END) AS sick,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('UNPAID')." THEN C.hours ELSE 0 END) AS unpaid,
		COUNT(CASE WHEN D.invoicecoderefid = ".$db->quote('HOL')." THEN C.dateworked END) AS holdays,
		B.submitted, E.enrolled, B.submissiontime
		FROM tbl_staff_lookup AS A LEFT JOIN tbl_staff_preferences E ON E.staff_refid = A.refid
		join tbl_office_time_sheet AS B ON A.refid = B.staffrefid 
		LEFT JOIN tbl_office_time_sheet_entry AS D ON (D.officetimesheetrefid = B.refid)
		LEFT JOIN tbl_office_time_sheet_entry_details AS C ON (C.officetimesheetentryrefid = D.refid)
		WHERE A.employed = true AND A.refid = B.staffrefid AND B.enddate = ".$db->quote($thisFriday)." 
		".$cost_centre_addon." ".$location_addon."
		GROUP BY A.refid,A.fname,A.lname,A.minhours,B.refid,B.submitted,E.enrolled, B.submissiontime 
		ORDER BY B.submitted DESC, E.enrolled DESC, A.lname ASC,A.fname ASC,B.refid ASC";
$db->query($sql);
$keyArr = Array();
if($db->numRows() > 0) {
	$keyArr = array_keys($db->getColumnNames());
	foreach ($keyArr as $key) {
		echo "\"".$key."\",";
	}
	echo "\n";
}
for ($i=0; $i < $db->numRows(); ++$i) {
	foreach ($keyArr as $key) {
		$value =  $db->getElement($key);
		echo "\"".$value."\",";
	}
	echo "\n";
	$db->nextRow();
}
$sql = "SELECT ".$db->function->concat('a.lname',$db->quote(' '),'a.fname')." AS name 
		FROM tbl_staff_lookup a LEFT JOIN tbl_staff_preferences b ON b.staff_refid = a.refid 
		WHERE a.employed = true AND b.enrolled = true AND a.refid NOT IN 
		(SELECT staffrefid FROM tbl_office_time_sheet WHERE enddate = ".$db->quote($thisFriday).") 
		".$cost_centre_addon." ".$location_addon."
		ORDER BY lname ASC, fname ASC ";
$db->query($sql);
$keyArr1 = Array('name');
if (count($keyArr) == 0) {
	foreach ($keyArr1 as $key) {
		echo "\"".$key."\",";
	}
	echo "\n";
}
$keyArr = array_diff($keyArr, $keyArr1);
for ($i=0; $i < $db->numRows(); ++$i) {
	foreach ($keyArr1 as $key) {
		$value = $db->getElement($key);
		echo "\"".$value."\",";
	}
	foreach ($keyArr as $key) {
		echo "\"-\",";
	}
	echo "\n";
	$db->nextRow();
}
?>