<?php
require_once('header.php');
$db = new DB();

if(isset($_GET["field"]))
	$field = htmlspecialchars(strtolower(trim($_GET["field"])));
else
    $field = "";
if(isset($_GET["query"]))
	$query = "".strtolower(trim(rawurldecode($_GET["query"])));
else
	$query = "";

$response = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$response.= "<RESPONSE>\n";
switch($field){
	case "invoicecode":
        $conditions = "WHERE completed <> ".$db->quote('closed')." AND ".$db->function->lower('invoicecode')." LIKE ".$db->quote($query.'%');
        $sql = "(SELECT DISTINCT invoicecode,projectname,completed
                    FROM tbl_office_time_sheet_entry AS tse JOIN tbl_invoice_code_lookup icl ON tse.invoicecoderefid = icl.invoicecode
                    " . $conditions . " AND officetimesheetrefid IN (select refid from tbl_office_time_sheet where staffrefid = " . $_SESSION["user"]->refid . " order by enddate desc limit 5)
                    ORDER BY invoicecode ASC)
                UNION ALL
                SELECT DISTINCT invoicecode,projectname,completed FROM tbl_invoice_code_lookup ".$conditions;
        $arrUnique = array();
		$db->query($sql, Array('text', 'text', 'text'));
		for($i = 0; $i < $db->numRows();++$i){
            $invoiceCode = $db->getElement('invoicecode');
            if(in_array($invoiceCode,$arrUnique)){
                $db->nextRow();
                continue;
            }
            $arrUnique[] = $invoiceCode;
			$response .= "\t<RESULT>\n";
			$response .= "\t\t<INVOICECODE>".htmlspecialchars($db->getElement('invoicecode'))."</INVOICECODE>\n";
			$response .= "\t\t<PROJECTNAME>".htmlspecialchars($db->getElement('projectname'))."</PROJECTNAME>\n";
			$response .= "\t\t<COMPLETED>".($db->getElement('completed') == "complete" ? 'true' : 'false')."</COMPLETED>\n";
			$response .= "\t</RESULT>\n";
            if(count($arrUnique) >= 100){//will return only the first 100 matches
                break;
            }
			$db->nextRow();
		}
		break;
	case "taskcode":
		if(isset($_GET["limit"]) && intval($_GET["limit"])>0)
			$limit = intval($_GET["limit"]);
		else
			$limit = 30;
		$invoicecode = urldecode(strtolower(trim($_GET["invoicecode"])));
		$sql = "SELECT DISTINCT tsk.taskcode,tsk.taskname,i.taskneeded FROM tbl_invoice_code_lookup i
				LEFT JOIN tbl_invoice_group_matrix g ON i.invoicecode = g.invoicecoderefid 
				LEFT JOIN tbl_invoice_task_group it ON g.groupnamerefid = it.groupname 
				LEFT JOIN tbl_task_group_matrix t ON it.groupname = t.groupnamerefid 
				LEFT JOIN tbl_task_code_lookup tsk ON t.taskcoderefid = tsk.taskcode 
				WHERE i.invoicecode LIKE ".$db->quote(strtoupper($invoicecode))." AND 
				".$db->function->lower('tsk.taskcode')." LIKE ".$db->quote($query.'%')." 
				ORDER BY tsk.taskcode ASC";
		$db->query($sql,Array('text', 'text', 'boolean'));
		$rowNr = $db->numRows();
		for($i=0;$i<$rowNr && $i<$limit;++$i)//will return only the first $limit matches
		{
			$rowarray = $db->getRow();
			if(strcmp(trim($query),"")==0 && is_numeric($rowarray["taskcode"]) && $rowNr > $limit)
			{//if an empty query string was given and there are a lot of values, I won't provide the numeric taskcodes.
                $db->nextRow();
				continue;
			}
			if(strcmp($rowarray["taskcode"],"") == 0 || ($rowarray["taskneeded"] && strcmp($rowarray["taskcode"],"N/A") == 0)) {
                $db->nextRow();
                continue;
            }
			$response .= "\t<RESULT>\n";
			foreach($rowarray as $key => $value)
				$response .= "\t\t<".strtoupper($key).">".htmlspecialchars($value)."</".strtoupper($key).">\n";
			$response .= "\t</RESULT>\n";
			$db->nextRow();
		}
		break;
	case "users":
		if(!$_SESSION["user"]->isAdmin()){
			break;
		}
		$orderBy = "go to default";
		$orderDir = "ascending";
		if(isset($_GET["by"]) && isset($_GET["dir"]))
		{
			$orderBy = htmlspecialchars(strtolower(trim($_GET["by"])));
			$orderDir = htmlspecialchars(strtolower(trim($_GET["dir"])));
		}
		if(isset($_GET["showunemployed"]) && strcmp($_GET["showunemployed"],"true")==0)
			$showunemployed = "";
		else
			$showunemployed = " employed = true AND ";
		//cost centre processing
		$fav_cost_centre = $_SESSION["user"]->costCentre;
		if (isset($_GET["cost_centre"]) && $fav_cost_centre != trim($_GET["cost_centre"])) {
			$fav_cost_centre = trim($_GET["cost_centre"]);
			if ($fav_cost_centre == '') {
				$db_cost_centre = '-';
				$fav_cost_centre = '-';
			}
			else {
				$db_cost_centre = $fav_cost_centre;
			}
			$values = Array("cost_centre" => $db->escape($db_cost_centre) );
			$where = "staff_refid = ".$_SESSION["user"]->refid;
			$db->autoExec('tbl_staff_preferences', $values, MDB2_AUTOQUERY_UPDATE, $where);
			$_SESSION["user"]->costCentre = $fav_cost_centre;
		}
		$cost_centre_addon = '';
		if ($fav_cost_centre != '-' && $fav_cost_centre != '') {
			$cost_centre_addon = "AND cost_centre = ".$db->quote($fav_cost_centre);	
		}
	
		$orderString = " ORDER BY";
		switch($orderBy)
		{
			case "lname":
				$orderString .= " lname";
				$orderString.= " ".strtoupper($orderDir);
				break;
			case "fname":
				$orderString .= " fname";
				$orderString.= " ".strtoupper($orderDir);  
				break;
			case "lname-fname":
				$orderString .= " lname";
				$orderString.= " ".strtoupper($orderDir);
				$orderString .= ", fname";
				$orderString.= " ".strtoupper($orderDir);
				break;
			case "fname-lname":
				$orderString .= " fname";
				$orderString.= " ".strtoupper($orderDir);
				$orderString .= ", lname";
				$orderString.= " ".strtoupper($orderDir);
				break;
  			default:
				$orderString = " ORDER BY lname ASC, fname ASC";
		}
		$sql = "SELECT refid,fname,lname FROM tbl_staff_lookup 
				WHERE ".$showunemployed." ( ".$db->function->lower('lname')." LIKE ".$db->quote($query.'%')." 
				OR ".$db->function->lower('fname')." LIKE ".$db->quote($query.'%')." 
				OR ".$db->function->lower('username')." LIKE ".$db->quote($query.'%')." 
				OR ".$db->function->lower('email')." LIKE ".$db->quote($query.'%').") ".$cost_centre_addon.$orderString;
		$response .= createResponse($db,$sql);
		break;
	case "managers":
		if(!$_SESSION["user"]->isAdmin()){
			break;
		}
		$sql = "SELECT refid,lname,fname FROM tbl_staff_lookup 
				WHERE lname IS NOT NULL AND fname IS NOT NULL AND fname <> 'Not set' AND lname <> 'Not set' 
				ORDER BY lname ASC,fname ASC";
		$response .= createResponse($db,$sql);
		break;
	case "employee":
		$sql = "SELECT refid,lname,fname FROM tbl_staff_lookup 
				WHERE LOWER(lname) LIKE ".$db->quote($query.'%')." OR LOWER(fname) LIKE ".$db->quote($query.'%')."
				 ORDER BY fname ASC,lname ASC";
		$response .= createResponse($db,$sql);
		break;
 	default: ;// nothing
}
function createResponse($dbconn,$sql){
	//$response = '<SQL>'.htmlspecialchars($sql).'</SQL>';
	$dbconn->query($sql);
	$rowNr = $dbconn->numRows();
	for($i=0;$i<$rowNr && $i<100;++$i)//will return only the first 100 matches
	{
		$rowarray = $dbconn->getRow();
		$response .= "\t<RESULT>\n";
		foreach($rowarray as $key => $value)
				$response .= "\t\t<".strtoupper($key).">".htmlspecialchars($value)."</".strtoupper($key).">\n";
		$response .= "\t</RESULT>\n";
		$dbconn->nextRow();
	}
	return $response;
}
$response.= "</RESPONSE>";
header("Content-type: application/xml");
echo $response;
?>