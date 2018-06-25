<?php require_once("header.php");

$db = new DB();
$acceptableTypes = Array("subordinates");
$type="subordinates";
if (isset($_GET["type"])) {
	$type = trim($_GET["type"]);
	if(!in_array($type,$acceptableTypes)){
		$type="subordinates";
	}
}
header("Content-type: text/csv; charset=UTF-8");
header("Content-disposition: attachment ; filename = ".ucwords($type)."Report-".date("d/m/Y").".csv");

switch($type){
	case 'subordinates':
		//check permissions first 
		//any one has permissions to data of their subordinates
		//write a query
		$dateAttribArray = getdate();
		if($dateAttribArray["wday"]== 5)
			$thisFridayTime = time();
		else
			$thisFridayTime = strtotime("last Friday");
		$thisFriday = date("Y-m-d",$thisFridayTime);
		$weekStart = strtotime("-6 day",$thisFridayTime);
		$weekStart = date("Y-m-d",$weekStart);
		$sql = 'SELECT fname,lname,dateworked,invoicecoderefid AS "invoice code",
				taskcoderefid AS "task code",normal,lieu,charged,sundaycharged,hours AS "total hours"
				FROM view_time_report
				WHERE submitted = \'submitted\' AND dateworked <= '.$db->quote($thisFriday).' 
				AND dateworked >= '.$db->quote($weekStart).' AND refid IN 
				(SELECT refid FROM tbl_staff_lookup WHERE employed = true AND linemanager = '.$_SESSION["user"]->refid.' AND refid <> linemanager)
				ORDER BY fname,lname,dateworked,invoicecoderefid ASC';
		break;
	default: //nada
		break;
}
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
if($db->numRows()==0){
	echo _('No data');
}
?>