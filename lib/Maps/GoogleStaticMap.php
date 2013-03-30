<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

require_once 'Polyline.php';

// http://code.google.com/apis/maps/documentation/staticmaps
class GoogleStaticMap extends StaticMapImageController {

    protected $maxZoomLevel = 21;
    protected $minZoomLevel = 0;

    protected $googleClientID;
    protected $googlePrivateKey;

    // image format
    protected $supportedImageFormats = array( // default: png8
        'png', 'jpg', 'gif', 'png32', 'png8', 'jpg-baseline');

    // custom overlays
    private $markers = array();
    private $paths = array();

    // http://code.google.com/apis/maps/documentation/javascript/reference.html
    // #MapTypeStyleFeatureType
    protected static $availableLayers = array(
        //'all', // name for root node
        'administrative', 
        'landscape', 
        'poi',
        'road',
        'transit',
        'water'
        );

    // currently not using this, but should incorporate sometime
    private static $detailedLayers = array(
        'administrative.country',
        'administrative.land_parcel',
        'administrative.locality',
        'administrative.neighborhood',
        'administrative.province',
        'landscape.man_made',
        'landscape.natural',
        'poi.attraction',
        'poi.business',
        'poi.government',
        'poi.medical',
        'poi.park',
        'poi.place_of_worship',
        'poi.school',
        'poi.sports_complex',
        'road.arterial',
        'road.highway',
        'road.local',
        'transit.line',
        //'transit.station', // only allow leaf nodes in this array
        'transit.station.airport',
        'transit.station.bus',
        'transit.station.rail',
        );
    
    // mapType -- may want to implement as baseLayer
    protected $mapType = null; // default: roadmap
    protected static $allowedMapTypes = array('roadmap', 'satellite', 'hybrid', 'terrain');
    public function setMapType($mapType) {
        if (in_array($mapType, $this->allowedMapTypes)) {
            $this->mapType = $mapType;
        }
    }

    // set sensor to true if user location is used in the app
    protected $sensor = false;
    public function setSensor($sensor) {
        if ($sensor === true || $sensor === false) {
            $this->sensor = $sensor;
        }
    }

    ////////////// overlays ///////////////

