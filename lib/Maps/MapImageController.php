<?php

abstract class MapImageController
{
    protected $baseURL = null;
    
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

    protected $dataProjection; // projection that source data is provided in
    protected $mapProjection = GEOGRAPHIC_PROJECTION; // projection to pass to map image generator
    protected $mapProjector;

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
        return $this instanceof StaticMapImageController;
    }

    public function getCenter()
    {
        return $this->center;
    }

    public function getZoomLevel()
    {
        return $this->zoomLevel;
    }

    public function getAvailableLayers()
    {
        return array();
    }

    public function setDataProjection($proj)
    {
        if ($proj && $this->dataProjection != $proj) {
            $this->dataProjection = $proj;
            if ($this->dataProjection !== $this->mapProjection) {
                if (!isset($this->mapProjector)) {
                    $this->mapProjector = new MapProjector();
                    $this->mapProjector->setDstProj($this->mapProjection);
                }
                $this->mapProjector->setSrcProj($this->dataProjection);

            } else { // if source and dest are the same, we don't need projector
                $this->mapProjector = null;
            }
        }
    }
    
    public function setMapProjection($proj)
    {
        if ($proj && $this->mapProjection != $proj) {
            $this->mapProjection = $proj;
            if ($this->dataProjection !== $this->mapProjection) {
                if (!isset($this->mapProjector)) {
                    $this->mapProjector = new MapProjector();
                    $this->mapProjector->setSrcProj($this->dataProjection);
                }
                $this->mapProjector->setDstProj($this->mapProjection);

            } else { // if source and dest are the same, we don't need projector
                $this->mapProjector = null;
            }
        }
    }

    // overlays
    public function addAnnotation($coord, $style=null, $title=null)
    {
    }

    public function addPath($points, $style=null)
    {
    }

    public function addPolygon($rings, $style=null)
    {
    }

    // TODO: pass the Placemark object directly instead of breaking it up
    public function addPlacemark(Placemark $placemark)
    {
        $geometry = $placemark->getGeometry();
        $style = $placemark->getStyle();

        if ($geometry instanceof MapPolygon) {
            $this->addPolygon($geometry->getRings(), $style);
        } elseif ($geometry instanceof MapPolyline) {
            $this->addPath($geometry->getPoints(), $style);
        } else {
            $this->addAnnotation(
                $geometry->getCenterCoordinate(),
                $style,
                $placemark->getTitle());
        }
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

    public function setCenter($center)
    {
        // subclasses need to watch out for projected points
        if (is_array($center) && isset($center['lat'], $center['lon'])) {
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

    // below is a generic javascript template populating thing
    // that we're putting in maps for now as an experiment

    public function prepareJavascriptTemplate($filename) {
        // TODO better way to search for package-specific templates
        $path = __DIR__.'/javascript/'.$filename.'.js';
        $path = realpath_exists($path);
        if ($path) {
            return new JavascriptTemplate($path);
        }
    }
}

class JavascriptTemplate
{
    private $repeating = false;
    private $template;
    private $values = array();

    public function __construct($path) {
        $this->template = file_get_contents($path);
    }

    public function setRepeating($repeating) {
        $this->repeating = $repeating;
    }

    public function setValues(Array $values) {
        if (count($this->values) == 0) {
            $this->values[] = array();
        }
        $existingValues = end($this->values);
        foreach ($values as $placeholder => $value) {
            $existingValues[$placeholder] = $value;
        }
        $this->values[count($this->values) - 1] = $existingValues;
    }

    public function setValue($placeholder, $value) {
        $this->setValues(array($placeholder => $value));
    }

    public function appendValues(Array $values) {
        $this->values[] = array();
        $this->setValues($values);
    }

    public function getScript() {
        $script = "\n";

        if ($this->values) {
            foreach ($this->values as $values) {
                $template = $this->template;
                foreach ($values as $placeholder => $value) {
                    $template = preg_replace('/\[?'.$placeholder.'\]?/', $value, $template);
                }

                while (preg_match('/\[___\w+___\]/', $template, $matches)) {
                    $template = str_replace($matches[0], '', $template);
                }

                $script .= $template;
            }
        }
        return $script;
    }
}


