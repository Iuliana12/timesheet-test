<?php

setlocale(LC_TIME, array('ro.utf-8', 'ro_RO.UTF-8', 'ro_RO.utf-8', 'ro', 'ro_RO', 'ro_RO.ISO8859-2'));

ini_set('display_errors','On');
require_once("header.php");
$db = new DB();


$objPHPExcel = new PHPExcel();
//$objWriter = new PHPExcel_Writer_PDF($objPHPExcel);
$title = 'Pontaj';

if(!$_SESSION["user"]->isAdmin()){
	error(_("access denied"));
	exit;
}

$startOfMonth;
$endOfMonth;

if (isset($_POST["month"])) {
	$date = strtotime($_POST["month"]);
	$startOfMonth = date('Y-m-01', $date);
	$endOfMonth = date('Y-m-t', $date);

}else{

	die('No month sent!');
}


// header('Content-Type: text/csv; charset=utf-8');
// header("Content-disposition: attachment ; filename = Report.csv");

$sql = "SELECT A.lname || ' ' || A.fname AS name,
		A.refid,
 		date_part('day', C.dateworked) AS day,
		SUM(C.hours) AS hours,
		SUM(CASE WHEN C.ratetype = ".$db->quote('1')." THEN C.hours ELSE 0 END) AS lieu,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('HOL')." THEN C.hours ELSE 0 END) AS co,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('PMHOL')." THEN C.hours ELSE 0 END) AS m,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('SICK')." THEN C.hours ELSE 0 END) AS bo,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('ACCM')." THEN C.hours ELSE 0 END) AS am,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('SICKM')." THEN C.hours ELSE 0 END) AS bp,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('ABS')." THEN C.hours ELSE 0 END) AS n,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('UNPAID')." THEN C.hours ELSE 0 END) AS in,
		SUM(CASE WHEN D.invoicecoderefid = ".$db->quote('BH')." THEN C.hours ELSE 0 END) AS bh

		FROM tbl_staff_lookup AS A LEFT JOIN tbl_staff_preferences E ON E.staff_refid = A.refid
		join tbl_office_time_sheet AS B ON A.refid = B.staffrefid
		LEFT JOIN tbl_office_time_sheet_entry AS D ON (D.officetimesheetrefid = B.refid)
		LEFT JOIN tbl_office_time_sheet_entry_details AS C ON (C.officetimesheetentryrefid = D.refid)
		WHERE A.refid > 2 AND A.employed = true AND A.refid = B.staffrefid AND C.dateworked >= ".$db->quote($startOfMonth)." AND C.dateworked <= ".$db->quote($endOfMonth)."

		GROUP BY A.refid,A.fname,A.lname,date_part('day', dateworked)
		ORDER BY date_part('day', C.dateworked),A.lname ASC,A.fname ASC";


$arrColumns = Array();
$arrEmployees = Array();
$db->query($sql);

for ($i = 0; $i < $db->numRows(); ++$i) {
	$staffRefid = $db->getElement('refid');
	$day = $db->getElement('day');
	$name = $db->getElement('name');
	$hours = floatval($db->getElement('hours'));
	$lieu = floatval($db->getElement('lieu'));
	$timeType = '';
	$co = intval($db->getElement('co'));
	$m  = intval($db->getElement('m'));
	$bo = intval($db->getElement('bo'));
	$am = intval($db->getElement('am'));
	$bp = intval($db->getElement('bp'));
	$ne = intval($db->getElement('n'));
	$in = intval($db->getElement('in'));
	$bh = intval($db->getElement('bh'));

	if ($lieu > 0) {
		$hours = $hours - $lieu;
	}

	if ($co > 0) {
		$hours = $co;
		$timeType = 'co';
		}

	if ($m > 0) {
		$hours = $m;
		$timeType = 'm';
	}

	if ($bo > 0) {
		$hours = $bo;
		$timeType = 'bo';
	}

	if ($am > 0) {
		$hours = $am;
		$timeType = 'am';
	}

	if ($bp > 0) {
		$hours = $bp;
		$timeType = 'bp';
	}

	if ($ne > 0) {
		$hours = $n;
		$timeType = 'n';
	}

	if ($in > 0) {
		$hours = $in;
		$timeType = 'in';
	}

	if ($bh > 0) {
		$hours = $bh;
		$timeType = 'bh';
	}

	if(!isset($arrEmployees[$staffRefid])){
		$arrEmployees[$staffRefid] = $name;
	}

	if(isset($arrColumns[$day])){
		$newStaff = new ReportExportStaff($staffRefid,$name,$hours,$timeType);
		$newColumn->addStaff($newStaff);
	}else
	{
		$newStaff = new ReportExportStaff($staffRefid,$name,$hours,$timeType);
		$newColumn = new ReportExportColumn($day);
		$newColumn->addStaff($newStaff);
		$arrColumns[$day] = $newColumn;
	}

	$db->nextRow();
}

