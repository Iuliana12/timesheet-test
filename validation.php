<?php
	require_once('header.php');
	$db = new DB();
	
	$xml= new DOMDocument();
	$xml->preserveWhiteSpace = false;
	$xml->loadXML(urldecode($_POST["xmlResponse"]));
//  $xml->loadXML(urldecode("%3C?xml%20version=%221.0%22%20encoding=%22UTF-8%22?%3E%0A%3CVALIDATE%20TYPE=%22invoicecode%22%3E%0A%09%09%3CCODE%3EV&amp;ABS%3C/CODE%3E%0A%3C/VALIDATE%3E"));

	$string = $_POST["xmlResponse"];
// 	$string = "%3C?xml%20version=%221.0%22%20encoding=%22UTF-8%22?%3E%0A%3CVALIDATE%20TYPE=%22invoicecode%22%3E%0A%09%09%3CCODE%3EV&amp;ABS%3C/CODE%3E%0A%3C/VALIDATE%3E";
 
	$validates = $xml->getElementsByTagName("VALIDATE");
	$validate = $validates->item(0);
	$type = $validate->getAttribute("TYPE");
	$entryIds = $xml->getElementsByTagName("ID");
	if($entryIds->length != 0)
		$entryId = $entryIds->item(0)->nodeValue;
	$refIds = $xml->getElementsByTagName("REFID");
	if($refIds->length != 0)
		$refid = $refIds->item(0)->nodeValue;

	$status = "invalid";
	$statusText = "Please contact the web developer";
	$debugString = "";

	switch($type){
		case "invoicecode":
			$codes = $xml->getElementsByTagName("CODE");
			$invoiceCode = strtoupper($codes->item(0)->nodeValue);
			$sql = "SELECT projectname,taskneeded,completed FROM tbl_invoice_code_lookup 
					WHERE invoicecode = ".$db->quote($invoiceCode)." OR invoicecode = ".$db->quote(strtoupper($invoiceCode));
			$db->query($sql, Array('text', 'boolean', 'text'));
			if($db->numRows() == 0)	{
				$statusText = _("Invoice code not found. \n Please choose a valid invoice code from the list.");
				break;
			}
			$projectName = "".$db->getElement("projectname");
			$taskNeeded = $db->getElement("taskneeded");
			$completed = $db->getElement('completed');
			if($db->getElement("completed") == 'closed'){
				$status = "closed";
				$statusText = _("Project closed. \n You are not allowed to use this invoice code");
				break;
			}
			if($completed == 'complete'){
				$status = "completed";
				$statusText = _("Invoice code completed. \n Are you sure you want to use this invoice code?");
				break;
			}			
			$status = "valid";
			$statusText = "";
			break;
		case "taskcode":
			$codes = $xml->getElementsByTagName("CODE");
			$taskCode = strtoupper($codes->item(0)->nodeValue);
			$icodes = $xml->getElementsByTagName("ICODE");
			$invoiceCode = strtoupper($icodes->item(0)->nodeValue);
			
			$sql = "SELECT department,taskneeded FROM tbl_invoice_code_lookup 
					WHERE invoicecode = ".$db->quote($invoiceCode)." OR invoicecode = ".$db->quote(strtoupper($invoiceCode));
			$db->query($sql, Array('text', 'boolean'));
			$department = "nothing!!";
			$taskneeded = true;
			if($db->numRows() > 0) {
				$department = $db->getElement("department");
				$taskneeded = formatBoolean($db->getElement("taskneeded"),true,false);
			}
			if($taskneeded){
				if(strcmp(strtoupper($taskCode),"N/A")==0) {
					$statusText =_("The N/A task code is not allowed for this invoice code.Please choose another!");
					break;
				}
				if(strcmp(trim($taskCode),"")==0) {
					$statusText = _("Empty task code not allowed for this invoice code!");
					break;
				}
			}
			$allowedNewTaskCodes = false;
			if(($db->numRows() != 0 && strcmp($department,"PX") ==0)){
				$allowedNewTaskCodes = true;
			}
			$sql = "SELECT * FROM tbl_task_code_lookup WHERE taskcode = ".$db->quote($taskCode)." OR taskcode = ".$db->quote(strtoupper($taskCode));
			$db->query($sql);
			if($db->numRows() == 0 && !$allowedNewTaskCodes) {
				$statusText = _("Task code not found. \n Please choose a valid task code from the list.");
				break;
			}
			if($db->numRows() == 0 && $allowedNewTaskCodes){
				$status = "allowednew";
				$statusText = _("Your task code has not been found, but you are allowed to add a new one.");
				break;
			}
			if($allowedNewTaskCodes){
				$status = "valid";
				$statusText = "";
				break;
			}
			//checking if this task code is related to the given invoice code
			$sql = "SELECT tsk.taskcode,tsk.taskname FROM tbl_invoice_code_lookup i 
					LEFT JOIN tbl_invoice_group_matrix g ON i.invoicecode = g.invoicecoderefid 
					LEFT JOIN tbl_invoice_task_group it ON g.groupnamerefid = it.groupname 
					LEFT JOIN tbl_task_group_matrix t ON it.groupname = t.groupnamerefid 
					LEFT JOIN tbl_task_code_lookup tsk ON t.taskcoderefid = tsk.taskcode 
					WHERE (i.invoicecode = ".$db->quote($invoiceCode)." 
					OR i.invoicecode = ".$db->quote(strtoupper($invoiceCode)).") 
					AND ( tsk.taskcode = ".$db->quote($taskCode)." 
					OR tsk.taskcode = ".$db->quote(strtoupper($taskCode)).")";
			$db->query($sql);
			if($db->numRows() == 0){
				$statusText = sprintf(_("The task code you selected is invalid for invoice code '%s'. \n Please choose a valid task code from the list."),htmlspecialchars($invoiceCode));
				break;
			}
			$status = "valid";
			$statusText = "";
			break;
	}

	$response = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	$response.= "<RESPONSE>\n";
	$response .="\t<STATUS>".$status."</STATUS>\n";
	$response .="\t<STATUSTEXT>".$statusText."</STATUSTEXT>\n";
	if(isset($projectName))//this will return the projectname in case the invoice code is valid
		$response .="\t<PROJECTNAME>".htmlspecialchars($projectName)."</PROJECTNAME>\n";
	if(isset($taskNeeded))
		$response .="\t<TASKNEEDED>".formatBoolean($taskNeeded,"true","false")."</TASKNEEDED>\n";
	if(isset($entryId))
		$response .="\t<ID>".$entryId."</ID>\n";
	$response.= "</RESPONSE>";
	header("Content-type: application/xml");
	echo $response;
?>