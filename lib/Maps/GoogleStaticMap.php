<?php

require_once 'Polyline.php';

// http://code.google.com/apis/maps/documentation/staticmaps
class GoogleStaticMap extends StaticMapImageController {

    protected $baseURL = 'http://maps.google.com/maps/api/staticmap';

    // capabilities
    protected $canAddAnnotations = true;
    protected $canAddPaths = true;
    protected $canAddLayers = true;
    //protected $canAddPolygons = true;

    protected $maxZoomLevel = 21;
    protected $minZoomLevel = 0;

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

    ///////////////// query functions /////////////

    public function getHorizontalRange()
    {
        // at zoom level 0 the whole earth is shown, i.e. 360 degrees
        return 360 / pow(2, $this->zoomLevel);
    }

    public function getVerticalRange()
    {
        // this should be 180 at zoom level 0, though may vary with latitude
        return 180 / pow(2, $this->zoomLevel);
    }

    ////////////// overlays ///////////////

    // should expand to support addresses
    public function addAnnotation($latitude, $longitude, $style=null, $title=null)
    {
        if ($style === null) {
            $styleArgs = array('color:red');
        } else {
            $styleArgs = array();
            $color = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::COLOR);
            if ($color) $styleArgs[] = 'color:'.$color;
            $size = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::SIZE);
            if ($size) $styleArgs[] = 'size:'.$size;
            $icon = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::ICON);
            if ($icon) $styleArgs[] = 'icon:'.$icon;
            // also can use label, shadow
        }
        $styleString = implode('|', $styleArgs);

        if (!array_key_exists($styleString, $this->markers))
            $this->markers[$styleString] = array();
        $this->markers[$styleString][] = $latitude . ',' . $longitude;
    }

    public function addPath($points, $style=null)
    {
        $polyline = Polyline::encodeFromArray($points);

        if ($style === null) {
            // color can be 0xRRGGBB or
            // {black, brown, green, purple, yellow, blue, gray, orange, red, white}
            $styleArgs = array('color:red');
        } else {
            $styleArgs = array();
            $color = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::COLOR);
            if ($color) $styleArgs[] = 'color:0x'.$color;
            $weight = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::WEIGHT);
            if ($weight) $styleArgs[] = 'weight:'.$weight;
        }

        $this->paths[] = implode('|', $styleArgs).'|enc:'.$polyline;
    }
    
    /*
    public function addPolygon($rings, $style=null)
    {
    	
    }
    */

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

    public function getImageURL() {
        $params = array(
            'center' => $this->center['lat'].','.$this->center['lon'],
            'mapType' => $this->mapType,
            'size' => $this->imageWidth .'x'. $this->imageHeight, // "size=512x512"
            'markers' => $this->getMarkers(),
            'path' => $this->getPaths(),
            'style' => $this->getLayerStyles(),
            'zoom' => $this->zoomLevel,
            'sensor' => ($this->sensor ? 'true' : 'false'),
            'format' => $this->imageFormat,
            );

        $query = http_build_query($params);
        // remove brackets
        $query = preg_replace('/%5B\d+%5D/', '', $query);

        return $this->baseURL . '?' . $query;
    }

    public function __construct() {
        $this->enableAllLayers();
    }
}






