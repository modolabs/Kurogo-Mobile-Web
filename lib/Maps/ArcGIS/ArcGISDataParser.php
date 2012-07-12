<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class ArcGISDataParser extends DataParser implements MapDataParser
{
    protected $folders = array();
    protected $placemarks = array();
    protected $currentFolder;
    protected $projection;
    protected $mapProjector;
    protected $isTiledService;
    protected $feedId;

    /////// MapDataParser

    public function placemarks() {
        return $this->placemarks;
    }

    public function categories() {
        return array_values($this->folders);
    }

    public function getProjection() {
        return $this->projection;
    }

    public function setProjection($proj) {
        $this->projection = $proj;
    }
    
    public function init($args) {
        parent::init($args);
        $this->feedId = mapIdForFeedData($args);
    }

    public function getId() {
        return $this->feedId;
    }

    ////

    public function isTiledService() {
        return $this->isTiledService;
    }

    ///

    public function createFolder($folderId, $title) {
        $this->folders[$folderId] = new ArcGISFolder($folderId, $title);
        $this->setCurrentFolderId($folderId);
    }

    public function setCurrentFolderId($folderId) {
        if (isset($this->folders[$folderId])) {
            $this->currentFolder = $this->folders[$folderId];
        } else {
            foreach ($this->categories() as $category) {
                if (($found = $category->findCategory($folderId))) {
                    $this->currentFolder = $found;
                    break;
                }
            }
        }
    }

    public function getExtent() {
        if (isset($this->currentFolder)) {
            return $this->currentFolder->getExtent();
        }
    }

    public function getFieldKeys() {
        return $this->currentFolder->getFieldKeys();
    }
    
    public function getDisplayFieldForFolder($folderId) {
        $displayField = null;
        if (isset($this->folders[$folderId])) {
            $displayField = $this->folders[$folderId]->getDisplayField();
        }
        return $displayField;
    }

    public function parseData($content) {
        $data = json_decode($content, true);
        if (isset($data['error'])) {
            $error = $data['error'];
            $details = isset($error['details']) ? json_encode($error['details']) : '';
            Kurogo::log(LOG_ERR, "Error response from ArcGIS server:\n"
                ."Code: {$error['code']}\n"
                ."Message: {$error['message']}\n"
                ."Details: $details\n", 'maps');
            //throw new KurogoDataServerException("Map server returned error: \"{$error['message']}\"");
        }

        if (isset($data['serviceDescription'])) {
            // this is a service (top level)
            if (isset($data['spatialReference'], $data['spatialReference']['wkid'])) {
                $wkid = $data['spatialReference']['wkid'];
                $this->setProjection($wkid);
            }

            if (isset($data['singleFusedMapCache'])) {
                $this->isTiledService = true;
            }

            foreach ($data['layers'] as $layerData) {
                $parentId = $layerData['parentLayerId'];
                $folderId = $layerData['id'];
                if (isset($this->folders[$parentId])) {
                    $this->folders[$parentId]->addFolder(new ArcGISFolder($folderId, $layerData['name']));
                } else {
                    $this->createFolder($folderId, $layerData['name']);
                }
            }
            return $this->categories();

        } elseif (isset($data['type'])) { // this is a feature layer or group layer
            $this->currentFolder->setSubtitle($data['description']);

            if (isset($data['displayField']) && $data['displayField']) {
                $this->currentFolder->setDisplayField($data['displayField']);
            }

            if (isset($data['geometryType']) && $data['geometryType']) {
                $this->currentFolder->setGeometryType($data['geometryType']);
            }

            if (isset($data['extent'])) {
                $this->currentFolder->setExtent($data['extent']);
                if (!$this->projection) {
                    $this->setProjection($data['extent']['spatialReference']['wkid']);
                }
            }

            if (isset($data['drawingInfo'])) {
                // TODO: figure out the API spec to create styles from this
            }

            if (isset($data['fields'])) {
                $displayField = $this->currentFolder->getDisplayField();
                foreach ($data['fields'] as $fieldInfo) {
                    if ($fieldInfo['type'] == 'esriFieldTypeOID') {
                        $this->currentFolder->setIdField($fieldInfo['name']);
                        continue;
                    } else if (strtolower($fieldInfo['name']) == 'shape'
                            || strtolower($fieldInfo['name']) == 'shape_length'
                            || strtolower($fieldInfo['name']) == 'shape_area')
                    {
                        continue;
                    } else if ($fieldInfo['type'] == 'esriFieldTypeGeometry') {
                        $this->currentFolder->setGeometryField($fieldInfo['name']);
                        continue;
                    } else if (!isset($possibleDisplayField) && $fieldInfo['type'] == 'esriFieldTypeString') {
                        $possibleDisplayField = $fieldInfo['name'];
                    }

                    $name = $fieldInfo['name'];
                    if (strtoupper($name) == strtoupper($displayField)) {
                        // handle case where display field is returned in
                        // a different capitalization from return fields
                        $name = $displayField;
                    }
                    $this->currentFolder->setFieldAlias($name, $fieldInfo['alias']);
                }

                if (!($this->currentFolder->hasField($displayField)) && isset($possibleDisplayField)) {
                    // if the display field is still problematic (e.g. the OID
                    // field was returned as the display field), just choose the
                    // first string field that shows up. obviously if there are no
                    // other string fields then this will also fail.
                    $this->currentFolder->setDisplayField($possibleDisplayField);
                }
            }

            if ($data['type'] == 'Group Layer') {
                return $this->currentFolder->categories();
            }

            return null;

        } elseif (isset($data['features'])) {

            $idField = $this->currentFolder->getIdField();
            if (isset($data['geometryType'])) {
                $geometryType = $data['geometryType'];
            } else {
                $geometryType = $this->currentFolder->getGeometryType();
            }

            if (isset($data['displayFieldName'])) { // will set if we got here via layer query
                $displayField = $data['displayFieldName'];
            } else {
                $displayField = $this->currentFolder->getDisplayField();
            }

            foreach ($data['features'] as $featureInfo) {
                if (isset($featureInfo['foundFieldName'])) { // may be set if we got here via search
                    $displayField = $featureInfo['foundFieldName'];
                }

                $title = null;
                $placemarkId = null;
                $displayAttribs = array();

                // use human-readable field alias to construct feature details
                foreach ($featureInfo['attributes'] as $name => $value) {
                    if (strtoupper($name) == strtoupper($displayField)) {
                        $title = $value;
                    } elseif ($idField && strtoupper($name) == strtoupper($idField)) {
                        $placemarkId = $value;
                    } elseif ($value !== null && trim($value) !== '') {
                        $finalField = $this->currentFolder->aliasForField($name);
                        if ($finalField !== null) {
                            $displayAttribs[$finalField] = $value;
                        }
                    }
                }

                $geometryJSON = null;
                if ($geometryType && isset($featureInfo['geometry'])) {
                    $geometryJSON = $featureInfo['geometry'];
                }
                if ($title || $placemarkId) {
                    // only create placemarks if there is usable data associated with it
                    $geometry = null;
                    if ($geometryJSON) {
                        switch ($geometryType) {
                            case 'esriGeometryPoint':
                                $geometry = new ArcGISPoint($geometryJSON);
                                break;
                            case 'esriGeometryPolyline':
                                $geometry = new ArcGISPolyline($geometryJSON);
                                break;
                            case 'esriGeometryPolygon':
                                $geometry = new ArcGISPolygon($geometryJSON);
                                break;
                        }
                    }

                    $geometry = $this->projectGeometry($geometry);
                    $placemark = new BasePlacemark($geometry);
                    foreach ($displayAttribs as $name => $value) {
                        $placemark->setField($name, $value);
                    }

                    if ($title === null) {
                        $title = $placemarkId;
                    }
                    if ($placemarkId === null) {
                        $placemarkId = $title;
                    }
                    $placemark->setTitle($title);
                    $placemark->setId($placemarkId);

                    $this->currentFolder->addPlacemark($placemark);
                }
                
            }

            return $this->currentFolder->placemarks();
        }

        return null;
    }

    protected function projectGeometry(MapGeometry $geometry) {
        if ($this->projection && !isset($this->mapProjector)) {
            $this->mapProjector = new MapProjector();
            $this->mapProjector->setSrcProj($this->projection);
        }
        if ($this->mapProjector) {
            return $this->mapProjector->projectGeometry($geometry);
        }
        return $geometry;
    }









}
