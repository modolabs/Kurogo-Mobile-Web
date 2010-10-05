<?php

abstract class DataParser
{
	abstract public function parseData($data);

	public function parseFile($filename) 
	{
		return $this->parseData(file_get_contents($filename));
	}
	
}

?>