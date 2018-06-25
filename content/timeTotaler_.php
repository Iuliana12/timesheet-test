<div id="mainFormContainer">
	<table class="FormTable small-mobile-input">
		<tr>
			<td class="mainFormText" colspan="2">
				<h4>
					<?php if($_SESSION["user"]->isAdmin())
						echo _("This section gives a time total spent on a specific project by a specific employee");
					else
						echo _("This section gives you a time total spent on a specific project");
					?>
				</h4>
			</td>
		</tr>
		<tr>
			<td class="mainFormTextMenu" colspan="2"><?php echo _("Project")?></td>
		</tr>
		<tr>
			<td class="mainFormTextLabel1"><?php echo _("Invoice code")?></td>
			<td class="mainFormTextLabel">
				<input name="invoicecode" id="invoicecode" type="text" title="<?php echo _('type the invoice code or choose it from the list')?>" value=""  onfocus="addAutoComplete(this, 'invoicecode')"/>
				<!-- <input class="check-me-please check-this-invoice" name="specificinvoice" id="specificinvoice" type="checkbox" onchange="total()"/> -->
				<span class="sidenote"><?php echo _("check this to see only time spent on a specific invoice code")?></span>
			</td>
		</tr>
		<tr>
			<td class="mainFormTextLabel1"><?php echo _("Task code")?></td>
			<td class="mainFormTextLabel">
				<input name="taskcode" id="taskcode" type="text" value="" onfocus="addAutoComplete(this, 'taskcode', document.getElementById('invoicecode').value)" />
				<!-- <input class="check-me-please check-this-task" name="specifictask" id="specifictask" type="checkbox" onchange="total()"/> -->
				<span class="sidenote"><?php echo _("check this to see only time spent on a specific task code")?></span>
			</td>
		</tr>
		<tr>
			<td class="mainFormTextMenu" colspan="2"><?php echo _("Date range")?></td>
		</tr>
		<tr>
			<td class="mainFormTextLabel1"><?php echo _("Date start")?></td>
			<td class="mainFormTextLabel">
				<input name="datestart" id="datestart" type="text" class="dateInput" title="DD/MM/YYYY" value="<?php echo   date("d/m/Y",strtotime(date("Y-m-")."01")) ?>" onchange="total()"/>
				<input type="image" onclick="onDateInput(event)" src="images/calendar.gif"/>
				<span class="sidenote">DD/MM/YYYY</span>
			</td>
		</tr>
		<tr>
			<td class="mainFormTextLabel1"><?php echo _("Date end")?></td>
			<td class="mainFormTextLabel">
				<input name="dateend" id="dateend" type="text" class="dateInput" title="DD/MM/YYYY" value="<?php echo   date("d/m/Y") ?>" onchange="total()"/>
				<input type="image" onclick="onDateInput(event)" src="images/calendar.gif"/>
				<span class="sidenote">DD/MM/YYYY</span>
			</td>
		</tr>
		<?php
        if($_SESSION["user"]->isAdmin())
		{
		?>
 		<tr>
			<td class="mainFormTextMenu" colspan="2"><?php echo _("Employee")?></td>
		</tr>
		<tr>
			<td class="mainFormTextLabel1"><?php echo _("Employee name")?></td>
			<td class="mainFormTextLabel">
				<input name="employee" id="employee" type="text" value="" disabled="disabled" style="text-transform: capitalize"  onfocus="addAutoComplete(this,'employee')"/>
				<input name="employeerefid" id="employeerefid" type="hidden" value="" />
				<!-- <input name="specificemployee" id="specificemployee" type="checkbox" onchange="total()" disabled="disabled"/> -->

			</td>
		</tr>
		<tr>
			<td class="mainFormTextLabel1"><?php echo _("Show own time")?></td>
			<td class="mainFormTextLabel">
				<input name="selftime" id="selftime" type="checkbox" checked="checked" onchange="total()" />
				<span class="sidenote"><?php echo _("uncheck this to see time spent by a specific employee")?></span>
				<span class="sidenote"><?php echo _(" / check this to see only time spent by yourself")?></span>

			</td>
		</tr>
		<?php }?>
		<tr>
			<td class="mainFormTextMenu" colspan="2" >&nbsp;</td>
		</tr>
		<tr>
			<td class="mainFormTextLabel1">
				<input class="highlightRed" readonly="readonly" type="text" name="totaltime" id="totaltime" value="0" maxlength="20" style="width:100px;text-align: right;"/>&nbsp;<?php echo _("hours&nbsp;total")?>
			</td>
			<td class="mainFormTextLabel min-height-for-button">
				<a class="keyLink bootstrap-btn-style bootstrap-blue" href="javascript:total()"><?php echo _("update")?></a>
			</td>
		</tr>
		<tr>
			<td class="mainFormText" colspan="2"><span id="status">&nbsp;</span></td>
		</tr>
	</table>
</div>
