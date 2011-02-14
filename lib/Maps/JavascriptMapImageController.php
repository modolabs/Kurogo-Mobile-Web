<?php

abstract class JavascriptMapImageController extends MapImageController
{
    protected $mapElement; // required, this is the HTML element where the map will appear

    // TODO make this support multiple include scripts
	abstract public function getIncludeScript();
	abstract public function getHeaderScript();
	abstract public function getFooterScript();
	
	public function setMapElement($mapElement) {
        $this->mapElement = $mapElement;
    }
}


