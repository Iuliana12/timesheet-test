<?php
require_once("contentScriptStart.php");
class Timesheet
{
	public $refid;
	public $endDate;// an int obtained from strtotime
	public $lastName;//owner details
	public $firstName;
	public $entries = array(); //an array of entries
}
class Entry
{
	public $refid;
	public $invoiceCode;
	public $taskCode;
}

$db = new DB();
$timeSheets = getNotSubmittedTimeSheets();
?>

<div id="mainFormContainer">
	<table>
		<tr class="no-card-row">
			<td class="mainFormTextMenu no-paddig-left-cell bootstrap-btn-style bootstrap-blue" colspan="4"><a class="keyLink" href="timesheetNew.php"><?php echo _("Add a new time sheet")?></a></td>
		</tr>
	</table>
	<table class="FormTable borderRow no-cards" id="simpleMinifiedTable">

		<tr class="no-card-row">
			<td class="mainFormTextMenu no-paddig-left-cell" colspan="4"><?php echo _("Edit previous saved time sheets")?></td>
		</tr>
		<?php 		if(count($timeSheets) == 0)
		{
			?>
			<tr>
				<td class="mainFormTextLabel" colspan="4"><?php echo _("no saved time sheets")?></td>
			</tr>
			<?php
		}
		else
		{
			foreach($timeSheets as $refid => $object){
				?>
				<tr>
					<td class="mainFormTextLabel"><?php echo  $object->displayString?></td>
					<td class="mainFormTextMenu half-row-cell bootstrap-btn-style bootstrap-blue"><a class="keyLink" href="timesheetEdit.php?refid=<?php echo  $refid?>"><?php echo  _("edit")?></a></td><td class="mainFormTextMenu half-row-cell no-border bootstrap-btn-style bootstrap-red"><a class="keyLink" href="javascript:deleteTimesheet(<?php echo  $refid?>)"><?php echo  _("discard")?></a></td>
										<td class="mainFormTextMenu convert full-row-cell bootstrap-btn-style bootstrap-black">
                        <?php
                            if($object->guiResolution == 0.5 || $object->guiResolution == 30){
                                echo '<a class="keyLink" href="javascript:convertTimesheet('.$refid.')">'._("convert to 15min intervals").'</a>';
                            }
                        ?>
                    </td>
				</tr>
				<?php

			}
		}
		?>

		<!-- project manager section -->

		<?php 		$sql = "SELECT * FROM tbl_invoice_code_lookup
				WHERE projectmanager = ".$_SESSION["user"]->refid." AND completed <> 'closed' ORDER BY department ASC ,invoicecode ASC";
		$db->query($sql);
		if($db->numRows() != 0)
		{
		?>
		<tr class="no-card-row">
			<td class="mainFormTextMenu" colspan="4"><?php echo _("Your managed invoice code list")?></td>
		</tr>
		<?php 			for($i=0;$i<$db->numRows();++$i)
			{
				$invoiceCode = htmlspecialchars($db->getElement("invoicecode"));
				$projectName = htmlspecialchars($db->getElement("projectname"));
				$department = htmlspecialchars($db->getElement("department"));
				$displayString = "";
				if(strcmp($department,"")!=0)
					$displayString .= $department." : ";
				if(strcmp($projectName,"")!=0)
					$displayString .= ucfirst($projectName);
				$displayString .= " ( <span class=\"highlightGreen\">".$invoiceCode."</span> ) ";
				?>
				<tr>
					<td class="mainFormTextLabel"><?php echo  $displayString?></td>
					<td class="mainFormTextMenu half-row-cell bootstrap-btn-style bootstrap-blue"><a class="keyLink" href="manageInvoice.php?invoicecode=<?php echo  urlencode($invoiceCode)?>"><?php echo  _("manage")?></a></td><td class="mainFormTextMenu half-row-cell bootstrap-btn-style bootstrap-green"><a class="keyLink" href="report.php?invoicecode=<?php echo  urlencode($invoiceCode)?>"><?php echo  _("reports")?></a></td>
                    <!-- <td class="mainFormTextMenu"></td> -->
				</tr>
				<?php
				$db->nextRow();
			}
		}
		?>

		<!-- section for line managers that chose to autorize their subordinates' timesheets-->

		<?php 		$dateAttribArray = getdate();
		if($dateAttribArray["wday"]== 5)
			$thisFridayTime = time();
		else
			$thisFridayTime = strtotime("last Friday");
		$thisFriday = date("Y-m-d",$thisFridayTime);
		if($_SESSION["user"]->authorizesSubordinates){
			$sql = "SELECT a.refid AS timesheetrefid,a.enddate,c.fname,c.lname
					FROM tbl_office_time_sheet AS a,tbl_staff_lookup AS c
					WHERE a.staffrefid IN
					(SELECT refid FROM tbl_staff_lookup WHERE employed = true AND linemanager = ".$_SESSION["user"]->refid." AND refid <> linemanager)
					AND a.submitted = true AND a.enddate = ".$db->quote($thisFriday)."
					AND c.refid = a.staffrefid ORDER BY a.enddate DESC,c.fname ASC,c.lname ASC";
			$db->query($sql);
			if($db->numRows() != 0)
			{

		?>
		 <tr class="no-card-row">
			<td class="mainFormTextMenu" colspan="4"><?php echo _("Time sheets of your subordinates")?></td>
		</tr>
		<?php 				$refidList = "";
				$timesheetArray = array();
				for($i=0; $i < $db->numRows(); ++$i)
				{
					$timesheet = new Timesheet();
					$timesheet->refid = $db->getElement("timesheetrefid");
					$timesheet->endDate = strtotime($db->getElement("enddate"));
					$timesheet->firstName = $db->getElement("fname");
					$timesheet->lastName = $db->getElement("lname");
					array_push($timesheetArray,$timesheet);
					$refidList .= $db->getElement("timesheetrefid").",";
					$db->nextRow();
				}
				$refidList = substr($refidList,0,strlen($refidList)-1);
				foreach($timesheetArray as $timesheet)
				{
					$displayString = date("d/m/Y",$timesheet->endDate)." - ".htmlspecialchars(ucwords($timesheet->firstName."  ".$timesheet->lastName));
					?>
					<tr>
						<td colspan="3" class="mainFormTextLabel"><?php echo  $displayString?></td>
						<td class="mainFormTextMenu"><a class="keyLink" href="timesheetView.php?refid=<?php echo  $timesheet->refid?>"><?php echo  _("view")?></a></td>
					</tr>
					<?php
				}
				?>
				<tr>
					<td colspan="4" class="mainFormTextMenu half-row-cell bootstrap-btn-style bootstrap-green">
						<a class="keyLink" href="CSVTimeSheetReport.php?type=subordinates"><?php echo  _("Download report as CSV") ?></a>
					</td>
				</tr>
				<?php
			}
		}
		?>

		<!-- section for project managers that chose to autorize their workers' timesheets-->

		<?php 		if($_SESSION["user"]->authorizesInvoiceCodes){
			//THESE queries NEED UPDATING!!!
			/* removed 'AND b.authorized = false'*/
			/* removed 'AND d.authorized = false' */
			if(isset($refidList))
				$sql = "SELECT a.refid AS timesheetrefid,c.fname,c.lname,a.enddate,b.refid AS invoicerefid,
						b.invoicecoderefid,b.taskcoderefid FROM tbl_office_time_sheet AS a,
						tbl_office_time_sheet_entry AS b, tbl_staff_lookup AS c
						WHERE a.refid NOT IN (".$refidList.")
						AND a.staffrefid <> ".$_SESSION["user"]->refid."
						AND c.refid = a.staffrefid AND a.refid = b.officetimesheetrefid
						AND a.submitted = true AND a.enddate = ".$db->quote($thisFriday)."
						AND b.invoicecoderefid IN (SELECT invoicecode FROM tbl_invoice_code_lookup
						WHERE requiresauthorization = true AND projectmanager = ".$_SESSION["user"]->refid." )
						ORDER BY a.enddate DESC,c.fname ASC,c.lname ASC";
			else
				$sql = "SELECT a.refid AS timesheetrefid,c.fname,c.lname,a.enddate,b.refid AS invoicerefid,
						b.invoicecoderefid,b.taskcoderefid FROM tbl_office_time_sheet AS a,
						tbl_office_time_sheet_entry AS b, tbl_staff_lookup AS c
						WHERE a.staffrefid <> ".$_SESSION["user"]->refid."
						AND c.refid = a.staffrefid AND a.refid = b.officetimesheetrefid
						AND a.submitted = true AND a.enddate = ".$db->quote($thisFriday)."
						AND b.invoicecoderefid IN (SELECT invoicecode FROM tbl_invoice_code_lookup
						WHERE requiresauthorization = true AND projectmanager = ".$_SESSION["user"]->refid." )
						ORDER BY a.enddate DESC,c.fname ASC,c.lname ASC";
			$db->query($sql);
			$rowNr = $db->numRows();
			if($rowNr != 0)
			{
			?>
			 <tr class="no-card-row">
				<td class="mainFormTextMenu" colspan="4"><?php echo _("Authorize time sheets")?></td>
			</tr>
			<?php 			    $timesheetArray = array();
				$timesheet = null;
				$currentRefid = -1;
				for($i=0;$i<$rowNr;++$i){
					if($currentRefid != intval($db->getElement("timesheetrefid")))
					{
						$currentRefid = intval($db->getElement("timesheetrefid"));
						if($timesheet != null)
							array_push($timesheetArray,$timesheet);
						$timesheet = new Timesheet();
						$timesheet->refid = $db->getElement("timesheetrefid");
						$timesheet->endDate = strtotime($db->getElement("enddate"));
						$timesheet->firstName = $db->getElement("fname");
						$timesheet->lastName = $db->getElement("lname");
					}
					$entry = new Entry();
					$entry->refid = intval($db->getElement("invoicerefid"));
					$entry->invoiceCode = $db->getElement("invoicecoderefid");
					$entry->taskCode = $db->getElement("taskcoderefid");
					array_push($timesheet->entries,$entry);
					$db->nextRow();
				}
				array_push($timesheetArray,$timesheet);
				foreach($timesheetArray as $timesheet)
				{
					$displayString = date("d/m/Y",$timesheet->endDate)." - ".htmlspecialchars(ucwords($timesheet->firstName."  ".$timesheet->lastName));
					?>
						<tr>
							<td colspan="3" class="mainFormTextLabel"><?php echo  $displayString?></td>
							<td class="mainFormTextMenu"><a class="keyLink" href="timesheetView.php?refid=<?php echo  $timesheet->refid?>"><?php echo  _("view")?></a></td>
						</tr>
					<?php
					foreach($timesheet->entries as $entry)
					{
						$displayString2 = htmlspecialchars($entry->invoiceCode." - ".$entry->taskCode);
						?>
						<tr>
							<td colspan="4" class="mainFormTextLabelIndent"><?php echo  $displayString2 ?></td>
						</tr>
						<?php
					}
				}
			}
		}
		?>
		<tr class="hide-me-on-mobile">
			<td class="mainFormText" colspan="4">&nbsp;</td>
		</tr>
	</table>
</div>
