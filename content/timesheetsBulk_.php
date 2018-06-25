<?php
require_once("contentScriptStart.php");
require_once("parseResponseFunctions.php");
$db = new DB();

//This denotes the resolutions used to transfor time into table cells
//The hours specified for each project has to be divisible by these
//Please put the biggest first.
$availableResolutions = Array(30,15);
$resolution = $availableResolutions[0];
//First colour will be black, which is never present in normal TSs, this will make TSs created here easy to distinguish
$availableColours = Array('rgb(125,125,125)',
							'rgb(255,0,51)',
							'rgb(255,255,51)',
							'rgb(51,0,255)',
							'rgb(0,255,204)',
							'rgb(204,204,204)',
							'rgb(204,51,0)',
							'rgb(153,51,102)',
							'rgb(255,153,255)',
							'rgb(51,204,255)',
							'rgb(0,255,0)',
							'rgb(170,170,170)',
							'rgb(153,102,51)',
							'rgb(51,153,204)',
							'rgb(238,204,51)');

$invoicecode = $taskcode = $employee = $hours = $employeerefid = $ot = Array('');
$dayHours = Array();
$arrays = Array('invoicecode','taskcode','hours','employee','employeerefid','dayHours','ot');
foreach($arrays as $name){
	if(isset($_POST[$name])){
		$$name = $_POST[$name];
	}
}
$enterByDay = false;
if(isset($_POST['enterByDay']) && $_POST['enterByDay'] == 'on'){
	$enterByDay = true;
}

$projectRows = count($invoicecode);
$employeeRows = count($employee);
$projectNotice = Array();
$dayTotalHours = Array(0,0,0,0,0,0,0);
$employeeNotice = Array();
$employeeNoticeClass = Array();
$totalHours = 0;
$totalOT = 0;

if(isset($_REQUEST["date"])){
	$thisFriday = new DateTime($_REQUEST["date"]);
}
else{
	$dateAttribArray = getdate();
	if($dateAttribArray["wday"]== 5)
		$thisFriday = new DateTime();
	else
		$thisFriday = new DateTime("last Friday");
}
$strThisFriday = $thisFriday->format("Y-m-d");
$timeSheetWeekEnd = "Fri ".$thisFriday->format("d M");
$thisFriday->modify("-6 day");
$timeSheetWeekStart = "Sat ".$thisFriday->format("d M");
$thisFriday->modify("+6 day");

//populating $dayTotalHours
if($enterByDay){
	$hours = Array();
	for($i = 0; $i < count($dayHours); ++$i){
		$hours[$i] = 0;
		for($j = 0; $j < count($dayHours[$i]); ++$j){
			$hours[$i] += floatval($dayHours[$i][$j]);
			$dayTotalHours[$j] += floatval($dayHours[$i][$j]);
//			echo $dayTotalHours[$j].' - ';
		}
		$totalHours += $hours[$i];
	}
}else{
	foreach($hours as $h){
		$totalHours += $h;
	}
}
foreach($ot as $h){
	$totalOT += $h;
}

$errors = 0;
//run data checks:
/**
 * 1. Discard projects with hours = 0
 * 2. Make sure there is an invoice code
 * 3. If the invoice code requires a task code one is provided
 * 4. If a task code is provided, does it exist and can it be associated with the current invoice code?
 * 5. Each number of hours provided is divisible by at least one of available resolutions, the greater os prefered.
 * 6. Check if we have colours for each project
 * 7. Over time is not greater than the total hours for the current project
 * 8. All employees are employed
 * 9. All employees have the same amount for minimum hours
 * 10.None of the employees selected don't already have a time sheet. If they do a link to it will be provided
 * 11.Total hours is at least the minimum allowed for all employees
 * 12.If the total is greater than the minimum, over time covers for the difference
 */
