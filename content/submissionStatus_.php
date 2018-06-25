<?php
require_once("contentScriptStart.php");
function displayHeader($count){
    $displayString = "";
    if($count == 0){
        $displayString .= "<thead>\n";
    }
	$displayString .= "<tr>\n";
	$displayString .= "\t<th class=\"mainFormTextLabel2\">"._('Employee')."</th>\n";
	$displayString .= "\t<th class=\"mainFormTextLabel2\">"._('Hours')."</th>\n";
	$displayString .= "\t<th class=\"mainFormTextLabel2\">"._('Lieu OT')."</th>\n";
	$displayString .= "\t<th class=\"mainFormTextLabel2\">"._('Charged OT')."</th>\n";
	$displayString .= "\t<th class=\"mainFormTextLabel2\">"._('Sunday OT')."</th>\n";
	$displayString .= "\t<th class=\"mainFormTextLabel2\">"._('HOLS')."</th>\n";
	$displayString .= "\t<th class=\"mainFormTextLabel2\">"._('TOIL')."</th>\n";
	$displayString .= "\t<th class=\"mainFormTextLabel2\">"._('SICK')."</th>\n";
	$displayString .= "\t<th class=\"mainFormTextLabel2\">"._('UNPAID')."</th>\n";
	$displayString .= "\t<th class=\"mainFormTextLabel2\">"._('Submission time')."</th>\n";
	$displayString .= "\t<th class=\"mainFormTextLabel2\"></th>\n";
	$displayString .= "\t<th class=\"mainFormTextLabel2\"></th>\n";
	$displayString .= "\t<th class=\"mainFormTextLabel2\"></th>\n";
	$displayString .= "</tr>\n";
    if($count == 0){
        $displayString .= "</thead>\n";
    }
	return $displayString;
}
function displayOneRecord($timesheet){
	$displayString = "";
	$displayString .= "<tr>\n";
	if($timesheet->lieu	!=0 && $timesheet->toil !=0){
		$displayString .= "\t<td class=\"mainFormTextLabel highlightRed\">".$timesheet->lastName." ".$timesheet->firstName."</td>";
	}
	else {
		$displayString .= "\t<td class=\"mainFormTextLabel\">".$timesheet->lastName." ".$timesheet->firstName."</td>";
	}
	//if they worked more than the minimum hours but they haven't submitted any lieu or charged overtime
	if($timesheet->hours != $timesheet->minHours && $timesheet->lieu ==0 && $timesheet->charged==0)
		$displayString .= "\t<td class=\"mainFormTextLabel\"><span class=\"highlightRed\">".$timesheet->hours."</span></td>";
	else
		$displayString .= "\t<td class=\"mainFormTextLabel\">".$timesheet->hours."</td>";
	$lieu = $timesheet->lieu;
	$charged = $timesheet->charged;
	$sundaycharged = $timesheet->sundaycharged;
	$hols = $timesheet->hols;
	$toil = $timesheet->toil;
	$sick = $timesheet->sick;
	$unpaid = $timesheet->unpaid;
	if ($timesheet->lieu == 0) {
		$lieu = "-";
	}
	if ($timesheet->charged == 0) {
		$charged = "-";
	}
	if ($timesheet->sundaycharged == 0) {
		$sundaycharged = "-";
	}
	if ($timesheet->hols == 0) {
		$hols = "-";
	}
	if ($timesheet->toil == 0) {
		$toil = "-";
	}
	if ($timesheet->sick == 0) {
		$sick = "-";
	}
	if ($timesheet->unpaid == 0) {
		$unpaid = "-";
	}
	$displayString .= "\t<td class=\"mainFormTextLabel\">".$lieu."</td>";
	$displayString .= "\t<td class=\"mainFormTextLabel\">".$charged."</td>";
	$displayString .= "\t<td class=\"mainFormTextLabel\">".$sundaycharged."</td>";
	$displayString .= "\t<td class=\"mainFormTextLabel\">".$hols."</td>";
	$displayString .= "\t<td class=\"mainFormTextLabel\">".$toil."</td>";
	$displayString .= "\t<td class=\"mainFormTextLabel\">".$sick."</td>";
	$displayString .= "\t<td class=\"mainFormTextLabel\">".$unpaid."</td>";
    $insideMenuButtons = 'inside-tbl-menu-3';
	if($timesheet->submitted) {
		$displayString .= "\t<td class=\"mainFormTextLabel\" style=\"font-size:7pt\">".$timesheet->submissiontime."</td>\n";
	}
	else {
        $insideMenuButtons = 'inside-tbl-menu-4';
		$displayString .= "\t<th class=\"mainFormTextMenu $insideMenuButtons bootstrap-btn-style bootstrap-orange\"><a class=\"keyLink\" href=\"javascript:remindEmployee(".$timesheet->employeerefid.")\">remind</a><input type=\"checkbox\" name=\"remind\" checked=\"checked\" value=\"".$timesheet->employeerefid."\"/></td>\n";
	}
	$displayString .= "\t<th class=\"mainFormTextMenu $insideMenuButtons bootstrap-btn-style bootstrap-green\"><a class=\"keyLink\" href=\"timesheetView.php?refid=".$timesheet->refid."\">view</a></td>";
	$displayString .= "<th class=\"mainFormTextMenu $insideMenuButtons bootstrap-btn-style bootstrap-blue\"><a class=\"keyLink\" href=\"timesheetEdit.php?refid=".$timesheet->refid."\">edit</a></td>";
	$displayString .= "<th class=\"mainFormTextMenu $insideMenuButtons bootstrap-btn-style bootstrap-red\"><a class=\"keyLink\" href=\"javascript:deleteTimesheet(".$timesheet->refid.")\">delete</a></td>\n";
	$displayString .= "</tr>\n";
	return $displayString;
}

