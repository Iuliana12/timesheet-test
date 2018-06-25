<table id="StaffContainer">
	<tr>
	<td id="leftStaffContainer">
		<table id="StaffListHeader">
			<tr>
				<td colspan="2">
					<?php echo _("Sort by")?>:
					<select name="sortby" id="sortby" title="<?php echo _('choose the value to sort by')?>" onchange="search(event)" style="width: 10em">
						<option value="lname-fname"><?php echo _("last name")?> + <?php echo _("first name")?></option>
						<option value="fname-lname"><?php echo _("first name")?> + <?php echo _("last name")?></option>
						<option value="lname"><?php echo _("last name")?></option>
						<option value="fname"><?php echo _("first name")?></option>
					</select>
					<select name="sortorder" id="sortorder" title="<?php echo _('choose the sorting order')?>" onchange="search(event)">
						<option value="asc"><?php echo _("ascending")?></option>
						<option value="desc"><?php echo _("descending")?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<?php echo _("Search")?>:&nbsp;<input name="searchBox" id="searchBox" title="<?php echo _('type a name here to search for an employee')?>" type="text" onkeyup="search(event)" style="width: 10em"/>
					<?php
					//cost centre list
					if (count($costCentres) > 0) {
					?>
					<br/>
					<?php echo _("Cost centre")?>:&nbsp;
					<select name="cost_centre" id="cost_centre" onchange="search(event)" >
						<option value=""></option>
						<?php
							$fav_cost_centre = $_SESSION["user"]->costCentre;
							foreach($costCentres as $costCentre){
								$selected = '';
								if ($fav_cost_centre == $costCentre) {
									$selected = 'selected="selected"';
								}
							 	echo "\t\t\t<option value=\"".$costCentre."\" ".$selected.">".$costCentre."</option>\n";
							 }
					 	?>
					</select>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<?php echo _("Show unemployed")?>:<input name="showunemployed" id="showunemployed" title="<?php echo   _('check this to include the unemplyed staff')?>" type="checkbox" onchange="search(event)"/>
				</td>
			</tr>
			<tr>
				<th><?php echo _("First name")?></th>
				<th><?php echo _("Last name")?></th>
			</tr>
		</table>
		<div id="StaffListContainer">
			<table id="StaffListTable">
			</table>
		</div>
	</td>
	<td id="rightStaffContainer">
		<div id="StaffDetailsBlank">
			<?php echo _("click on an employee in the list to see their details")?>
		</div>
		<div id="StaffDetailsContainer" class="hide">
			<table id="StaffDetailsTable">
				<tr>
					<td class="mainFormTextLabel"><?php echo _("First Name")?></td>
					<td class="mainFormTextLabel">
						<input type="text" name="fname" id="fname" style="text-transform: capitalize" readonly="readonly"/>
						<input type="hidden" name="hiddenRefid" id="hiddenRefid"  value="-1"/>
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("Last Name")?></td>
					<td class="mainFormTextLabel">
						<input type="text" name="lname" id="lname" style="text-transform: capitalize" readonly="readonly"/>
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("User Name")?></td>
					<td class="mainFormTextLabel">
						<input type="text" name="username" id="username" readonly="readonly" />
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("E-mail")?></td>
					<td class="mainFormTextLabel">
						<input type="text" name="email" id="email" readonly="readonly" />
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("Line Manager")?></td>
					<td class="mainFormTextLabel">
						<input type="text" name="lineManager" id="lineManager" style="text-transform: capitalize" readonly="readonly"/>
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("Minimum Hours")?></td>
					<td class="mainFormTextLabel">
						<input type="text" name="minHours" id="minHours" />
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("Employed")?></td>
					<td class="mainFormTextLabel">
						<input type="checkbox" name="employed" id="employed" disabled="disabled"/>
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("Variable hours")?></td>
					<td class="mainFormTextLabel">
						<input type="checkbox" name="variable" id="variable" />
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("TOIL adjustment")?></td>
					<td class="mainFormTextLabel">
						<input type="text" name="toilAdjustment" id="toilAdjustment" class="smallInput"/>
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("TOIL adjustment<br/>commencing date")?></td>
					<td class="mainFormTextLabel">
						<input type="text" name="toilAdjustmentDate" id="toilAdjustmentDate" class="dateInput"/>
						<input type="image" onclick="onDateInput(event)" src="images/calendar.gif"/>
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("TOIL Adjustment comment")?><br/><span class="sidenote">(<?php echo _("required for TOIL adjustment changes")?></span></td>
					<td class="mainFormTextLabel">
						<input type="text" name="toilAdjustmentComment" id="toilAdjustmentComment" />
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("HOLS adjustment (hours)")?></td>
					<td class="mainFormTextLabel">
						<input type="text" name="holsAdjustment" id="holsAdjustment" class="smallInput" />
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("HOLS Adjustment comment")?><br/><span class="sidenote">(<?php echo _("required for HOLS adjustment changes")?></span></td>
					<td class="mainFormTextLabel">
						<input type="text" name="holsAdjustmentComment" id="holsAdjustmentComment" />
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("User Type")?></td>
					<td class="mainFormTextLabel">
						<select name="userType" id="userType" >
							<option value="0"><?php echo _("normal")?></option>
							<option value="1"><?php echo _("administrator")?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("Authorizes Subordinates")?></td>
					<td class="mainFormTextLabel">
						<input type="checkbox" name="authorizes_subordinates" id="authorizes_subordinates"/>
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("Authorizes Invoice Codes")?></td>
					<td class="mainFormTextLabel">
						<input type="checkbox" name="authorizes_invoice_codes" id="authorizes_invoice_codes"/>
					</td>
				</tr>
				<tr>
					<td class="mainFormTextLabel"><?php echo _("Enrolled")?></td>
					<td class="mainFormTextLabel">
						<input type="checkbox" name="enrolled" id="enrolled" />
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: center">
						<textarea id="statusText" readonly="readonly" rows="1" cols="40"></textarea>
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: center">
						<input type="button" class="half-row-cell bootstrap-btn-style bootstrap-green" name="save" value="<?php echo _('save')?>" title="<?php echo _('click to save this employee')?>" alt="<?php echo _('click to save this employee')?>" onclick="saveEmployee()"/>
					</td>
				</tr>
			</table>
		</div>
	</td>
	</tr>
</table>
