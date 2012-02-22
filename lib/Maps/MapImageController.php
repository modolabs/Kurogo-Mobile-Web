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

    protected $initOptions;

    public static function basemapClassForDevice(MapDevice $mapDevice, $params=array())
    {
        $isStatic = false;

        if (isset($params['JS_MAP_CLASS']) && $mapDevice->pageSupportsDynamicMap()) {
            $mapClass = $params['JS_MAP_CLASS'];

        } elseif (isset($params['STATIC_MAP_CLASS'])) {
            $mapClass = $params['STATIC_MAP_CLASS'];
            $isStatic = true;

        } elseif ($mapDevice->pageSupportsDynamicMap()) {
            $mapClass = self::$DEFAULT_JS_MAP_CLASS;

        } else {
            $mapClass = self::$DEFAULT_STATIC_MAP_CLASS;
            $isStatic = true;
        }

        return array($mapClass, $isStatic);
    }

    public static function factory($params, MapDevice $mapDevice)
    {
        $baseURL = null;
        list($mapClass, $isStatic) = self::basemapClassForDevice($mapDevice, $params);
        $baseURLParam = $isStatic ? 'STATIC_MAP_BASE_URL' : 'DYNAMIC_MAP_BASE_URL';

        if (isset($params[$baseURLParam])) {
            $params['BASE_URL'] = $params[$baseURLParam];
        }

        $baseMap = new $mapClass();
        $baseMap->init($params);

        return $baseMap;
    }

    public function init($params)
    {
        if (isset($params['center'])) {
            $this->setCenter(filterLatLon($params['center']));
        }

        if (isset($params['DEFAULT_ZOOM_LEVEL'])) {
            $this->setZoomLevel($params['DEFAULT_ZOOM_LEVEL']);
        }

        $this->maxZoomLevel = isset($params['MAXIMUM_ZOOM_LEVEL']) ? $params['MAXIMUM_ZOOM_LEVEL'] : $this->zoomLevel;

        $this->bufferBox = array('xmin' => 180, 'ymin' => 90, 'xmax' => -180, 'ymax' => -90);

        $this->initOptions = $params;
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

    public function getMaximumZoomLevel() {
        return $this->maxZoomLevel;
    }

    public function getMinimumLatSpan() {
        return 180 / pow(2, $this->maxZoomLevel);
    }

    public function getMinimumLonSpan() {
        return 360 / pow(2, $this->maxZoomLevel);
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
        if ($count < 20) {
            $sample = $points;
        } else {
            $sample = array();
            $interval = $count / 20;
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
    }

    public function prepareForOutput()
    {
        $vRange = $this->bufferBox['ymax'] - $this->bufferBox['ymin'];
        $hRange = $this->bufferBox['xmax'] - $this->bufferBox['xmin'];
        if ($vRange >= 0 && $hRange >= 0) {
            $this->setCenter(array(
                'lat' => ($this->bufferBox['ymin'] + $this->bufferBox['ymax']) / 2,
                'lon' => ($this->bufferBox['xmin'] + $this->bufferBox['xmax']) / 2,
                ));
            if ($vRange > 0 && $hRange > 0) {
                $vZoom = ceil(log(180 / $vRange, 2));
                $hZoom = ceil(log(360 / $hRange, 2));
                $zoom = min($vZoom, $hZoom);
                if ($zoom < $this->maxZoomLevel) {
                    $this->setZoomLevel($zoom);
                }
            }
        }
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
                    if (!strlen($value)) {
                        $value = ''; // nulls may show up as strings
                    }
                    $template = preg_replace('/'.$placeholder.'/', $value, $template);
                }

                $script .= $template;
            }
        }
        return $script;
    }
}


