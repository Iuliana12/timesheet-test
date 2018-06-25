<?php
class ReportExportColumn{
	
	public $label;
	/** 
	Array(ReportExportStaff) 
	*/
	public $arrStaff;

	function __construct ($label = 0){
		$this -> label = $label;
		$this -> arrStaff  = Array();
	}

	/**
		param $staff ReportExportStaff
	*/
	function addStaff($staff){
		if($this->arrStaff[$staff->refid] && $this->arrStaff[$staff->refid]->timeType == $staff->timeType){
			$this->arrStaff[$staff->refid] += 
		}
		foreach ($staff as $key => $value) {
			$this->arrStaff[] = $staff;
			if (isset($staff)) {
				$time = count($time);
			}
		}

	}

}