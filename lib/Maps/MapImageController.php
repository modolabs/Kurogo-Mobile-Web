<?php

abstract class MapImageController
{
    protected $baseURL = null;

    const STYLE_LINE_WEIGHT = 'weight';
    const STYLE_LINE_ALPHA = 'alpha';
    const STYLE_LINE_COLOR = 'color';
    const STYLE_LINE_CONSISTENCY = 'consistency'; // dotted, dashed, etc
    
    const STYLE_POINT_COLOR = 'color';
    const STYLE_POINT_SIZE = 'size';
    const STYLE_POINT_ICON = 'icon';
    
    const STYLE_FILL_COLOR = 'fillColor';
    const STYLE_FILL_ALPHA = 'fillAlpha';
    
    protected $center = null; // array('lat' => 0.0, 'lon' => 0.0), or address

    protected $zoomLevel = 14;
    protected $maxZoomLevel = 20;
    protected $minZoomLevel = 0;

    protected $imageWidth = 300;
    protected $imageHeight = 300;

    // layers are sets of overlays that span the full range of the map
    // as opposed to a selection
    protected $enabledLayers = array(); // array of map layers to show
    protected $layerStyles = array(); // id => styleName

    // capabilities
    protected $canAddAnnotations = false;
    protected $canAddPaths = false;
    protected $canAddPolygons = false;
    protected $canAddLayers = false;
    protected $supportsProjections = false;
    
    protected $dataProjection; // projection that source data is provided in
    protected $mapProjection = GEOGRAPHIC_PROJECTION; // projection to pass to map image generator

    public static function factory($imageClass, $baseURL)
    {
        if (isset($baseURL)) {
            $controller = new $imageClass($baseURL);
        } else {
            $controller = new $imageClass();
        }
        return $controller;
    }

    // query functions
    public function isStatic() {
        return false;
    }

    public function getCenter()
    {
        return $this->center;
    }

    public function getAvailableLayers()
    {
        return array();
    }

    public function canAddAnnotations()
    {
        return $this->canAddAnnotations;
    }

    public function canAddPaths()
    {
        return $this->canAddPaths;
    }
    
    public function canAddPolygons()
    {
        return $this->canAddPolygons;
    }

    public function canAddLayers()
    {
        return $this->canAddlayers;
    }
    
    public function supportsProjections()
    {
        return $this->supportsProjections;
    }
    
    public function setDataProjection($proj)
    {
        $this->dataProjection = $proj;
    }
    
    public function getMapProjection() {
        return $this->mapProjection;
    }

    // overlays
    public function addAnnotation($coord, $style=null, $title=null)
    {
    }

    public function addPath($points, $style=null)
    {
    }

    public function enableLayer($layer)
    {
        if (!$this->isEnabledLayer($layer) && $this->isAvalableLayer($layer)) {
            $this->enabledLayers[] = $layer;
        }
    }

    public function disableLayer($layer)
    {
        $position = array_search($layer, $this->enabledLayers);
        if ($position !== false) {
            $this->enabledLayers = array_splice(
                $this->enabledLayers,
                $position,
                1);
        }
    }

    public function enableAllLayers()
    {
        $this->enabledLayers = $this->getAvailableLayers();
    }

    public function disableAllLayers()
    {
        $this->enabledLayers = array();
    }

    protected function isEnabledLayer($layer) {
        return in_array($layer, $this->enabledLayers);
    }

    protected function isAvailableLayer($layer) {
        return in_array($layer, $this->getAvailableLayers());
    }

    public function setCenter($center) {
        if (is_array($center)
            && isset($center['lat'])
            && isset($center['lon']))
        {
            $this->center = $center;
        }
    }

    public function setImageWidth($width) {
        $this->imageWidth = $width;
    }

    public function setImageHeight($height) {
        $this->imageHeight = $height;
    }

    public function setZoomLevel($zoomLevel)
    {
        $this->zoomLevel = $zoomLevel;
    }
}


