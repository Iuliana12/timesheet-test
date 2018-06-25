<div id="mainFormContainer" class="minifiedBlockTable">
	<table class="FormTable">
		<tr>
			<td class="mainFormText" colspan="5"><h4><?php echo _("Edit invoice code details")?></h4></td>
		</tr>
		<tr>
			<td class="mainFormTextLabel" colspan="2"><?php echo _("Invoice code")?></td>
			<td class="mainFormTextLabel" colspan="3"><input type="text" name="invoicecode" id="invoicecode" value="<?php echo  $invoiceCode?>" readonly="readonly"/></td>
		</tr>
		<tr>
			<td class="mainFormTextLabel" colspan="2"><?php echo _("Project name")?></td>
			<td class="mainFormTextLabel" colspan="3"><input type="text" name="projectname" id="projectname" value="<?php echo  $projectName?>"/></td>
		</tr>
		<tr>
			<td class="mainFormTextLabel" colspan="2"><?php echo _("Department")?></td>
			<td class="mainFormTextLabel" colspan="3">
				<select name="department" id="department"/>
				<?php 					if(count($departments) == 0)
						echo "<option value=\"-1\">none</option>\n";
					else{
						foreach($departments as $dep){
							$selected = '';
							if($dep == $department){
								$selected = 'selected="selected"';
							}
							echo "\t\t\t<option $selected value=\"".$dep."\">".$dep."</option>\n";
						}
					}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="mainFormTextLabel" colspan="2"><?php echo _("Task needed")?> <input type="checkbox" name="taskneeded" id="taskneeded" <?php if ($taskNeeded) echo "checked=\"checked\"" ?>/></td>
			<!-- <td class="mainFormTextLabel" colspan="3"></td> -->
		</tr>
		<tr>
			<td class="mainFormTextLabel" colspan="2"><?php echo _("Requires authorization")?><input type="checkbox" name="reqauth" id="reqauth" <?php if ($reqAuth) echo "checked=\"checked\"" ?>/></td>
			<!-- <td class="mainFormTextLabel" colspan="3"></td> -->
		</tr>
		<tr>
			<td class="mainFormTextLink text-center no-bg min-height-for-button" colspan="5" >
				<a href="javascript:saveDetails()" id="saveDetailsInvoice" class="keyLink half-row-cell bootstrap-btn-style bootstrap-blue"><?php echo _("Save details")?></a>
			</td>
		</tr>
		<tr>
			<td class="mainFormText" id="statusDisplay" colspan="5">&nbsp;</td>
		</tr>
		<tr>
			<td class="mainFormTextLabel" colspan="2">
				<?php echo _("Task code")?>:
			</td>
			<td class="mainFormTextLabel">
				<input type="text" name="taskcode" id="taskcode" disabled="disabled"/><br/>
			</td>
			<td class="mainFormTextLink half-row-cell bootstrap-btn-style bootstrap-blue inline-block half-space" id="add_new_task_button" onclick="addTask(event)" rowspan="2"><?php echo _("add new")?></td>
			<td class="mainFormTextLink half-row-cell bootstrap-btn-style bootstrap-black inline-block half-space" id="cancelAddTask" onclick="cancelAddTask(event)" rowspan="2"><?php echo _("cancel")?></td>
		</tr>
		<tr>
			<td class="mainFormTextLabel" colspan="2">
				<?php echo _("Task name")?>:
			</td>
			<td class="mainFormTextLabel">
				<input type="text" name="taskname" id="taskname" disabled="disabled"/>
			</td>
		</tr>
		<tr>
			<td class="mainFormTextMenu no-paddig-left-cell" colspan="5"><?php echo _("Related task code list")?></td>
		</tr>
		<tr>
			<td class="mainFormTextLabel" colspan="2">
				<?php echo _("Task code")?>:
			</td>
			<td class="mainFormTextLabel" >
				<input type="text" name="taskcodeEdit" id="taskcodeEdit" /><br/>
				<span class="sidenote"><?php echo _("start typing a task code to see a list")?></span>
			</td>
			<td class="mainFormTextLink half-row-cell bootstrap-btn-style bootstrap-blue inline-block half-space" id="editTaskDetails" onclick="editTask(event)" rowspan="2"><?php echo _("edit")?></td>
			<td class="mainFormTextLink half-row-cell bootstrap-btn-style bootstrap-black inline-block half-space"  id="cancelEditTask" onclick="cancelEditTask(event)" rowspan="2"><?php echo _("cancel")?></td>
		</tr>
		<tr>
			<td class="mainFormTextLabel" colspan="2">
				<?php echo _("Task name")?>:
			</td>
			<td class="mainFormTextLabel" >
				<input type="text" name="tasknameEdit" id="tasknameEdit" disabled="disabled"/>
			</td>
		</tr>

		<tr>
			<td class="mainFormText" colspan="5">&nbsp;</td>
		</tr>
	</table>
	<div id="taskCodeEditDojoDiv" class="AutoCompleteResultContainer"></div>
</div>
