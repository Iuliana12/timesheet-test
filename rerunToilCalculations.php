<?php
/**
 * This script updates the TOIL values for an employee from a commencing date
 * It works by running the toil expiry procedure for all the submitted weeks after the commencing date. 
 * 
 * */
ini_set('display_errors', 1);
require_once("header.php");

$date_commencing = '2009-03-15';
$db = new DB();
if(isset($_POST['employee'])){
	$employee = intval($_POST['employee']);
	//for Cambridge staff the date commencing is 1st of April 2010
	$sql = "select cost_centre from tbl_staff_lookup where refid = ".$employee;
	$db->query($sql, Array('text'));
	if(strtolower($db->getElement("cost_centre")) == 'cam'){
		$date_commencing = '2010-04-03';
	}
	$display = $_POST["display"];
	//taking all the time sheets 90days or less old
		
	$sql = "SELECT refid 
			FROM tbl_office_time_sheet 
			WHERE staffrefid = ".$employee." AND submitted = true 
			AND enddate >= ".$db->quote($date_commencing)."
			ORDER BY enddate ASC";
	$db->query($sql,Array('integer', 'text'));
	for($i=0; $i < $db->numRows(); ++$i){
		$tsRefid = $db->getElement("refid");
		$th = new ToilHelper($tsRefid,true,true);
		$ret = $th->adjustToil();
		$db->nextRow();
	}
	//processing finished
	header('Location: archive.php?display='.$display.'&employee='.$employee.'&r=');
}else{
	header('Location: archive.php');
}
?>