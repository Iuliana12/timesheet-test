<?php
require_once("header.php");
if(!$_SESSION["user"]->isAdmin()){
	error(_("access denied"));
	exit;
}
$template = new Template(Array("subTitle" => _("submission status"),
							"content" => "submissionStatus_.php",
							"styleSheets" => Array("timesheets.css","yui/build/calendar/assets/calendar.css"),
							"scriptFiles" => Array('js/ajaxRequestModule.js',
                                'js/ErrorDisplay.js',
                                'js/timesheetScripts.js',
                                'yui/build/yahoo-dom-event/yahoo-dom-event.js',
                                'yui/build/calendar/calendar-min.js',
                                'js/submissionScripts.js')
							));
$template->display();
?>