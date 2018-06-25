<?php
require_once("contentScriptStart.php");
ini_set('display_errors', 1);
$db1 = new DB();
$db2 = new DB();

$isThePMofCurrentProject = false;
$canSeeAllProjects = false;
if ($_SESSION["user"]->usertype == 2 || $_SESSION["user"]->isAdmin()) {
    $canSeeAllProjects = true;
}
$invoiceCode = null;
/**
 * 0 - today report
 * 1 - yesterday report
 * 2 - this week report
 * 3 - last week report
 * 4 - report for all time
 */
$reportType = '0';
if (isset ($_REQUEST['reportType'])) {
    $reportType = $_REQUEST['reportType'];
}
if (isset ($_REQUEST['invoicecode'])) {
    $invoiceCode = $_REQUEST['invoicecode'];
}


$sql2 = "SELECT projectname FROM tbl_invoice_code_lookup WHERE invoicecode = ".$db2->quote($invoiceCode);
$db2->query($sql2);

$invoiceCodeExists = false;
if ($db2->numRows() == 1) {
    $invoiceCodeExists = true;
    $projectName = $db2->getElement("projectname");
}

$dateAttribArray = getdate();
if ($reportType == 2) {
    if ($dateAttribArray["wday"] == 5) {
        $thisFridayTime = time();
    } else {
       $thisFridayTime = strtotime("next Friday");
    }
} else if ($reportType == 3) {
    $thisFridayTime = strtotime("last Friday");
}

if(isset($thisFridayTime)){
	$friday = date("Y-m-d",$thisFridayTime);
	$timeSheetPeriodStart = _("Sat")." ".strftime("%d %b",strtotime("-6 days",$thisFridayTime));
	$timeSheetPeriodEnd = _("Fri")." ".strftime("%d %b",$thisFridayTime);
}
if(strpos($reportType,'m') > -1){
    $month = intval($reportType[1]);
    $dateWorked = strtotime('now');
    $dateWorked = strtotime("-$month months",$dateWorked);
    $timeSheetPeriodStartDate = strtotime(date('01-m-Y',$dateWorked));
    $dateWorked = strtotime("+1 months",$dateWorked);
    $dateWorked = strtotime(date('01-m-Y',$dateWorked));
    $timeSheetPeriodEndDate = strtotime("-1 day",$dateWorked);
    $timeSheetPeriodStart = date("d-m-Y",$timeSheetPeriodStartDate);
    $timeSheetPeriodEnd = date("d-m-Y",$timeSheetPeriodEndDate);
    $timeSheetPeriodStartDate = date("Y-m-d",$timeSheetPeriodStartDate);
    $timeSheetPeriodEndDate = date("Y-m-d",$timeSheetPeriodEndDate);
}

if ($reportType == 0) {
    $dateWorked = date("Y-m-d");
    $dateWorkedPrint = date("D d M");
} else if ($reportType == 1) {
    $dateWorked = date("Y-m-d", strtotime("yesterday"));
    $dateWorkedPrint = date("D d M", strtotime("yesterday"));
}

$sql2 = "SELECT * FROM tbl_invoice_code_lookup
    WHERE projectmanager = ".$_SESSION["user"]->refid." AND completed <> 'closed' ORDER BY department ASC ,invoicecode ASC";
$db2->query($sql2);

$myArray = Array();
for($i=0;$i<$db2->numRows();++$i) {
    $invoiceCodeL = htmlspecialchars($db2->getElement("invoicecode"));
    $projectNameL = htmlspecialchars($db2->getElement("projectname"));
    $department = htmlspecialchars($db2->getElement("department"));
    $myArray[$invoiceCodeL] = $projectNameL;
    if(mb_strtolower($invoiceCode) == mb_strtolower($invoiceCodeL)){
    	$isThePMofCurrentProject = true;
    }

    if ($i == 0 && (!isset($invoiceCode) || $invoiceCode == "") && (!$canSeeAllProjects)) {
        $invoiceCode = $invoiceCodeL;
        $projectName = $projectNameL;
        $isThePMofCurrentProject = true;
    }

    $db2->nextRow();
}

