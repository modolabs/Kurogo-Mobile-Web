<?php

class WMSStaticMap extends StaticMapImageController {

    const LIFE_SIZE_METERS_PER_PIXEL = 0.00028; // standard definition at 1:1 scale
    const NO_PROJECTION = -1;
    // how much context to provide around a building relative to its size
    const OBJECT_PADDING = 1.0;

    protected $canAddAnnotations = false;
    protected $canaddPaths = false;
    protected $canAddLayers = true;
    protected $supportsProjections = true;

    protected $availableLayers = null;
    private $wmsParser;
    private $diskCache;
    private $defaultProjection = 'CRS:84';
    protected $mapProjection = null;
    protected $unitsPerMeter = null;

    public function __construct($baseURL) {
        $this->baseURL = $baseURL;
        $this->diskCache = new DiskCache($GLOBALS['siteConfig']->getVar('WMS_CACHE'), 86400 * 7, true);
        $this->diskCache->preserveFormat();
        $filename = md5($this->baseURL);
        $metafile = $filename.'-meta.txt';
        
        if (!$this->diskCache->isFresh($filename)) {
            $params = array(
                'request' => 'GetCapabilities',
                'service' => 'WMS',
                );
            $query = $this->baseURL.'?'.http_build_query($params);
            file_put_contents($this->diskCache->getFullPath($metafile), $query);
            $contents = file_get_contents($query);
            $this->diskCache->write($contents, $filename);
        } else {
            $contents = $this->diskCache->read($filename);
        }
        $this->wmsParser = new WMSDataParser();
        $this->wmsParser->parseData($contents);
        $this->enableAllLayers();
        $this->setMapProjection(GEOGRAPHIC_PROJECTION); // currently defined in MapDataController.php
    }

    // http://wiki.openstreetmap.org/wiki/MinScaleDenominator
    protected function getCurrentScale()
    {
        if ($this->unitsPerMeter === null) {
            $contents = MapProjector::getProjSpecs($this->mapProjection);

            if (preg_match('/to_meter=([\d\.]+)/', $contents, $matches)) {
                $this->unitsPerMeter = 1 / $matches[1];
            } else {
                $this->unitsPerMeter = self::NO_PROJECTION;
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

    // just pass this to setMapProjection since the WMS server
    // itself can support multiple projections.
    // TODO add a check to make sure the projection is supported via WMS capabilities
    public function setDataProjection($proj) {
        $this->setMapProjection($proj);
    }
    
    // currently the map will recenter as a side effect if projection is reset
    public function setMapProjection($proj)
    {
        if (!$proj) return;
    
        $this->mapProjection = $proj;
        $this->unitsPerMeter = null;

        // arbitrarily set initial bounding box to the center (1/10)^2 of the containing map
        $bbox = $this->wmsParser->getBBoxForProjection($this->mapProjection);
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
    
    public function getImageURL()
    {
        $bboxStr = $this->bbox['xmin'].','.$this->bbox['ymin'].','
                  .$this->bbox['xmax'].','.$this->bbox['ymax'];

        $layers = array();
        $styles = array();
        
        // TODO figure out if maxScale and minScale in the XMl feed
        // are based on meters or the feed's inherent units
        $currentScale = $this->getCurrentScale();
        foreach ($this->enabledLayers as $layerName) {
            // exclude if out of bounds
            $aLayer = $this->wmsParser->getLayer($layerName);
            $bbox = $aLayer->getBBoxForProjection($this->mapProjection);
            if ($bbox['xmin'] > $this->center['lon']
                || $bbox['xmax'] < $this->center['lon']
                || $bbox['ymin'] > $this->center['lat']
                || $bbox['ymax'] < $this->center['lat'])
                continue;

            if (!$aLayer->canDrawAtScale($currentScale) )
                continue;
            $layers[] = $aLayer->getLayerName();
            $styles[] = $aLayer->getDefaultStyle()->getStyleName();
        }

        $params = array(
            'request' => 'GetMap',
            'version' => '1.3.0',  // TODO allow config
            'format'  => 'png',    // TODO allow config
            'bbox' => $bboxStr,
            'width' => $this->imageWidth,
            'height' => $this->imageHeight,
            'crs' => $this->mapProjection,
            'layers' => implode(',', $layers),
            'styles' => implode(',', $styles),
            );
            
        if (!isset($params['crs'])) $params['crs'] = $this->defaultProjection;

        return $this->baseURL.'?'.http_build_query($params);
    }

    public function getJavascriptControlOptions() {
        $params = array(
            'request' => 'GetMap',
            'version' => '1.3.0',  // TODO allow config
            'format'  => 'png',    // TODO allow config
            'width' => $this->imageWidth,
            'height' => $this->imageHeight,
            'crs' => $this->mapProjection,
            'layers' => implode(',', $layers),
            'styles' => implode(',', $styles),
            );
            
        if (!isset($params['crs'])) $params['crs'] = $this->defaultProjection;

        $baseURL = $this->baseURL.'?'.http_build_query($params);

        return json_encode(array(
            'bbox' => $this->bbox,
            'baseURL' => $baseURL,
            ));
    }
    
    public function getAvailableLayers() {
        if ($this->availableLayers === null) {
            $this->availableLayers = $this->wmsParser->getLayerNames();
        }
        return $this->availableLayers;
    }
}

