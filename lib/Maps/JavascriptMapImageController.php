<?php

abstract class JavascriptMapImageController extends MapImageController
{
    protected $mapElement; // required, this is the HTML element where the map will appear

    abstract public function getIncludeScripts();
    abstract public function getHeaderScript();
    abstract public function getFooterScript();

    public function setMapElement($mapElement) {
        $this->mapElement = $mapElement;
    }
}


