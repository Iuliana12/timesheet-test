<div id="mainFormContainer">
	<table class="FormTable small-mobile-input">
		<tr>
			<td class="mainFormText" colspan="2">
				<h4>
					<?php if($_SESSION["user"]->isAdmin())
						echo _("This section gives a time total spent on a specific month");
					else
						echo _("This section gives you a time total spent on a specific month");
					?>
				</h4>
		</tr>
			<td class="mainFormTextMenu" colspan="2"><?php echo _("Month range")?></td>
		</tr>
		<tr>
			<td class="mainFormTextLabel1"><?php echo _("Choose Date")?></td>
			<td class="mainFormTextLabel">

			<form action="ctrlExportPontaj.php" method="post" enctype="multipart/form-data">
                <select name="month" onchange="total()">
					<?php
					for ($i = 0; $i <= 12; ++$i) {
						$time = strtotime(sprintf('-%d months', $i));
						$value = date('Y-m', $time);
						$label = date('F Y', $time);
						printf('<option value="%s">%s</option>', $value, $label);
						}
					?>
				</select>
                <button type="submit" name="csv" class="keyLink half-row-cell bootstrap-btn-style bootstrap-green" data-loading-text="Loading...">Download as CSV</button>
            </form>

			</td>
		</tr>
		<tr>
			<td class="mainFormText" colspan="2"><span id="status">&nbsp;</span></td>
		</tr>
	</table>
</div>
