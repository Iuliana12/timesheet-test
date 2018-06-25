<?php
/*
$check = @$_REQUEST['descent'];
if ($check != 'gabbagabbahey'){
	die('You don\'t have access to this area!');
}
*/
$optOutThresholdHours = 48;
$optOutThresholdWeeks = 12;
$sicknessPeriodMonths = 12;
$sicknessThresholdDays = 9;
$intervalStart = "";
$intervalEnd = "";
$staff = Array();
require_once 'classes/DB.php';
$db = new DB();

$dateAttribArray = getdate();
if($dateAttribArray["wday"]== 5){
	$lastFridayTime = time();
}
else{
	$lastFridayTime = strtotime("last Friday");
}
$lastFriday = date("Y-m-d",$lastFridayTime);
$interval = ($optOutThresholdWeeks-1).' weeks';
$intervalEnd = date("d/m/Y",$lastFridayTime);
$intervalStart = strtotime("-".$interval,$lastFridayTime);//this will be a Friday
$intervalStart = strtotime("-6 days",$intervalStart);//this is the Saturday date of that week
$intervalStart = date("d/m/Y",$intervalStart);

$sql = "SELECT A.refid,A.fname || ' ' || A.lname as name, A.cost_centre, SUM(C.hours)/$optOutThresholdWeeks AS avghours
		FROM tbl_staff_lookup A 
		JOIN tbl_office_time_sheet AS B ON A.refid = B.staffrefid
		LEFT JOIN tbl_office_time_sheet_entry AS D ON (D.officetimesheetrefid = B.refid)
		LEFT JOIN tbl_office_time_sheet_entry_details AS C ON (C.officetimesheetentryrefid = D.refid)
		WHERE A.employed = true AND B.submitted = true
		AND enddate <= timestamp ".$db->quote($lastFriday)."
		AND enddate >= timestamp ".$db->quote($lastFriday)." - interval ".$db->quote($interval)."		
		GROUP BY A.refid,name,A.cost_centre
		HAVING SUM(C.hours)/$optOutThresholdWeeks >= ".$optOutThresholdHours;
$db->query($sql);
for($i = 0; $i < $db->numRows(); ++$i){
	$staffRefid = $db->getElement('refid');
	$staffName = $db->getElement('name').' ('.$db->getElement('cost_centre').')';
	$avgHours = floatVal($db->getElement('avghours'));
	if($avgHours >= $optOutThresholdHours){
		$staff[$staffName] = $avgHours;
	}
	$db->nextRow();
}

if(count($staff) > 0){
	$subject = "Opt Out Notice";
	$message = '';
	$message.= "Dear Admin, \n\n";
	$message.= "Here's the list of staff members that worked more than $optOutThresholdHours hours over the previous $optOutThresholdWeeks weeks ($intervalStart - $intervalEnd): \n\n";
	foreach($staff as $name => $avgHours){
		$message.= ucwords($name)." - ".$avgHours."\n";
	}
	//echo nl2br($message).'<br/>';
	mail(EMAIL_ADMIN,$subject,$message,"From: ".EMAIL_SENDER);
}


// The part that checks for staff that put more than 9 days of sickness in the last 12 months
 
$interval = $sicknessPeriodMonths.' months';
$intervalEnd = date("d/m/Y",$lastFridayTime);
$intervalStart = strtotime("-".$interval,$lastFridayTime);
$intervalStart = date("d/m/Y",$intervalStart);
$staffList = '';

