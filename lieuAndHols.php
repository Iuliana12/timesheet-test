<?php require_once("header.php");
if(!$_SESSION["user"]->isAdmin()){
	error(_("access denied"));
	exit;
}
$template = new Template(Array("subTitle" => _("lieu & hols"),
							"content" => "lieuAndHols_.php"));
$template->display();
?>
		