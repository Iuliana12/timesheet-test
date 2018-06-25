<?php
require_once("contentScriptStart.php");

$defaultMenu = new Collection("Default Menu");
$timesheetMenuItems = new Collection("Time Sheet Menu Items");

$item = new MenuItem(Array("text" => _("Add new time sheet"),"href" => "timesheetNew.php"));
$timesheetMenuItems->addItem($item);
foreach(getNotSubmittedTimeSheets() as $refid => $object){
	$item = new MenuItem(Array("text" => $object->displayString,"href" => "timesheetEdit.php?refid=".$refid));
	$timesheetMenuItems->addItem($item);
}
if(isset($_SESSION["user"]) && $_SESSION["user"]->authorizesSubordinates){
	$item = new MenuItem(Array("text" => _("Subordinate CSV report"),"href" => "CSVTimeSheetReport.php?type=subordinates"));
	$timesheetMenuItems->addItem($item);
}

$item = new MenuItem(Array("text" => _("Archive"),"href" => "archive.php"));
$defaultMenu->addItem($item);
$item = new MenuItem(Array("text" => _("Time Sheets"),"href" => "timesheetList.php", "subItems" => $timesheetMenuItems));
$defaultMenu->addItem($item);
$toolsMenuItems = new Collection("Tools Menu Items");
$item = new MenuItem(Array("text" => _("Time Calculator"),"href" => "timeTotaler.php"));
$toolsMenuItems->addItem($item);

if(isset($_SESSION["user"]) && $_SESSION["user"]->canAccessReports()){
	$item = new MenuItem(Array("text" => _("Reports"),"href" => "report.php"));
	$toolsMenuItems->addItem($item);
	$item = new MenuItem(Array("text" => _("Report Export"),"href" => "reportExport.php"));
	$toolsMenuItems->addItem($item);
}
$item = new MenuItem(Array("text" =>_("Tools"),"href" => "", "subItems" => $toolsMenuItems));
$defaultMenu->addItem($item);

if(isset($_SESSION["user"]) && $_SESSION["user"]->isAdmin()){
	$adminMenuItems = new Collection("Admin Menu Items");
	$item = new MenuItem(Array("text" => _("Employees"),"href" => "employees.php"));
	$adminMenuItems->addItem($item);
	$item = new MenuItem(Array("text" => _("Submission Status"),"href" => "submissionStatus.php"));
	$adminMenuItems->addItem($item);
	$item = new MenuItem(Array("text" => _("Lieu & Hols"),"href" => "lieuAndHols.php"));
	$adminMenuItems->addItem($item);
	$item = new MenuItem(Array("text" => _("Archive"),"href" => "archive.php"));
	$adminMenuItems->addItem($item);
	$item = new MenuItem(Array("text" => _("Bulk time sheets"),"href" => "timesheetsBulk.php"));
	$adminMenuItems->addItem($item);

	$item = new MenuItem(Array("text" => _("Admin"),"href" => "", "subItems" => $adminMenuItems));
	$defaultMenu->addItem($item);
}

//displaying the menu
$pathParts = pathinfo($_SERVER["SCRIPT_NAME"]);
$currentMenuItem = $pathParts['basename'];
$pathParts = pathinfo($_SERVER["REQUEST_URI"]);
$currentMenuUri = $pathParts['basename'];

echo '<ul>';
for($i = 0; $i < $defaultMenu->length; ++$i){
	$menuItem = $defaultMenu->get($i);
	$menuItemTitle = $menuItem->text;
	$class = '';
	if ($menuItem->href == $currentMenuItem) {
		$class = 'active';
	}
	//check if one of the sub menu items is active
	for($j = 0; $j < $menuItem->subItems->length; ++$j){
		$subMenuItem = $menuItem->subItems->get($j);
		if ($subMenuItem->href == $currentMenuUri) {
			$class = 'active';
		}
	}
	if($menuItem->subItems->length > 0) {
		$class .= ' parent';
	}
	echo '<li class="'.$class.'">';
	if ($menuItem->href == '') {
		$menuItem->href = "javascript:void(0)";
	}
	echo '<a href="'.$menuItem->href.'" '.$class.'>'.$menuItemTitle.'</a>';
	if($menuItem->subItems->length > 0) {
		echo '<ul class="subMenu">';
		for($j = 0; $j < $menuItem->subItems->length; ++$j){
			$subMenuItem = $menuItem->subItems->get($j);
			$subMenuItemTitle = str_replace(' ','&nbsp;',$subMenuItem->text);
			$class = '';
			if ($subMenuItem->href == $currentMenuUri) {
				$class = 'active';
			}
			echo '<li  class="'.$class.'"><a href="'.$subMenuItem->href.'">'.$subMenuItemTitle.'</a></li><li class="clear"></li>';
		}
		echo '</ul>';
	}
	echo '</li>';
}
//adding the language selection item
echo '<li><a>';
echo '<form name="language" method="get" style="display:inline">';
echo _("Language").'&nbsp;<select name="language" onchange="document.forms.language.submit()">';
$lang = getBestSuitedLanguage(Array('en','fr'));
$addon = '';
if($lang->general == 'en'){
	$addon = 'selected="selected"';
}
echo '<option value="en" '.$addon.'>'._("English").'</option>';
$addon = '';
if($lang->general == 'fr'){
	$addon = 'selected="selected"';
}
echo '<option value="fr" '.$addon.'>'._("French").'</option>';
echo '</select></form></a></li>';
echo '</ul>';
?><span class="hide-me-on-mobile">&nbsp;</span>
