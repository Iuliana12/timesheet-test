<?php
//this is a generic collection of objects
class Collection
{
	public $name;//a name for this collection, this can be anything, including a refid to identify the collection 
	private $items;//this is an array of objects
	public $length;//this is the length
	
	
	public function __construct($name = null,$items = null)
	{
		$this->items = Array();
		$this->length = 0;
		$this->name = $name;
		
		if(isset($items) && is_array($items))
			foreach($items as $key => $value)
			{
				$this->items[$key] = $value;
				++$this->length;
			}
	}
	//this adds a new value to the Collection, if a key is specified then it will be used
	public function addItem($value,$key = null)
	{
		if($key !== null)
		{
			if(array_key_exists($key,$this->items) === FALSE)
			{
				$this->items[$key] = $value;
				++$this->length;
			}
			else
				throw new Exception("key is already in the array");
		}
		else
		{
			array_push($this->items,$value);
			++$this->length;
		}
	}
	//this will remove an item, the key can either be a numeric value or a string
	public function removeItem($key)
	{
		if(is_numeric($key))
		{
			if($key < $this->length)
			{
				$tmp = $this->items[$key];
				$this->items = array_slice($this->items,$key,1,TRUE);
				--$this->length;
				return $tmp;
			}
			else
				throw new Exception("The '".$key."' position is not defined in the array");
		}
		elseif(is_string($key))
		{
			if(array_key_exists($key,$this->items) === TRUE)
			{
				$tmp = $this->items[$key];
				unset($this->items[$key]);
				--$this->length;
				return $tmp;
			}
			else
				throw new Exception(" '".$key."' key not found in the array");
		}
		else
			throw new Exception("Key is invalid");
	}
	//return the element in the collection at the specified position or for the given key
	public function get($key)
	{
		if(is_numeric($key))
		{
			if($key < $this->length)
				return $this->items[$key];
			else
				throw new Exception("The '".$key."' position is not defined in the array");
		}
		elseif(is_string($key))
		{
			if(array_key_exists($key,$this->items) === TRUE)
				return $this->items[$key];
			else
				throw new Exception(" '".$key."' key not found in the array");
		}
		else
			throw new Exception("Key is invalid");
	}
	//sets the value for an exisiting element at a given position or key
	public function set($key,$value)
	{
		if(is_numeric($key))
		{
			if($key < $this->length)
				$this->items[$key] = $value;
			else
				throw new Exception("The '".$key."' position is not defined in the array");
		}
		elseif(is_string($key))
		{
			if(array_key_exists($key,$this->items) === TRUE)
				$this->items[$key] = $value;
			else
				throw new Exception(" '".$key."' key not found in the array");
		}
		else
			throw new Exception("Key is invalid");
	}
	public function __toString()
	{
		$ret = $this->name.": [";
		foreach($this->items as $key => $value)
		{
			$ret .= " [".$key."] = '";
			$ret .= $value->__toString();
			$ret .= "' ";
		}
		$ret .= "]";
		return $ret;
	}
}
?>