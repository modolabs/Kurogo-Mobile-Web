<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class ArcGISDataRetriever extends URLDataRetriever
{
    const ACTION_CATEGORIES = 'categories';
    const ACTION_PLACEMARKS = 'placemarks';
    const ACTION_SEARCH = 'search';
    const ACTION_SEARCH_NEARBY = 'searchByProximity';

    protected $projection;
    protected $action;

    protected $selectedLayer;
    protected $orderByFields;
    protected $useExtentGeometry = 0;
    //protected $layerTypes = array();
    protected $searchFilters = array();

    public function init($args) {
        parent::init($args);
        if (isset($args['ARCGIS_LAYER_ID'])) {
            $this->selectedLayer = $args['ARCGIS_LAYER_ID'];
            $this->parser->createFolder($this->selectedLayer, $args['TITLE']);
        }
        if (isset($args['SORT_FIELD'])) {
            $this->orderByFields = $args['SORT_FIELD'];
        }
        if (isset($args['USE_EXTENT_GEOMETRY'])) {
            $this->useExtentGeometry = $args['USE_EXTENT_GEOMETRY'];
        }

        $this->filters = array('f' => 'json');
    }

    protected function parameters() {
        switch ($this->action) {
            case self::ACTION_PLACEMARKS:
                $fields = $this->parser->getFieldKeys();

                $params = array(
                    'text'           => '%',
                    'inSR'           => $this->parser->getProjection(),
                    'spatialRel'     => 'esriSpatialRelIntersects',
                    'where'          => '',
                    'returnGeometry' => 'true',
                    'outSR'          => '',
                    'outFields'      => implode(',', $fields),
                    'f'              => 'json',
				);

                if ($this->useExtentGeometry) {
					$extent = $this->parser->getExtent();
					$bbox = $extent['xmin'].','.$extent['ymin'].','.$extent['xmax'].','.$extent['ymax'];
                    $params['text'] = '';
                    $params['geometry'] = $bbox;
                    $params['geometryType'] = 'esriGeometryEnvelope';
                }

                if ($this->orderByFields) {
                    $params['where']          = 'OBJECTID>0';
                	$params['orderByFields']  = $this->orderByFields;
                }
                return $params;

            case self::ACTION_SEARCH:
                $displayField = null;
                if (isset($this->selectedLayer)) {
                    $displayField = $this->parser->getDisplayFieldForFolder($this->selectedLayer);
                }
                if ($displayField) {
                    $searchText = strtoupper(str_replace("'","''",$this->searchFilters['text']));
                    return array(
                        'where' => "UPPER($displayField) LIKE '%$searchText%'",
                        'f'    => 'json',
                    );
                } else {
                    return array(
                        'text' => str_replace("'","''",$this->searchFilters['text']),
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
        // Use strlen to protect against a layer id of 0
        if (strlen($this->selectedLayer)==0 && $this->action == self::ACTION_PLACEMARKS) {
            return array();
        }

        $data = parent::getData($response);

        if ($data === null) {
            $data = array();
        }

        return $data;
    }

    public function setSaveMemory($saveMemory) {
        $this->parser->setSaveMemory($saveMemory);
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