//For projects
$arrProjects = Array();//of type Project
for($i =0;$i < $projectRows; ++$i){
	$p = new BulkProject();
	$p->hours = floatval($hours[$i]);
	$p->ot = floatval($ot[$i]);
	if(trim($invoicecode[$i]) == ''){
		$errors++;
		$projectNotice[$i] = _('No invoice code');
		continue;
	}
	//if hours is 0, write notice that the current row's data will not be included
	if($p->hours <= 0){
//		$errors++;
		//$projectNotice[$i] = _('This row will be discarded because hours <= 0');
		continue;
	}


	//invoice code exists
	$sql = "SELECT invoicecode,taskneeded FROM tbl_invoice_code_lookup WHERE ".$db->function->lower('invoicecode')."=".$db->function->lower($db->quote(trim($invoicecode[$i])));
	$db->query($sql,Array('text','boolean'));
	if($db->numRows() == 0){
		$errors++;
		$projectNotice[$i] = _('Invoice code not found');
		continue;
	}
	$p->invoiceCode = $db->getElement('invoicecode');
	$taskNeeded = formatBoolean($db->getElement('taskneeded'),true,false);
	// if the invoice code requires a task code, one is provided
	if($taskNeeded && trim($taskcode[$i]) == ''){
		$errors++;
		$projectNotice[$i] = _('This invoice code requires a task code');
		continue;
	}
	if(trim($taskcode[$i]) != ''){
		// the task code exists can be associated to the current invoice code
		$sql = "SELECT tsk.taskcode FROM tbl_invoice_code_lookup i
				LEFT JOIN tbl_invoice_group_matrix g ON i.invoicecode = g.invoicecoderefid
				LEFT JOIN tbl_invoice_task_group it ON g.groupnamerefid = it.groupname
				LEFT JOIN tbl_task_group_matrix t ON it.groupname = t.groupnamerefid
				LEFT JOIN tbl_task_code_lookup tsk ON t.taskcoderefid = tsk.taskcode
				WHERE i.invoicecode = ".$db->quote($p->invoiceCode)." AND
				".$db->function->lower('tsk.taskcode')." = ".$db->function->lower($db->quote(trim($taskcode[$i])));
		$db->query($sql,Array('text'));
		if($db->numRows() == 0){
			$errors++;
			$projectNotice[$i] = _('Task code could not be found or can not be associated with this invoice code');
			continue;
		}
		$p->taskCode = $db->getElement('taskcode');
	}else{
		$p->taskCode = null;
	}
	// hours for each project is divisible with at least one of the available resolutions (definition at top)
	$acceptabile = false;

	foreach($availableResolutions as $res){
		$acceptabile = true;
		if($enterByDay){
			for($j = 0; $j < count($dayHours[$i]);++$j){
				$r = floatval($dayHours[$i][$j]) * 60 % $res;
				if($r != 0){
					$acceptabile = false;
					break;
				}
			}
		}else{
			$r = $hours[$i] * 60 % $res;
			if($r != 0){
				$acceptabile = false;
			}
		}
		//checking over time
		$r = $ot[$i] * 60 % $res;
		if($r != 0){
			$acceptabile = false;
		}
		if($acceptabile){
			//searching for the biggest resolution acceptible to accomodate all hour figures.
			if($res < $resolution){
				$resolution = $res;
			}
			break;
		}
	}
	if(!$acceptabile){
		$errors++;
		$projectNotice[$i] = _('The hours figure multiplied by 60 has to be dividable by ').implode(' '._('or').' ',$availableResolutions);
		continue;
	}
	//let's hope it doesn't get this far, but if it happens, we're covered
	if(count($arrProjects) >= count($availableColours)){
		$errors++;
		$projectNotice[$i] = _('Too many projects for one time sheet');
		continue;
	}
	$p->colour = $availableColours[count($arrProjects)];
	if($p->ot > $p->hours){
		$errors++;
		$projectNotice[$i] = _('Over time hours greater than the total');
		continue;
	}
	if($enterByDay){
		$p->arrHoursByDay = $dayHours[$i];
	}
	$arrProjects[] = $p;
}

if($enterByDay){
	//making the arrCellsByDay
	foreach($arrProjects as $project){
		for($j = 0; $j < 7;++$j){
			$project->arrCellsByDay[$j] = $project->arrHoursByDay[$j] * 60 / $resolution;
		}
	}
}


