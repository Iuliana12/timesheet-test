<?php
class TimeSheet
{
	public $refid;
	public $submitted;
	public $employeerefid;
	public $lastName;//owner details
	public $firstName;
	public $enrolled;//about the owner
	public $minHours;
	public $hours;
	public $lieu;
	public $charged;
	public $sundaycharged;
	public $hols;
	public $toil;
	public $sick;
	public $unpaid;
	public $submissiontime;
	
	public function TimeSheet($refid = -1,$submitted= false,$emprefid= -1,$lastName="Doe",$firstName="John",$minHours = 0,$hours = 0,$lieu = 0,$charged = 0,$sundaycharged = 0,$hols = 0,$toil = 0,$sick = 0,$unpaid = 0)
	{
		$this->employeerefid = $emprefid;
		$this->refid = $refid;
		$this->submitted = $submitted;
		$this->lastName = $lastName;
		$this->firstName = $firstName;
		$this->minHours = $minHours;
		$this->hours = $hours;
		$this->lieu = $lieu;
		$this->charged = $charged;
		$this->sundaycharged = $sundaycharged;
		$this->hols = $hols;
		$this->toil = $toil;
		$this->sick = $sick;
		$this->unpaid = $unpaid;
	}
	public function fromArray($rowarray)
	{
	    $this->employeerefid = $rowarray["refid"];
		$this->refid = $rowarray["timesheetrefid"];
		$this->submitted = formatBoolean($rowarray["submitted"],true,false);
		$this->lastName = htmlspecialchars($rowarray["lname"]);
		$this->firstName = htmlspecialchars($rowarray["fname"]);
		$this->minHours = $rowarray["minhours"];
		$this->enrolled = formatBoolean($rowarray["enrolled"],true,false);
		if(trim($rowarray["submissiontime"]) === '')
			$this->submissiontime = '-';
		else
			$this->submissiontime = date("jS M Y H:i:s",strtotime($rowarray["submissiontime"]));
		$this->hours = $rowarray["hours"];
		$this->lieu = $rowarray["lieu"];
		$this->charged = $rowarray["charged"];
		$this->sundaycharged = $rowarray["sundaycharged"];
		$this->hols = $rowarray["hols"];
		$this->toil = $rowarray["toil"];
		$this->sick = $rowarray["sick"];
		$this->unpaid = $rowarray["unpaid"];
	}
}
?>