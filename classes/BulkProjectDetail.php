<?php
 class BulkProjectDetail{
	public $date,$rateType,$hours,$cells;
	public $identities;
	
	function BulkProjectDetail(){
		$this->date = '';
		$this->rateType = 0;
		$this->hours = 0;
		$this->cells = 0;
		$this->identities = null;
	}
} 
?>