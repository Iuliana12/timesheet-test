<?php

class ToilHelper {
	public $timeSheetRefid,$debug,$allowTOILinAdvance, $dontTouchTheAdjustment;
	public $employeeRefid,$timeSheetEnddate;
	public $lieu,$this, $SUM;
	public $toilAdjustmentValue;
	public $toilAdjustmentDate;
	private $db;
	
	function __construct($timeSheetRefid, $allowTOILinAdvance = false, $dontTouchTheAdjustment = false, $debug = false) {
		$this->timeSheetRefid = $timeSheetRefid;
		$this->debug = $debug;
		$this->allowTOILinAdvance = $allowTOILinAdvance;
		$this->dontTouchTheAdjustment = $dontTouchTheAdjustment;
		
		$this->db = new DB();
		$sql = "SELECT B.staffrefid, B.enddate,
				SUM(CASE WHEN C.ratetype = ".$this->db->quote('1')." THEN C.hours ELSE 0 END) AS lieu,
				SUM(CASE WHEN D.invoicecoderefid = ".$this->db->quote('TOIL')." THEN C.hours ELSE 0 END) AS toil
				FROM tbl_office_time_sheet AS B 
				LEFT JOIN tbl_office_time_sheet_entry AS D ON (D.officetimesheetrefid = B.refid)
				LEFT JOIN tbl_office_time_sheet_entry_details AS C ON (C.officetimesheetentryrefid = D.refid)
				WHERE B.refid = ".$this->timeSheetRefid." GROUP BY B.staffrefid,B.enddate";
		$this->db->query($sql,Array('integer','text', 'float', 'float'));
		if ($this->db->numRows() != 1 ) {
			return -1;
		}
		$this->employeeRefid = $this->db->getElement("staffrefid");
		$this->timeSheetEnddate = $this->db->getElement("enddate");
		$this->lieu = $this->db->getElement("lieu");
		$this->toil = $this->db->getElement("toil");
		$this->acquireAdjustmentData();
		if($this->debug){
			echo $this->timeSheetRefid." --- <br/>";
			echo "LIEU = ".$this->lieu."<br/>";
			echo "TOIL = ".$this->toil."<br/>";
		}
		$this->calculateSUM();
	}
	/**
	 * Loads the adjustment figures to current object 
	 */
	private function acquireAdjustmentData(){
		$db = new DB();
		if (!$this->dontTouchTheAdjustment) {
			$sql = 'SELECT adjustment,date_commencing FROM tbl_staff_toil_adjustment 
					WHERE staff_refid = '.$this->employeeRefid.' AND refid = 
					(SELECT MAX(refid) FROM tbl_staff_toil_adjustment WHERE staff_refid = '.$this->employeeRefid.')';
			$db->query($sql,Array('float','text'));
			if($this->db->numRows() > 0){
				$this->toilAdjustmentValue = $db->getElement("adjustment");
				$this->toilAdjustmentDate = strtotime($db->getElement("date_commencing"));
			}
		}else{
			$this->toilAdjustmentValue = 0;
			$this->toilAdjustmentDate = time();			
		}
	}
	/**
	 * Calculates the total amount of LIEU available to take by the current employee at the date of submission of the time sheet in question
	 */
	private function calculateSUM(){
		$db = new DB();
		$this->SUM = 0;
		$sql = "SELECT SUM(lieu_ot) AS toil_remaining FROM tbl_office_time_sheet 
				WHERE staffrefid = ".$this->employeeRefid." AND submitted = true AND lieu_ot <> 0
				AND enddate < timestamp ".$db->quote($this->timeSheetEnddate)."
				AND enddate >= timestamp ".$db->quote($this->timeSheetEnddate)." - interval ".$db->quote((TOIL_EXPIRATION_DAYS + 7).' days');
		$db->query($sql,Array('float'));
		if($db->numRows() > 0){
			$this->SUM = floatval($db->getElement("toil_remaining"));
		}
		//adding the toilAdjustment which aids the decision if we should go further or tell the employee they don't have enough TOIL accrued
		$this->SUM += $this->toilAdjustmentValue;
		if($this->debug){
			echo "SUM = ".$this->SUM."<br/>";
		}
	}
	