$orderPart = ' ORDER BY lname, fname, taskcoderefid ASC';
if ($reportType === 0 || $reportType == 1) {
    $sql1 = "select * from view_time_report where invoicecoderefid=".$db1->quote($invoiceCode)." and dateworked=".$db1->quote($dateWorked).$orderPart;
    $db1->query($sql1);
} elseif ($reportType == 2 || $reportType == 3) {
	$sql1 = "select * from view_time_report_weekly where invoicecoderefid=".$db1->quote($invoiceCode)." and enddate=".$db1->quote($friday).$orderPart;
	$db1->query($sql1);
}
elseif($reportType == 4){
	$sql1 = "select * from view_time_report_ever where invoicecoderefid=".$db1->quote($invoiceCode);
	$db1->query($sql1);
}elseif(strpos($reportType,'m') > -1){
    $sql1 = "select * from view_time_report where invoicecoderefid=".$db1->quote($invoiceCode)." and dateworked >=".$db1->quote($timeSheetPeriodStartDate)." and dateworked <=".$db1->quote($timeSheetPeriodEndDate).$orderPart;
    $db1->query($sql1);
}
?>

<div id="mainFormContainer">

    <form method="get" name="display" style="margin: 10px" action="">
        <b><?php echo _("Project")?>: </b>

        <?php if ($canSeeAllProjects) { ?>
        <input name="invoicecode" value="<?php echo $invoiceCode; ?>" id="invoicecode" type="text" onfocus="addAutoComplete(this,'invoicecode')" />

        <?php } else {?>
        <select name="invoicecode">
            <?php
            foreach (array_keys($myArray) as $invCode) {
            	$selected = "";
              	if ($invoiceCode == $invCode) {
			        $selected = 'selected="selected"';
			    }
                $projName = $myArray[$invCode];
                echo ("<option $selected value=\"$invCode\">$projName ($invCode)</option>");
            }
            ?>

        </select>
        <?php } ?>

        <b><?php echo("&nbsp;&nbsp;"); echo _("Period")?>: </b>
        <select name="reportType" onchange="document.forms.display.submit()">
            <option <?php if ($reportType === '0') echo 'selected="selected"';?> value="0"><?php echo _("today")?></option>
            <option <?php if ($reportType == 1) echo 'selected="selected"';?> value="1"><?php echo _("yesterday")?></option>
            <option <?php if ($reportType == 2) echo 'selected="selected"';?> value="2"><?php echo _("this week")?></option>
            <option <?php if ($reportType == 3) echo 'selected="selected"';?> value="3"><?php echo _("last week")?></option>
            <option <?php if ($reportType == 4) echo 'selected="selected"';?> value="4"><?php echo _("ever")?></option>
            <?php
                $dateWorked = strtotime('now');
                $month = 0;
                while($month < 12){
                    $selected = '';
                    if($reportType == 'm'.$month){
                        $selected = 'selected="selected"';
                    }
                    echo '<option value="m'.$month.'" '.$selected.'>'.date('M Y',$dateWorked).'</option>';
                    $dateWorked = strtotime("-1 month",$dateWorked);
                    $month++;
                }

            ?>
        </select>
        <input type="submit" class="half-row-cell bootstrap-btn-style bootstrap-blue" value="submit" />
    </form>
    <table class="FormTable">
        <tr>
            <td class="mainFormTextLabel" colspan="4">&nbsp;</td>
        </tr>
        <tr>
            <td class="mainFormTextLabel2" colspan="4">
                <?php
				if ($reportType === '0' || $reportType == 1) { echo _("Day report"); echo " ($dateWorkedPrint)"; }
                if ($reportType == 2 || $reportType == 3) { echo _("Week report");echo " ($timeSheetPeriodStart - $timeSheetPeriodEnd)"; }
                if (strpos($reportType,'m') > -1) { echo _("Period report"); echo " ($timeSheetPeriodStart - $timeSheetPeriodEnd)"; }
                ?>
            </td>
        </tr>
        <tr>
            <td class="mainFormTextLabel2" colspan="4">
            <?php
                if (!isset($invoiceCode) || $invoiceCode == "") {
                    echo _("Please enter invoice code.");
                } else {
                    if ($invoiceCodeExists || !$canSeeAllProjects) {
                        echo '<span class="highlightGreen">'.$projectName.'('.$invoiceCode.')</span>';
                    } else {
                        echo _("Invoice code was not found in the database.");
                    }
                }
            ?>
            </td>
        </tr>
        <tr>
            <td class="mainFormTextLabel" colspan="4">&nbsp;</td>
        </tr>
		<tr>
        	<td class="mainFormTextLabel2"><?php echo _('Employee')?></td>
          <td class="mainFormTextLabel2"><?php echo _('Task code')?></td>
          <td class="mainFormTextLabel2" colspan="2"><?php echo _('Hours')?></td>
        </tr>

        <?php
        $prevEmployeeRefid = $db1->getElement("refid");
        $prevTaskCode = $db1->getElement("taskcoderefid");
        $hoursArr = Array();
        $prevName = '';
        $recordsForSameEmployee = 0;
        $totalHours = 0;
        $grandTotal = 0;
        for($i=0;$i < $db1->numRows();++$i) {
        	$employee = $db1->getElement("refid");
        	$refid = $db1->getElement("timesheetrefid");
          $fname = ucwords($db1->getElement("fname"));
          $lname = ucwords($db1->getElement("lname"));
          $taskCode = $db1->getElement("taskcoderefid");
            if($taskCode == ''){
              	$taskCode = '-';
            }
          $hours = $db1->getElement("hours");
          $grandTotal += $hours;

          if (!isset($hoursArr[$taskCode])) {
            $hoursArr[$taskCode] = $hours;
          }

            //building up the total hours for employees with many entries
            if($employee == $prevEmployeeRefid){
            	$totalHours += $hours;
            	$recordsForSameEmployee++;

               if ($taskCode == $prevTaskCode) {
                 $hoursArr[$taskCode] += $hours;
               }
               else{
                 ?>
               <tr>
                 <td class="mainFormTextLabel"><?php echo $prevName?></td>
                 <td class="mainFormTextLabel highlightRed">TOTAL <?php echo $taskCode?></td>
                 <td class="mainFormTextLabel highlightRed" style="text-align:right"><?php echo formatNumber($hoursArr[$taskCode])?></td>
                 <td></td>
               </tr>
               <?php
               }


            }else{
            	if($recordsForSameEmployee > 1){
            		?>
			        <tr>
			        	<td class="mainFormTextLabel"><?php echo $prevName?></td>
			          <td class="mainFormTextLabel highlightRed">TOTAL</td>
			          <td class="mainFormTextLabel" style="text-align:right"><?php echo formatNumber($totalHours)?></td>
			          <td></td>
			        </tr>
		        	<?php
            	}
            	$totalHours = $hours;
            	$recordsForSameEmployee = 1;

              ?>
              <tr>
                <td class="mainFormTextLabel"><?php echo $prevName?></td>
                <td class="mainFormTextLabel highlightRed">TOTAL <?php echo $taskCode?></td>
                <td class="mainFormTextLabel highlightRed" style="text-align:right"><?php echo formatNumber($hoursArr[$taskCode])?></td>
                <td></td>
              </tr>
              <?php
            }


        	$paranthesis = "";
            if($refid && ($isThePMofCurrentProject || $refid == $_SESSION['user']->refid || $_SESSION['user']->isAdmin())){
            	$paranthesis = '(<a class="keyLink" href="timesheetView.php?refid='.$refid.'">'._("view").'</a>)';
            }

            $prevEmployeeRefid = $employee;
            $prevTaskCode = $taskCode;
            $prevName = $fname.' '.$lname;
            $db1->nextRow();
            ?>
	        <tr>
	            <td class="mainFormTextLabel"><?php echo $fname.' '.$lname.' '.$paranthesis?></td>
	            <td class="mainFormTextLabel"><?php echo $taskCode?></td>
	            <td class="mainFormTextLabel" style="text-align:right"><?php echo $hours?></td>
	            <td></td>
	        </tr>
        	<?php
        }
        if($recordsForSameEmployee > 1){
            ?>
		        <tr>
		            <td class="mainFormTextLabel"><?php echo $prevName?></td>
		            <td class="mainFormTextLabel highlightRed">TOTAL</td>
		            <td class="mainFormTextLabel" style="text-align:right"><?php echo formatNumber($totalHours)?></td>
		            <td></td>
		        </tr>
		    <?php
        }
        if($grandTotal > $totalHours){
            ?>
            <tr>
                <td class="mainFormTextLabel highlightRed">GRAND TOTAL</td>
                <td class="mainFormTextLabel highlightRed">TOTAL</td>
                <td class="mainFormTextLabel" style="text-align:right"><?php echo formatNumber($grandTotal)?></td>
                <td></td>
            </tr>
        <?php
        }
        ?>
        <tr>
            <td class="mainFormTextLabel" colspan="4">&nbsp;</td>
        </tr>
    </table>
</div>
