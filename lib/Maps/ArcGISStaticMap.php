<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

// http://resources.esri.com/help/9.3/arcgisserver/apis/rest/index.html
class ArcGISStaticMap extends StaticMapImageController {

    private $layerFilters = array();
    protected $availableLayers = null;
    private $transparent = false;

    public function setTransparent($tranparent) {
        $this->transparent = ($transparent == true);
    }

    public function init($args) {
        parent::init($args);

        $this->baseURL = $args['BASE_URL'];

        // TODO find a better way to reuse JSON parsing code for ArcGIS-related data
        $url = $this->baseURL.'?'.http_build_query(array('f' => 'json'));
        $content = file_get_contents($url);
        $data = json_decode($content, true);
        if (isset($data['spatialReference'], $data['spatialReference']['wkid'])) {
            $this->setMapProjection($data['spatialReference']['wkid']);
        }

        if (isset($data['supportedImageFormatTypes'])) {
            $this->supportedImageFormats = $data['supportedImageFormatTypes'];
        }

        foreach ($data['layers'] as $layerData) {
            $id = $layerData['id'];
            $this->availableLayers[] = $id;
        }
        $this->enableAllLayers();
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
        if (isset($args['transparent'])) {
            $this->transparent = $args['transparent'] == 'true';
        }

        if (isset($args['format'])) {
            $this->imageFormat = $args['format'];
        }

        if (isset($args['size'])) {
            $sizeParts = explode(',', $args['size']);
            $this->imageWidth = $sizeParts[0];
            $this->imageHeight = $sizeParts[1];
        }

        if (isset($args['imageSR'])) {
            $this->setMapProjection($args['imageSR']);
        }

        if (isset($args['bbox'])) {
            list($xmin, $ymin, $xmax, $ymax) = explode(',', $args['bbox']);
            $zoom = log($this->globeWidth() / ($xmax - $xmin), 2);

            $northeast = array('lat' => $ymax, 'lon' => $xmax);
            $southwest = array('lat' => $ymin, 'lon' => $xmin);
            if ($this->mapProjector) {
                $northeast = $this->mapProjector->reverseProject($northeast);
                $southwest = $this->mapProjector->reverseProject($southwest);
            }
            $this->setBoundingBox(
                $southwest['lon'], $southwest['lat'],
                $northeast['lon'], $northeast['lat']);
        }
        // TODO read layerDefs and layers
    }

    private function getProjectedBBox() {
        $bbox = $this->getBoundingBox();
        $northeast = array('lat' => $bbox['ymax'], 'lon' => $bbox['xmax']);
        $southwest = array('lat' => $bbox['ymin'], 'lon' => $bbox['xmin']);
        if ($this->mapProjector) {
            $northeast = $this->mapProjector->projectPoint($northeast, MapProjector::FORMAT_LATLON);
            $southwest = $this->mapProjector->projectPoint($southwest, MapProjector::FORMAT_LATLON);
        }
        list($bbox['xmin'], $bbox['ymin'], $fmt) = MapProjector::getXYFromPoint($southwest);
        list($bbox['xmax'], $bbox['ymax'], $fmt) = MapProjector::getXYFromPoint($northeast);
        return $bbox;
    }

    public function getImageURL() {
        $bbox = $this->getProjectedBBox();
        $bboxStr = $bbox['xmin'].','.$bbox['ymin'].','
                  .$bbox['xmax'].','.$bbox['ymax'];
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
            'bbox' => $this->getProjectedBBox(),
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






