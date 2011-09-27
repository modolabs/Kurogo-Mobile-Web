<?php

// TODO reduce duplication between this class and WMSStaticMap/ArcGISJSMap
// by moving some methods to config, utility, or superclass

// http://resources.esri.com/help/9.3/arcgisserver/apis/rest/index.html
class ArcGISStaticMap extends StaticMapImageController {

    const LIFE_SIZE_METERS_PER_PIXEL = 0.00028; // standard definition at 1:1 scale
    const NO_PROJECTION = -1;
    
    private $layerFilters = array();

    protected $availableLayers = null;
    protected $unitsPerMeter = null;
    
    private $transparent = false;

    public function setTransparent($tranparent) {
        $this->transparent = ($transparent == true);
    }

    public function __construct($baseURL, $parser=null) {
        $this->baseURL = $baseURL;

        // TODO find a better way to reuse JSON parsing code for ArcGIS-related data
        $url = $this->baseURL.'?'.http_build_query(array('f' => 'json'));
        $content = file_get_contents($url);
        $data = json_decode($content, true);
        if (isset($data['spatialReference'], $data['spatialReference']['wkid'])) {
            $this->setMapProjection($data['spatialReference']['wkid']);
        }

        $this->unitsPerMeter = self::getScaleForEsriUnits($data['units']);

        if (isset($data['supportedImageFormatTypes'])) {
            $this->supportedImageFormats = $data['supportedImageFormatTypes'];
        }

        foreach ($data['layers'] as $layerData) {
            $id = $layerData['id'];
            $this->availableLayers[] = $id;
        }
        $this->enableAllLayers();

        $bbox = $data['initialExtent'];
        unset($bbox['spatialReference']);

        $xrange = $bbox['xmax'] - $bbox['xmin'];
        $yrange = $bbox['ymax'] - $bbox['ymin'];
        $bbox['xmin'] += 0.4 * $xrange;
        $bbox['xmax'] -= 0.4 * $xrange;
        $bbox['ymin'] += 0.4 * $yrange;
        $bbox['ymax'] -= 0.4 * $yrange;
        $this->bbox = $bbox;
        $this->zoomLevel = $this->zoomLevelForScale($this->getCurrentScale());
        $this->center = array(
            'lat' => ($this->bbox['ymin'] + $this->bbox['ymax']) / 2,
            'lon' => ($this->bbox['xmin'] + $this->bbox['xmax']) / 2,
            );
    }

    public function setCenter($center)
    {
        if ($center['lon'] < 180 && $center['lon'] > -180
            && $center['lat'] < 90 && $center['lat'] > -90 && $this->mapProjector)
        {
            $this->center = $this->mapProjector->projectPoint($center);
        }
    }

    public static function getScaleForEsriUnits($units) {
        switch ($units) {
            case 'esriCentimeters':
                return 100;
            case 'esriDecimeters':
                return 0.1;
            case 'esriFeet':
                return 3.2808399;
            case 'esriInches':
                return 39.3700787;
            case 'esriKilometers':
                return 0.001;
            case 'esriMeters':
                return 1;
            case 'esriMiles':
                return 0.000621371192;
            case 'esriMillimeters':
                return 1000;
            case 'esriNauticalMiles':
                return 0.000539956803;
            case 'esriYards':
                return 1.0936133;
            default:
                return self::NO_PROJECTION;
        }
    }

    // http://wiki.openstreetmap.org/wiki/MinScaleDenominator
    protected function getCurrentScale()
    {
        if ($this->unitsPerMeter != self::NO_PROJECTION) {
            $metersPerPixel = $this->getHorizontalRange() / $this->imageWidth / $this->unitsPerMeter;
            return $metersPerPixel / self::LIFE_SIZE_METERS_PER_PIXEL;
        }
        return null;
    }
    
    protected function zoomLevelForScale($scale)
    {
        // not sure if ceil is the right rounding in both cases
        if ($scale == self::NO_PROJECTION) {
            $range = $this->getHorizontalRange();
            return ceil(log(360 / $range, 2));
        } else {
            return oldPixelZoomLevelForScale($scale);
        }
    }

    ////////////// overlays ///////////////
    
    public function getAvailableLayers() {
        return $this->availableLayers;
    }
    
    // $filter is something like "POP>1000000", "ID='51'"
    // where you know the name of the field and its range of values
    public function setLayerFilter($layer, $filter) {
        if ($this->isAvailableLayer($layer)) {
            $this->layerFilters[$layer] = $filter;
        }
    }

    /////////// query builders ////////////

    public function parseQuery($query) {
        parse_str($query, $args);

        if (isset($args['bbox'])) {
            $bboxParts = explode(',', $args['bbox']);
            $this->bbox = array(
                'xmin' => $bboxParts[0],
                'ymin' => $bboxParts[1],
                'xmax' => $bboxParts[2],
                'ymax' => $bboxParts[3],
                );
        }

        if (isset($args['transparent'])) {
            $this->transparent = $args['transparent'] == 'true';
        }

        if (isset($args['imageSR'])) {
            $this->mapProjection = $args['imageSR'];
        }

        if (isset($args['format'])) {
            $this->imageFormat = $args['format'];
        }

        if (isset($args['size'])) {
            $sizeParts = explode(',', $args['size']);
            $this->imageWidth = $sizeParts[0];
            $this->imageHeight = $sizeParts[1];
        }

        // TODO read layerDefs and layers
    }

    public function getImageURL() {
        $bboxStr = $this->bbox['xmin'].','.$this->bbox['ymin'].','
                  .$this->bbox['xmax'].','.$this->bbox['ymax'];
        
        $params = array(
            'f' => 'image',
            'bbox' => $bboxStr,
            'size' => $this->imageWidth.','.$this->imageHeight,
            'dpi' => null, // default 96
            'imageSR' => $this->mapProjection,
            'bboxSR' => $this->mapProjection,
            'format' => $this->imageFormat,
            'layerDefs' => $this->getLayerDefs(),
            'layers' => 'show:'.implode(',', $this->enabledLayers),
            'transparent' => $this->transparent ? 'true' : 'false',
            );

        $query = http_build_query($params);

        return $this->baseURL . '/export?' . $query;
    }

    public function getJavascriptControlOptions() {
        return json_encode(array(
            'bbox' => $this->bbox,
            'mapClass' => get_class($this),
            'baseURL' => $this->baseURL,
            ));
    }
    
    private function getLayerDefs() {
        if (!$this->layerFilters)
            return null;

        $layerDefs = array();
        foreach ($this->layerFilters as $layer => $filter) {
            $layerDefs[] = $layer.':'.$filter;
        }
        return implode(';', $layerDefs);
    }
}






