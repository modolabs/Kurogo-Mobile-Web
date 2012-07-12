<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

define('MINIMUM_POSSIBLE_ZOOM_LEVEL', 0); // show whole earth (projected) in the frame
define('MAXIMUM_POSSIBLE_ZOOM_LEVEL', 25); // google maps normally goes to about 20

// http://msdn.microsoft.com/en-us/library/aa940990.aspx

abstract class BaseMap extends DataModel
{
    protected static $COMPLIANT_DISPLAY_CLASS = 'GoogleBaseMap';
    protected static $BASIC_DISPLAY_CLASS = 'GoogleStaticBaseMap';

    protected $DEFAULT_ZOOM_LEVEL = 14;

    protected $center;
    protected $zoomLevel;
    protected $bbox;
    protected $projection;
    protected $mapProjector;

    public static function basemapClassForDevice(MapDevice $mapDevice, $params=array()) {
        $isStatic = false;

        if (isset($params['JS_MAP_CLASS']) && $mapDevice->pageSupportsDynamicMap()) {
            $mapClass = $params['JS_MAP_CLASS'];

        } elseif (isset($params['STATIC_MAP_CLASS'])) {
            $mapClass = $params['STATIC_MAP_CLASS'];
            $isStatic = true;

        } elseif ($mapDevice->pageSupportsDynamicMap()) {
            $mapClass = self::$COMPLIANT_DISPLAY_CLASS;

        } else {
            $mapClass = self::$BASIC_DISPLAY_CLASS;
            $isStatic = true;
        }

        return array($mapClass, $isStatic);
    }

    /*
     * don't use DataModel::factory because we will choose which class to
     * instantiate based on device capabilities
     */
    public static function factory($args, MapDevice $mapDevice)
    {
        $baseURL = null;
        list($mapClass, $isStatic) = self::basemapClassForDevice($mapDevice, $args);
        $baseURLParam = $isStatic ? 'STATIC_MAP_BASE_URL' : 'DYNAMIC_MAP_BASE_URL';

        if (isset($args[$baseURLParam])) {
            $args['BASE_URL'] = $args[$baseURLParam];
        }

        $baseMap = new $mapClass();
        $baseMap->init($args);

        return $baseMap;
    }

    public function init($args) {
        parent::init($args);

        if (isset($args['center'])) {
            $this->setCenter($args['center']);
        }
        $zoom = isset($args['zoom'] ? $args['zoom'] : $this->DEFAULT_ZOOM_LEVEL;
        $this->setZoomLevel($zoom);
        $this->bbox = array('xmin' => 10000000, 'ymin' => 10000000, 'xmax' => -10000000, 'ymax' => -10000000);
    }

    public function isStatic() {
        return $this instanceof StaticBaseMap;
    }

    public function setCenter($center) {
        if (is_array($center) && isset($center['lat'], $center['lon'])) {
            $this->center = $center;
        } else if (is_string($center) && ($latlon = filterLatLon($center)) {
            $this->center = $latlon;
        }
    }

    public function getCenter() {
        return $this->center;
    }

    public function setZoomLevel($zoomLevel) {
        if ($zoomLevel >= MINIMUM_POSSIBLE_ZOOM_LEVEL && $zoomLevel <= MAXIMUM_POSSIBLE_ZOOM_LEVEL) {
            $this->zoomLevel = $zoomLevel;
        }
    }

    public function getZoomLevel() {
        return $this->zoomLevel;
    }

    protected function addPolygon(Placemark $polygon) {
        $rings = $polygon->getGeometry()->getRings();
        $this->adjustBufferForPolyline($rings[0]);
    }

    protected function addPath(Placemark $polyline) {
        $geometry = $polyline->getGeometry();
        $this->adjustBufferForPolyline($geometry);
    }

    protected function addPoint(Placemark $point) {
        $center = $point->getGeometry()->getCenterCoordinate();
        $this->adjustBufferForPoint($center);
    }

    protected function adjustBufferForPolyline(MapPolyline $polyline) {
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

    protected function adjustBufferForPoint($point) {
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

    public function prepareForOutput() {
        $vRange = $this->bufferBox['ymax'] - $this->bufferBox['ymin'];
        if ($vRange > 0) {
            $vZoom = ceil(log(360 / $vRange, 2));
        }

        $hRange = $this->bufferBox['xmax'] - $this->bufferBox['xmin'];
        if ($hRange > 0) {
            $hZoom = ceil(log(180 / $hRange, 2));
        }

        if (isset($vZoom, $hZoom)) {
            $this->setZoomLevel(min($vZoom, $hZoom));

            $this->setCenter(array(
                'lat' => ($this->bufferBox['ymin'] + $this->bufferBox['ymax']) / 2,
                'lon' => ($this->bufferBox['xmin'] + $this->bufferBox['xmax']) / 2,
                ));
        }
    }
    
    public function setMapProjection($proj)
    {
        if ($proj === null) {
            $this->mapProjector = null;

        } else if ($proj && $this->projection != $proj) {
            $this->projection = $proj;
            if (!isset($this->mapProjector)) {
                $this->mapProjector = new MapProjector();
            }
            $this->mapProjector->setDstProj($this->projection);
        }
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
                    if (!$value) {
                        $value = ''; // nulls may show up as strings
                    }
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
