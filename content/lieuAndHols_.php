<?php
require_once("contentScriptStart.php");

$finDateArr = explode("-",FINANCIAL_YEAR_CHANGE_DATE);
$finMonth = intval($finDateArr[1]);
$finDay = intval($finDateArr[2]);
$finYear = intval(date("Y"));
//if the date is in the same year but before the exact date before the financial year changes, update the finish year by substracting 1 from the financial year
if( intval(date("m")) < $finMonth || ( intval(date("m")) == $finMonth && intval(date("d")) == $finDay) )
	$finYear--;
$financialYearChangeDate = strtotime($finYear.'-'.$finMonth.'-'.$finDay);
$financialYearChangeDateStr = date("Y-m-d",$financialYearChangeDate);
/*$dateAttribArray = getdate();
if($dateAttribArray["wday"]== 5)
	$thisFridayTime = time();
else
	$thisFridayTime = strtotime("last Friday");
$thisFriday = date("Y-m-d",$thisFridayTime);
*/

$db = new DB();
$db2 = new DB();
//cost centre processing
$fav_cost_centre = $_SESSION["user"]->costCentre;
if (isset($_GET["cost_centre"]) && $fav_cost_centre != trim($_GET["cost_centre"])) {
	$fav_cost_centre = trim($_GET["cost_centre"]);
	if ($fav_cost_centre == '') {
		$db_cost_centre = '-';
		$fav_cost_centre = '';
	}
	else {
		$db_cost_centre = $fav_cost_centre;
	}
	$values = Array("cost_centre" => $db->escape($db_cost_centre) );
	$where = "staff_refid = ".$_SESSION["user"]->refid;
	$db->autoExec('tbl_staff_preferences', $values, MDB2_AUTOQUERY_UPDATE, $where);
	$_SESSION["user"]->costCentre = $fav_cost_centre;
}
?>
<div id="mainFormContainer">
	<div class="new-text-content">
		<h3><?php echo _("The financial year has changed on the")?> <span class="highlightRed"><?php echo date("jS \of M Y",$financialYearChangeDate) ?></span></h3>
		<ul>
			<li><?php echo _("NOTE1: TOIL available is <b>live</b> and contains the toil_adjustment value.")?></li>
			<li><?php echo _("NOTE2: The expired TOIL is calculated from the begining of the current financial year.")?></li>
			<li><?php echo _("NOTE3: The holidays are calculated from the begining of this financial year.")?></li>
			<li><?php echo sprintf(_("NOTE4: The number of days of holidays are calculated by dividing the number of hours by %s."),HOURS_PER_DAY)?></li>
		</ul>
	</div>
	<div >
		<table class="FormTable minifiedTable" id="">
			<thead>
				<!-- <tr>
					<td class="mainFormTextMenu" colspan="7">

					</td>
				</tr> -->
				<?php
				//cost centre list
				$sql = 'SELECT DISTINCT cost_centre FROM tbl_staff_lookup WHERE employed = true AND cost_centre IS NOT NULL';
				$db->query($sql, Array('text'));
				$cost_centreNr = $db->numRows();
				if ($db->numRows() > 0) {
				?>
				<tr>
					<td class="mainFormTextMenu" colspan="7">
						<form name="cost_centre" method="get">
						<?php echo _("Cost centre")?>&nbsp;
						<select name="cost_centre" onchange="document.forms.cost_centre.submit()" class="shrink-to-mobile-size">
							<option value=""></option>
							<?php
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
						</form>
					</td>
				</tr>
				<?php } ?>
				<tr>
					<th class="mainFormTextLabel2"><?php echo _("Employee")?></th>
					<th class="mainFormTextLabel2"><?php echo _("TOIL available")?></th>
					<th class="mainFormTextLabel2"><?php echo _("TOIL expired")?></th>
					<th class="mainFormTextLabel2"><?php echo _("TOIL adjustment")?></th>
					<th class="mainFormTextLabel2"><?php echo _("Holidays taken (hours)")?></th>
					<th class="mainFormTextLabel2"><?php echo _("Holidays taken (days)")?></th>
					<th class="mainFormTextLabel2"><?php echo _("Holidays adjustment")?></th>
				</tr>
			</thead>
			<tbody>
	<?php

	$cost_centre_addon = '';
	if ($fav_cost_centre != '-' && $fav_cost_centre != '') {
		$cost_centre_addon = "AND staff.cost_centre = ".$db->quote($fav_cost_centre);
	}

	$sql = "SELECT staff.refid, staff.lname, staff.fname, toil.adjustment AS toiladjustment, hols.adjustment AS holsadjustment, SUM(ts.lieu_ot) AS toil_remaining
			FROM tbl_staff_lookup staff
			LEFT JOIN tbl_staff_toil_adjustment toil ON toil.staff_refid = staff.refid
			LEFT JOIN tbl_staff_hols_adjustment hols ON hols.staff_refid = staff.refid
			JOIN tbl_office_time_sheet ts ON ts.staffrefid = staff.refid
			WHERE ts.submitted = true ".$cost_centre_addon." AND enddate >= ".$db->function->now()." - interval ".$db->quote(TOIL_EXPIRATION_DAYS.' days')."
			AND (toil.refid IS NULL OR toil.refid = (select max(refid) from tbl_staff_toil_adjustment where staff_refid = staff.refid))
			AND (hols.refid IS NULL OR hols.refid = (select max(refid) from tbl_staff_hols_adjustment where staff_refid = staff.refid))
			GROUP BY staff.refid, staff.lname, staff.fname, toil.adjustment, hols.adjustment ORDER by staff.lname, staff.fname";
	$db->query($sql, Array('integer', 'text', 'text', 'float', 'float', 'float'));
	for($i=0; $i < $db->numRows(); ++$i)
	{
		if ($i %20 == 0) {
			?>

			<?php
		}
		$refid = $db->getElement("refid");
		$employee = ucwords($db->getElement("lname")." ".$db->getElement("fname"));
		$toil_remaining = $db->getElement("toil_remaining");
		$toil_adjustment = $db->getElement("toiladjustment");
		$hols_adjustment = $db->getElement("holsadjustment");
		$toil_expired = 0;
		$hols = 0;
		//obtaining expired LIEU
		$sql = "SELECT SUM(lieu_ot) AS toil_expired
				FROM tbl_office_time_sheet WHERE submitted = true AND staffrefid = ".$refid."
				AND enddate <= ".$db->function->now()." - interval ".$db->quote(TOIL_EXPIRATION_DAYS.' days');
		$db2->query($sql, Array('float'));
		$toil_expired = $db2->getElement("toil_expired");
		//obtaining hols taken in this financial year
		$sql = "SELECT SUM(C.hours) AS hols
				FROM tbl_office_time_sheet AS B
				LEFT JOIN tbl_office_time_sheet_entry AS D ON (D.officetimesheetrefid = B.refid)
				LEFT JOIN tbl_office_time_sheet_entry_details AS C ON (C.officetimesheetentryrefid = D.refid)
				WHERE B.submitted = true AND B.staffrefid = ".$refid." AND D.invoicecoderefid = ".$db->quote('HOL')."
				AND C.dateworked > ".$db->quote($financialYearChangeDateStr);
		$db2->query($sql, Array('float'));
		$hols = $db2->getElement("hols");
		$hols_days = ceil($hols/HOURS_PER_DAY);
		//adding the adjustments
		$toil_remaining += $toil_adjustment;
		$hols += $hols_adjustment;

		if ($toil_remaining == 0) {
			$toil_remaining = "-";
		}
		if ($toil_expired == 0) {
			$toil_expired = "-";
		}
		if ($hols == 0) {
			$hols = "-";
		}
		if ($hols_days == 0) {
			$hols_days = "-";
		}
		if ($toil_adjustment == 0) {
			$toil_adjustment = "-";
		}
		if ($hols_adjustment == 0) {
			$hols_adjustment = "-";
		}
		?>
			<tr>
				<td class="mainFormTextLabel"><a class="keyLink" href="archive.php?employee=<?php echo $refid?>" ><?php echo $employee ?></a></td>
				<td class="mainFormTextLabel"><?php echo $toil_remaining ?></td>
				<td class="mainFormTextLabel"><?php echo $toil_expired?></td>
				<td class="mainFormTextLabel"><?php echo $toil_adjustment?></td>
				<td class="mainFormTextLabel"><?php echo $hols ?></td>
				<td class="mainFormTextLabel"><?php echo $hols_days?></td>
				<td class="mainFormTextLabel"><?php echo $hols_adjustment?></td>
			</tr>
		<?php
		$db->nextRow();
	}

	?>
			</tbody>
		</table>
	</div>
</div>
