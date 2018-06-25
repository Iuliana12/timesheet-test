<?php
require_once("header.php");
$template = new Template(Array("subTitle" => _("report export"),
								"content" => "reportExport_.php",
                                "styleSheets" => Array("timesheets.css","yui/build/calendar/assets/calendar.css"),
								"scriptFiles" => Array('js/ajaxRequestModule.js',
														'js/ErrorDisplay.js',
														'yui/build/yahoo-dom-event/yahoo-dom-event.js',
														'yui/build/connection/connection.js',
														'yui/build/animation/animation.js',
														'yui/build/autocomplete/autocomplete.js',
                                                        'yui/build/calendar/calendar-min.js',
														'js/totalerScript.js')
								));
$template->display();
?>