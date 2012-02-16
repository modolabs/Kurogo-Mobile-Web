<?php

abstract class JavascriptMapImageController extends MapImageController
{
    protected $mapElement; // required, this is the HTML element where the map will appear
    protected $webModule; // optional, used to generate URLs via linkForItem

    abstract public function getIncludeScripts();
    abstract public function getInternalScripts();

    public function setMapElement($mapElement) {
        $this->mapElement = $mapElement;
    }

    public function setWebModule(WebModule $module) {
        $this->webModule = $module;
    }

    protected function urlForPlacemark(Placemark $placemark)
    {
        $url = '';
        if ($this->webModule && method_exists($this->webModule, 'linkForItem')) {
            $link = $this->webModule->linkForItem($placemark);
            if ($link && isset($link['url'])) {
                $url = FULL_URL_PREFIX.$link['url'];
            }
        }
        return $url;
    }

    /*
    public function getHeaderScript() {
        return '';
    }
    */

    public function getFooterScript() {
        return '';
    }
}


