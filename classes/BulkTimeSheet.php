<?php
 class BulkTimeSheet{
 	/**
 	 * 
 	 * @var DateTime
 	 */
	private $enddate,$startTime,$stopTime,$ot;
	/**
	 * 
	 * @var BulkProject[]
	 */
	private $projects;
	public $resolution,$showWeekend;
	/**
	 * Creates a new TS object and generates all the necessary data
	 * @param $enddate
	 * @param $resolution
	 * @param $projects
	 * @param $showWeekend
	 * @return BulkTimeSheet
	 */
	function BulkTimeSheet($enddate,$resolution,$projects,$showWeekend = false){
		$firstHourInTheMorning = 8;
		
		$this->enddate = $enddate;
		$this->resolution = $resolution;
		$this->showWeekend = $showWeekend;
		$this->projects = $projects;
		$this->startTime = new DateTime();
		$this->startTime->setTime($firstHourInTheMorning,0,0);
		$this->stopTime = new DateTime();
		$this->stopTime->setTime($firstHourInTheMorning,0,0);
		//building the hours per each day array

		foreach($this->projects as $project){
			if($project->generateCellsByDay()){
				$this->generateCellsByDay($project);
			}
			$project->otCells = $project->ot * 60 / $this->resolution;
		}
		//getting the maximum number of cells for one day
		$max = 0;
		for($i= 0;$i < 7;++$i){
			$totalPerDay = 0;
			foreach($this->projects as $project){
                if(isset($project->arrCellsByDay[$i])){
                    $totalPerDay += $project->arrCellsByDay[$i];
                }
			}
			if($max < $totalPerDay){
				$max = $totalPerDay;
			}
		}
		$maxHours = ceil($max * $this->resolution / 60);
		if($maxHours + $firstHourInTheMorning > 24){
			$difference = abs(24 - $maxHours - $firstHourInTheMorning);
			$this->startTime->setTime($firstHourInTheMorning - $difference,0,0);
			$this->stopTime->setTime(0,0,0);
		}else{
			$this->stopTime->modify('+'.$maxHours.' hour');
		}
		$this->generateProjectDetails();
	}
	/**
	 * Returns the date in ISO format yyyy-mm-dd
	 * @return string
	 */
	function getDate(){
		return $this->enddate->format('Y-m-d');
	}
	/**
	 * Returns the start time hh:mm
	 * @return sring
	 */
	function getStartTime(){
		return $this->startTime->format('H:i');
	}
	/**
	 * Returns the stop time hh:mm
	 * @return sring
	 */
	function getStopTime(){
		return $this->stopTime->format('H:i');
	}
	/**
	 * Returns the stop time hh:mm
	 * @return BulkProject[]
	 */
	function getProjects(){
		return $this->projects;
	}
	/**
	 * Generates the number of cells per day array
	 * @param $project BulkProject
	 * @return void
	 */
	function generateCellsByDay(&$project){
		$divider = 5;
		if($this->showWeekend){
			$divider = 6;//no cells will be distributed for Sunday!
		}
		//the result is an int, the error checks must make sure of this
		$totalCells = $project->hours * 60 / $this->resolution; 
		$reminder = $totalCells % $divider;
		//the reminder is added one by on to each day until it becomes 0
		$cellsPerDay = ($totalCells - $reminder)/ $divider;
		if($this->showWeekend){//covering Saturday
			$project->arrCellsByDay[0] = $cellsPerDay;
		}
		//skiping Sunday
		$project->arrCellsByDay[1] = 0;
		for($i=2; $i <7; ++$i){
			$project->arrCellsByDay[$i] = $cellsPerDay;
			//allocating one of the extra cells for each day until it's brought to 0
			if($reminder > 0){
				$project->arrCellsByDay[$i]++;
				$reminder--;
			}
		}
	}
	/**
	 * Generates the BulkProjectDetail array and the identities for each detail
	 * @param $project BulkProject
	 * @return void
	 */
	function generateProjectDetails(){
		//we need a BulkProjectdetail instance for each different day for each project
		$date = new DateTime($this->enddate->format('Y-m-d'));
		for($i=6; $i >= 0; --$i){
			$date->setTime($this->startTime->format('H'),$this->startTime->format('i'));
//			echo $date->format('l-').'<br/>';
			foreach($this->projects as $project){
//				echo $project->invoiceCode.' ';
				if(isset($project->arrCellsByDay[$i]) && $project->arrCellsByDay[$i] > 0){
					$d = new BulkProjectDetail();
					$d->cells = $project->arrCellsByDay[$i];
					//if we have over time cells waiting to be added
					if($project->otCells > 0){
						//if more over time than the cells for this day
						if($project->otCells >= $project->arrCellsByDay[$i]){
							$d->rateType = 1;
							$project->otCells -= $project->arrCellsByDay[$i];
							//let the rest of the detail processing happen.
						}else{// otcells <= cells
							//adding an extra detail object 
							$extraD = new BulkProjectDetail();
							$extraD->cells = $project->otCells;
							$extraD->rateType = 1;
							$extraD->hours = $extraD->cells * $this->resolution / 60;
							$extraD->date = $date->format('Y-m-d');
							//indetities
							$cells = $extraD->cells;
							while($cells > 0){
								$extraD->identities[] = $date->format('l-H-i');
								$date->modify('+'.$this->resolution.' minute');
								$cells--;
							}
//							echo $extraD->hours.'('.$extraD->rateType.')';
							$project->details[] = $extraD;
							//changing the cells of the already created detail object
							$d->cells -= $project->otCells;
							$project->otCells = 0;
						}
					}
					$d->hours = $d->cells * $this->resolution / 60;
					$d->date = $date->format('Y-m-d');
					//indetities
					$cells = $d->cells;
					while($cells > 0){
						$d->identities[] = $date->format('l-H-i');
						$date->modify('+'.$this->resolution.' minute');
						$cells--;
					}
					$project->details[] = $d;
//					echo $d->hours.'('.$d->rateType.')';
				}
//				echo '<br/>';
			}
			$date->modify('-1 day');
		}
	}
} 

?>