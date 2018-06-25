<?php
require_once("contentScriptStart.php");
global $resolution, $showWeekendDays, $startTime, $stopTime, $weekEndingDate, $minHours, $refid, $viewingMode, $variable, $submitted;
?>
<input type="hidden" id="resolution" name="resolution" value="<?php echo  $resolution ?>" />
<input type="hidden" id="startTime" name="startTime" value="<?php echo  $startTime ?>" />
<input type="hidden" id="stopTime" name="stopTime" value="<?php echo  $stopTime ?>" />
<input type="hidden" id="showWeekendDays" name="showWeekendDays" value="<?php echo  $showWeekendDays ?>" />
<input type="hidden" id="weekEndingDate" name="weekEndingDate" value="<?php echo  $weekEndingDate ?>" />
<input type="hidden" id="minHours" name="minHours" value="<?php echo  $minHours ?>" />
<input type="hidden" id="refid" name="refid" value="<?php echo  $refid ?>" />
<input type="hidden" id="viewingMode" name="viewingMode" value="<?php echo  $viewingMode ?>" />
<input type="hidden" id="variable" name="variable" value="<?php echo  $variable ?>" />
<input type="hidden" id="submitted" name="submitted" value="<?php echo  $submitted ?>" />
<p id="weekDate"></p>
<div class="tbl-table">
<div class="tbl-row table-cells-for-buttons">
<div id="scroll-tbl" class="alignInputTbl">
	<table id="entryConsole">
		<tr>
			<td>
				<table id="inputTable" class="inputHours"></table>
				<div id="selectCounterFloater" style="display: none; top: 589px; left: 712px;"></div>
			</td>
			<!-- <td>

			</td> -->
		</tr>
	</table>
</div>

<div class="side-content">
	<div id="textEntry">
		<table id="outputTable">
			<tr>
				<td title="<?php echo _("Normal time declared")?>" class="OutputLabel"><?php echo _("Normal")?></td>
				<td title="<?php echo _("Number of charged overtime hours")?>" class="OutputLabel"><?php echo _("Charged")?></td>
				<td title="<?php echo _("Number of lieu overtime hours")?>" class="OutputLabel"><?php echo _("Lieu")?></td>
				<td title="<?php echo _("Minimum accountable hours")?>" class="OutputLabel"><?php echo _("Min")?></td>
				<td title="<?php echo _("Total number of declared hours")?>" class="OutputLabel"><?php echo _("TOTAL")?></td>
			</tr>
			<tr>
				<td id="ntTd" class="OutputTextHighlight" title="<?php echo _("Number of normal time hours")?>">0</td>
				<td id="chargedTd" class="OutputText" title="<?php echo _("Number of charged overtime hours")?>">0</td>
				<td id="undefined" class="OutputText" title="<?php echo _("Number of lieu overtime hours")?>">0</td>
				<td id="mahTd" class="OutputText" title="<?php echo _("Minimum accountable hours")?>"><?php echo  $minHours ?></td>
				<td id="totalTd" class="OutputTextRed" title="<?php echo _("Total number of declared hours")?>">0</td>
			</tr>
		</table>
	</div>
	<div id="buttonEntry">
			<input type="button" class="bootstrap-btn-style bootstrap-green" id="enterNormal" value="<?php echo _("Normal time")?>" title="<?php echo _("Press this button to start selecting normal time")?>" disabled="disabled"/>
			<input class="bootstrap-btn-style bootstrap-orange" type="button" id="enterLieu" value="<?php echo _("Lieu Overtime")?>" title="<?php echo _("Press this button to start selecting lieu overtime")?>"/>
			<input class="bootstrap-btn-style bootstrap-orange" type="button" id="enterCharged" value="<?php echo _("Charged Overtime")?>" title="<?php echo _("Press this button to start selecting charged overtime")?>"/>
	</div>
	<div id="buttonEntry2">
			<input class="bootstrap-btn-style bootstrap-blue" type="button" id="entryButton" value="<?php echo _("Enter Details")?>" title="<?php echo _("Press this button to enter details about the selected worked time")?>" disabled="disabled"/>
			<input class="bootstrap-btn-style bootstrap-black" type="button" id="undoButton" value="<?php echo _("Undo")?>" title="<?php echo _("Press this button to undo your last action")?>" disabled="disabled"/>
			<input class="bootstrap-btn-style bootstrap-green" type="button" id="saveButton" value="<?php echo _("Save for later")?>" title="<?php echo _("Pressing this button will save all the data for future edit. You may continue entering data after save")?>" disabled="disabled"/>
			<input class="bootstrap-btn-style bootstrap-blue" type="button" id="proceedButton" value="<?php echo _("Submit Timesheet")?>" title="<?php echo _("Pressing this button will submit all the data to the database. You will not be able to edit this time sheet after submitting")?>" disabled="disabled"/>
	</div>
	<div id="formEntry"></div>
	<div id="keyEntry">
		<table id="keyEntryTable" class="minifiedTable no-cards">
			<!-- style="width:510px" -->
			<thead>
			<tr>
				<th></th>
				<th></th>
				<th><?php echo _("invoice code")?></th>
				<th><?php echo _("project name")?></th>
				<th><?php echo _("task code")?></th>
				<th><?php echo _("note")?></th>
				<th class="buttonTh"></th>
			</tr>
			</thead>
			<tbody id="keyTable">
			</tbody>
		</table>
	</div>
</div>

</div>
</div>
