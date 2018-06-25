<?php
class TimeSheetList{
	public $list;
	public $length;
	public $submitted;//number of submitted time sheets
	public $notEnrolled;//number of time sheets from unenrolled people
	public $top;
	
	public function TimeSheetList()
	{
		$this->list = array();
		$this->length = 0;
		$this->submitted = 0;
		$this->top = null;
	}
	public function add(&$timesheet)
	{
		array_push($this->list,$timesheet);
		$this->length++;
	    $this->top=$timesheet;
		if($timesheet->submitted) {
            $this->submitted++;
        }
		if(!$timesheet->enrolled) {
            $this->notEnrolled++;
        }
	}
	public function get($i)
	{
		return $this->list[$i];
	}
}
?>