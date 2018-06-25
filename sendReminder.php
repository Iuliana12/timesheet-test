<?php require_once("header.php");

$response = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
if(!isset($_POST["xmlResponse"]))
{
	$status = "parseerror";
	$response .= "<RESPONSE>\n";
	$response .= "\t<STATUS>".$status."</STATUS>\n";
	$response .= "</RESPONSE>\n";
	header("Content-type: application/xml");
	echo $response;
	die();
}
$xml = new DOMDocument();
$xml->preserveWhiteSpace=false;
$xml->loadXML(rawurldecode($_POST["xmlResponse"]));
	
$list = Array();
$items = $xml->getElementsByTagName("ITEM");
for($i = 0; $i < $items->length; ++$i)
{
	$list[] = intval($items->item($i)->textContent);
}
	$failNumber = 0;
	$db = new DB();
	$sql = "SELECT * FROM tbl_staff_lookup WHERE refid IN (".implode(",",$list).")";
	$db->query($sql);
	$rowNr = $db->numRows();
	if($rowNr < 1){
		$status = "error0";
	}
	else{
		for($i=0;$i<$rowNr;++$i){
			//sending an e-mail to the employee
			$email = strtolower($db->getElement("email"));
		   	$subject = _("Time sheet reminder");
			$message = _("Dear ").ucwords($db->getElement("fname")." ".$db->getElement("lname")).",\n\n";
			$message.= sprintf(_("This is an automated reminder for you to fill in your timesheet. Please login at %s and submit it as soon as possible.\n\n"),APP_URL);
			$message.= _("Thank you \n\nAdmin");
			$patern = '/^([a-z0-9])(([\\-]|[\.]|[_]+)?([a-z0-9]+))*(@)([a-z0-9])((([-]+)?([a-z0-9]+))?)*((.[a-z]{2,3})?(.[a-z]{2,6}))$/';

			if(preg_match($patern,$email)==0){
				++$failNumber;
				$status .= ucwords($db->getElement("fname")." ".$db->getElement("lname"))."(".$email."), ";
// 				echo strtoupper($rowarray["email"]);
				continue;
			}
			if(!@mail($email,$subject,$message,"From: ".EMAIL_SENDER)){
				++$failNumber;
			}
			$db->nextRow();
		}
		if($failNumber==0){
			$status = "sent";
		}
	}
	$response .= "<RESPONSE>\n";
	$response .= "\t<STATUS>".$status."</STATUS>\n";
	$response .= "\t<FAIL>".$failNumber."</FAIL>\n";
	$response .= "\t<SUCCESS>".($rowNr-$failNumber)."</SUCCESS>\n";
	$response .= "</RESPONSE>\n";
	header("Content-type: application/xml");
	echo $response;
	die();
?>