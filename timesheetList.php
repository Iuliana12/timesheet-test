<?php
require_once('header.php');
$template = new Template(Array("subTitle" => _("time sheet list"),
								"content" => "timesheetList_.php",
								"scriptFiles" => Array("js/ajaxRequestModule.js","js/timesheetScripts.js")));
$template->display();
 ?>