//For employees
$numberOfEmployees = 0;
$minHours = 0;
for($i = 0; $i < $employeeRows; ++$i){
	if(trim($employee[$i]) == ''){
		//ignoring empty rows
		$employeerefid[$i] = null;
		continue;
	}
	$sql = "SELECT s.employed,s.minhours,s.variable,ts.refid
			FROM tbl_staff_lookup s LEFT JOIN tbl_office_time_sheet ts
			ON s.refid = ts.staffrefid AND ts.enddate = ".$db->quote($strThisFriday)."
			WHERE s.refid = ".intval($employeerefid[$i]);
	$db->query($sql,Array('boolean','float','boolean','text'));
	//the employee exists and is employed
	if($db->numRows() == 0){
		$errors++;
		$employeeNotice[$i] = _('This employee could not be found');
		continue;
	}
	if(!formatBoolean($db->getElement("employed"),true,false)){
		$errors++;
		$employeeNotice[$i] = _('This employee is not employed any more');
		continue;
	}

	$cMinHours = $db->getElement('minhours');
	if($i == 0){
		$minHours = $cMinHours;
	}
	//all the employees have the same minhours!
	if($cMinHours != $minHours){
		$errors++;
		$employeeNotice[$i] = sprintf(_('This employee has a different value for minimum hours (%s) then the rest (%s)'),$cMinHours,$minHours);
		continue;
	}
	//the selected employees don't already have a time sheet for the selected week. If they do, provide the link so it can be edited
	if($db->getElement('refid') !== null){
		$errors++;
		$employeeNotice[$i] =_('This employee already has a time sheet for the selected week: ').'<a href="timesheetEdit.php?refid='.$db->getElement('refid').'">'.$db->getElement('refid').'</a>';
		continue;
	}
	$numberOfEmployees++;
}
if($numberOfEmployees == 0 && $errors == 0){
	$errors++;
	$employeeNotice[0] = _('No employees selected');
}
//Check that the total number of hours is at least the minimum number allowed for each employee
$diff = $totalHours - $minHours;
if($errors == 0 && $diff < 0){
	$errors++;
	$employeeNotice[0] = sprintf(_('The specified number of hours is %s less than the minimum allowed for employees'),abs($diff));
}
//Check that the declared OT covers the difference between minHours and total hours
$diff -= $totalOT;
if($errors == 0 && $diff > 0){
	$errors++;
	$employeeNotice[0] = sprintf(_('%s more hours of lieu over time are required'),$diff);
}
if($errors == 0 && $diff < 0){
	$errors++;
	$employeeNotice[0] = sprintf(_('Too much over time declared: %s'),abs($diff));
}

if($errors == 0){//everything is fine - creating time sheets
	//this time sheet will be appllied to all employees
	$TS = new BulkTimeSheet($thisFriday,$resolution,$arrProjects);


	for($i = 0; $i < $employeeRows; ++$i){
		if($employeerefid[$i] == null){
			continue;
		}
		$db->beginTransaction();
		$ok = true;
		//add a new entry in tbl_office_time_sheet
		$values = Array("staffrefid" => intval($employeerefid[$i]),
						"enddate" => $TS->getDate(),
						"guiresolution" => $TS->resolution,
						"starttime" => $TS->getStartTime(),
						"stoptime" => $TS->getStopTime(),
						"showweekend" => $TS->showWeekend,
						"submitted" => false,
						"submissiontime" => 'now()');
//		print_r($values);
//		echo '<br/>';
		$db->autoExec('tbl_office_time_sheet', $values, MDB2_AUTOQUERY_INSERT);
		$refid = $db->getLastInsertID('tbl_office_time_sheet','refid');
//		$refid = 21915;
		foreach($TS->getProjects() as $project){
			$values = Array("invoicecoderefid" => substr($project->invoiceCode,0,30),
							"taskcoderefid" => substr($project->taskCode,0,30),
							"officetimesheetrefid" => $refid,
							"colour" => substr($project->colour,0,20));
			if($values["taskcoderefid"] == ''){
				$values["taskcoderefid"] = null;
			}
//			echo '<br/><br/>';
//			print_r($values);
			$db->autoExec('tbl_office_time_sheet_entry', $values, MDB2_AUTOQUERY_INSERT);
			$entryRefid = $db->getLastInsertID('tbl_office_time_sheet_entry','refid');
			foreach($project->details as $d){
				//inserting data in the tbl_office_time_sheet_entry_details table
				//assembling the identity list string
				$identities = "{\"".implode('","',$d->identities)."\"}";
				$values = Array("dateworked" => $d->date,
								"officetimesheetentryrefid" => $entryRefid,
								"ratetype" => $d->rateType,
								"hours" => $d->hours,
								"identities" => $identities);
//				echo '<br/>---';
//				print_r($values);
				$db->autoExec('tbl_office_time_sheet_entry_details', $values, MDB2_AUTOQUERY_INSERT);
			}
		}
		if($ok){
			$db->commit();
		}else{
			$db->rollback();
		}
		//return the refid of the time sheet
		$ret = onTimeSheetSubmit($refid);
		if(!$ret){
			$employeeNoticeClass[$i] = 'highlightRed';
			$employeeNotice[$i] = '<a href="timesheetEdit.php?refid='.$refid.'">'._('Time sheet').'</a> '._('created but not submitted because of some toil issues. Please revise it.');
		}else{
			$employeeNoticeClass[$i] = 'highlightGreen';
			$employeeNotice[$i] = '<a href="timesheetEdit.php?refid='.$refid.'">'._('Time sheet').'</a> '._('successfully created');
		}
	}

}
?>

