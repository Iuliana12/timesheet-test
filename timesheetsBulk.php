<?php
require_once("header.php");
if(!$_SESSION["user"]->isAdmin()){
	error(_("access denied"));
	exit;
}
$template = new Template(Array("subTitle" => _("Bulk time sheets"),
							"content" => "timesheetsBulk_.php",
							"styleSheets" => Array("timesheets.css","yui/build/calendar/assets/calendar.css"),
							"scriptFiles" => Array(
									'yui/build/yahoo-dom-event/yahoo-dom-event.js',
									'yui/build/connection/connection.js',
                                    'yui/build/animation/animation.js',
									'yui/build/autocomplete/autocomplete.js',
                                    'yui/build/calendar/calendar-min.js',
									'js/bulkScript.js',
									'js/newTimesheetScripts.js')));
$template->display();
?>