    protected function addPolygon(Placemark $placemark)
    {
        parent::addPolygon($placemark);

        $pointArr = array();
        $rings = $placemark->getGeometry()->getRings();
        foreach ($rings[0]->getPoints() as $point) {
            $pointArr[] = array($point['lat'], $point['lon']);
        }
        $polyline = Polyline::encodeFromArray($pointArr);

        $styleArgs = array();
        $style = $placemark->getStyle();
        if ($style !== null) {
            $color = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::COLOR);
            if ($color) $styleArgs[] = 'color:0x'.htmlColorForColorString($color);
            $weight = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::WEIGHT);
            if ($weight) $styleArgs[] = 'weight:'.$weight;
        }
        
        if (!$styleArgs) {
            // color can be 0xRRGGBB or
            // {black, brown, green, purple, yellow, blue, gray, orange, red, white}
            $styleArgs = array('color:red', 'weight:4');
        }
        $this->paths[] = implode('|', $styleArgs).'|enc:'.$polyline;
    }

    protected function addPoint(Placemark $placemark)
    {
        parent::addPoint($placemark);

        $styleArgs = array();
        $style = $placemark->getStyle();
        $center = $placemark->getGeometry()->getCenterCoordinate();
        if ($style) {
            $color = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::COLOR);
            if ($color) $styleArgs[] = 'color:'.htmlColorForColorString($color);
            $size = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::SIZE);
            if ($size) $styleArgs[] = 'size:'.$size;
            $icon = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::ICON);
            if ($icon) $styleArgs[] = 'icon:'.$icon;
        }
        
        if (!$styleArgs) {
            $styleArgs = array('color:red', 'weight:4');
        }
        $styleString = implode('|', $styleArgs);

        if (!array_key_exists($styleString, $this->markers)) {
            $this->markers[$styleString] = array();
        }
        $this->markers[$styleString][] = $center['lat'] . ',' . $center['lon'];
    }

    protected function addPath(Placemark $placemark)
    {
        parent::addPath($placemark);

        $pointArr = array();
        foreach ($placemark->getGeometry()->getPoints() as $point) {
            $pointArr[] = array($point['lat'], $point['lon']);
        }
        $polyline = Polyline::encodeFromArray($pointArr);

        $styleArgs = array();
        $style = $placemark->getStyle();
        if ($style) {
            $color = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::COLOR);
            if ($color) $styleArgs[] = 'color:0x'.htmlColorForColorString($color);
            $weight = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::WEIGHT);
            if ($weight) $styleArgs[] = 'weight:'.$weight;
        }
        
        if (!$styleArgs) {
            // color can be 0xRRGGBB or
            // {black, brown, green, purple, yellow, blue, gray, orange, red, white}
            $styleArgs = array('color:red', 'weight:4');
        } 

        $this->paths[] = implode('|', $styleArgs).'|enc:'.$polyline;
    }

    // google layers can be selectively displayed using geometry,
    // label, or both.  so we use this syntax for modified layers:
    // "$layer" (default all), "$layer geometry", "$layer labels"

    // override the following methods for geometry or labels modifier
    public function disableLayer($layer)
    {
        $layerParts = explode(' ', $layer);
        $layerName = $layerParts[0];

        $position = array_search($layerName, $this->enabledLayers);
        if ($position !== false) { // full layer was enabled
            if (count($layerParts) > 1) { // remove complementary element
                switch ($layerParts[1]) {
                    case 'geometry':
                        $this->enabledLayers[$position] = $layerName.' labels';
                        break;
                    case 'labels':
                        $this->enabledLayers[$position] = $layerName.' geometry';
                        break;
                    default:
                        // do nothing, though in the future may
                        // want to special case "all"
                        break;
                }
            } else { // remove entire layer
                $this->enabledLayers = array_splice(
                    $this->enabledLayers,
                    $position,
                    1);
            }
        }
        else {
            $position = array_search($layer, $this->enabledLayers);
            if ($position !== false) {
                $this->enabledLayers = array_splice(
                    $this->enabledLayers,
                    $position,
                    1);
            }
        }
    }

    public function enableLayer($layer)
    {
        // inefficiency here: if the user disables then enables half a
        // layer, e.g. disableLayer('road geometry');
        // enableLayer('road geometry'); enabledLayers will contain
        // 'road geometry' and 'road labels' instead of just 'road'
        if (!$this->isEnabledLayer($layer) && $this->isAvalableLayer($layer)) {
            $this->enabledLayers[] = $layer;
        }
    }

    public function enableAllLayers()
    {
        $this->enabledLayers = self::$availableLayers;
    }

    protected function isEnabledLayer($layer) {
        $layerParts = explode(' ', $layer);
        $layerName = $layerParts[0];
        // if bare layerName is found, both geometry and labels are covered
        $found = in_array($layerName, $this->enabledLayers);
        if (!$found) { // otherwise search for modified layer
            $found = in_array($layer, $this->enabledLayers);
        }
        return $found;
    }

    protected function isAvailableLayer($layer) {
        $layerParts = explode(' ', $layer);
        return in_array($layerParts[0], self::$availableLayers);
    }

    // use hex strings for rgb values
    public function setLayerColor($layer, $color) {
        if (strlen($color) < 6) {
            $bits = 16;
            $pattern = '/([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])$/';
        } else {
            $bits = 256;
            $pattern = '/([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})$/';
        }
        // see HSL segments of http://en.wikipedia.org/wiki/HSL_and_HSV
        if (preg_match($pattern, $color, $matches)) {
            $rr = hexdec($matches[1]) / $bits;
            $gg = hexdec($matches[2]) / $bits;
            $bb = hexdec($matches[3]) / $bits;

            if ($bits == 16)
                $color = $matches[1].$matches[1].$matches[2]
                        .$matches[2].$matches[3].$matches[3];
            else
                $color = $matches[0];

            $max = max($rr, $gg, $bb);
            $min = min($rr, $gg, $bb);
            $chroma = $max - $min;

            $lightness = ($max + $min) / 2;
            if ($chroma == 0)
                $saturation = 0;
            else
                $saturation = $chroma / (1 - abs(2 * $lightness - 1));

            $lightness = -100 + 200 * $lightness;
            $saturation = -100 + 200 * $saturation;

            $style = 'hue:0x'.$color             // 0xRRGGBB
                    .'|lightness:'.$lightness    // -100 to 100
                    .'|saturation:'.$saturation; // -100 to 100
                    // gamma: 0.01 to 10.0
                    // inverse_lightness: true
                    // visibility: (on|off|simplified)

            $this->layerStyles[$layer] = $style;
        }
    }

    /////////// query builders ////////////

    // constructs the styles parameter of the url query
    private function getLayerStyles() {
        $styles = array();

        // specify disabled layers
        foreach (self::$availableLayers as $aLayer) {
            if (!$this->isEnabledLayer($aLayer)) {
                if ($this->isEnabledLayer($aLayer.' geometry'))
                    $styles[] = 'feature:'.$aLayer.'|element:labels|visibility:off';
                else if ($this->isEnabledLayer($aLayer.' labels'))
                    $styles[] = 'feature:'.$aLayer.'|element:geometry|visibility:off';
                else
                    $styles[] = 'feature:'.$aLayer.'|element:all|visibility:off';
            }
        }
        // specify styled layers
        foreach ($this->layerStyles as $layer => $style) {
            if ($this->isEnabledLayer($layer)) {
                $layerParts = explode(' ', $layer);
                $styles[] = 'feature:'.$layerParts[0].'|element:'
                           .(count($layerParts) > 1 ? $layerParts[1] : 'all')
                           .'|'.$style;
            }
        }

        return count($styles) ? $styles : null; // null removes from query
    }

    // constructs the markers parameter of the url query
    // markers=color:blue|label:S|40.702147,-74.015794|40.711614,-74.012318
    private function getMarkers() {
        $markers = array();
        foreach ($this->markers as $style => $coordinates) {
            $markers[] = $style.'|'.implode('|', $coordinates);
        }
        return count($markers) ? $markers : null; // null removes from query
    }

    private function getPaths() {
        return count($this->paths) ? $this->paths : null;
    }

    public function parseQuery($query) {
        // php will only parse the last argument if it isn't specified as an array
        $query = str_replace('markers=', 'markers[]=', $query);
        parse_str($query, $args);
        if (isset($args['center'])) {
            $this->center = filterLatLon($args['center']);
        }
        if (isset($args['zoom'])) {
            $this->zoomLevel = $args['zoom'];
        }
        if (isset($args['sensor'])) {
            $this->sensor = $args['sensor'] == 'true';
        }
        if (isset($args['format'])) {
            $this->imageFormat = $args['format'];
        }
        if (isset($args['mapType'])) {
            $this->mapType = $args['mapType'];
        }
        if (isset($args['size'])) {
            $sizeParts = explode('x', $args['size']);
            $this->imageWidth = $sizeParts[0];
            $this->imageHeight = $sizeParts[1];
        }
        if (isset($args['path'])) {
            $this->paths = $args['path'];
        }
        if (isset($args['markers'])) {
            foreach ($args['markers'] as $markerGroup) {
                $parts = explode('|', $markerGroup);
                for ($i = 0; $i < count($parts); $i++) {
                    if (filterLatLon($parts[$i])) {
                        $this->markers[implode('|', array_slice($parts, 0, $i))] = array_slice($parts, $i);
                        break;
                    }
                }
            }
        }
        if (isset($args['userLat'], $args['userLon'])) {
            $center = array('lat' => $args['userLat'], 'lon' => $args['userLon']);
            $userLocation = new BasePlacemark(new MapBasePoint($center));
            $style = new MapBaseStyle();
            // the following doesn't work from a local server
            //$style->setStyleForTypeAndParam(
            //    MapStyle::POINT,
            //    MapStyle::ICON,
            //    FULL_URL_BASE.'/modules/map/images/map-location@2x.png');
            $userLocation->setStyle($style);
            $this->addPoint($userLocation);
        }
        if (isset($args['style'])) {
            // TODO
        }
    }

    public function getImageURL() {
        $center = $this->center['lat'].','.$this->center['lon'];
        $params = array(
            'center' => $center,
            'mapType' => $this->mapType,
            'size' => $this->imageWidth .'x'. $this->imageHeight, // "size=512x512"
            'markers' => $this->getMarkers(),
            'path' => $this->getPaths(),
            'style' => $this->getLayerStyles(),
            'zoom' => $this->zoomLevel,
            'sensor' => ($this->sensor ? 'true' : 'false'),
            'format' => $this->imageFormat,
            );
        
        if ($this->googleClientID) {
            $params['client'] = $this->googleClientID;
        }

        $query = http_build_query($params);
        // remove brackets
        $query = preg_replace('/%5B\d+%5D/', '', $query);
        return signURLForGoogle($this->baseURL . '?' . $query);
    }

    public function getJavascriptControlOptions() {
        return json_encode(array(
            'center' => $this->center,
            'zoom' => $this->zoomLevel,
            'mapClass' => get_class($this),
            'baseURL' => $this->baseURL,
            ));
    }
    
    public function __construct() {
        $this->baseURL = HTTP_PROTOCOL .'://maps.google.com/maps/api/staticmap';
        $this->enableAllLayers();
    }
}