asort($arrEmployees);
//print_r($arrColumns);

$nrcrt = 1;
$endOfRange = date('t',strtotime($endOfMonth));
$monthOfDate = date('m',$date);
$yearOfDate = date('Y',$date);
$luna = date('M',$date);
$daysFirstHalf = range(1,15);
$daysLastHalf = range(16,$endOfRange);
$row = 1;
$rows = 9;
$textStyleLegend;
$textStyleStaff;
$borderTable;
$rowNewTable;
$startRowLegenda;

//Remove Saturday and Suday columns
foreach (range(1,$endOfRange) as $day) {
	$newDate = strtotime($yearOfDate.'-'.$monthOfDate.'-'.$day);
	$dayOfWeek = date('N',$newDate);
	if($dayOfWeek == 6 || $dayOfWeek == 7){
		unset($arrColumns[$day]);
	}
}

foreach (range(7, $arrStaff) as $staff) {
	$textStyleStaff++;
	$textStyleLegend++;
	$borderTable++;
	$startRowLegenda = $startRowLegenda + 2;
	$rowNewTable = $rowNewTable + 2;
}

	 // Set document properties
	 $objPHPExcel->setActiveSheetIndex(0);
	 $objPHPExcel->getActiveSheet()->SetCellValue('A1', "NEWROCO SRL");
	 $objPHPExcel->getActiveSheet()->SetCellValue('A2', "Registru comertului: J22/1692/2016");
	 $objPHPExcel->getActiveSheet()->SetCellValue('A3', "Cod fiscal: 36349622");
	 $objPHPExcel->getActiveSheet()->SetCellValue('A4', "Adresa: ST.CEL MARE 4, Bl.A10, Et.1, Ap.11");
	 $objPHPExcel->getActiveSheet()->SetCellValue('A5', "Pontaj ".$luna." ".$yearOfDate);
	 $objPHPExcel->getActiveSheet()->SetCellValue('A7', "Nr.Crt");
	 $objPHPExcel->getActiveSheet()->SetCellValue('B7', "Angajat");
	 $objPHPExcel->getActiveSheet()->SetCellValue('C7', "1 - 15");
	 $objPHPExcel->getActiveSheet()->SetCellValue('R7', "Ore 1-15");
	 $objPHPExcel->getActiveSheet()->SetCellValue('S7', "16 - ".$endOfRange);
	 $objPHPExcel->getActiveSheet()->SetCellValue('AI7', "Ore 1-".$endOfRange);
	 $objPHPExcel->getActiveSheet()->SetCellValue('AJ7', "din care: ");
	 $objPHPExcel->getActiveSheet()->SetCellValue('AJ8', "sI");
	 $objPHPExcel->getActiveSheet()->SetCellValue('AK8', "sII");
	 $objPHPExcel->getActiveSheet()->SetCellValue('AL8', "n");
	 $objPHPExcel->getActiveSheet()->SetCellValue('AM7', "Zile intr.");
	 $objPHPExcel->getActiveSheet()->SetCellValue('AN7', "din care: ");
	 $objPHPExcel->getActiveSheet()->SetCellValue('AN8', "Co");
	 $objPHPExcel->getActiveSheet()->SetCellValue('AO8', "Bo");
	 $objPHPExcel->getActiveSheet()->SetCellValue('AP8', "Bp");
	 $objPHPExcel->getActiveSheet()->SetCellValue('AQ8', "M");
	 $objPHPExcel->getActiveSheet()->SetCellValue('AR8', "I");
	 $objPHPExcel->getActiveSheet()->SetCellValue('AS8', "N");
	 $objPHPExcel->getActiveSheet()->SetCellValue('S'.$startRowLegenda, "Co - concediu odihna,");
	 $objPHPExcel->getActiveSheet()->SetCellValue('S'.($startRowLegenda + 1), "Bo - boala, ingrijire copil bolnav,");
	 $objPHPExcel->getActiveSheet()->SetCellValue('S'.($startRowLegenda + 2), "BP - boala profesionala,");
	 $objPHPExcel->getActiveSheet()->SetCellValue('S'.($startRowLegenda + 3), "Am - accident de munca,");
	 $objPHPExcel->getActiveSheet()->SetCellValue('S'.($startRowLegenda + 4), "M - maternitate, crestere copil,");
	 $objPHPExcel->getActiveSheet()->SetCellValue('S'.($startRowLegenda + 5), "I - invoiri, concedii fara salar,");
	 $objPHPExcel->getActiveSheet()->SetCellValue('S'.($startRowLegenda + 6), "N - absente nemotivate");
	 $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowNewTable, "Nr.Crt");
	 $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowNewTable, "Angajat");
	 $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowNewTable, "Semnatura");
	 $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(3);
	 $objPHPExcel->getActiveSheet()->getColumnDimension('AI')->setWidth(10);
	 $objPHPExcel->getActiveSheet()->getColumnDimension('AM')->setWidth(10);
	 $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
	 $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(4.5);
	 $objPHPExcel->getActiveSheet()->getColumnDimension('AI')->setWidth(4.5);
	 $objPHPExcel->getActiveSheet()->getColumnDimension('AM')->setWidth(4);
	 for($col = 'C'; $col !== 'R'; $col++) {
  	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
		}
	 for($col = 'S'; $col !== 'AI'; $col++) {
		$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
		}
	 for($col = 'AJ'; $col !== 'AM'; $col++) {
 		$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
 		}
	 for($col = 'AN'; $col !== 'AT'; $col++) {
		$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);

		}

	// bold
	$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
	$objPHPExcel->getActiveSheet()->getStyle('A5')->getFont()->setBold(true);

	//  alignment
	$style = array(
	      'alignment' => array(
	          'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				 		'vertical' 	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
	      )
	  );

	$styleFooter = array(
	      'alignment' => array(
	          'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
	      )
	  );

	//  border for cells
	$borderOutside = array(
		'borders' => array(
			'outline' => array(
				'style' => PHPExcel_Style_Border::BORDER_MEDIUM
				)
			)
		);

		$borderInside = array(
			'borders' => array(
				'inside' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
					)
				)
			);


	$objPHPExcel->getDefaultStyle()->applyFromArray($styleFooter);
	$objPHPExcel->getActiveSheet()->getStyle('A5')->applyFromArray($style);
	$objPHPExcel->getActiveSheet()->getStyle('B7')->applyFromArray($style);
	$objPHPExcel->getActiveSheet()->getStyle('A1:A4')->applyFromArray($styleFooter);
	$objPHPExcel->getActiveSheet()->getStyle('C7:AS'.($textStyleStaff + 43))->applyFromArray($style);
	$objPHPExcel->getActiveSheet()->getStyle('S'.$startRowLegenda.':AS'.($textStyleStaff + 15))->applyFromArray($styleFooter);
	$objPHPExcel->getActiveSheet()->getStyle('A7:AS'.($borderTable + 5))->applyFromArray($borderOutside);
	$objPHPExcel->getActiveSheet()->getStyle('A'.$rowNewTable.':H'.($borderTable + 13))->applyFromArray($borderOutside);
	$objPHPExcel->getActiveSheet()->getStyle('A'.$rowNewTable.':H'.($borderTable + 13))->applyFromArray($borderInside);
	$objPHPExcel->getActiveSheet()->getStyle('A7:AS'.($borderTable + 5))->applyFromArray($borderInside);
	$objPHPExcel->getActiveSheet()->getStyle('A7:AS8')->getAlignment()->setWrapText(true);
	$objPHPExcel->getActiveSheet()->getStyle('A7:AS'.($textStyleStaff + 100))->getFont()->setSize(8);


	//Get data
	$objPHPExcel->getActiveSheet()->fromArray($daysFirstHalf, null, 'C8');
	$objPHPExcel->getActiveSheet()->fromArray($daysLastHalf, null, 'S8');

	//Cell Merging
	$objPHPExcel->getActiveSheet()->mergeCells('A5:AS5');
	$objPHPExcel->getActiveSheet()->mergeCells('C7:Q7');
	$objPHPExcel->getActiveSheet()->mergeCells('S7:AH7');
	$objPHPExcel->getActiveSheet()->mergeCells('AJ7:AL7');
	$objPHPExcel->getActiveSheet()->mergeCells('AN7:AS7');
	$objPHPExcel->getActiveSheet()->mergeCells('A7:A8');
	$objPHPExcel->getActiveSheet()->mergeCells('S'.$startRowLegenda.':Z'.$startRowLegenda);
	$objPHPExcel->getActiveSheet()->mergeCells('S'.($startRowLegenda + 1).':Z'.($startRowLegenda + 1));
	$objPHPExcel->getActiveSheet()->mergeCells('S'.($startRowLegenda + 2).':Z'.($startRowLegenda + 2));
	$objPHPExcel->getActiveSheet()->mergeCells('S'.($startRowLegenda + 3).':Z'.($startRowLegenda + 3));
	$objPHPExcel->getActiveSheet()->mergeCells('S'.($startRowLegenda + 4).':Z'.($startRowLegenda + 4));
	$objPHPExcel->getActiveSheet()->mergeCells('S'.($startRowLegenda + 5).':Z'.($startRowLegenda + 5));
	$objPHPExcel->getActiveSheet()->mergeCells('S'.($startRowLegenda + 6).':Z'.($startRowLegenda + 6));
	$objPHPExcel->getActiveSheet()->mergeCells('B7:B8');
	$objPHPExcel->getActiveSheet()->mergeCells('R7:R8');
	$objPHPExcel->getActiveSheet()->mergeCells('AI7:AI8');
	$objPHPExcel->getActiveSheet()->mergeCells('AM7:AM8');
	$objPHPExcel->getActiveSheet()->mergeCells('C'.$startRowLegenda.':H'.$startRowLegenda);
	$objPHPExcel->getActiveSheet()->mergeCells('C'.($startRowLegenda + 1).':H'.($startRowLegenda + 1));
	$objPHPExcel->getActiveSheet()->mergeCells('C'.($startRowLegenda + 2).':H'.($startRowLegenda + 2));
	$objPHPExcel->getActiveSheet()->mergeCells('C'.($startRowLegenda + 3).':H'.($startRowLegenda + 3));
	$objPHPExcel->getActiveSheet()->mergeCells('C'.($startRowLegenda + 4).':H'.($startRowLegenda + 4));
	$objPHPExcel->getActiveSheet()->mergeCells('C'.($startRowLegenda + 5).':H'.($startRowLegenda + 5));

	//  set the paper size and the paper view style
	$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
	$objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);


