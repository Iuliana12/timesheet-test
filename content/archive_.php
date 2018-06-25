<?php
require_once("contentScriptStart.php");
$db = new DB();

/**
 * Only admins may see other employee's archives
 */
if(isset($_REQUEST['employee']) && $_SESSION["user"]->isAdmin()){
	$EMPrefid = $_REQUEST['employee'];
}
else {
	$EMPrefid = $_SESSION['user']->refid;
}
$finDateArr = explode("-",FINANCIAL_YEAR_CHANGE_DATE);
$finMonth = intval($finDateArr[1]);
$finDay = intval($finDateArr[2]);
$finYear = intval(date("Y"));
//if the date is in the same year but before the exact date before the financial year changes, update the finish year by substracting 1 from the financial year
if( intval(date("m")) < $finMonth || ( intval(date("m")) == $finMonth && intval(date("d")) == $finDay) )
	$finYear--;
$financialYearChangeDate = strtotime($finYear.'-'.$finMonth.'-'.$finDay);


//get all available years of time sheets for the selected employee
$years = Array();
$sql = "select distinct date_part('year',enddate) AS year from tbl_office_time_sheet WHERE staffrefid = ".$EMPrefid." ORDER BY date_part('year',enddate) DESC";
$db->query($sql, Array('integer'));
for($i = 0; $i < $db->numRows(); ++$i){
    $years[] = $db->getElement('year');
    $db->nextRow();
}

//managing the time sheets display preference
$display = '3';
if (isset($_REQUEST["display"])) {
	$display = $_REQUEST["display"];
}
if (!in_array($display, Array('3','year', 'all')) && !in_array($display, $years)) {
	$display = '3';
}
switch ($display){
	case '3'://last three months
		case '3'://last three months
		$ThreeMonthsAgo = strtotime("3 months ago");
		$timeCondition = "AND B.enddate > ".$db->quote(date("Y-m-d",$ThreeMonthsAgo));
		break;
	case 'year'://last financial year
		$timeCondition = "AND B.enddate > ".$db->quote($finYear.'-'.$finMonth.'-'.$finDay);
        break;
    case 'all'://all time sheets
		$timeCondition = '';
		break;
    default:
        //in this case we're going to filter by year
        $timeCondition = "AND B.enddate >= ".$db->quote($display.'-01-01')." AND B.enddate <= ".$db->quote($display.'-12-31');
        break;
}

$showToil = true;
//calculating the total Hols taken for the whole financial year
$sql = "SELECT SUM(C.hours) AS hols
		FROM tbl_office_time_sheet AS B
		LEFT JOIN tbl_office_time_sheet_entry AS D ON (D.officetimesheetrefid = B.refid)
		LEFT JOIN tbl_office_time_sheet_entry_details AS C ON (C.officetimesheetentryrefid = D.refid)
		WHERE B.submitted = true AND B.staffrefid = ".$EMPrefid." AND D.invoicecoderefid = ".$db->quote('HOL')."
		AND C.dateworked >= ".$db->quote(date("Y-m-d",$financialYearChangeDate));
$db->query($sql,Array('float'));
$totalHols = $db->getElement('hols');
$sql = 'SELECT adjustment FROM tbl_staff_hols_adjustment
		WHERE staff_refid = '.$EMPrefid.' AND refid =
		(SELECT MAX(refid) FROM tbl_staff_hols_adjustment WHERE staff_refid = '.$EMPrefid.')';
$db->query($sql, Array('float'));
if($db->numRows() > 0){
	$totalHols += $db->getElement("adjustment");
}
$totalHolDays = floor($totalHols / 7.5) ;
$totalHolDaysRest = fmod($totalHols,7.5);
if($totalHolDaysRest > 3.5 && $totalHolDaysRest <= 4){
    $totalHolDays += 0.5;
}elseif($totalHolDaysRest > 4){
    $totalHolDays += 1;
}


