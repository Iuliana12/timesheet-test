<?php

class ReportExportStaff{

	public $refid;
	public $name;
	public $hours;
	public $timeType;

	function __construct ($refid = 0, $name= '', $hours = 0, $timeType= ''){
		$this -> refid = $refid;
		$this -> name = $name;
		$this -> hours = $hours;
		$this -> timeType = $timeType;
	}

}
