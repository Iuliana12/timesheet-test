<?php
require_once("header.php");
$vars = Array();
if(!isset($_GET["invoicecode"])){
	error(_("Invoice code not found"));
}
$invoiceCode = rawurldecode(trim($_GET["invoicecode"]));
$db = new DB();
$sql = "SELECT projectmanager, projectname,department, requiresauthorization, taskneeded
		FROM tbl_invoice_code_lookup WHERE invoicecode = ".$db->quote($invoiceCode);
$db->query($sql, Array('integer', 'text', 'text', 'boolean', 'boolean'));
if($db->numRows() == 0){
	error(_("Invoice code not found"));
}
if($_SESSION["user"]->refid != $db->getElement("projectmanager")){
	error(_("access denied"));
	exit;
}
$vars["invoiceCode"] = $invoiceCode;
$vars["projectName"] = $db->getElement("projectname");
$vars["department"] = $db->getElement("department");
$vars["reqAuth"] = $db->getElement("requiresauthorization");
$vars["taskNeeded"] = $db->getElement("taskneeded");
$vars["departments"] = Array();

$sql = "SELECT DISTINCT department FROM tbl_invoice_code_lookup ORDER BY department ASC";
$db->query($sql);
for($i=0; $i<$db->numRows() ;++$i){
	if($db->getElement("department") == "")
		continue;
	$vars["departments"][] = $db->getElement("department");
	$db->nextRow();
}

$template = new Template(Array("subTitle" => _("manage invoice code"),
	"content" => "manageInvoice_.php",
	"scriptFiles" => Array(
        'js/ajaxRequestModule.js',
        'yui/build/yahoo-dom-event/yahoo-dom-event.js',
        'yui/build/connection/connection.js',
        'yui/build/animation/animation.js',
        'yui/build/autocomplete/autocomplete.js',
        'js/manageInvoiceScript.js'),
	"vars" => $vars
));
$template->display();
?>