?>
<div id="mainFormContainer">
	<form method="get" name="display" style="margin: 10px">
		<b><?php echo _("Display")?>: </b>
		<select name="display" onchange="document.forms.display.submit()">
			<option value="3" <?php if($display=='3'){ echo 'selected="selected"';} ?>><?php echo _("last 3 months")?></option>
			<option value="year" <?php if($display=='year'){ echo 'selected="selected"';} ?>><?php echo _("this financial year")?></option>
            <?php
            foreach($years as $year){
                $selected = '';
                if($display == $year){
                    $selected = 'selected="selected"';
                }
                echo '<option value="'.$year.'" '.$selected.'>'.$year.'</option>';
            }
            ?>
			<option value="all" <?php if($display=='all'){ echo 'selected="selected"';} ?>><?php echo _("all the time sheets")?></option>
		</select>
		<?php if($_SESSION["user"]->isAdmin()){?>
			<b><?php echo _("Employee")?>: </b>
			<select name="employee" onchange="document.forms.display.submit()">
			<?php
				$sql = "SELECT refid,lname,fname FROM tbl_staff_lookup
						WHERE ".$db->function->lower('lname')." not like ".$db->quote('%not set%')."
						ORDER BY lname ASC,fname ASC";
				$db->query($sql, Array('integer','text','text'));
				for($i=0;$i < $db->numRows();++$i){
					$selected = "";
					$refid = $db->getElement("refid");
					$fname = $db->getElement("fname");
					$lname = $db->getElement("lname");
					if($refid == $EMPrefid) {
						$selected = 'selected="selected"';
					}
					echo "<option value=".$refid." ".$selected.">".ucwords($lname." ".$fname)."</option>";
					$db->nextRow();
				}
			?>
			</select>
		<?php } ?>
		<br/><br/>
		<?php echo _("You have 90 days to take TOIL after it was accrued.")?><br/>
		<?php echo _("These figures <b>are official</b>")?>.
		<?php echo _("If you think they're not accurate, please report it to the Administration Department")?>.<br/>
	</form>
	<table class="FormTable">
	<?php
		if($showToil){
			$totalTOIL = 0;
			$toilAdjustmentValue = null;
			//taking the toil adjustment value from the database
			$sql = 'SELECT adjustment,date_commencing FROM tbl_staff_toil_adjustment
					WHERE staff_refid = '.$EMPrefid.' AND refid =
					(SELECT MAX(refid) FROM tbl_staff_toil_adjustment WHERE staff_refid = '.$EMPrefid.')
					AND date_commencing + interval '.$db->quote(TOIL_EXPIRATION_DAYS.' days').' > '.$db->function->now();
			$db->query($sql, Array('float','text'));
			if($db->numRows() > 0){
				$toilAdjustmentValue = $db->getElement("adjustment");
				$toilAdjustmentDate = strtotime($db->getElement("date_commencing"));
			}
			//calculating the TOIL left to take
			$sql = "SELECT SUM(lieu_ot) AS debt FROM tbl_office_time_sheet
					WHERE staffrefid = ".$EMPrefid." AND submitted = true AND lieu_ot < 0
					AND enddate >= ".$db->function->now()." - interval ".$db->quote(TOIL_EXPIRATION_DAYS.' days');
			$db->query($sql,Array('float'));
			$debt = floatval($db->getElement('debt'));
			//calculating the TOIL left to take
			$sql = "SELECT lieu_ot, enddate
					FROM tbl_office_time_sheet
					WHERE staffrefid = ".$EMPrefid." AND submitted = true AND lieu_ot > 0
					AND enddate >= ".$db->function->now()." - interval ".$db->quote(TOIL_EXPIRATION_DAYS.' days')."
					ORDER BY enddate ASC";
			$db->query($sql,Array('float', 'text'));
			if($db->numRows() > 0){
				?>
					<tr>
						<td class="mainFormTextLabel2" style="width: 20em"><?php echo _("TOIL accrued (hours)")?></td>
						<td class="mainFormTextLabel2"><?php echo _("Take by")?></td>
					</tr>
				<?php
			}
			$adjustmentShown = false;
			if($toilAdjustmentValue <= 0){
				$adjustmentShown = true;
			}
			$previous_date = strtotime('2 years ago');
			for($i = 0; $i < $db->numRows(); $i++ ){
				$lieu_ot = $db->getElement('lieu_ot');
				$enddate = $db->getElement('enddate');
				$current_date = strtotime($db->getElement("enddate"));
				if(!$adjustmentShown && $toilAdjustmentDate >= $previous_date && $toilAdjustmentDate < $current_date ){
					$totalTOIL += $toilAdjustmentValue;
					$lastDate = date('d/m/Y',strtotime('+'.TOIL_EXPIRATION_DAYS.' days',$toilAdjustmentDate));
					echo '<tr><td class="mainFormTextLabel">'.$toilAdjustmentValue.' ('. _("adjustment") . ')</td>
					  <td class="mainFormTextLabel">'.$lastDate.'</td></tr>';
					$adjustmentShown = true;
				}
				$lastDate = date('d/m/Y',strtotime('+'.TOIL_EXPIRATION_DAYS.' days',$current_date));
				$totalTOIL += $lieu_ot;
				echo '<tr><td class="mainFormTextLabel">'.$lieu_ot.'</td>
					  <td class="mainFormTextLabel">'.$lastDate.'</td></tr>';
				$previous_date = $current_date;
				$db->nextRow();
			}

			if(!$adjustmentShown){
				if($db->numRows() == 0){
					?>
					<tr>
						<td class="mainFormTextLabel2" style="width: 20em"><?php echo _("TOIL accrued (hours)")?></td>
						<td class="mainFormTextLabel2"><?php echo _("Take by")?></td>
					</tr>
					<?php
				}
				$totalTOIL += $toilAdjustmentValue;
				$lastDate = date('d/m/Y',strtotime('+'.TOIL_EXPIRATION_DAYS.' days',$toilAdjustmentDate));
				echo '<tr><td class="mainFormTextLabel">'.$toilAdjustmentValue.' ('. _("adjustment") . ')</td>
				  <td class="mainFormTextLabel">'.$lastDate.'</td></tr>';
			}
			if($debt < 0){
				$totalTOIL += $debt;
				?>
					<tr>
						<td class="mainFormTextLabel2" colspan="2"><?php echo _("Debt")?></td>
					</tr>
					<tr>
						<td class="mainFormTextLabel" colspan="2"><?php echo $debt ?></td>
					</tr>
				<?php
			}
			if($toilAdjustmentValue < 0){
				$totalTOIL += $toilAdjustmentValue;
				?>
					<tr>
						<td class="mainFormTextLabel2" colspan="2"><?php echo _("TOIL Adjustment")?></td>
					</tr>
					<tr>
						<td class="mainFormTextLabel" colspan="2"><?php echo $toilAdjustmentValue ?></td>
					</tr>
				<?php
			}
		 	?>
				<tr>
					<td class="mainFormTextLabel2" colspan="2"><?php echo _("Total TOIL")?></td>
				</tr>
				<tr>
					<td class="mainFormTextLabel" colspan="2"><?php echo $totalTOIL ?>&nbsp;<?php echo _("hours")?></td>
				</tr>
			<?php
			if($_SESSION["user"]->isAdmin()){
			?>
				<tr>
					<td class="mainFormTextLabel2" colspan="2">Recalulate TOIL?</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel" colspan="2">
					<?php echo _("If you think the TOIL figures shown above are erroneous, press the \"recalculate\" button.") ?><br/>
					<span class="highlightRed"><?php echo _("Please note that the TOIL adjustment will not be altered!") ?></span>
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel" colspan="2">
						<form action="rerunToilCalculations.php" method="post">
							<input type="hidden" name="employee" value="<?php echo $EMPrefid ?>"/>
							<input type="hidden" name="display" value="<?php echo $display ?>"/>
							<input type="button" class="inside-tbl-menu bootstrap-btn-style bootstrap-blue" value="<?php echo _('Refresh')?>" onclick="window.location.reload()"/>
							<input type="submit" class="inside-tbl-menu bootstrap-btn-style bootstrap-blue" value="<?php echo _('Recalculate')?>"/>
							<?php
								if(isset($_GET['r'])){
									echo '<span class="highlightGreen">'._('Completed!').'</span>';
								}
							?>
						</form>
					</td>
				</tr>
			<?php
			}
		}
	?>
		<tr>
			<td class="mainFormTextLabel" colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<td class="mainFormTextLabel2" colspan="2"><?php echo _("Total Holidays taken during this financial year (including adjustment)")?></td>
		</tr>
		<tr>
			<td class="mainFormTextLabel" colspan="2">
				<?php echo ($totalHols)?>&nbsp;<?php echo _("hours")?>
				(<span class="highlightRed"><?php echo $totalHolDays?></span> <?php echo sprintf(_("days, based on %s work hours a day"),HOURS_PER_DAY)?>)
			</td>
		</tr>
	</table>
	<table class="FormTable minifiedTable" id="">
		<!-- <tr>
			<td class="mainFormText" colspan="8"></td>
		</tr> -->
		<?php
			//getting the time sheets to display
			$sql = "SELECT A.minhours,B.refid,B.enddate,date_part('year',B.enddate) AS year,SUM(C.hours) AS hours,
					SUM(CASE WHEN C.ratetype = ".$db->quote('1')." THEN C.hours ELSE 0 END) AS lieu,
					SUM(CASE WHEN C.ratetype = ".$db->quote('2')." OR C.ratetype = ".$db->quote('3')." THEN C.hours ELSE 0 END) AS charged,
					SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('TOIL')." THEN C.hours ELSE 0 END) AS toil,
					SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('HOL')." THEN C.hours ELSE 0 END) AS hols,
					COUNT(CASE WHEN D.invoicecoderefid = ".$db->quote('HOL')." THEN C.dateworked END) AS holdays
					FROM tbl_staff_lookup AS A join tbl_office_time_sheet AS B ON A.refid = B.staffrefid
					LEFT JOIN tbl_office_time_sheet_entry AS D ON (D.officetimesheetrefid = B.refid)
					LEFT JOIN tbl_office_time_sheet_entry_details AS C ON (C.officetimesheetentryrefid = D.refid)
					WHERE B.submitted = true AND A.refid = ".$EMPrefid." ".$timeCondition."
					GROUP BY A.minhours,B.refid,B.enddate,date_part('year',B.enddate) ORDER BY B.enddate DESC";
			$db->query($sql,Array('float', 'integer', 'text','integer', 'float', 'float', 'float', 'float', 'float', 'float'));
			if($db->numRows() > 0)
			{
				?>
				<thead>
					<tr>
											<th class="mainFormTextLabel2"><?php echo _("Year")?></th>
						<th class="mainFormTextLabel2"><?php echo _("Week")?></th>
						<th class="mainFormTextLabel2"><?php echo _("Hours")?></th>
						<th class="mainFormTextLabel2"><?php echo _("Lieu OT")?></th>
						<th class="mainFormTextLabel2"><?php echo _("Charged OT")?></th>
						<th class="mainFormTextLabel2"><?php echo _("TOIL")?></th>
						<th class="mainFormTextLabel2"><?php echo _("Hols")?></th>
						<th class="mainFormTextLabel2">&nbsp;</th>
					</tr>
				</thead>
				<?php
				$hoursTotal = 0;
				$lieuTotal = 0;
				$chargedTotal = 0;
				$toilTotal = 0;
				$holsTotal = 0;
				for($i=0; $i < $db->numRows();++$i)
				{
					$refid = $db->getElement("refid");
                    $year = $db->getElement("year");
					$hours = $db->getElement("hours");
					$lieu = $db->getElement("lieu");
					$charged = $db->getElement("charged");
					$toil = $db->getElement("toil");
					$hols = floatval($db->getElement("hols"));
                    $holDays = floor($hols / 7.5);
                    $holDaysRest = fmod($hols,7.5);
                    if($holDaysRest > 3.5 && $holDaysRest <= 4){
                        $holDays += 0.5;
                    }elseif($holDaysRest > 4){
                        $holDays = $db->getElement("holdays");
                    }

					$minhours = $db->getElement("minhours");
					$enddate = $db->getElement("enddate");
					$timeSheetWeek = strftime("%d %b",strtotime("-6 day",strtotime($enddate)))." - ".strftime("%d %b",strtotime($enddate));

					$hoursTotal += $hours;
					$lieuTotal += $lieu;
					$chargedTotal += $charged;
					$toilTotal += $toil;
					$holsTotal += $hols;

					if($lieu==0) $lieu = "";
					if($charged==0) $charged = "";
					if($toil==0) $toil = "";
					if($hols==0) $holsString = "";
					else
						$holsString = $hols." (".$holDays." days)";
					?>
					<tr>
                        <td class="mainFormTextLabel"><?php echo $year?></td>
						<td class="mainFormTextLabel"><?php echo $timeSheetWeek?></td>
						<td class="mainFormTextLabel"><?php echo $hours?></td>
						<td class="mainFormTextLabel"><?php echo $lieu?></td>
						<td class="mainFormTextLabel"><?php echo $charged?></td>
						<td class="mainFormTextLabel"><?php echo $toil?></td>
						<td class="mainFormTextLabel"><?php echo $holsString?></td>
						<td class="mainFormTextLabel bootstrap-btn-style bootstrap-green inside-tbl-menu"><a class="keyLink" href="timesheetView.php?refid=<?php echo $refid?>"><?php echo _("view")?></a></td>
					</tr>
					<?php
					$db->nextRow();
				}
                $totalHolDays = floor($holsTotal / 7.5) ;
                $totalHolDaysRest = fmod($holsTotal,7.5);
                if($totalHolDaysRest > 3.5 && $totalHolDaysRest <= 4){
                    $totalHolDays += 0.5;
                }elseif($totalHolDaysRest > 4){
                    $totalHolDays += 1;
                }
				?>
					<tr>
						<td colspan="2" class="mainFormTextLabel2"><?php echo _("TOTALS (hours)")?></td>
						<td class="mainFormTextLabel2"><?php echo $hoursTotal?></td>
						<td class="mainFormTextLabel2"><?php echo $lieuTotal?></td>
						<td class="mainFormTextLabel2"><?php echo $chargedTotal?></td>
						<td class="mainFormTextLabel2"><?php echo $toilTotal?></td>
						<td class="mainFormTextLabel2"><?php echo $holsTotal.' ('.$totalHolDays.' '._('days').')'?></td>
						<td class="mainFormTextLabel2">&nbsp;</td>
					</tr>
				<?php
			}else {
				?>
				<tr>
					<td class="mainFormText" colspan="8"><?php echo _("no time sheets")?></td>
				</tr>
				<?php
			}
		?>
		<!-- <tr>
			<td class="mainFormText" colspan="8"></td>
		</tr> -->
	</table>
</div>
