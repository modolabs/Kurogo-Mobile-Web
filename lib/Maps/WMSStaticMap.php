<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class WMSStaticMap extends StaticMapImageController {

    const LIFE_SIZE_METERS_PER_PIXEL = 0.00028; // standard definition at 1:1 scale
    const NO_PROJECTION = -1;
    // how much context to provide around a building relative to its size
    const OBJECT_PADDING = 1.0;

    protected $availableLayers = null;
    protected $unitsPerMeter = null;

    private $wmsParser;
    private $diskCache;

    public function init($args) {
        $this->baseURL = $args['BASE_URL'];
        $this->diskCache = new DiskCache(Kurogo::getSiteVar('WMS_CACHE','maps'), 86400 * 7, true);
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

        // TODO make sure this projection is supported by the server
        $projections = $this->wmsParser->getProjections();
        if (count($projections)) {
            // make sure this is a projection we can handle
            foreach ($projections as $proj) {
                $contents = MapProjector::getProjSpecs($proj);
                if ($contents) {
                    $this->setMapProjection($proj);
                }
            }

        } else {
            $this->setMapProjection(GEOGRAPHIC_PROJECTION);
        }
    }

    // http://wiki.openstreetmap.org/wiki/MinScaleDenominator
    protected function getCurrentScale()
    {
        if ($this->unitsPerMeter === null) {
            $this->unitsPerMeter = self::NO_PROJECTION;
            $projRep = new MapProjection($this->mapProjection);
            if (!$projRep->isGeographic()) {
                $unitsPerMeter = $projRep->getUnitsPerMeter();
                if ($unitsPerMeter) {
                    $this->unitsPerMeter = $unitsPerMeter;
                }
            }
        }

        if ($this->unitsPerMeter != self::NO_PROJECTION) {
            $metersPerPixel = $this->getHorizontalRange() / $this->imageWidth / $this->unitsPerMeter;
            return $metersPerPixel / self::LIFE_SIZE_METERS_PER_PIXEL;

        } else {
            $range = $this->getHorizontalRange();
            $zoomLevel = ceil(log(360 / $range, 2));
            return oldPixelScaleForZoomLevel($zoomLevel);
        }
        
    }
    
    // currently the map will recenter as a side effect if projection is reset
    public function setMapProjection($proj)
    {
        if (!$proj) return;

        parent::setMapProjection($proj);
    
        //$this->mapProjection = $proj;
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
        $this->zoomLevel = oldPixelZoomLevelForScale($this->getCurrentScale());
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

        if (isset($args['width'])) {       
            $this->imageWidth = $args['width'];
        }
        if (isset($args['height'])) {
            $this->imageHeight = $args['height'];
        }
        if (isset($args['crs'])) {
            $this->mapProjection = $args['crs'];
        }

        // TODO parse layers and styles
    }

    private function getURLParameters() {
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
            {
                continue;
            }

            if ($aLayer->canDrawAtScale($currentScale)) {
                $layerName = $aLayer->getlayerName();
                $style = $aLayer->getDefaultStyle();
                if ($layerName && $style) {
                    $layers[] = $layerName;
                    $styles[] = $style->getStyleName();
                }
            }
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

        return $params;
    }
    
    public function getImageURL()
    {
        return $this->baseURL.'?'.http_build_query($this->getURLParameters());
    }

    public function getJavascriptControlOptions() {
        return json_encode(array(
            'bbox' => $this->bbox,
            'mapClass' => get_class($this),
            'projection' => $this->mapProjection,
            'baseURL' => $this->baseURL,
            ));
    }
    
    public function getAvailableLayers() {
        if ($this->availableLayers === null) {
            $this->availableLayers = $this->wmsParser->getLayerNames();
        }
        return $this->availableLayers;
    }
}

