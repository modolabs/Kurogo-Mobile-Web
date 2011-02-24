<?php

// TODO reduce duplication between this class and WMSStaticMap/ArcGISJSMap
// by moving some methods to config, utility, or superclass

// http://resources.esri.com/help/9.3/arcgisserver/apis/rest/index.html
class ArcGISStaticMap extends StaticMapImageController {

    const LIFE_SIZE_METERS_PER_PIXEL = 0.00028; // standard definition at 1:1 scale
    const NO_PROJECTION = -1;
    
    private $parser;
    private $layerFilters = array();

    protected $canAddAnnotations = false;
    protected $canaddPaths = false;
    protected $canAddLayers = true;
    protected $supportsProjections = true;

    protected $availableLayers = null;
    protected $unitsPerMeter = null;
    
    private $mapProjector;
    
    private $transparent = false;
    public function setTransparent($tranparent) {
        $this->transparent = ($transparent == true);
    }

    public function __construct($baseURL, $parser=null) {
        $this->baseURL = $baseURL;
        $this->parser = ArcGISDataController::parserFactory($this->baseURL);
        $this->mapProjector = new MapProjector();
        
        $this->supportedImageFormats = $this->parser->getSupportedImageFormats();
        $this->enableAllLayers();

        // permanently set projection based on associated parser        
        $this->mapProjection = $this->parser->getProjection();
        $this->mapProjector->setDstProj($this->mapProjection);
        $this->unitsPerMeter = null;

        $bbox = $this->parser->getInitialExtent();

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

    // http://wiki.openstreetmap.org/wiki/MinScaleDenominator
    protected function getCurrentScale()
    {
        if ($this->unitsPerMeter === null) {
            switch ($this->parser->getUnits()) {
            case 'esriCentimeters':
                $this->unitsPerMeter = 100;
                break;
            case 'esriDecimeters':
                $this->unitsPerMeter = 0.1;
                break;
            case 'esriFeet':
                $this->unitsPerMeter = 3.2808399;
                break;
            case 'esriInches':
                $this->unitsPerMeter = 39.3700787;
                break;
            case 'esriKilometers':
                $this->unitsPerMeter = 0.001;
                break;
            case 'esriMeters':
                $this->unitsPerMeter = 1;
                break;
            case 'esriMiles':
                $this->unitsPerMeter = 0.000621371192;
                break;
            case 'esriMillimeters':
                $this->unitsPerMeter = 1000;
                break;
            case 'esriNauticalMiles':
                $this->unitsPerMeter = 0.000539956803;
                break;
            case 'esriYards':
                $this->unitsPerMeter = 1.0936133;
                break;
            default:
                $this->unitsPerMeter = self::NO_PROJECTION;
                break;
            }
        }
        
        if ($this->unitsPerMeter != self::NO_PROJECTION) {
            $metersPerPixel = $this->getHorizontalRange() / $this->imageWidth / $this->unitsPerMeter;
            return $metersPerPixel / self::LIFE_SIZE_METERS_PER_PIXEL;
        } else {
            // TODO this isn't quite right, this won't allow us to use
            // maxScaleDenom and minScaleDenom in any layers
            return self::NO_PROJECTION;
        }
    }
    
    protected function zoomLevelForScale($scale)
    {
        // not sure if ceil is the right rounding in both cases
        if ($scale == self::NO_PROJECTION) {
            $range = $this->getHorizontalRange();
            return ceil(log(360 / $range, 2));
        } else {
            // http://wiki.openstreetmap.org/wiki/MinScaleDenominator
            return ceil(log(559082264 / $scale, 2));
        }
    }
    
    public function setDataProjection($proj) {
        $this->mapProjector->setSrcProj($proj);
    }
    
    // do conversion here since this and ArcGISJSMap are the
    // only map image controllers that can project input
    // features from other coordinate systems
    public function setCenter($center) {
        $newCenter = $this->mapProjector->projectPoint($center);
        parent::setCenter(array('lat' => $newCenter['y'], 'lon' => $newCenter['x']));
    }

    ////////////// overlays ///////////////
    
    public function getAvailableLayers() {
        if ($this->availableLayers === null) {
            $this->availableLayers = $this->parser->getSubLayerIds();
        }
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
        
        $params = array(
            'f' => 'image',
            'dpi' => null, // default 96
            'imageSR' => $this->mapProjection,
            'bboxSR' => $this->mapProjection,
            'format' => $this->imageFormat,
            'layerDefs' => $this->getLayerDefs(),
            'layers' => 'show:'.implode(',', $this->enabledLayers),
            'transparent' => $this->transparent ? 'true' : 'false',
            );

        $query = http_build_query($params);
        $baseURL = $this->baseURL . '/export?' . $query;

        return json_encode(array(
            'bbox' => $this->bbox,
            'stringFromDimensions' => 'return "&size="+width+","+height;',
            'baseURL' => $baseURL,
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






