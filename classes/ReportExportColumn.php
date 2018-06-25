<?php
class ReportExportColumn{

	public $label;
	/**
	Array(ReportExportStaff)
	*/
	public $arrStaff;
	public $staff;

	function __construct ($label = 0, $staff = 0, $arrStaff = ''){
		$this -> label = $label;
		$this -> staff = $staff;
		$this -> arrStaff  = Array();
	}

	/**
		param $staff ReportExportStaff
	*/

	function addStaff($staff){
		$found = false;
		foreach ($this->arrStaff as $currStaff) {
			if ($currStaff->refid == $staff->refid && $currStaff->timeType == $staff->timeType) {
					$currStaff->hours += $staff->hours;
					$found = true;
					break;
			}
		}
		if(!$found){
			$this->arrStaff[] = $staff;
		}
}

	function getStaff($refid){
		$arrGetStaff = Array();
		foreach ($this->arrStaff as $currStaff) {
			if ($currStaff->refid == $refid) {
				$arrGetStaff[] = $currStaff;
			}
		}
		return $arrGetStaff;
	}

}
