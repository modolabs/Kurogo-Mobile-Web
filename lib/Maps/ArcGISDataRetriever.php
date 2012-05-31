<?php

class ArcGISDataRetriever extends URLDataRetriever
{
    const ACTION_CATEGORIES = 'categories';
    const ACTION_PLACEMARKS = 'placemarks';
    const ACTION_SEARCH = 'search';
    const ACTION_SEARCH_NEARBY = 'searchByProximity';

    protected $projection;
    protected $action;

    protected $selectedLayer;
    //protected $layerTypes = array();
    protected $searchFilters = array();

    public function init($args) {
        parent::init($args);
        if (isset($args['ARCGIS_LAYER_ID'])) {
            $this->selectedLayer = $args['ARCGIS_LAYER_ID'];
            $this->parser->createFolder($this->selectedLayer, $args['TITLE']);
        }
        $this->filters = array('f' => 'json');
    }

    protected function parameters() {
        switch ($this->action) {
            case self::ACTION_PLACEMARKS:
                $extent = $this->parser->getExtent();
                $fields = $this->parser->getFieldKeys();

                $bbox = $extent['xmin'].','.$extent['ymin'].','.$extent['xmax'].','.$extent['ymax'];
                
                return array(
                    'text'           => '',
                    'geometry'       => $bbox,
                    'geometryType'   => 'esriGeometryEnvelope',
                    'inSR'           => $this->parser->getProjection(),
                    'spatialRel'     => 'esriSpatialRelIntersects',
                    'where'          => '',
                    'returnGeometry' => 'true',
                    'outSR'          => '',
                    'outFields'      => implode(',', $fields),
                    'f'              => 'json',
                    );

            case self::ACTION_SEARCH:
                $displayField = null;
                if (isset($this->selectedLayer)) {
                    $displayField = $this->parser->getDisplayFieldForFolder($this->selectedLayer);
                }
                if ($displayField) {
                    $searchText = strtoupper($this->searchFilters['text']);
                    return array(
                        'where' => "UPPER($displayField) LIKE '%$searchText%'",
                        'f'    => 'json',
                    );
                } else {
                    return array(
                        'text' => $this->searchFilters['text'],
                        'f'    => 'json',
                        );
                }

            case self::ACTION_SEARCH_NEARBY:
                $bbox = normalizedBoundingBox(
                    $this->searchFilters['center'],
                    $this->searchFilters['tolerance'],
                    null,
                    $this->parser->getProjection());
                return array(
                    'spatialRel'   => 'esriSpatialRelIntersects',
                    'geometryType' => 'esriGeometryEnvelope',
                    'geometry'     => "{$bbox['min']['lon']},{$bbox['min']['lat']},{$bbox['max']['lon']},{$bbox['max']['lat']}",
                    'f'            => 'json',
                    );
        }
        return parent::parameters();
    }

    protected function baseURL() {
        $baseURL = $this->baseURL;
        if (isset($this->selectedLayer)) {
            $baseURL .= '/'. $this->selectedLayer;
        }
        switch ($this->action) {
            case self::ACTION_PLACEMARKS:
            case self::ACTION_SEARCH:
            case self::ACTION_SEARCH_NEARBY:
                $baseURL .= '/query';
                break;
            default:
                break;
        }
        return $baseURL;
    }

    public function setSearchFilters($filters) {
        $this->searchFilters = $filters;
    }

    public function setSelectedLayer($layerId) {
        $this->selectedLayer = $layerId;
        $this->parser->setCurrentFolderId($layerId);
    }

    // intercept this since we sometimes have to parse two calls to get everything
    public function getData(&$response=null) {
        // this happens when we start out at the top level of a service instance
        if (!$this->selectedLayer && $this->action == self::ACTION_PLACEMARKS) {
            return array();
        }

        $data = parent::getData();

        if ($data === null) {
            $data = array();
        }

        return $data;
    }

    public function setAction($action) {
        if ($action == self::ACTION_PLACEMARKS) {
            // this won't work out of the box because we need metadata
            $this->action = self::ACTION_CATEGORIES;
            $this->getData();
        }
        $this->action = $action;
    }

}

