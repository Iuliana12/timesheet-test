<?php
/**
 *  
 * Represents language in the HTTP_ACCEPT_LANGUAGE header
 * @author Lucian
 *
 */
class Language{

	public $string;
	public $code;//string val
	public $general;//string val
	public $specific;//string val
	public $q;//flaot val

	public function __construct($string=null){
		if($string==null)
			return;
		$this->string = $string;
		$items = explode(";",$string);
		$this->code = $items[0];
		if(isset($items[1]))
			$this->q = floatval(substr($items[1],2));
		else
			$this->q = 1;

		$parts = explode("-",$this->code);
		$this->general = $parts[0];
		if(count($parts) > 1)
		$this->specific = $parts[1];
	}

	public function __toString(){
		return $this->code.";q=".$this->q;
	}

}
?>
