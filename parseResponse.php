<?php
require_once("header.php");
require_once("parseResponseFunctions.php");



$debugString = "";
$db = new DB();
$status = "";
$statusText = "";
$more = "";


if(!isset($_POST["xmlResponse"])){
	finish("parseerror");
}
$xml = new DOMDocument();
$xml->preserveWhiteSpace=false;
$xml->loadXML(rawurldecode($_POST["xmlResponse"]));

// 	$xml->load("log.xml");
$requests = $xml->getElementsByTagName("REQUEST");
if($requests->length == 0) {
	$requestObject = "saveTimeSheet";
	$forms = $xml->getElementsByTagName("FORM");
	if($forms->length == 0) {
		finish("parseerror");
	}
}
else {
	$request = $requests->item(0);
	$requestObject = $request->getAttribute("OBJECT");
}


switch($requestObject) {
    case "saveInvoice":
        //manage invoice code related stuff - invoice code manager access only
        //search for a match with the current user as manager
        //update details if they check
        $invs = $request->getElementsByTagName("INVOICECODE");
        $invoicecode = strtoupper(htmlspecialchars_decode($invs->item(0)->nodeValue));
        $pns = $request->getElementsByTagName("PROJECTNAME");
        $projectname = strtoupper(htmlspecialchars_decode($pns->item(0)->nodeValue));
        $deps = $request->getElementsByTagName("DEPARTMENT");
        $department = strtoupper(htmlspecialchars_decode($deps->item(0)->nodeValue));
        $taskneeded = strcmp(strtolower($request->getElementsByTagName("TASKNEEDED")->item(0)->nodeValue), "true") == 0 ? 'true' : 'false';
        $requiresauthorization = strcmp(strtolower($request->getElementsByTagName("REQAUTH")->item(0)->nodeValue), "true") == 0 ? 'true' : 'false';

        $values = Array("projectname" => substr($projectname, 0, 50),
            "department" => substr($department, 0, 20),
            "taskneeded" => $taskneeded,
            "requiresauthorization" => $requiresauthorization);
        $where = "invoicecode = " . $db->quote($invoicecode);
        $db->autoExec('tbl_invoice_code_lookup', $values, MDB2_AUTOQUERY_UPDATE, $where);
        switch ($db->getAffectedRows()) {
            case 0:
                $status = _("Error: The invoice code could not be found");
                break;
            case 1:
                $status = _("Invoice code details saved successfuly");
                break;
            default:
                $status = _("Warning: Your action updated more than one invoice code");
        }
        break;
    case "addTask":
    case "saveTask":
        $tasks = $request->getElementsByTagName("TASKCODE");
        $taskcode = substr(strtoupper(htmlspecialchars_decode($tasks->item(0)->nodeValue)), 0, 20);
        $tasknames = $request->getElementsByTagName("TASKNAME");
        $taskname = substr(htmlspecialchars_decode($tasknames->item(0)->nodeValue), 0, 50);
        //search for a match for the given task code
        $sql = "SELECT taskcode FROM tbl_task_code_lookup WHERE taskcode = " . $db->quote($taskcode);
        $db->query($sql);
        if ($db->numRows() != 0) {
            $taskcode = $db->getElement('taskcode');
            //if found and reqObject == addTask, then throw error
            if ($requestObject == "addTask") {
                $invs = $request->getElementsByTagName("INVOICECODE");
                $invoicecode = strtoupper(htmlspecialchars_decode($invs->item(0)->nodeValue));
                $sql = "SELECT groupnamerefid FROM tbl_invoice_group_matrix
						WHERE invoicecoderefid = " . $db->quote($invoicecode);
                $db->query($sql);
                //CAUTION: DB design allows many groupnames, even though the logic of this app does not permit this
                $groupname = $db->getElement("groupnamerefid");
                if ($groupname == 'ALL TASKS' && $db->numRows() > 1) {//trying to avoid adding these task code to the most general task code group
                    $db->nextRow();
                    $groupname = $db->getElement("groupnamerefid");
                }
                //checking if this taskcode is already assigned to this groupname
                $sql = "SELECT * FROM tbl_task_group_matrix
						WHERE taskcoderefid = " . $db->quote($taskcode) . " AND groupnamerefid = " . $db->quote($groupname);
                $db->query($sql);
                if ($db->numRows() > 0) {
                    $status = sprintf(_("Error: taskcode %s is already assigned to this project"), $tasks->item(0)->nodeValue);
                } else {
                    $values = Array("taskcoderefid" => $taskcode,
                        "groupnamerefid" => $groupname);
                    $db->autoExec('tbl_task_group_matrix', $values, MDB2_AUTOQUERY_INSERT);
                    if ($db->getAffectedRows() == 0) {
                        $status = _("Task code was not added successfuly");
                    } else {
                        $status = _("Task code assigned successfuly");
                    }
                }
            } //if found and reqObject == saveTask, update data
            else {
                $values = Array("taskname" => substr($taskname, 0, 500));
                $where = "taskcode = " . $db->quote($taskcode);
                $db->autoExec('tbl_task_code_lookup', $values, MDB2_AUTOQUERY_UPDATE, $where);
                $status = _("Task code updated successfuly");
            }
        } else {
            //if not found and reqObject == addTask, add the new task
            if ($requestObject == "addTask") {
                $invs = $request->getElementsByTagName("INVOICECODE");
                $invoicecode = strtoupper(htmlspecialchars_decode($invs->item(0)->nodeValue));
                $db->beginTransaction();
                $values = Array("taskcode" => substr($taskcode, 0, 30),
                    "taskname" => substr($taskname, 0, 500));
                $db->autoExec('tbl_task_code_lookup', $values, MDB2_AUTOQUERY_INSERT);
                $sql = "SELECT groupnamerefid FROM tbl_invoice_group_matrix WHERE invoicecoderefid = " . $db->quote($invoicecode);
                $db->query($sql);
                $groupname = $db->getElement("groupnamerefid");
                if ($groupname == 'ALL TASKS' && $db->numRows() > 1) {//trying to avoid adding these task code to the most general task code group
                    $db->nextRow();
                    $groupname = $db->getElement("groupnamerefid");
                }

                $values = Array("taskcoderefid" => substr($taskcode, 0, 30),
                    "groupnamerefid" => $groupname);
                $db->autoExec('tbl_task_group_matrix', $values, MDB2_AUTOQUERY_INSERT);
                if ($db->getAffectedRows() == 0) {
                    $db->rollback();
                    $status = _("Task code was not added successfuly");
                } else {
                    $db->commit();
                    $status = _("Task code added successfuly");
                }
            } //if not found and reqObject == saveTask, delete the previous taskcode and then insert
            else {
                $status = sprintf(_("Error: taskcode %s was not found in the database"), $tasks->item(0)->nodeValue);
            }
        }
        break;
    case "employee":
        //employee related stuff - admin access only
        $response = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        if (!$_SESSION["user"]->isAdmin()) {
            $status = "error";
            $statusText = _("You're not allowed in this section");
            break;
        }
        $refids = $request->getElementsByTagName("REFID");
        $refid = intval($refids->item(0)->nodeValue);
        $sql = "SELECT staff.refid, staff.fname, staff.lname, staff.username,staff.email, staff.minhours, staff.employed,
					" . $db->function->concat('linemanager.fname', $db->quote(' '), 'linemanager.lname') . " AS line_manager_name,
					pref.user_type,pref.enrolled,pref.authorizes_subordinates,pref.authorizes_invoice_codes,
				  	(CASE WHEN toil.adjustment IS NOT NULL THEN toil.adjustment ELSE 0 END) AS toiladjustment,
				  	(CASE WHEN hols.adjustment IS NOT NULL THEN hols.adjustment ELSE 0 END) AS holsadjustment,
				  	toil.date_commencing AS toildate,toil.comment AS toilcomment,hols.comment AS holscomment
				FROM tbl_staff_lookup staff
				LEFT JOIN tbl_staff_preferences pref ON pref.staff_refid = staff.refid
				LEFT JOIN tbl_staff_toil_adjustment toil ON toil.staff_refid = staff.refid
				LEFT JOIN tbl_staff_hols_adjustment hols ON hols.staff_refid = staff.refid
				LEFT JOIN tbl_staff_lookup linemanager ON linemanager.refid = staff.linemanager
				WHERE staff.refid = " . $refid . "
				AND (toil.refid IS NULL OR toil.refid = (select max(refid) from tbl_staff_toil_adjustment where staff_refid = staff.refid))
				AND (hols.refid IS NULL OR hols.refid = (select max(refid) from tbl_staff_hols_adjustment where staff_refid = staff.refid))";
        $db->query($sql, Array('integer', 'text', 'text', 'text', 'text', 'float', 'boolean',
            'text', 'integer', 'boolean', 'boolean', 'boolean', 'float', 'float', 'text', 'text', 'text'));
        if ($db->numRows() != 1) {
            $status = "error";
            $statusText = _("Employee not found");
            break;
        }
        $status = "ok";
        $more .= "<EMPLOYEE>\n";
        $more .= "<REFID>" . $db->getElement("refid") . "</REFID>";
        $more .= "<FNAME>" . htmlspecialchars($db->getElement("fname")) . "</FNAME>";
        $more .= "<LNAME>" . htmlspecialchars($db->getElement("lname")) . "</LNAME>";
        $more .= "<USERNAME>" . htmlspecialchars($db->getElement("username")) . "</USERNAME>";
        $more .= "<EMAIL>" . htmlspecialchars($db->getElement("email")) . "</EMAIL>";
        $more .= "<MINHOURS>" . $db->getElement("minhours") . "</MINHOURS>";
        $more .= "<EMPLOYED>" . formatBoolean($db->getElement("employed"), "true", "false") . "</EMPLOYED>";
        $more .= "<LINEMANAGER>" . htmlspecialchars($db->getElement("line_manager_name")) . "</LINEMANAGER>";
        $more .= "<USERTYPE>" . $db->getElement("user_type") . "</USERTYPE>";
        $more .= "<ENROLLED>" . formatBoolean($db->getElement("enrolled"), "true", "false") . "</ENROLLED>";
        $more .= "<VARIABLE>" . formatBoolean($db->getElement("variable"), "true", "false") . "</VARIABLE>";
        $more .= "<AUTHORIZES_SUBORDINATES>" . formatBoolean($db->getElement("authorizes_subordinates"), "true", "false") . "</AUTHORIZES_SUBORDINATES>";
        $more .= "<AUTHORIZES_INVOICE_CODES>" . formatBoolean($db->getElement("authorizes_invoice_codes"), "true", "false") . "</AUTHORIZES_INVOICE_CODES>";
        $more .= "<TOILADJUSTMENT>" . $db->getElement("toiladjustment") . "</TOILADJUSTMENT>";
        $toilDate = $db->getElement("toildate");
        if ($toilDate == "") {
            $toilDate = date("d/m/Y");
        } else {
            $toilDate = date("d/m/Y", strtotime($toilDate));
        }
        $more .= "<TOILDATE>" . $toilDate . "</TOILDATE>";
        $more .= "<TOILCOMMENT>" . htmlspecialchars($db->getElement("toilcomment")) . "</TOILCOMMENT>";
        $more .= "<HOLSADJUSTMENT>" . $db->getElement("holsadjustment") . "</HOLSADJUSTMENT>";
        $more .= "<HOLSCOMMENT>" . htmlspecialchars($db->getElement("holscomment")) . "</HOLSCOMMENT>";
        $more .= "</EMPLOYEE>\n";
        break;
    case "saveEmployee":
        if (!$_SESSION["user"]->isAdmin()) {
            $status = _("access denied");
            break;
        }

		$updateStaffTable = false;
        $refids = $request->getElementsByTagName("REFID");
        $refid = intval($refids->item(0)->nodeValue);
        $more .= "\t<REFID>" . $refid . "</REFID>\n";
        $usertype = intval($request->getElementsByTagName("USERTYPE")->item(0)->nodeValue);
		if($request->getElementsByTagName("MINHOURS")->length > 0){
			$updateStaffTable = true;
			$minHours = floatval($request->getElementsByTagName("MINHOURS")->item(0)->nodeValue);
			$variable = strcmp($request->getElementsByTagName("VARIABLE")->item(0)->nodeValue, "true") == 0 ? "true" : "false";
		}
        $toilAdjustment = floatval($request->getElementsByTagName("TOILADJUSTMENT")->item(0)->nodeValue);
        $toilAdjustmentDate = trim($request->getElementsByTagName("TOILDATE")->item(0)->nodeValue);
        $toilAdjustmentComment = trim(htmlspecialchars_decode($request->getElementsByTagName("TOILCOMMENT")->item(0)->nodeValue));
        $holsAdjustment = floatval($request->getElementsByTagName("HOLSADJUSTMENT")->item(0)->nodeValue);
        $holsAdjustmentComment = trim(htmlspecialchars_decode($request->getElementsByTagName("HOLSCOMMENT")->item(0)->nodeValue));
        $authorizesSub = strcmp($request->getElementsByTagName("AUTHORIZES_SUBORDINATES")->item(0)->nodeValue, "true") == 0 ? "true" : "false";
        $authorizesInv = strcmp($request->getElementsByTagName("AUTHORIZES_INVOICE_CODES")->item(0)->nodeValue, "true") == 0 ? "true" : "false";
        $enrolled = strcmp($request->getElementsByTagName("ENROLLED")->item(0)->nodeValue, "true") == 0 ? "true" : "false";

        $status = "failed to save";
        $ok = true;
        //TOIL and HOLS adjustments
        //getting the previous values
        $currentToilAdjustment = "nothing";
        $currentToilAdjustmentDate = "nothing";
        $currentHolsAdjustment = "nothing";
        $sql = 'SELECT adjustment,date_commencing FROM tbl_staff_toil_adjustment
				WHERE staff_refid = ' . $refid . ' AND refid = (SELECT MAX(refid) FROM tbl_staff_toil_adjustment WHERE staff_refid = ' . $refid . ')';
        $db->query($sql, Array('float'));
        if ($db->numRows() > 0) {
            $currentToilAdjustment = $db->getElement("adjustment");
            $currentToilAdjustmentDate = $db->getElement("date_commencing");
        }
        $sql = 'SELECT adjustment FROM tbl_staff_hols_adjustment
				WHERE staff_refid = ' . $refid . ' AND refid = (SELECT MAX(refid) FROM tbl_staff_hols_adjustment WHERE staff_refid = ' . $refid . ')';
        $db->query($sql, Array('float'));
        if ($db->numRows() > 0) {
            $currentHolsAdjustment = $db->getElement("adjustment");
        }
        if ($currentToilAdjustment != $toilAdjustment && $toilAdjustmentDate == "") {
            $status = _("Please specify the commencing date for the new TOIL adjustment");
            $ok = false;
        } elseif ($currentToilAdjustment != $toilAdjustment || $currentToilAdjustmentDate != $toilAdjustmentDate) {
            //check the date format and transform it
            $dateArr = explode("/", $toilAdjustmentDate);
            if (count($dateArr) < 3) {
                $status = _("Invalid date format for toil adjustment commencing date");
                $ok = false;
                break;
            }
            if (strlen($dateArr[1]) < 2)
                $dateArr[1] = '0' . $dateArr[1];
            if (strlen($dateArr[0]) < 2)
                $dateArr[0] = '0' . $dateArr[0];
            $usDate = $dateArr[1] . "/" . $dateArr[0] . "/" . $dateArr[2];
            $dateInt = strtotime($usDate);
            if ($dateInt === FALSE) {
                $status = _("Invalid date format for toil adjustment commencing date");
                $ok = false;
                break;
            }
            $toilAdjustmentDate = date("Y-m-d", $dateInt);
        }

        if (($currentToilAdjustment != $toilAdjustment || ($currentToilAdjustmentDate != $toilAdjustmentDate && $toilAdjustment != 0)) && $toilAdjustmentComment == "") {
            $status = _("Please specify the reason you're adjusting the TOIL value for this employee");
            $ok = false;
        }

        if ($currentHolsAdjustment != $holsAdjustment && $holsAdjustmentComment == "") {
            $status = _("Please specify the reason you're adjusting the Holidays value for this employee");
            $ok = false;
        }

        if ($ok) {
            $db->beginTransaction();
			if($updateStaffTable){
				$values = Array("minhours" => $minHours,"variable" => $variable);
				$where = "refid = " . $refid;
				$db->autoExec('tbl_staff_lookup', $values, MDB2_AUTOQUERY_UPDATE, $where);
			}

            if ($currentToilAdjustment != $toilAdjustment || $currentToilAdjustmentDate != $toilAdjustmentDate) {
                $values = Array("adjustment" => $toilAdjustment,
                    "staff_refid" => $refid,
                    "added_by" => $_SESSION["user"]->refid,
                    "date_commencing" => $toilAdjustmentDate,
                    "comment" => $toilAdjustmentComment);
                $db->autoExec('tbl_staff_toil_adjustment', $values, MDB2_AUTOQUERY_INSERT);
            }
            if ($currentHolsAdjustment != $holsAdjustment) {
                $values = Array("adjustment" => $holsAdjustment,
                    "staff_refid" => $refid,
                    "added_by" => $_SESSION["user"]->refid,
                    "comment" => $holsAdjustmentComment);
                $db->autoExec('tbl_staff_hols_adjustment', $values, MDB2_AUTOQUERY_INSERT);
            }
            $values = Array("enrolled" => $enrolled,
                "user_type" => $usertype,
                "authorizes_subordinates" => $authorizesSub,
                "authorizes_invoice_codes" => $authorizesInv);
            $where = "staff_refid = " . $refid;
            $db->autoExec('tbl_staff_preferences', $values, MDB2_AUTOQUERY_UPDATE, $where);
            $affectedRows = $db->getAffectedRows();
            //if no affected rows, then we insert a new row only if the values are significant
            if ($affectedRows == 0 && ($usertype != 0 || $authorizesSub == "true" || $authorizesInv == "true" || $toilAdjustment != 0 || $holsAdjustment != 0)) {
                $values = Array("staff_refid" => $refid,
                    "enrolled" => $enrolled,
                    "user_type" => $usertype,
                    "authorizes_subordinates" => $authorizesSub,
                    "authorizes_invoice_codes" => $authorizesInv);
                $db->autoExec('tbl_staff_preferences', $values, MDB2_AUTOQUERY_INSERT);
            }
            if ($ok) {
                $db->commit();
                $status = _("save successful");
            } else {
                $db->rollback();
                $status = _("failed to save");
            }
        }
        break;
    case "deleteTimesheet":
        //this happens when discard button is pressed in option list
        $refids = $request->getElementsByTagName("REFID");
        $refid = intval($refids->item(0)->nodeValue);
        $more .= "\t<REFID>" . $refid . "</REFID>\n";
        $status = _("delete error");


        $oCredentials = new TimeSheetCredentials($refid);

        if ($oCredentials->isNull()) {
            echo $oCredentials->canDelete();
            $status = "no permissions";
        } else {
            if ($oCredentials->canDelete()) {
                $db->beginTransaction();
                //make sure to reinstate any TOIL this time sheet had claimed, etc
                onTimeSheetSave($refid, $db);
                $sql = "DELETE FROM tbl_office_time_sheet_entry_details	WHERE officetimesheetentryrefid IN
                        (SELECT refid FROM tbl_office_time_sheet_entry WHERE officetimesheetrefid = " . $refid . "); ";
                $sql .= "DELETE FROM tbl_office_time_sheet_entry WHERE officetimesheetrefid = " . $refid . "; ";
                $sql .= "DELETE FROM tbl_office_time_sheet WHERE refid = " . $refid;
                //$rep = $db->execMultiple($sql);
                $rep = $db->exec($sql);
                $rep .= $db->commit();
                $status = "deleted";
            }
        }
		break;
    case "convertTimesheet":
        $refids = $request->getElementsByTagName("REFID");
        $refid = intval($refids->item(0)->nodeValue);
        $more .= "\t<REFID>".$refid."</REFID>\n";
        $status = _("convert error");

        $oCredentials = new TimeSheetCredentials($refid);
        if($oCredentials->isNull()){
            $status="nocredentials";
            $statusText = _("No credentials information");
            break;
        }
        if($oCredentials->canEdit()){
            $db->beginTransaction();
            onTimeSheetSave($refid,$db);
            $arrIdentities = Array();
            //update identities by doubling them
            $sql = 'SELECT refid,identities FROM tbl_office_time_sheet_entry_details WHERE officetimesheetentryrefid IN
                    (SELECT refid FROM tbl_office_time_sheet_entry WHERE officetimesheetrefid = '.$refid.')
                    AND identities IS NOT NULL';
            $db->query($sql);
            $rowNr = $db->numRows();
            for($i=0;$i<$rowNr;++$i){
                $idRefid = $db->getElement('refid');
                $arrIdentities[$idRefid] = $db->getElement('identities');
                $db->nextRow();
            }
            foreach($arrIdentities as $idRefid => $identities){
                //take out the curly brackets and the first and last double quotation marks
                $identities = explode('","',substr($identities,2,-2));
                $newIdentities = Array();
                foreach($identities as $identity){
                    if($identity == ''){
                        continue;
                    }
                    $newIdentities[] = $identity;
                    $idParts = explode('-',$identity);
                    if($idParts[2] == '30'){
                        $newIdentities[] = $idParts[0].'-'.$idParts[1].'-45';
                    }else{
                        $newIdentities[] = $idParts[0].'-'.$idParts[1].'-15';
                    }
                }
                $identities = '{"'.implode('","',$newIdentities).'"}';
                $db->autoExec('tbl_office_time_sheet_entry_details', Array('identities' => $identities), MDB2_AUTOQUERY_UPDATE, 'refid = '.$idRefid);
            }
            //update the guiresolution parameter in tbl_office_time_sheet
            $db->autoExec('tbl_office_time_sheet', Array('guiresolution' => 15), MDB2_AUTOQUERY_UPDATE, 'refid = '.$refid);
            $db->commit();
            $status = "converted";
        }
        break;
	case "submitTimesheet":
		//this is the action triggered by an admin submitting a time sheet for someone else
		$status = "No refid or insufficient rights for this action";
		$refids = $request->getElementsByTagName("REFID");
		$refid = intval($refids->item(0)->nodeValue);
		$more .= "\t<REFID>".$refid."</REFID>\n";

		$oCredentials = new TimeSheetCredentials($refid);
		if($oCredentials->isNull()){
			 break;
		}
		if($oCredentials->canEdit() || $oCredentials->isLineManager()){//if it's an admin or a line manager
			if(onTimeSheetSubmit($refid, true) >= 0){
				$status = "submitted";
			}
		}
		break;
	case "timetotal":
		//this is called by timeTotaler.php to get the total time spent on a specific project
		$status = "valid";
		$statusText = _("Everything is fine!");
		$invs = $request->getElementsByTagName("INVOICECODE");
		$invoicecode = strtoupper(htmlspecialchars_decode($invs->item(0)->nodeValue));
		$tsks = $request->getElementsByTagName("TASKCODE");
		$taskcode = strtoupper(htmlspecialchars_decode($tsks->item(0)->nodeValue));
		$emps = $request->getElementsByTagName("EMPLOYEE");
        $employee = null;
        if($emps->item(0)->nodeValue != '') {
            $employee = intval($emps->item(0)->nodeValue);
        }
		//date start
		$dates = $request->getElementsByTagName("DATESTART");
		$dateStart = $dates->item(0)->nodeValue;
		$dateArr = explode("/",$dateStart);
		$dateStart = null;
		if(count($dateArr) == 3) {
			if(strlen($dateArr[1]) < 2)
				$dateArr[1] = '0'.$dateArr[1];
			if(strlen($dateArr[0]) < 2)
				$dateArr[0] = '0'.$dateArr[0];
			$usDate = $dateArr[1]."/".$dateArr[0]."/".$dateArr[2];
			$dateInt = strtotime($usDate);
			if($dateInt !== FALSE) {
				$dateStart = date("Y-m-d",$dateInt);
			}
		}
		$dates = $request->getElementsByTagName("DATEEND");
		$dateEnd = $dates->item(0)->nodeValue;
		$dateArr = explode("/",$dateEnd);
		$dateEnd = null;
		if(count($dateArr) == 3) {
			if(strlen($dateArr[1]) < 2)
				$dateArr[1] = '0'.$dateArr[1];
			if(strlen($dateArr[0]) < 2)
				$dateArr[0] = '0'.$dateArr[0];
			$usDate = $dateArr[1]."/".$dateArr[0]."/".$dateArr[2];
			$dateInt = strtotime($usDate);
			if($dateInt !== FALSE) {
				$dateEnd = date("Y-m-d",$dateInt);
			}
		}
		$dateCondition = "";
		if($dateStart != null && $dateEnd != null){
			$dateCondition = " AND b.dateworked >= ".$db->quote($dateStart)." AND b.dateworked <= ".$db->quote($dateEnd);
		}else{
			$status="invalid";
			$statusText = _("Invalid date value");
		}

		$invoiceCheck = ( strcmp(strtolower($request->getElementsByTagName("INVOICECHECK")->item(0)->nodeValue),"true")==0 );
		$taskCheck = ( strcmp(strtolower($request->getElementsByTagName("TASKCHECK")->item(0)->nodeValue),"true")==0 );
		$employeeCheck = ( strcmp(strtolower($request->getElementsByTagName("EMPLOYEECHECK")->item(0)->nodeValue),"true")==0 );
		$selftime = ( strcmp(strtolower($request->getElementsByTagName("SELFTIME")->item(0)->nodeValue),"true")==0 );

		/*if(strcmp(trim($taskcode),"")==0)
			$taskcode = "N/A";*/
		if($invoiceCheck){
			if($taskCheck){
				$projectDetails = "a.invoicecoderefid = ".$db->quote($invoicecode)." AND a.taskcoderefid = ".$db->quote($taskcode);
			}
			else{
				$projectDetails = "a.invoicecoderefid = ".$db->quote($invoicecode);
			}
		}else{
			$projectDetails = '';
		}
		//if the user does not have administrative privileges
		if(!$_SESSION["user"]->isAdmin() || $selftime){
			$employee = $_SESSION["user"]->refid;
			$employeeCheck = true;
		}
		if($employeeCheck && $employee){
			if($projectDetails != ''){
				$projectDetails = 'AND '.$projectDetails;
			}
			$sql = "SELECT SUM(b.hours) AS total FROM tbl_office_time_sheet x
					JOIN tbl_office_time_sheet_entry a ON x.refid = a.officetimesheetrefid
					LEFT JOIN tbl_office_time_sheet_entry_details b ON a.refid = b.officetimesheetentryrefid
					WHERE x.staffrefid = ".$employee." ".$projectDetails." ".$dateCondition;
		}
		elseif($projectDetails != ''){
			$sql = "SELECT SUM(b.hours) AS total FROM tbl_office_time_sheet_entry a
					LEFT JOIN tbl_office_time_sheet_entry_details b ON a.refid = b.officetimesheetentryrefid
					WHERE ".$projectDetails." ".$dateCondition;
		}else{
			$sql = "SELECT 0";
		}
//		echo $sql;
		$db->query($sql, Array('float'));
		$total =  $db->getElement("total");
		$more .= "\t<TOTAL>".$total."</TOTAL>\n";
		break;
	case "timesheet":
		$refids = $request->getElementsByTagName("REFID");
		$refid = intval($refids->item(0)->nodeValue);

		$staffrefid = $_SESSION["user"]->refid;
		$response = "<FORM>\n";

		//checking if this user has access to the referenced timesheet
		$oCredentials = new TimeSheetCredentials($refid);
		if($oCredentials->isNull() || !$oCredentials->canView()){
			$status = "norights";
			$statusText = _("You don't have access to any part of this time sheet!");
			$response .= "</FORM>\n";
			$more .= $response;
			break;
		}
		if($oCredentials->isProjectManager()){
			$sql = "SELECT a.invoicecoderefid,a.taskcoderefid,a.colour,a.note,b.dateworked,b.ratetype,
					b.hours,b.identities,c.projectname
					FROM tbl_office_time_sheet_entry AS a LEFT JOIN
					tbl_office_time_sheet_entry_details AS b ON a.refid = b.officetimesheetentryrefid,
					tbl_invoice_code_lookup AS c WHERE c.invoicecode = a.invoicecoderefid
					AND a.officetimesheetrefid = ".$refid." AND a.invoicecoderefid
					IN (".$oCredentials->getInvoiceCodeList().") ORDER BY a.refid";
		}
		else{
			$sql = "SELECT a.invoicecoderefid,a.taskcoderefid,a.colour,a.note,b.dateworked,
					b.ratetype,b.hours,b.identities,c.projectname
					FROM tbl_office_time_sheet_entry AS a LEFT JOIN
					tbl_office_time_sheet_entry_details AS b ON a.refid = b.officetimesheetentryrefid,
					tbl_invoice_code_lookup AS c WHERE c.invoicecode = a.invoicecoderefid
					AND a.officetimesheetrefid = ".$refid." ORDER BY a.refid";
		}
		$db->query($sql);
		if($db->numRows() > 0){
			//first row is always special!!
				$invoiceCode = htmlspecialchars($db->getElement("invoicecoderefid"));
				$taskCode = htmlspecialchars($db->getElement("taskcoderefid"));
				$projectName = htmlspecialchars($db->getElement("projectname"));
				$colour = $db->getElement("colour");
				$note = $db->getElement("note");
				$date = date("d/m/Y",strtotime($db->getElement("dateworked")));
				$rateType = $db->getElement("ratetype");
				$hours = $db->getElement("hours");
				$identities = $db->getElement("identities");
				$db->nextRow();

			$response .="\t<STRUCT INVOICECODE=\"".$invoiceCode."\" TASKCODE=\"".$taskCode."\" PROJECTNAME=\"".$projectName."\" COLOUR=\"".$colour."\" NOTES=\"".$note."\">\n";
			$response .="\t\t<CHILDNODE";
			$response .=" DATE=\"".$date."\"";
			$response .=" RATETYPE=\"".$rateType."\"";
			$response .=" HOURS=\"".$hours."\"";
			$response .=">\n";
			$response .= translateEntries($identities);

			$prevInvoiceCode = $invoiceCode;
			$prevTaskCode = $taskCode;

			for($i = 1; $i < $db->numRows(); ++$i){

				$invoiceCode = htmlspecialchars($db->getElement("invoicecoderefid"));
				$taskCode = htmlspecialchars($db->getElement("taskcoderefid"));
				$projectName = htmlspecialchars($db->getElement("projectname"));
				$colour = $db->getElement("colour");
				$note = $db->getElement("note");
				$date = date("d/m/Y",strtotime($db->getElement("dateworked")));
				$rateType = $db->getElement("ratetype");
				$hours = $db->getElement("hours");
				$identities = $db->getElement("identities");
				$db->nextRow();

				if($invoiceCode != $prevInvoiceCode || $taskCode != $prevTaskCode ){
					$response .="\t\t</CHILDNODE>\n";
					$response .="\t</STRUCT>\n";
					$response .="\t<STRUCT INVOICECODE=\"".$invoiceCode."\" TASKCODE=\"".$taskCode."\" PROJECTNAME=\"".$projectName."\" COLOUR=\"".$colour."\"  NOTES=\"".$note."\">\n";
					$response .="\t\t<CHILDNODE";
					$response .=" DATE=\"".$date."\"";
					$response .=" RATETYPE=\"".$rateType."\"";
					$response .=" HOURS=\"".$hours."\"";
					$response .=">\n";
					$response .= translateEntries($identities);
					$prevInvoiceCode = $invoiceCode;
					$prevTaskCode = $taskCode;
					continue;
				}
				$response .="\t\t</CHILDNODE>\n";
				$response .="\t\t<CHILDNODE";
				$response .=" DATE=\"".$date."\"";
				$response .=" RATETYPE=\"".$rateType."\"";
				$response .=" HOURS=\"".$hours."\"";
				$response .=">\n";
				$response .= translateEntries($identities);
			}
			$response .="\t\t</CHILDNODE>\n";
			$response .="\t</STRUCT>\n";
		}
		$response .= "</FORM>\n";
		$more .= $response;
		break;
	case "saveTimeSheet":
		if(isset($_POST["xmlResponse"])){
			$status = "received";
		}
		//this tells the TOIL adjustment function if it should mark TOIL in advance or deny submission if not enough TOIL present
		$allowTOILinAdvance = false;
		//here is where data received from the interface is processed and added to the database
		$forms = $xml->getElementsByTagName("FORM");
		$form = $forms->item(0);
		$final = $form->getAttribute("FINAL");
		$showWeekend = $form->getAttribute("SHOWWEEKEND");
		$refid = intval($form->getAttribute("REFID"));
		$startTime = $form->getAttribute("STARTTIME");
		$stopTime = $form->getAttribute("STOPTIME");
		$resolution = $form->getAttribute("RESOLUTION");
		$weekEndingDate = $form->getAttribute("WEEKENDINGDATE");

		$oCredentials = new TimeSheetCredentials($refid);
		if($oCredentials->isNull()){
			$status = _("Time sheet not found");
			$ok = false;
		}
		if(!$oCredentials->canEdit()){
			//neither the owner, nor an administrator is saving this time sheet
			$status = _("You don't have clearance for this action!");
			$ok = false;
		}
		if($_SESSION["user"]->isAdmin()){
			$allowTOILinAdvance = true;//an admin can submit a time sheet with TOIL in advance
		}

		$db->beginTransaction();
		$ok = true;
		if($refid !=-1)	{
			onTimeSheetSave($refid,$db);
			//update = delete + insert
			$sql = "DELETE FROM tbl_office_time_sheet_entry_details	WHERE officetimesheetentryrefid IN
					(SELECT refid FROM tbl_office_time_sheet_entry WHERE officetimesheetrefid = ".$refid."); ";
			$sql .= "DELETE FROM tbl_office_time_sheet_entry WHERE officetimesheetrefid = ".$refid;
			$db->execMultiple($sql);
			$values = Array("starttime" => $startTime,
							"stoptime" => $stopTime,
							"showweekend" => $showWeekend,
							"submitted" => false,
							"submissiontime" => null);
			$where = " refid = ".$refid;
			$db->autoExec('tbl_office_time_sheet', $values, MDB2_AUTOQUERY_UPDATE, $where);
		}
		else{
			//inserting a new timesheet
			$dateArr = explode("/",$weekEndingDate);
			$usDate = $dateArr[1]."/".$dateArr[0]."/".$dateArr[2];
			$staffrefid = $_SESSION["user"]->refid;
			if(isset($_SESSION["adminCreatingTimeSheetFor"])){
				$staffrefid = intval($_SESSION["adminCreatingTimeSheetFor"]);
				$allowTOILinAdvance = true;
			}
			//checking if there's no other time sheet for this week for this employee
			$sql = "SELECT * FROM tbl_office_time_sheet
					WHERE enddate = ".$db->quote(date("Y-m-d",strtotime($usDate)))." AND staffrefid = ".$staffrefid;
			$db->query($sql);
			if($db->numRows() != 0) {
				$status = "duplicateTimeSheet";
				$ok = false;
			}
			else {
				$values = Array("staffrefid" => $staffrefid,
								"enddate" => date("Y-m-d",strtotime($usDate)),
								"guiresolution" => $resolution,
								"starttime" => $startTime,
								"stoptime" => $stopTime,
								"showweekend" => $showWeekend,
								"submitted" => false,
								"submissiontime" => null);
				$db->autoExec('tbl_office_time_sheet', $values, MDB2_AUTOQUERY_INSERT);
				$refid = $db->getLastInsertID('tbl_office_time_sheet','refid');
			}
		}
		if ($ok) {
			//insert part
			$structs = $form->getElementsByTagName("STRUCT");
			$i = 0;
			for($i = 0; $i < $structs->length; ++$i) {
				$struct = $structs->item($i);
				$invoiceCode = strtoupper($struct->getAttribute("INVOICECODE"));
				$taskCode = strtoupper(trim($struct->getAttribute("TASKCODE")));
				//this is usually blank, unless the taskcode is new to the database
				$taskName = trim($struct->getAttribute("TASKNAME"));
				$colour = $struct->getAttribute("COLOUR");
				$note = trim($struct->getAttribute("NOTES"));
				//checking the invoice code exists
				$sql = "SELECT invoicecode, department,taskneeded,completed FROM tbl_invoice_code_lookup
						WHERE ".$db->function->upper('invoicecode')." = ".$db->quote($invoiceCode);
				$db->query($sql, Array('text','text', 'boolean','text'));
				if($db->numRows() == 0)	{
					$status = "invoicecodeerror";
					$ok = false;
					break;//breaking out of the for loop
				}
				$invoiceCode = $db->getElement('invoicecode');
				if($db->getElement('completed') == 'closed'){
					$status = "closedproject";
					$more .= "\t<OFFENDING>".$invoiceCode."</OFFENDING>\n";
					$statusText = _("Project closed. \n You're not allowed to use invoice code ").htmlspecialchars($invoiceCode);
					$ok = false;
					break;//breaking out of the for loop
				}
				//checking if new task codes are allowed for this invoice code
				$allowedNewTaskCodes = true;
				if(strcmp($db->getElement("department"),"PX")!=0){
					$allowedNewTaskCodes = false;
				}
				if(formatBoolean($db->getElement("taskneeded"),true,false) || $taskCode != "" ) {
					//looking after the task code
					$sql = "SELECT taskcode FROM tbl_task_code_lookup WHERE ".$db->function->upper('taskcode')." = ".$db->quote($taskCode);
					$db->query($sql);
					if($db->numRows() == 0) {
						if($allowedNewTaskCodes){
							//the task code wasn't found and for this invoice code, new task codes are allowed to be inserted
							$values = Array("taskcode" => substr($taskCode,0,30),
											"taskname" => substr($taskName,0,500));
							$db->autoExec('tbl_task_code_lookup', $values, MDB2_AUTOQUERY_INSERT);
							if($db->getAffectedRows() != 1){
								$ok = false;
								$status = "error";
								$statusText = _("task code insert failed");
							}
							$sql = "SELECT groupnamerefid FROM tbl_invoice_group_matrix
									WHERE invoicecoderefid = ".$db->quote($invoiceCode);
							$db->query($sql);
							$groupName = $db->getElement("groupnamerefid");
							$values = Array("taskcoderefid" => substr($taskCode,0,30),
											"groupnamerefid" => $groupName);
							$db->autoExec('tbl_task_group_matrix', $values, MDB2_AUTOQUERY_INSERT);
							if($db->getAffectedRows() != 1){
								$ok = false;
								$status = "error";
								$statusText = _("task - group insert failed");
							}
						}
						else{
							$ok = false;
							$more .= "\t<OFFENDING>".$taskCode."</OFFENDING>\n";
							$status = "taskcodeerror";
							break;//breaking out of the for loop
						}
					}
					else{
						$taskCode = $db->getElement('taskcode');
					}
				}
				else{
					//$taskCode = "N/A";
				}

				//inserting data in the tbl_office_time_sheet_entry table
				$values = Array("invoicecoderefid" => substr($invoiceCode,0,30),
								"taskcoderefid" => substr($taskCode,0,30),
								"officetimesheetrefid" => $refid,
								"colour" => substr($colour,0,20),
								"note" => $note);
				$db->autoExec('tbl_office_time_sheet_entry', $values, MDB2_AUTOQUERY_INSERT);
				$structRefid = $db->getLastInsertID('tbl_office_time_sheet_entry','refid');

				$childnodes = $struct->getElementsByTagName("CHILDNODE");
				for($j = 0 ; $j < $childnodes->length ; ++$j){
					$childnode = $childnodes->item($j);
					$rateType = intval($childnode->getAttribute("RATETYPE"));
					$date = $childnode->getAttribute("DATE");
					$hours = $childnode->getAttribute("HOURS");
					$dateArr = explode("/",$date);
					$usDate = $dateArr[1]."/".$dateArr[0]."/".$dateArr[2];

					//inserting data in the tbl_office_time_sheet_entry_details table
					//assembling the identity list string
					$identities = "{";
					$entries = $childnode->getElementsByTagName("ENTRY");
					for($k = 0; $k < $entries->length-1; ++$k) {
						$entry = $entries->item($k);
						$guiref = $entry->getAttribute("ID");
						$identities .= "\"".$guiref."\",";
					}
					//adding the last entry id
					$entry = $entries->item($entries->length-1);
					$guiref = $entry->getAttribute("ID");
					$identities .= "\"".$guiref."\"}";

					$values = Array("dateworked" => date("Y-m-d",strtotime($usDate)),
									"officetimesheetentryrefid" => $structRefid,
									"ratetype" => $rateType,
									"hours" => $hours,
									"identities" => $identities);
					$db->autoExec('tbl_office_time_sheet_entry_details', $values, MDB2_AUTOQUERY_INSERT);
				}
			}
			if($i == $structs->length) {
				$status = "saved";
			}
			else {
				$ok = false;
				if (!isset($status)) {
					$status = "dberror";
				}
				if ($statusText == "") {
					$statusText = _("Some entries could not be saved");
				}
			}
		}
		if($ok){
			$db->commit();
			$status = "saved";
			if(strcmp($final,"true")==0) {
				onTimeSheetSubmit($refid, $allowTOILinAdvance);
			}
		}
		else{
			$db->rollback();
			$refid = -1;
			if (!isset($status)) {
				$status = "dberror";
			}
		}
		$more .= "\t<REFID>".$refid."</REFID>\n";
		break;
	default:
		$status = "request unrecognised";
}
finish($status, $statusText, $more);

?>