$db = new DB();
//cost centre processing
$fav_cost_centre = $_SESSION["user"]->costCentre;
if (isset($_GET["cost_centre"]) && $fav_cost_centre != trim($_GET["cost_centre"])) {
	$fav_cost_centre = trim($_GET["cost_centre"]);
	if ($fav_cost_centre == '') {
		$db_cost_centre = '-';
		$fav_cost_centre = '-';
	}
	else {
		$db_cost_centre = $fav_cost_centre;
	}
	$values = Array("cost_centre" => $db->escape($db_cost_centre) );
	$where = "staff_refid = ".$_SESSION["user"]->refid;
	$db->autoExec('tbl_staff_preferences', $values, MDB2_AUTOQUERY_UPDATE, $where);
	$_SESSION["user"]->costCentre = $fav_cost_centre;
}
$cost_centre_addon = '';
if ($fav_cost_centre != '-' && $fav_cost_centre != '') {
	$cost_centre_addon = "AND A.cost_centre = ".$db->quote($fav_cost_centre);
}

$fav_location = '';
$location_addon = '';
if (isset($_GET["location"])) {
	$fav_location = trim($_GET["location"]);
	if ($fav_location == '') {
		$fav_location = '-';
	}
	if ($fav_location != '-' && $fav_location != '') {
		$location_addon = "AND A.location = ".$db->quote($fav_location);
	}
}

if(isset($_GET["date"])){
	$thisFridayTime = strtotime($_GET["date"]);
}
else{
	$dateAttribArray = getdate();
	if($dateAttribArray["wday"]== 5)
		$thisFridayTime = time();
	else
		$thisFridayTime = strtotime("last Friday");
}
$thisFriday = date("Y-m-d",$thisFridayTime);
$timeSheetWeekStart = _("Sat")." ".strftime("%d %b",strtotime("-6 days",$thisFridayTime));
$timeSheetWeekEnd = _("Fri")." ".strftime("%d %b",$thisFridayTime);
$sql = "SELECT A.refid,A.fname,A.lname,A.minhours,E.enrolled,B.refid AS timesheetrefid,B.submitted,B.submissiontime,
		SUM(C.hours) AS hours,
		SUM(CASE WHEN C.ratetype = ".$db->quote('1')." THEN C.hours ELSE 0 END) AS lieu,
		SUM(CASE WHEN C.ratetype = ".$db->quote('2')." THEN C.hours ELSE 0 END) AS charged,
		SUM(CASE WHEN C.ratetype = ".$db->quote('3')." THEN C.hours ELSE 0 END) AS sundaycharged,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('TOIL')." THEN C.hours ELSE 0 END) AS toil,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('HOL')." THEN C.hours ELSE 0 END) AS hols,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('SICK')." THEN C.hours ELSE 0 END) AS sick,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('UNPAID')." THEN C.hours ELSE 0 END) AS unpaid,
		COUNT(CASE WHEN D.invoicecoderefid = ".$db->quote('HOL')." THEN C.dateworked END) AS holdays
		FROM tbl_staff_lookup AS A LEFT JOIN tbl_staff_preferences E ON E.staff_refid = A.refid
		join tbl_office_time_sheet AS B ON A.refid = B.staffrefid
		LEFT JOIN tbl_office_time_sheet_entry AS D ON (D.officetimesheetrefid = B.refid)
		LEFT JOIN tbl_office_time_sheet_entry_details AS C ON (C.officetimesheetentryrefid = D.refid)
		WHERE A.refid = B.staffrefid AND B.enddate = ".$db->quote($thisFriday)."
		".$cost_centre_addon." ".$location_addon."
		GROUP BY A.refid,A.fname,A.lname,A.minhours,B.refid,B.submitted,B.submissiontime,E.enrolled
		ORDER BY B.submitted ASC,A.lname ASC,A.fname ASC,B.refid ASC";
