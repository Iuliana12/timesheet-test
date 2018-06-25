<?php
require_once("header.php");

$template = new Template(Array("subTitle" => _("archive"),
							"content" => "archive_.php",
							"styleSheets" => Array("timesheets.css","yui/build/calendar/assets/calendar.css")));
$template->display();
?>