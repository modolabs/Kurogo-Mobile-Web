<?php

abstract class MapImageController
{
    protected static $DEFAULT_JS_MAP_CLASS = 'GoogleJSMap';
    protected static $DEFAULT_STATIC_MAP_CLASS = 'GoogleStaticMap';
    protected $baseURL = null;
    
    protected $center = null; // array('lat' => 0.0, 'lon' => 0.0), or address
    protected $bufferBox;

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

    protected $mapDevice;

    public static function factory($params, MapDevice $mapDevice)
    {
        $baseURL = null;
        $baseURLParam = 'STATIC_MAP_BASE_URL';

        if (isset($params['JS_MAP_CLASS']) && $mapDevice->pageSupportsDynamicMap()) {
            $imageClass = $params['JS_MAP_CLASS'];
            $baseURLParam = 'DYNAMIC_MAP_BASE_URL';

        } elseif (isset($params['STATIC_MAP_CLASS'])) {
            $imageClass = $params['STATIC_MAP_CLASS'];

        } elseif ($mapDevice->pageSupportsDynamicMap()) {
            $imageClass = self::$DEFAULT_JS_MAP_CLASS;
            $baseURLParam = 'DYNAMIC_MAP_BASE_URL';

        } else {
            $imageClass = self::$DEFAULT_STATIC_MAP_CLASS;
        }

        if (isset($params[$baseURLParam])) {
            $baseURL = $params[$baseURLParam];
        }

        if ($baseURL !== null) {
            $controller = new $imageClass($baseURL);
        } else {
            $controller = new $imageClass();
        }

        $controller->init();

        return $controller;
    }

    public function init()
    {
        $this->bufferBox = array('xmin' => 180, 'ymin' => 90, 'xmax' => -180, 'ymax' => -90);
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

    protected function addPolygon(Placemark $polygon)
    {
        $rings = $polygon->getGeometry()->getRings();
        $this->adjustBufferForPolyline($rings[0]);
    }

    protected function addPath(Placemark $polyline)
    {
        $geometry = $polyline->getGeometry();
        $this->adjustBufferForPolyline($geometry);
    }

    protected function addPoint(Placemark $point)
    {
        $center = $point->getGeometry()->getCenterCoordinate();
        $this->adjustBufferForPoint($center);
    }

    protected function adjustBufferForPolyline(MapPolyline $polyline)
    {
        // just pick a few sample points to calculate buffer
        $points = $polyline->getPoints();
        $count = count($points);
        if ($count < 4) {
            $sample = $points;
        } else {
            $sample = array();
            $interval = $count / 4;
            for ($i = 0; $i < $count; $i += $interval) {
                $index = intval($i);
                $sample[] = $points[$i];
            }
        }
        foreach ($sample as $point) {
            $this->adjustBufferForPoint($point);
        }
    }

    protected function adjustBufferForPoint($point)
    {
        if ($point['lat'] > $this->bufferBox['ymax']) {
            $this->bufferBox['ymax'] = $point['lat'];
        }
        if ($point['lat'] < $this->bufferBox['ymin']) {
            $this->bufferBox['ymin'] = $point['lat'];
        }
        if ($point['lon'] > $this->bufferBox['xmax']) {
            $this->bufferBox['xmax'] = $point['lon'];
        }
        if ($point['lon'] < $this->bufferBox['xmin']) {
            $this->bufferBox['xmin'] = $point['lon'];
        }

        $this->setCenter(array(
            'lat' => ($this->bufferBox['ymin'] + $this->bufferBox['ymax']) / 2,
            'lon' => ($this->bufferBox['xmin'] + $this->bufferBox['xmax']) / 2,
            ));
    }

    // overlays and annotations
    public function addPlacemark(Placemark $placemark)
    {
        // only GoogleJSMap, ArcGISJSMap, and GoogleStaticMap support overlays
        $geometry = $placemark->getGeometry();
        if ($geometry instanceof MapPolygon) {
            $this->addPolygon($placemark);
        } elseif ($geometry instanceof MapPolyline) {
            $this->addPath($placemark);
        } else {
            $this->addPoint($placemark);
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

    public function getImageWidth()
    {
        return $this->imageWidth;
    }

    public function setImageWidth($width) {
        $this->imageWidth = $width;
    }

    public function getImageHeight()
    {
        return $this->imageHeight;
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

    public function prepareJavascriptTemplate($filename, $repeating=false) {
        // TODO better way to search for package-specific templates
        $path = dirname(__FILE__).'/javascript/'.$filename.'.js';
        $path = realpath_exists($path);
        if ($path) {
            return new JavascriptTemplate($path, $repeating);
        }
    }
}

class JavascriptTemplate
{
    private $repeating;
    private $template;
    private $values = array();

    public function __construct($path, $repeating=false) {
        $this->template = file_get_contents($path);
        $this->repeating = $repeating;
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

        if (!$this->repeating && !$this->values) {
            $this->setValues(array());
        }

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