$sql = "SELECT A.refid,A.fname, A.lname, A.cost_centre, 
		COUNT(distinct C.dateworked) AS days,
		SUM(C.hours) AS hours
		FROM tbl_staff_lookup A 
		JOIN tbl_office_time_sheet AS B ON A.refid = B.staffrefid
		LEFT JOIN tbl_office_time_sheet_entry AS D ON (D.officetimesheetrefid = B.refid)
		LEFT JOIN tbl_office_time_sheet_entry_details AS C ON (C.officetimesheetentryrefid = D.refid)
		WHERE A.employed = true AND B.submitted = true AND D.invoicecoderefid = 'SICK'
		AND enddate <= timestamp ".$db->quote($lastFriday)."
		AND enddate >= timestamp ".$db->quote($lastFriday)." - interval ".$db->quote($interval)."		
		GROUP BY A.refid,A.fname,A.lname,A.cost_centre
		ORDER BY COUNT(distinct C.dateworked) DESC";
$db->query($sql);
for($i = 0; $i < $db->numRows(); ++$i){
	$staffRefid = $db->getElement('refid');
	$fName = ucwords($db->getElement('fname'));
	$lName = ucwords($db->getElement('lname'));
	$staffCostCentre = ucwords($db->getElement('cost_centre'));
	$days = floatVal($db->getElement('days'));
	$hours = floatVal($db->getElement('hours'));
	$calculatedDays = ceil($hours/HOURS_PER_DAY);
	if($days >= $sicknessThresholdDays){
		$staffList .= $staffRefid.',"'.$lName.'","'.$fName.'","'.$staffCostCentre.'",'.$days.','.$hours.','.$calculatedDays."\n\r";
	}
	$db->nextRow();
}

if($staffList != ''){
	//adding the CSV header
	$staffList = '"REFID","Surname","Forename","Cost Centre","SICK days","SICK hours","Days based on hours / '.HOURS_PER_DAY.'" '."\r\n".$staffList;
	$subject = "Sickness limit reach notice";
	
	//create a boundary string. It must be unique
	//so we use the MD5 algorithm to generate a random hash
	$random_hash = md5(date('r', time()));
	//define the headers we want passed. Note that they are separated with \r\n
	$headers = "From: ".EMAIL_SENDER;
	//add boundary string and mime type specification
	$headers .= "\r\nContent-Type: multipart/mixed; boundary=\"PHP-mixed-".$random_hash."\"";
	//read the atachment file contents into a string,
	//encode it with MIME base64,
	//and split it into smaller chunks
	$attachment = chunk_split(base64_encode($staffList));
	//define the body of the message.
	ob_start(); //Turn on output buffering
?>
--PHP-mixed-<?php echo $random_hash; ?> 
Content-Type: multipart/alternative; boundary="PHP-alt-<?php echo $random_hash; ?>"

--PHP-alt-<?php echo $random_hash; ?> 
Content-Type: text/plain; charset="iso-8859-1"
Content-Transfer-Encoding: 7bit

Dear Admin,\r\n

Attached as CSV is the list of staff members that booked more than <?php echo $sicknessThresholdDays ?> days of sickness over the previous 
<?php echo $sicknessPeriodMonths ?> months (<?php echo $intervalStart.' - '.$intervalEnd?>).
	
--PHP-alt-<?php echo $random_hash; ?>  
Content-Type: text/html; charset="iso-8859-1" 
Content-Transfer-Encoding: 7bit

Dear Admin,<br/><br/>

Attached as CSV is the list of staff members that booked more than <?php echo $sicknessThresholdDays ?> days of sickness over the previous 
<?php echo $sicknessPeriodMonths ?> months (<?php echo $intervalStart.' - '.$intervalEnd?>).<br/>
<br/><br/>

--PHP-alt-<?php echo $random_hash; ?>-- 

--PHP-mixed-<?php echo $random_hash; ?> 
Content-Type: text/csv; charset="UTF-8"; name="staff_sickness.csv" 
Content-Transfer-Encoding: base64 
Content-Disposition: attachment 

<?php echo $attachment; ?>
--PHP-mixed-<?php echo $random_hash; ?>--
	
<?php
	//copy current buffer contents into $message variable and delete current output buffer
	$message = ob_get_clean();
	//echo nl2br($message).'<br/>';
	mail(EMAIL_ADMIN,$subject,$message,$headers);
}
?>