$db->query($sql, Array('integer','text', 'text', 'float', 'boolean', 'integer', 'boolean', 'text',
						'float', 'float', 'float', 'float', 'float', 'float','float', 'float', 'float'));
$refidList = array();
$timesheets = new TimeSheetList();
for($i=0;$i < $db->numRows();++$i) {
	$timesheet = new TimeSheet();
	$timesheet->fromArray($db->getRow());
	$timesheets->add($timesheet);
	$db->nextRow();
}
?>
<h4><?php echo _("Time sheet submission status")?></h4>
<a class="keyLink half-row-cell bootstrap-btn-style bootstrap-green" href="CSVsubmissionStatus.php?date=<?php echo  $thisFriday?>&cost_centre=<?php echo  $fav_cost_centre ?>&location=<?php echo  $fav_location ?>"><?php echo _("download as CSV")?></a>
<div id="mainFormContainer" class="parag-padd">
		<?php echo  _("Week") ?>&nbsp;
		<a class="keyLink bootstrap-btn-style bootstrap-blue" href="javascript:prevWeek()">&lt;&lt;</a>
		<input name="weekStart" type="text" id="weekStart" value="<?php echo  $timeSheetWeekStart?>" readonly="readonly" class="weekPartDisplay"/>
		-
		<input name="WeekEnd" type="text" id="weekEnd" value="<?php echo  $timeSheetWeekEnd?>" readonly="readonly" class="weekPartDisplay"/>
		<a class="keyLink bootstrap-btn-style bootstrap-blue" href="javascript:nextWeek()">&gt;&gt;</a>
		<input type="image" src="images/calendar.gif" onclick="onFridayDateInput(event)" />
		&nbsp;<p class="makeMeBlock noMargin"><?php echo _("Cost centre")?></p>
		<form name="parameters" method="get" style="display: inline">
			<input name="date" type="hidden" id="friday" value="<?php echo  $thisFriday?>" />
			<select name="cost_centre" onchange="document.forms.parameters.submit()">
				<option value=""></option>
				<?php
					//cost centre list
					$sql = 'SELECT DISTINCT cost_centre FROM tbl_staff_lookup WHERE employed = true AND cost_centre IS NOT NULL';
					$db->query($sql);
					for ($i = 0; $i < $db->numRows(); ++$i){
						$cost_centre = htmlspecialchars($db->getElement("cost_centre"));
					 	$selected = '';
						if ($fav_cost_centre == $cost_centre) {
							$selected = 'selected="selected"';
						}
					 	echo "\t\t\t<option value=\"".$cost_centre."\" ".$selected.">".$cost_centre."</option>\n";
					 	$db->nextRow();
					 }
				?>
			</select>
			<p class="makeMeBlock"><?php echo _("Location")?></p>
			<select name="location" onchange="document.forms.parameters.submit()">
				<option value=""></option>
				<?php
					//location list
					$sql = 'SELECT DISTINCT location FROM tbl_staff_lookup WHERE employed = true AND location IS NOT NULL';
					$db->query($sql);
					for ($i = 0; $i < $db->numRows(); ++$i){
						$location = htmlspecialchars($db->getElement("location"));
						$selected = '';
						if ($fav_location == $location) {
							$selected = 'selected="selected"';
						}
					 	echo "\t\t\t<option value=\"".$location."\" ".$selected.">".$location."</option>\n";
					 	$db->nextRow();
					 }
				?>
			</select>

		</form>
</div>
<div class="minifiedTable">
    <table class="FormTable">
        <tr>
            <th class="mainFormTextMenu" colspan="13"><?php echo _("Submitted time sheets")." (".$timesheets->submitted.")"; ?></td>
        </tr>
        <?php
            if($timesheets->submitted == 0) {
                echo '<tr><th class="mainFormTextLabel" colspan="13">'._("no time sheets were submitted yet").'</td></tr>';;
            }
            if($timesheets->submitted > 0){
                $count = 0;
                for($i=0;$i<$timesheets->length;++$i)
                {
                    if(!$timesheets->get($i)->enrolled || !$timesheets->get($i)->submitted)
                        continue;
                    if($count == 0 || $count % 20 == 0) {
                        echo displayHeader($count);
                    }
                    $count++;
                    echo displayOneRecord($timesheets->get($i));
                }
            }
        ?>
    </table>
