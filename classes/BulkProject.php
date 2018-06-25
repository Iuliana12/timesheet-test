<?php
 class BulkProject{
	public $invoiceCode,$taskCode,$hours,$colour,$ot,$otCells;
	public $arrCellsByDay;//From Sat to Fri
	public $arrHoursByDay;//From Sat to Fri
	/**
	 * 
	 * @var BulkProjectDetail[]
	 */
	public $details;
	function BulkProject(){
		$this->invoiceCode = '';
		$this->taskCode = '';
		$this->hours = 0;
		$this->ot = 0;
		$this->colour = '';
		$this->arrCellsByDay = Array();
		$this->arrHoursByDay = Array();
		$this->details = Array();
	}
	/**
	 * Specifies if the project needs the hours per day array to be generated
	 * @return boolean
	 */
	function generateCellsByDay(){
		if(count($this->arrCellsByDay) == 0){
			return true;
		}
		foreach($this->arrCellsByDay as $h){
			if($h != 0){
				return false;
			}
		}
		return true;
	}
} 
?>