<h3 style="margin:10px 20px"><?php echo  _('This section allows administrators to add time sheets for many employees at the same time.') ?></h3>
<div id="mainFormContainer" class="min-height-for-button">
    <form method="post" name="parameters">
    <table class="FormTable">
    	<tbody>
    		<tr>
    			<td class="mainFormTextMenu" ><?php echo  _('Choose a week') ?></td>
    		</tr>
    		<tr>
    			<td class="mainFormTextLabel">
		    	<?php echo  _("Week") ?>&nbsp;
				<a class="keyLink" href="javascript:prevWeek()">&lt;&lt;</a>
				<input name="weekStart" type="text" id="weekStart" value="<?php echo  $timeSheetWeekStart?>" readonly="readonly" class="weekPartDisplay"/>
				-
				<input name="WeekEnd" type="text" id="weekEnd" value="<?php echo  $timeSheetWeekEnd?>" readonly="readonly" class="weekPartDisplay"/>
				<a class="keyLink" href="javascript:nextWeek()">&gt;&gt;</a>
				<img src="images/calendar.gif" width="16" height="16" onclick="onFridayDateInput(event,'newtimesheet')" />
				<input name="date" type="hidden" id="friday" value="<?php echo  $strThisFriday?>" />
				</td>
			</tr>
			<tr>
    			<td class="mainFormTextLabel">
    				<?php echo  _("Enter hours for each day") ?>&nbsp;
    				<input name="enterByDay" id="enterByDay" type="checkbox" <?php if($enterByDay) echo 'checked="checked"';?> onchange="byDay(this)"/>
    			</td>
    		</tr>
			<tr>
    			<td class="mainFormTextMenu">
    				<p><?php echo  _('Choose the projects and task codes') ?></p>
    				<p>
    				<?php echo  _('If you choose to enter only the total hours for each project, they will be distributed evenly accross days from Monday to Friday.') ?>
    				<br/>
    				<?php echo  _('The over time amount(OT) is part of the total hours(Hours).') ?>
    				</p>
    			</td>
    		</tr>
		</tbody>
		</table>
		<div class="minifiedTable">
			<table>
	    	<thead>
	    		<tr>
	    			<th class="mainFormTextLabel"></th>
	    			<th class="mainFormTextLabel1">
	    				<?php echo  _('Invoice code') ?>
	    			</th>
	    			<th class="mainFormTextLabel1">
	    				<?php echo  _('Task code') ?>
	    			</th>
	    			<th class="mainFormTextLabel1">
	    				<?php echo  _('Hours') ?>
	    			</th>
	    			<th class="mainFormTextLabel1">
	    				<?php echo  _('Sat') ?>
	    			</th>
	    			<th class="mainFormTextLabel1">
	    				<?php echo  _('Sun') ?>
	    			</th>
	    			<th class="mainFormTextLabel1">
	    				<?php echo  _('Mon') ?>
	    			</th>
	    			<th class="mainFormTextLabel1">
	    				<?php echo  _('Tue') ?>
	    			</th>
	    			<th class="mainFormTextLabel1">
	    				<?php echo  _('Wed') ?>
	    			</th>
	    			<th class="mainFormTextLabel1">
	    				<?php echo  _('Thu') ?>
	    			</th>
	    			<th class="mainFormTextLabel1">
	    				<?php echo  _('Fri') ?>
	    			</th>
	    			<th class="mainFormTextLabel1">
	    				<?php echo  _('OT') ?>
	    			</th>
	    			<td class="mainFormTextLabel1" style="width:80%"></td>
	    		</tr>
	    	</thead>
	    	<tfoot>
	    		<tr id="totals">
	    			<td colspan="2">
	    				<input type="button" name="add" class="bootstrap-btn-style bootstrap-blue" value="<?php echo  _('Add another project')?>" onmousedown="addProjectTR()"/>
	    			</td>
	    			<td class="mainFormTextMenu"><?php echo  _('Total hours') ?></td>
	    			<td>
	    				<input type="text" name="totalhours" value="<?php echo  $totalHours ?>" disabled="disabled" class="small"/>
	    			</td>
	    			<?php for($i =0; $i < 7; ++$i){?>
		    			<td>
		    				<input type="text" name="dayTotalHours[<?php echo  $i?>]" value="<?php echo  $dayTotalHours[$i] ?>" disabled="disabled" class="small"/>
		    			</td>
	    			<?php }?>
	    			<td>
	    				<input type="text" name="totalot" value="<?php echo  $totalOT ?>" disabled="disabled" class="small"/>
	    			</td>
	    			<td class="mainFormTextLabel" style="width:80%"></td>
	    		</tr>
	    	</tfoot>
	    	<tbody id="projectTable" class="table-with-no-cell-bg">
	    		<?php for($i =0; $i <$projectRows; ++$i){?>
	    		<tr>
	    			<td class="no-bg">
	    				<input type="button" class="bootstrap-btn-style bootstrap-red" name="remove" value="-" onclick="removeTR(this);updateTotalHours()" />
	    			</td>
	    			<td>
	    				<input name="invoicecode[]" value="<?php echo  make_safe($invoicecode[$i])?>" type="text" onfocus="addAutoComplete(this,'invoicecode')"/>
	    			</td>
	    			<td>
	    				<input name="taskcode[]" value="<?php echo  make_safe($taskcode[$i])?>" type="text" onfocus="bulkAddTaskCodeAutoComplete(this)" />
	    			</td>
	    			<td>
	    				<input type="text" name="hours[]" value="<?php echo  floatval($hours[$i]) ?>" onkeypress="onDigitInputsChange(event)" onkeyup="updateTotalHours()" class="small" autocomplete="off" <?php if($enterByDay) echo 'disabled="disabled"'?>/>
	    			</td>
	    			<?php for($j=0; $j < 7; ++$j) {?>
		    			<td>
		    				<input type="text" name="dayHours[<?php echo  $i?>][]" value="<?php if(isset($dayHours[$i])) echo floatval($dayHours[$i][$j]); ?>" onkeypress="onDigitInputsChange(event)" onkeyup="updateTotalHours()" class="small" autocomplete="off" <?php if(!$enterByDay) echo 'disabled="disabled"'?>/>
		    			</td>
	    			<?php }?>
	    			<td>
	    				<input type="text" name="ot[]" value="<?php echo  $ot[$i] ?>" onkeypress="onDigitInputsChange(event)"  onkeyup="updateTotalHours()" class="small" autocomplete="off"/>
	    			</td>
	    			<td><span class="notice highlightRed"><?php echo   isset($projectNotice[$i]) ? $projectNotice[$i] : ' '; ?></span></td>
	    		</tr>
	    		<?php }?>
	    	</tbody>
	    	</table>
		</div>
    <div class="">
			<table class="FormTable">
    	<thead>
    		<tr>
    			<td class="mainFormTextMenu" colspan="4"><?php echo  _('Choose the employees') ?></td>
    		</tr>
    		<tr>
    			<td class="mainFormTextLabel"></td>
    			<td class="mainFormTextLabel1">
    				<?php echo  _('Employee') ?>
    			</td>
    			<td class="mainFormTextLabel1" style="width:80%"></td>
    		</tr>
    	</thead>
    	<tfoot>
    		<tr>
    			<td colspan="3">
    				<input type="button" class="bootstrap-btn-style bootstrap-blue" name="add" value="<?php echo  _('Add another employee') ?>" onclick="addEmployeeTR()" />
    			</td>
    		</tr>
    		<tr>
    			<td class="mainFormTextMenu" colspan="5" style="padding: 10px 0;">
    				<input type="submit" value="<?php echo  _('Create time sheets') ?>"  class="bootstrap-btn-style bootstrap-blue" onclick="confirmSubmit(event)"/>
    			</td>
    		</tr>
      	</tfoot>
    	<tbody  id="employeeTable">
    		<?php for($i =0; $i <$employeeRows; ++$i){?>
    		<tr>
    			<td>
    				<input type="button"  class="bootstrap-btn-style bootstrap-red" name="remove" value="-" onclick="removeTR(this)" />
    			</td>
    			<td>
    				<input name="employee[]" value="<?php echo  make_safe($employee[$i])?>" type="text" style="text-transform: capitalize"  onfocus="addAutoComplete(this,'employee')"/>
					<input name="employeerefid[]" value="<?php echo  intval($employeerefid[$i])?>" type="hidden" />
    			</td>
	   			<td><span class="notice <?php echo  isset($employeeNoticeClass[$i]) ? $employeeNoticeClass[$i]: 'highlightRed' ?>"><?php echo   isset($employeeNotice[$i]) ? $employeeNotice[$i] : ' '; ?></span></td>
    		</tr>
    		<?php }?>
    	</tbody>
      	</table>
    </div>
    </form>
</div>