</div>

<div class="minifiedTable"><table class="full-width-tbl">
    <?php 			//staff that started their time sheet but haven't submitted it yet
        $dueTimeSheets = ($timesheets->length - $timesheets->submitted);
        if($dueTimeSheets != 0)
        {
            ?>
            <tr>
                <th class="mainFormTextMenu" colspan="13"><?php echo _("Started but due time sheets")?> (<?php echo  $dueTimeSheets ?>)</td>
            </tr>
            <?php

            $count = 0;
            for($i=0;$i<$timesheets->length;++$i)
            {
                if(!$timesheets->get($i)->enrolled || $timesheets->get($i)->submitted)
                    continue;
                if($count ==0 || $count % 20 == 0){
                    echo displayHeader($count);
                }
                $count++;
                array_push($refidList,$timesheets->get($i)->employeerefid);
                echo displayOneRecord($timesheets->get($i));
            }
        }
        //staff that hasn't even started their time sheet
        $dueNr = $timesheets->length - $timesheets->submitted;
        $sql = "SELECT a.refid,a.fname,a.lname FROM tbl_staff_lookup a LEFT JOIN
                tbl_staff_preferences b ON b.staff_refid = a.refid
                WHERE a.employed = true AND b.enrolled = true AND a.refid NOT IN
                (SELECT staffrefid FROM tbl_office_time_sheet WHERE enddate = ".$db->quote($thisFriday).") ".$cost_centre_addon."  ".$location_addon."
                ORDER BY lname ASC, fname ASC ";
        $db->query($sql);
        $rowNr = $db->numRows();
        $dueNr += $rowNr;
        if($rowNr != 0)	{
            ?>
            <tr>
                <th class="mainFormTextMenu" colspan="13"><?php echo _("Not started due time sheets")?> (<?php echo  $rowNr ?>)</td>
            </tr>
            <?php
            for($i=0;$i<$rowNr;++$i)
            {
                $refid = $db->getElement("refid");
                array_push($refidList,$refid);
                $fname = htmlspecialchars($db->getElement("fname"));
                $lname = htmlspecialchars($db->getElement("lname"));
                ?>
                <tr>
                    <td class="mainFormTextLabel" colspan="9"><?php echo  $lname." ".$fname ?></td>
                    <th class="mainFormTextMenu bootstrap-btn-style bootstrap-orange"><a class="keyLink" href="javascript:remindEmployee(<?php echo  $refid?>)"><?php echo  _("remind")?></a><input type="checkbox" name="remind" checked="checked" value="<?php echo  $refid?>"/></td>
                    <th class="mainFormTextMenu bootstrap-btn-style bootstrap-blue" colspan="3"><a class="keyLink" href="timesheetNew.php?friday=<?php echo  urlencode($thisFriday)?>&employee=<?php echo  $refid?>"><?php echo  _("create")?></a></td>
                </tr>
                <?php
                $db->nextRow();
            }
        }
        if($dueNr > 1){
            ?>
            <tr>
                <th class="mainFormTextLabel" colspan="9">&nbsp;</td>
                <th class="mainFormTextMenu bootstrap-btn-style bootstrap-orange" colspan="4"><a class="keyLink" href="javascript:remindAll()"><?php echo _("remind selected")?></a></td>
            </tr>
            <?php 	}
        else{
            ?>
            <tr>
                <th class="mainFormTextMenu" colspan="13"><?php echo _("Due time sheets for this week")?> (0)</td>
            </tr>
            <tr>
                <th class="mainFormTextLabel" colspan="13"><?php echo _("no due time sheets")?></td>
            </tr>
            <?php 	}
        ?>
</table></div>

<div class="minifiedTable"> <table class="full-width-tbl">
    <tr>
        <th class="mainFormTextMenu" colspan="13"><?php echo _("Not enrolled people's time sheets")?> (<?php echo  $timesheets->notEnrolled ?>)</td>
    </tr><?php
    if($timesheets->notEnrolled == 0)
    {	?>
        <tr>
            <th class="mainFormTextLabel" colspan="13"><?php echo _("no time sheets")?></td>
        </tr>
        <?php
    }
    else
    {
        $count = 0;
        for($i=0;$i<$timesheets->length;++$i)
        {
            if($timesheets->get($i)->enrolled)
                continue;
            if($count == 0 || $count % 20 == 0) {
                echo displayHeader($count);
            }
            $count++;
            echo displayOneRecord($timesheets->get($i));
        }
    }
    ?></table>
</div>

