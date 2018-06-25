<?php

function finish($status,$statusText = "",$more = ""){
//	if(function_exists('logWrite'){
//		logWrite();
//	}
	$status = htmlspecialchars($status);
	$statusText = htmlspecialchars($statusText);
	header("Content-type: application/xml");
	echo '<?xml version="1.0" encoding="UTF-8"?>';
	echo "\n<RESPONSE>\n";
	echo "\t<STATUS>".$status."</STATUS>\n";
	echo "\t<STATUSTEXT>".$statusText."</STATUSTEXT>\n";
	echo $more;
	echo "</RESPONSE>\n";
	exit;
}

/* 
NOTE:
	
this function takes a string with entries given in postgreSQL array format
{"str1","str2"}
and returns a XML list 
<ENTRY ID="str1"/><ENTRY ID="str2"/>
*/
function translateEntries($identities)
{
	if(strcmp($identities,"")==0 || strcmp($identities,"{}")==0 || strlen($identities)<=2)
		return "";
	$list = substr($identities,1,strlen($identities)-2);
	$arr = explode(",",$list);
	$return = "";
	foreach($arr as $rawId)
	{
		if(strlen($rawId)<2)
			continue;
		$id = substr($rawId,1,strlen($rawId)-2);
		$return .="\t\t\t<ENTRY ID=\"".$id."\"/>\n";
	}
	return $return;
}
function logError($errorString)
{
	$filename = 'error_log';
	if(strcmp(trim($errorString),"")==0)
		return;
	if (is_writable($filename)) {
	
		if (!$handle = fopen($filename, 'a')) {
			echo "Cannot open file ($filename)";
			exit;
		}
		$line = date("Y-m-d H:m:s")."\t".$_SESSION["user"]->displayName."\t[ ".$errorString." ]\n\r";
		if (fwrite($handle, $line) === FALSE) {
			echo "Cannot write to file ($filename)";
			exit;
		}
		fclose($handle);
	
	} else {
		echo "The file $filename is not writable";
	}
}
//for debugging purposes
function logWrite($text)
{
	$filename = 'log.txt';
	if (is_writable($filename)) {
	
	if (!$handle = fopen($filename, 'a')) {
			echo "Cannot open file ($filename)";
			exit;
	}
		if (fwrite($handle, $text) === FALSE) {
			echo "Cannot write to file ($filename)";
			exit;
		}
		fclose($handle);
	
	} else {
		echo "The file $filename is not writable";
	}
}
/**
 * Called when a time sheet is saved, before any other actions are performed.
 * The db object is passed so the session is maintained
 * @param int $refid
 * @param DB $db
 */

function onTimeSheetSave($refid,&$db){
	//for queries, the statements go under the same session as the calee
	$tempDB = new DB();
	$tempDB2 = new DB();
	//checking the submision status
	$sql = "SELECT submitted FROM tbl_office_time_sheet WHERE refid = ".$refid;
	$tempDB->query($sql,Array('boolean'));
	$submitted = formatBoolean($tempDB->getElement("submitted"),true,false);
	if($submitted){
		//check if there's anything in tbl_staff_toil_adjustment_log with source_time_sheet = $refid
		$sql = "SELECT * FROM tbl_staff_toil_adjustment_log WHERE affected_time_sheet = ".$refid;
		$tempDB->query($sql);
		//go through all the records and revert the changes 
		for($i=0; $i < $tempDB->numRows(); ++$i){
			$affected_ts = $tempDB->getElement('affected_time_sheet');
			$value = $tempDB->getElement('value');
			$previous_value = $tempDB->getElement('previous_value');
			$staff_refid = $tempDB->getElement('staff_refid');
			//find the difference value 
			//NOTE: previous_value is not necessarily the value to reinstate!
			$diff = floatval($previous_value - $value);
			
			if($affected_ts != null){
				//finding the current lieu_ot of this time sheet
				$sql = 'SELECT lieu_ot FROM tbl_office_time_sheet WHERE refid = '.$affected_ts;
				$tempDB2->query($sql,Array('float'));
				$curr_lieu = floatval($tempDB2->getElement("lieu_ot"));
				// revert lieu_ot of the affected time sheets
				$values = Array("lieu_ot" => floatval($curr_lieu + $diff));
				$where = "refid = ".$affected_ts;
				$db->autoExec('tbl_office_time_sheet', $values, MDB2_AUTOQUERY_UPDATE, $where);
			}else{
				//taking the current adjustment
				$sql = 'SELECT adjustment,date_commencing FROM tbl_staff_toil_adjustment 
						WHERE staff_refid = '.$staff_refid.' AND refid = 
						(SELECT MAX(refid) FROM tbl_staff_toil_adjustment WHERE staff_refid = '.$staff_refid.')';
				$tempDB2->query($sql,Array('float','text'));
				if($tempDB2->numRows() > 0){
					$toilAdjustmentValue = $tempDB2->getElement("adjustment");
					$toilAdjustmentDate = $tempDB2->getElement("date_commencing");
				}
				// revert the toil_adjustment
				$comment = 'SYSTEM UPDATE: reinstated time sheet';
				$values = Array("adjustment" => $toilAdjustmentValue + $diff,
								"staff_refid" => $staff_refid,
								"source_time_sheet" => $refid,
								"date_commencing" => $toilAdjustmentDate,
								"comment" => $comment);
				$db->autoExec('tbl_staff_toil_adjustment', $values, MDB2_AUTOQUERY_INSERT);
			}
			$tempDB->nextRow();
		}
		if($tempDB->numRows() > 0){
			//remove all the entries about this time sheet as source
			$where = "source_time_sheet = ".$refid;
			$db->autoExec('tbl_staff_toil_adjustment_log', null, MDB2_AUTOQUERY_DELETE, $where);
		}
		
	}
}
//called when a time sheet is being submitted
function onTimeSheetSubmit($refid, $allowTOILinAdvance = false){
	global $status, $more;
	$ok = true;
	$db = new DB();
	//adjusting TOIL and refusing 
	$th = new ToilHelper($refid,$allowTOILinAdvance);
	$ret = $th->adjustToil();
	if (!$allowTOILinAdvance && $ret < 0) { //more toil declared than available to take
		$status = "toomuchtoil";
		$more .= "\t<OFFENDING>".$ret."</OFFENDING>\n";
		$ok = false;
	}
	//if no errors so far, change the submitted flag.
	if($ok){
		$values = Array("submitted" => $ok,"transferred" => false,"submissiontime" => date("Y-m-d H:i:s"));
		$where = "refid = ".$refid;
		$db->autoExec('tbl_office_time_sheet', $values, MDB2_AUTOQUERY_UPDATE, $where);
		$more .= "\t<FINAL>true</FINAL>\n";
	}
	return $ok;
}

?>