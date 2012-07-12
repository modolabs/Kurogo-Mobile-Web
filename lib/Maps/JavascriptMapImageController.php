<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
                $url = FULL_URL_PREFIX.ltrim($link['url'], '/');
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


