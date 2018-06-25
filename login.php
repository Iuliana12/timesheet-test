<?php
require_once('header.php');
if(isset($_REQUEST['l'])){
	phpCAS::forceAuthentication();
	header("Location: index.php");
	exit();
}

$loginanchor = '<a href="login.php?l=" title="SSO login">';
$infoSectionContent = sprintf( _("Welcome to <b>Time Recording System</b>. Please %s login%s to continue"),$loginanchor,'</a>');
$template = new Template(Array("subTitle" => _("login"),
								"hasInfoSection" => true,
								"content" => 'login_.php',
								"infoSectionContent" => $infoSectionContent,
								"hasMenu" => false,
								"vars" => Array("status" => $status)));
$template->display();
 ?>
