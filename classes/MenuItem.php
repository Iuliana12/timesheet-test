<?php
class MenuItem
{
	public $text;
	public $href;
	public $subItems;//this is an object of type Collection with items of type SubMenuItem
	
	public function __construct($params)
	{
		$this->text = $params["text"];
		$this->href = $params["href"];
		if(isset($params["subItems"])){
			$this->subItems = &$params["subItems"];
		}
		else{
			$this->subItems = new Collection($this->text." subitems");
		}
	}
	public function __toString()
	{
		if($this->subItems->length > 0)
			return $this->text.": ".$this->subItems->__toString();
		else
			return $this->text;
	}
}
?>