	private function updateToilAdjustment($stage,$previous_value){
		if($this->debug){
			echo $stage." toil adjustment <br/>";
			echo "LIEU = ".$this->lieu."<br/>";
			echo "TOIL = ".$this->toil."<br/>";
			echo "new TOIL adjustment= ". $this->toilAdjustmentValue."<br/>";
		}
		$diff = $previous_value - $this->toilAdjustmentValue; 
		$comment = 'SYSTEM UPDATE: the employee used '.$diff.' hours of the toil adjustment';
		if($diff < 0){
			$comment = 'SYSTEM UPDATE: the employee accrued '.abs($diff).' hours of lieu';
		}
		//update the toil adjustment
		$values = Array("adjustment" => $this->toilAdjustmentValue,
						"staff_refid" => $this->employeeRefid,
						"source_time_sheet" => $this->timeSheetRefid,
						"date_commencing" => date("Y-m-d",$this->toilAdjustmentDate),
						"comment" => $comment);
		$this->db->autoExec('tbl_staff_toil_adjustment', $values, MDB2_AUTOQUERY_INSERT);
		//writing the action to log
		$values = Array("author_refid" => $_SESSION['user']->refid,
						"staff_refid" =>  $this->employeeRefid,
						"source_time_sheet" => $this->timeSheetRefid,
						"affected_time_sheet" => null,
						"action" => $stage.' update toil adjustment',
						"previous_value" => $previous_value,
						"value" => $this->toilAdjustmentValue);
		$this->db->autoExec('tbl_staff_toil_adjustment_log', $values, MDB2_AUTOQUERY_INSERT);
	}
	
	private function updateLieuOT($stage,$refid,$lieu_ot,$previous_value){
		$values = Array("lieu_ot" => $lieu_ot);
		$where = "refid = ".$refid;
		$this->db->autoExec('tbl_office_time_sheet', $values, MDB2_AUTOQUERY_UPDATE, $where);
		if($lieu_ot == $previous_value){
			return;
		}
		//writing the action to log
		$values = Array("author_refid" => $_SESSION['user']->refid,
						"staff_refid" =>  $this->employeeRefid,
						"source_time_sheet" => $this->timeSheetRefid,
						"affected_time_sheet" => $refid,
						"action" => $stage.' update lieu_ot',
						"previous_value" => $previous_value,
						"value" => $lieu_ot);
		$this->db->autoExec('tbl_staff_toil_adjustment_log', $values, MDB2_AUTOQUERY_INSERT);
	}
	
