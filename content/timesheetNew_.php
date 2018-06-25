<div id="mainFormContainer">
	<form name="preRequisites" method="post" action="timesheetEdit.php">
		<input name="employee" type="hidden" value="<?php echo  $employee?>" id="employee"/>
		<table class="FormTable" id="minifiedBlockTable">
			<tr>
				<td class="mainFormText" colspan="2"><h4><?php echo _("Please enter these details about your timesheet")?></h4></td>
			</tr>
			<tr>
				<td class="mainFormText"><?php echo _("Time interval")?></td>
				<td class="mainFormControl">
					<select name="resolution">
						<option value="30" <?php if ($resolution==30) echo 'selected="selected"';?>><?php echo  sprintf(_("%s minutes"),30)?></option>
						<option value="15" <?php if ($resolution==15) echo 'selected="selected"';?>><?php echo  sprintf(_("%s minutes"),15)?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="mainFormText"><?php echo _("Usual day start hour")?></td>
				<td class="mainFormControl">
					<select name="starttime">
						<?php 							for($i=0;$i<24;++$i)
							{
								if($i<10)
									$curTime="0".$i.":";
								else
									$curTime=$i.":";
								if(strcmp($startTime,$curTime."00")==0) echo "\t\t\t\t<option value=\"".$curTime."00\" selected=\"selected\">".$curTime."00</option>\n";
								else  echo "\t\t\t\t<option value=\"".$curTime."00\">".$curTime."00</option>\n";

								if(strcmp($startTime,$curTime."30")==0) echo "\t\t\t\t<option value=\"".$curTime."30\" selected=\"selected\">".$curTime."30</option>\n";
								else  echo "\t\t\t\t<option value=\"".$curTime."30\">".$curTime."30</option>\n";
						    }
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="mainFormText"><?php echo _("Usual day leave hour")?></td>
				<td class="mainFormControl">
					<select name="stoptime">
						<?php 							for($i=0;$i<24;++$i)
							{
								if($i<10)
									$curTime="0".$i.":";
								else
									$curTime=$i.":";
								if(strcmp($stopTime,$curTime."00")==0) echo "\t\t\t\t<option value=\"".$curTime."00\" selected=\"selected\">".$curTime."00</option>\n";
								else  echo "\t\t\t\t<option value=\"".$curTime."00\">".$curTime."00</option>\n";

								if(strcmp($stopTime,$curTime."30")==0) echo "\t\t\t\t<option value=\"".$curTime."30\" selected=\"selected\">".$curTime."30</option>\n";
								else  echo "\t\t\t\t<option value=\"".$curTime."30\">".$curTime."30</option>\n";
						    }
							$curTime="00:00";
							if(strcmp($stopTime,$curTime)==0) echo "\t\t\t\t<option value=\"".$curTime."\" selected=\"selected\">".$curTime."</option>\n";
							else  echo "\t\t\t\t<option value=\"".$curTime."\">".$curTime."</option>\n";
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="mainFormText"><?php echo _("Show weekend days")?></td>
				<td class="mainFormControl">
					<input type="checkbox"  name="showweekend" id="showweekend" <?php if ($showWeekendDays) echo "checked=\"checked\"";?> />
				</td>
			</tr>
			<tr>
				<td class="mainFormText"><?php echo _("Week")?></td>
				<td class="mainFormControl">
					<input name="weekendingdate" type="hidden" id="friday" value="<?php  echo $thisFriday?>" />
					<a class="keyLink" href="javascript:prevWeek()">&lt;&lt;</a>
					<input name="weekStart" type="text" id="weekStart" value="<?php  echo $timeSheetWeekStart?>" readonly="readonly" class="weekPartDisplay"/>
					-
					<input name="WeekEnd" type="text" id="weekEnd" value="<?php  echo $timeSheetWeekEnd?>" readonly="readonly" class="weekPartDisplay"/>
					<a class="keyLink" href="javascript:nextWeek()">&gt;&gt;</a>
					<input type="image" src="images/calendar.gif" onclick="onFridayDateInput(event,'newtimesheet')" />
				</td>
			</tr>
			<tr>
				<td class="mainFormRowText" colspan="2"><input type="submit" class="bootstrap-btn-style bootstrap-blue" value="<?php echo  _('continue')?>"/></td>
			</tr>
			<tr>
				<td id="status" class="mainFormRowText" colspan="2">
					<?php echo $status?>
				</td>
			</tr>
		</table>
	</form>
</div>