foreach($arrEmployees as $refid => $name){

	$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rows, $nrcrt);
	$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rows, $name);
	$objPHPExcel->getActiveSheet()->SetCellValue('A'.($rowNewTable + 1), $nrcrt);
	$objPHPExcel->getActiveSheet()->SetCellValue('B'.($rowNewTable + 1), $name);

	//echo "\"".$nrcrt++."\",";
	//echo "\"".$name."\",";
	$sI = 0;
	$sII = 0;
	$n = 0;
	$sumTotalHalf = 0;
	$sumTotal = 0;
	$sumCo = 0;
	$sumBo = 0;
	$sumBp = 0;
	$sumAM = 0;
	$sumBp = 0;
	$sumM  = 0;
	$sumI  = 0;
	$sumN  = 0;
	$timeTypeTotal = 0;

  $colNo = 2;
	foreach (range(1,$endOfRange) as $day) {
		if(!isset($arrColumns[$day])){
				//echo ",";
		}else{
			$arrStaff = $arrColumns[$day]->getStaff($refid);
			$listedValue = '';

			foreach($arrStaff as $staff){
				$normalTimeListed = false;
				$timeType = $staff->timeType;
				switch ($timeType) {
					case 'co':
						$listedValue = $timeType;
						$sumCo += ($staff->hours)/8;
						break;
					case 'm':
						$listedValue = $timeType;
						$sumM += ($staff->hours)/8;
						break;
					case 'bo':
						$listedValue = $timeType;
						$sumBo += ($staff->hours)/8;
						break;
					case 'am':
						$listedValue = $timeType;
						$sumAm += ($staff->hours)/8;
						break;
					case 'bp':
						$listedValue = $timeType;
						$sumBp += ($staff->hours)/8;
						break;
					case 'n':
						$listedValue = $timeType;
						$sumN += ($staff->hours)/8;
						break;
					case 'in':
						$listedValue = $timeType;
						$sumI += ($staff->hours)/8;
						break;
					// Ignore BH time
					case 'bh':
						break;

					default:
						$sumTotal += $staff->hours;

						$listedValue = $staff->hours;
						$normalTimeListed = true;
						$col++;
						break;
				}
				if($normalTimeListed){
					break;
				}
			}
			//echo "\"".$listedValue."\",";
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($colNo,$rows, $listedValue);
			}
		$colNo++;
		if($colNo == 17){
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($colNo,$rows, $sumTotal);
			$colNo++;
		}
		// if($day == 15 || $day == $endOfRange){
		// 	echo "\"".$sumTotal."\",";
		// }
		$sumCo = ceil($sumCo);
		$sumBo = ceil($sumBo);
		$sumBp = ceil($sumBp);
		$sumM = ceil($sumM);
		$sumI = ceil($sumI);		
		$sumN = ceil($sumN);		
		$timeTypeTotal = $sumCo + $sumBo + $sumBp + $sumM + $sumI + $sumN;
	}


	//Get data

	$objPHPExcel->getActiveSheet()->SetCellValue('AI'.$rows, $sumTotal);
	$objPHPExcel->getActiveSheet()->SetCellValue('AJ'.$rows, $sI);
	$objPHPExcel->getActiveSheet()->SetCellValue('AK'.$rows, $sII);
	$objPHPExcel->getActiveSheet()->SetCellValue('AL'.$rows, $n);
	$objPHPExcel->getActiveSheet()->SetCellValue('AM'.$rows, $timeTypeTotal);
	$objPHPExcel->getActiveSheet()->SetCellValue('AN'.$rows, $sumCo);
	$objPHPExcel->getActiveSheet()->SetCellValue('AO'.$rows, $sumBo);
	$objPHPExcel->getActiveSheet()->SetCellValue('AP'.$rows, $sumBp);
	$objPHPExcel->getActiveSheet()->SetCellValue('AQ'.$rows, $sumM);
	$objPHPExcel->getActiveSheet()->SetCellValue('AR'.$rows, $sumI);
	$objPHPExcel->getActiveSheet()->SetCellValue('AS'.$rows, $sumN);

 	 ++$rows;
	 ++$nrcrt;
	 ++$rowNewTable;

}
// Saving the document as ODS file...
$file = $title.' '.$luna.' '.$yearOfDate.'.xlsx';
$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
//$objWriter = new PHPExcel_Writer_OpenDocument($objPHPExcel);
//$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'PDF');
//PHPExcel_Settings::setPdfRenderer(PHPExcel_Settings::PDF_RENDERER_DOMPDF,$rendererLibraryPath);
header('Content-type: application/vnd.ms-excel');
//header('Content-type: application/vnd.oasis.opendocument.spreadsheet');
//header('Content-type: application/pdf');
header('Content-Disposition: attachment; filename="'.$file.'"');
header('Cache-Control: max-age=0');
ob_end_clean();
$objWriter->save('php://output');
