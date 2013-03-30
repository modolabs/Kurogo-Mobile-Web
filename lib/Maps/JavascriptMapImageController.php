<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
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

    /// ovelays
    protected $markers = array();
    protected $paths = array();
    protected $polygons = array();

    abstract public function getIncludeScripts();
    abstract public function getInternalScripts();

    // these should behave like static functions since they will be
    // called on objects constructed with no args
    abstract public function jsObjectForMarker($marker);
    abstract public function jsObjectForPath($path);
    abstract public function jsObjectForPolygon($polygon);

    public function getIncludeCSS() {
        return array();
    }

    public function setMapElement($mapElement) {
        $this->mapElement = $mapElement;
    }

    public function setWebModule(WebModule $module) {
        $this->webModule = $module;
    }

    public function setImageWidth($width) {
        if (strpos($width, '%') === FALSE) {
            $width = $width.'px';
        }
        $this->imageWidth = $width;
    }
    
    public function setImageHeight($height) {
        if (strpos($height, '%') === FALSE) {
            $height = $height.'px';
        }
        $this->imageHeight = $height;
    }

    ///// overlays
    // override getFooterScript to do things with the data stored in the
    // geometry properties
    protected function addPoint(Placemark $placemark) {
        parent::addPoint($placemark);
        $this->markers[] = $placemark;
    }

    protected function addPath(Placemark $placemark) {
        parent::addPath($placemark);
        $this->paths[] = $placemark;
    }
    
    protected function addPolygon(Placemark $placemark) {
        parent::addPolygon($placemark);
        $this->polygons[] = $placemark;
    }

    protected function getPlacemarkJS() {
        $js = '';
        foreach ($this->markers as $marker) {
            $js .= "\n" . $this->getSinglePlacemarkJS(
                $marker,
                $this->jsObjectForMarker($marker));
        }
        foreach ($this->paths as $path) {
            $js .= "\n" . $this->getSinglePlacemarkJS(
                $path,
                $this->jsObjectForPath($path));
        }
        foreach ($this->polygons as $polygon) {
            $js .= "\n" . $this->getSinglePlacemarkJS(
                $polygon,
                $this->jsObjectForPolygon($polygon));
        }
        return $js;
    }

    protected function getSinglePlacemarkJS($placemark, $objectJS) {
        $coord = $placemark->getGeometry()->getCenterCoordinate();
        if (isset($this->mapProjector)) {
            $coord = $this->mapProjector->projectPoint($coord);
        }

        $title = json_encode($placemark->getTitle());
        $subtitle = json_encode($placemark->getSubtitle());
        $url = $this->urlForPlacemark($placemark);

        return <<<JS
mapLoader.addPlacemark(
    "{$url}",
    {$objectJS},
    {
        title: {$title},
        subtitle: {$subtitle},
        url: "{$url}",
        lat: {$coord['lat']},
        lon: {$coord['lon']}
    }
);
JS;
    }

    protected function urlForPlacemark(Placemark $placemark) {
        $url = '';
        if ($this->webModule && method_exists($this->webModule, 'linkForItem')) {
            $link = $this->webModule->linkForItem($placemark);
            if ($link && isset($link['url'])) {
                $url = FULL_URL_PREFIX.ltrim($link['url'], '/');
            }
        }
        return $url;
    }

    public function getFooterScript() {
        return '';
    }
}