	/**
	 *  Here's how it works
	1. Calculate the total LIEU OverTtime declared(called LIEU) and TOIL(called TOIL) taken on this time sheet.
	2. If TOIL > 0 
	  2.1. Calculate the lieu overtime accrued in the past TOIL_EXPIRATION_DAYS days by summing the value of lieu_ot for those weeks and adding the toil_adjustment value.
		  Let's call it SUM.
	  2.2. If SUM < TOIL,
		   Let's call the amount of extra TOIL needed from the LIEU of this time sheet, extraTOIL.
		2.2.1 If SUM < 0 (we have debt)
			  extraTOIL <- TOIL + ABS(SUM)
		2.2.2 Else
			  extraTOIL <- TOIL - SUM
	    2.2.3. If LIEU < extraTOIL, STOP, tell the user they can't take that much TOIL.
	  2.3.Else if SUM >= TOIL  Go through all the time sheets not more than TOIL_EXPIRATION_DAYS days old, where lieu_ot > 0. Take the oldest one first. 
	      Treat the adjustment as a week, taking toil from it after the older weeks, but before the newer weeks.
	    2.4.1. If lieu_ot <= TOIL, update lieu_ot to 0; TOIL <- TOIL - lieu_ot
	    2.4.2. Else, update lieu_ot to lieu_ot - TOIL; TOIL <- 0;
	    2.4.3. If TOIL = 0, BREAK
	  2.5 If TOIL > 0 AND LIEU > TOIL
	      LIEU <- LIEU - TOIL
	      TOIL <- 0;
	3. If LIEU > 0
	  3.1. Go through all the time sheets not more than TOIL_EXPIRATION_DAYS days old, where lieu_ot < 0. Take the oldest first. 
	       Treat the adjustment as a week, if it is negative.
	    3.1.1. If ABS(lieu_ot) <= LIEU, update lieu_ot to 0; LIEU <- LIEU - ABS(lieu_ot)
	    3.1.2. Else, update lieu_ot to -(ABS(lieu_ot) - LIEU); LIEU <- 0
	    3.1.3. If LIEU is 0, BREAK;
	4. update lieu_ot of the current time sheet to LIEU, even if it's 0 
	
	If more toil than available is declared this function will return the difference between the available and declared which will be negative and 
	the caller should handle this accordingly.
	
	If allowTOILinAdvance is true, the function will not stop for negative toil, but write the result in the DB.
	In this case, the algorithm will not check that there is enough TOIL available, but proceed and take as much as 
	possible from the old one, then if there's any lieu accumulated on this time sheet, it will try to pay off
	old debt and then set the difference between lieu and toil as lieu_ot, wether it's negative or positive
	 */
	function adjustToil(){
		$db = new DB();
		$this->db->beginTransaction();
	//	1. Calculate the total LIEU OT declared(called LIEU) and TOIL(called TOIL) taken on this time sheet.
	//	2. If TOIL > 0
		if($this->toil > 0){
			if (!$this->allowTOILinAdvance) {//is TOIL is allowed in advance, we don't need to stop the procedure at this stage.
				if ($this->SUM < $this->toil) {
//					Let's call the amount of extra TOIL needed from the LIEU of this time sheet, extraTOIL.
					$extraTOIL = 0;
					//2.2.1 If SUM < 0 (we have debt)
					if ($this->SUM < 0){
						$extraTOIL = $this->toil + abs($this->SUM);
					} 
					else { 
						$extraTOIL = $this->toil - $this->SUM;//$SUM is smaller than $this->toil
					}
					//2.2.3. If LIEU < extraTOIL, STOP, tell the user they can't take that much TOIL.
					if($this->lieu < $extraTOIL){
						$this->lieu = $this->lieu - $extraTOIL;
						//this is the amount of hours in lieu needed to allow the continuation of this procedure(it will be negative)
						if($this->debug) {
							echo "2.3.4 returned ".$this->lieu ."<br/>";
						}
						return $this->lieu;
					}
				}
			}
			//2.3 Go through all the time sheets not older than TOIL_EXPIRATION_DAYS days, where lieu_ot > 0
			/*If the available toil can't cover for what they're trying to take now and 
			 * toil in advance is not permitted, the function would have already returned.
			 */
			$sql = "SELECT refid, lieu_ot, enddate FROM tbl_office_time_sheet 
					WHERE staffrefid = ".$this->employeeRefid." AND submitted = true AND lieu_ot > 0
					AND enddate < timestamp ".$db->quote($this->timeSheetEnddate)."
					AND enddate >= timestamp ".$db->quote($this->timeSheetEnddate)." - interval ".$db->quote((TOIL_EXPIRATION_DAYS + 7).' days')."
					ORDER BY enddate ASC";//by ordering by enddate ascending, I make sure I take the oldest week first.
			$previous_date = strtotime('2 years ago');//this is an initialisation to make sure the first step won't take it into consideration
			$db->query($sql,Array('integer','float', 'text'));
			for($i=0; $i<$db->numRows(); ++$i){
				$currRefid = $db->getElement("refid");
				$curr_lieu_ot = $db->getElement("lieu_ot");
				$current_date = strtotime($db->getElement("enddate"));
				//taking care of the toil adjustment first
				if($this->toilAdjustmentValue > 0 && $this->toilAdjustmentDate >= $previous_date && $this->toilAdjustmentDate < $current_date ){
					$prev_toilAdjustment = $this->toilAdjustmentValue;
					//the same procedure as below
					if($this->toilAdjustmentValue < $this->toil){
						$this->toil -= $this->toilAdjustmentValue;
						$this->toilAdjustmentValue = 0;
					}
					else{
						$this->toilAdjustmentValue -= $this->toil;
						$this->toil = 0;
					}
					$this->updateToilAdjustment("2.3",$prev_toilAdjustment,false);
					if($this->toil == 0){
						break;
					}
				}
				$prev = $curr_lieu_ot;
				if($curr_lieu_ot < $this->toil){
	//	    		2.3.1. If lieu_ot <= TOIL, update lieu_ot to 0; TOIL <- TOIL - lieu_ot
					$this->toil -= $curr_lieu_ot;
					$curr_lieu_ot = 0;
				}
				else{
	//				2.3.2. Else, update lieu_ot to lieu_ot - TOIL; TOIL <- 0;
					$curr_lieu_ot -= $this->toil;
					$this->toil = 0;
				}
				//update the current time sheet
				$this->updateLieuOT("2.3",$currRefid,$curr_lieu_ot,$prev);
				if($this->toil == 0){
	//	    		2.3.3. If TOIL = 0, BREAK
					break;
				}
				$previous_date = $current_date;
				$db->nextRow();
			}
			//if the previous step didn't make the toil 0 (no previous weeks with lieu_ot > 0 or the toil 
			//adjustment commencing date is newer than all the previous weeks)
			if($this->toil > 0 && $this->toilAdjustmentValue > 0){
				$prev_toilAdjustment = $this->toilAdjustmentValue;
				//the same procedure as above
				if($this->toilAdjustmentValue < $this->toil){
					$this->toil -= $this->toilAdjustmentValue;
					$this->toilAdjustmentValue = 0;
				}
				else{
					$this->toilAdjustmentValue -= $this->toil;
					$this->toil = 0;
				}
				$this->updateToilAdjustment("2.3b",$prev_toilAdjustment);
			}
			if (!$this->allowTOILinAdvance) {
				if($this->debug) {
					echo "2.4. <br/>";
					echo "LIEU = ".$this->lieu."<br/>";
					echo "TOIL = ".$this->toil."<br/>";
				}
				//2.4.
				//if the previous steps didn't make the toil 0 (some of the toil needs to be taken from the current time sheet)
				if($this->toil > 0){
					if($this->lieu >= $this->toil){
						$this->lieu -= $this->toil;
						$this->toil = 0;
					}
					else{//the 2.2 steps assure that we don't get so far, but if we do...
						$this->db->rollback();
						if($this->debug){
							echo "Breach: the algorithm returns ".(0 - $this->toil)." at step 2.4";
						}
						return -$this->toil;
					}
				}
			}
		}
		if($this->debug) {
			echo "3. <br/>";
			echo "LIEU = ".$this->lieu."<br/>";
			echo "TOIL = ".$this->toil."<br/>";
		}
	//	3. If LIEU > 0
		if($this->lieu > 0) {
	//	  	3.1 Go through all the time sheets before the current one with lieu_ot < 0, not older than TOIL_EXPIRATION_DAYS days
			$sql = "SELECT refid, lieu_ot,enddate FROM tbl_office_time_sheet 
					WHERE staffrefid = ".$this->employeeRefid." AND submitted = true AND lieu_ot < 0 
					AND enddate < ".$db->quote($this->timeSheetEnddate)."
					AND enddate >= timestamp ".$db->quote($this->timeSheetEnddate)." - interval ".$db->quote((TOIL_EXPIRATION_DAYS + 7).' days')."
					ORDER BY enddate ASC";
			$db->query($sql,Array('integer','float','text'));
			$previous_date = strtotime('2 years ago');
			for($i=0; $i<$db->numRows(); ++$i){
				$currRefid = $db->getElement("refid");
				$curr_lieu_ot = $db->getElement("lieu_ot");
				$current_date = strtotime($db->getElement("enddate"));
				//taking care of the toil adjustment
				if($this->toilAdjustmentValue < 0 && $this->toilAdjustmentDate >= $previous_date && $this->toilAdjustmentDate < $current_date){
					$prev_toilAdjustment = $this->toilAdjustmentValue;
					if(abs($this->toilAdjustmentValue) <= $this->lieu) {
						$this->lieu -= abs($this->toilAdjustmentValue);
						$this->toilAdjustmentValue = 0;
					}
					else{
						$this->toilAdjustmentValue = -(abs($this->toilAdjustmentValue) - $this->lieu);
						$this->lieu = 0;
					}
					if($this->debug){
						echo "3.1 toil adjustment <br/>";
						echo "LIEU = ".$this->lieu."<br/>";
						echo "new TOIL adjustment= ". $this->toilAdjustmentValue."<br/>";
					}
					$this->updateToilAdjustment("3.1",$prev_toilAdjustment,true);
					if($this->lieu == 0){
						break;
					}
				}
				if($this->debug) {
					echo "curr LIEU = ".$curr_lieu_ot."<br/>";
				}
				$prev = $curr_lieu_ot;
				if(abs($curr_lieu_ot) <= $this->lieu) {
	//		 		3.1.1 If ABS(lieu_ot) <= LIEU, update lieu_ot to 0; LIEU <- LIEU - ABS(lieu_ot)
					$this->lieu -= abs($curr_lieu_ot);
					$curr_lieu_ot = 0;
				}
				else{
	//	    		3.1.2 Else, update lieu_ot to -(ABS(lieu_ot) - LIEU); LIEU <- 0
					$curr_lieu_ot = -(abs($curr_lieu_ot) - $this->lieu);
					$this->lieu = 0;
				}
				if($this->debug) {
					echo "LIEU = ".$this->lieu."<br/>";
					echo "curr LIEU = ".$curr_lieu_ot."<br/>";
				}
				//update the current time sheet
				$this->updateLieuOT("3.1",$currRefid,$curr_lieu_ot,$prev);
				if($this->lieu == 0) {
	//	    		3.1.3 If LIEU is 0, BREAK;
					break;
				}
				$previous_date = $current_date;
				$db->nextRow();
			}
			if($this->lieu > 0 && $this->toilAdjustmentValue < 0){
				$prev_toilAdjustment = $this->toilAdjustmentValue;
				//the same procedure as above
				if(abs($this->toilAdjustmentValue) <= $this->lieu) {
					$this->lieu -= abs($this->toilAdjustmentValue);
					$this->toilAdjustmentValue = 0;
				}
				else{
					$this->toilAdjustmentValue = -(abs($this->toilAdjustmentValue) - $this->lieu);
					$this->lieu = 0;
				}
				$this->updateToilAdjustment("3.1b",$prev_toilAdjustment);
			}
		}
		if ($this->allowTOILinAdvance) {
			$this->lieu = $this->lieu - $this->toil;
		}
		if($this->debug) {
			echo "4. <br/>";
			echo "LIEU = ".$this->lieu."<br/>";
		}
	//	4. update lieu_ot of the current time sheet to LIEU
		$this->updateLieuOT("4.",$this->timeSheetRefid,$this->lieu,0);
	//	return -1;
		$this->db->commit();
		return $this->lieu;
	}
	
}

?>