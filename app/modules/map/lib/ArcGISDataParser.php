<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
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
    protected $saveMemory = false;

    protected $configTitleField;
    protected $configSubtitleField;

    // aliases for placemark searching

    protected $placemarkClass;
    protected $DEFAULT_PLACEMARK_CLASS = 'BasePlacemark';

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

    public function setSaveMemory($saveMemory) {
        $this->saveMemory = $saveMemory;
    }

    public function init($args) {
        parent::init($args);
        $this->feedId = mapIdForFeedData($args);
        if (isset($args['PLACEMARK_CLASS'])) {
            $this->placemarkClass = $args['PLACEMARK_CLASS'];
        } else {
            $this->placemarkClass = $this->DEFAULT_PLACEMARK_CLASS;
        }
        if (isset($args['TITLE_FIELD'])) {
            $this->configTitleField = $args['TITLE_FIELD'];
        }
        if (isset($args['SUBTITLE_FIELD'])) {
            $this->configSubtitleField = $args['SUBTITLE_FIELD'];
        }
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

    protected static function hexColorFromArray($rgba, $ignoreAlpha=false) {
        $color = dechex($rgba[0]).dechex($rgba[1]).dechex($rgba[2]);
        if (!$ignoreAlpha && $rgba[3] != 255) {
            return dechex($rgba[3]).$color;
        }
        return $color;
    }

    // http://servicesbeta2.esri.com/arcgis/sdk/rest/symbol.html
    protected function parseMapSymbol($symbolJSON, $ignoreAlpha=false) {
        switch ($symbolJSON['type']) {
            case 'esriSMS': // simple marker symbol
                $style = new MapBaseStyle();

                // TODO come up with internal representation for geometric shapes
                // of markers, if other engines support generic shapes.
                // the only JS map engine that can use these geometric shapes
                // is ArcGISJSMap, and we would need to pass them to the JS library
                // with these exact names. 

                // these will be one of the following values:
                // esriSMSCircle | esriSMSCross | esriSMSDiamond | esriSMSSquare | esriSMSX
                $shape = $symbolJSON['style'];
                $style->setStyleForTypeAndParam(MapStyle::POINT, MapStyle::SHAPE, $shape);

                if (isset($symbolJSON['color']) && $symbolJSON['color']) {
                    $color = self::hexColorFromArray($symbolJSON['color'], $ignoreAlpha);
                    $style->setStyleForTypeAndParam(MapStyle::POINT, MapStyle::COLOR, $color);
                }

                $style->setStyleForTypeAndParam(MapStyle::POINT, MapStyle::SIZE, $symbolJSON['size']);

                // ignored: angle, xoffset, yoffset, outline
                break;

            case 'esriSLS': // simple line symbol
                $style = new MapBaseStyle();
                $consistency = $symbolJSON['style'];
                // see comment above
                // esriSLSDash | esriSLSDashDot | esriSLSDashDotDot | esriSLSDot | esriSLSNull | esriSLSSolid
                $style->setStyleForTypeAndParam(MapStyle::LINE, MapStyle::CONSISTENCY, $consistency);

                if (isset($symbolJSON['color']) && $symbolJSON['color']) {
                    $color = self::hexColorFromArray($symbolJSON['color'], $ignoreAlpha);
                    $style->setStyleForTypeAndParam(MapStyle::LINE, MapStyle::STROKECOLOR, $color);
                }

                $style->setStyleForTypeAndParam(MapStyle::LINE, MapStyle::WIDTH, $symbolJSON['width']);

                break;
            case 'esriSFS': // simple fill symbol
                $style = new MapBaseStyle();
                $consistency = $symbolJSON['style'];
                // see comment above
                // esriSFSBackwardDiagonal | esriSFSCross | esriSFSDiagonalCross | esriSFSForwardDiagonal | esriSFSHorizontal | esriSFSNull | esriSFSSolid | esriSFSVertical
                $style->setStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::CONSISTENCY, $consistency);

                if (isset($symbolJSON['color']) && $symbolJSON['color']) {
                    $color = self::hexColorFromArray($symbolJSON['color'], $ignoreAlpha);
                    $style->setStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::FILLCOLOR, $color);
                }

                // ignored: outline
                break;

            case 'esriPMS':
                $style = new MapBaseStyle();
                // http://<mapservice-url>/<layerId1>/images/<imageUrl11>
                $url = $this->initArgs['BASE_URL']
                    . '/'. $this->currentFolder->getId()
                    . '/images/' . $symbolJSON['url'];
                $style->setStyleForTypeAndParam(MapStyle::POINT, MapStyle::ICON, $url);
                $style->setStyleForTypeAndParam(MapStyle::POINT, MapStyle::WIDTH, $symbolJSON['width']);
                $style->setStyleForTypeAndParam(MapStyle::POINT, MapStyle::HEIGHT, $symbolJSON['height']);

                // ignored: contentType, xoffset, yoffset, angle
                break;

        }

        if (isset($style)) {
            return $style;
        }
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
                if (isset($layerData['parentLayerId'])) {
                    $parentId = $layerData['parentLayerId'];
                }
                $folderId = $layerData['id'];
                if (isset($parentId) && isset($this->folders[$parentId])) {
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
                // TODO find out what can appear in labelingInfo and if we need it
                $labelingInfo = $data['drawingInfo']['labelingInfo'];

                // 
                if (isset($data['drawingInfo']['transparency'])) {
                    $alpha = 1 - $data['drawingInfo']['transparency'];
                }

                // in ArcMap, if transparency is defined on a layer group,
                // transparency levels of individual layers in the group is
                // ignored.  we assume this rule also applies to ArcGIS server.
                $ignoreAlpha = isset($alpha) && $alpha != 1;

                // http://servicesbeta2.esri.com/arcgis/sdk/rest/renderer.html
                $renderer = $data['drawingInfo']['renderer'];
                switch ($renderer['type']) {
                    case 'simple':
                        $symbol = $this->parseMapSymbol($renderer['symbol'], $ignoreAlpha);
                        if ($symbol) {
                            $this->currentFolder->setDefaultStyle($symbol);
                        }
                        break;
                    case 'uniqueValue':
                        $symbol = $this->parseMapSymbol($renderer['defaultSymbol'], $ignoreAlpha);
                        if ($symbol) {
                            $this->currentFolder->setDefaultStyle($symbol);
                        }

                        // TODO - future and only if necessary:
                        // ArcGIS 10 currently allows up to 3 fields for
                        // uniqueValues, but it less uncommon to set up
                        // symbology using multiple fields.
                        $this->currentFolder->setStyleField($renderer['field1']);
                        foreach ($renderer['uniqueValueInfos'] as $info) {
                            $symbol = $this->parseMapSymbol($info['symbol'], $ignoreAlpha);
                            if ($symbol) {
                                $this->currentFolder->addStyle($info['label'], $symbol);
                                $this->currentFolder->addStyleCriteria($info['label'], $info['value']);
                            }
                        }
                        break;
                    case 'classBreaks':
                        $symbol = $this->parseMapSymbol($renderer['defaultSymbol'], $ignoreAlpha);
                        if ($symbol) {
                            $this->currentFolder->setDefaultStyle($symbol);
                        }

                        $filterField = $renderer['field'];
                        $minValue = $renderer['minValue'];
                        foreach ($renderer['uniqueValueInfos'] as $info) {
                            $symbol = $this->parseMapSymbol($info['symbol'], $ignoreAlpha);

                            if ($symbol) {
                                $this->currentFolder->addStyle($info['label'], $symbol);
                                if (isset($info['classMinValue'])) {
                                    $minValue = $info['classMinValue'];
                                }

                                $this->currentFolder->addStyleCriteria(
                                    $info['label'],
                                    array($min, $info['classMaxValue']));
                            }

                            // assumes class breaks are sorted in increasing order
                            $minValue = $info['classMaxValue'];
                        }
                        break;
                }
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

            if (isset($data['displayFieldName'])
                && $data['displayFieldName'] != $this->currentFolder->getIdField())
            {
                // will set if we got here via layer query
                $displayField = $data['displayFieldName'];
            } else {
                $displayField = $this->currentFolder->getDisplayField();
            }

            foreach ($data['features'] as $featureInfo) {
                if (isset($featureInfo['foundFieldName'])) { // may be set if we got here via search
                    $displayField = $featureInfo['foundFieldName'];
                }
                $title = null;
                $subtitle = null;
                $placemarkId = null;
                $displayAttribs = array();

                $idField = strtoupper($idField);
                $displayField = strtoupper($displayField);

                // use human-readable field alias to construct feature details
                foreach ($featureInfo['attributes'] as $name => $value) {
                    $ucname = strtoupper($name);

                    if (isset($this->configTitleField) && $ucname == strtoupper($this->configTitleField)) {
                        $title = $value;
                    } elseif (!isset($title) && $ucname == $displayField) {
                        // have something to show in case they configured a bad title field
                        $title = $value;
                    }

                    if (isset($this->configSubtitleField) && $ucname == strtoupper($this->configSubtitleField)) {
                        $subtitle = $value;
                    }

                    if ($idField && $ucname == $idField) {
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
                                $geometry = new ArcGISPolyline($geometryJSON, $this->saveMemory);
                                break;
                            case 'esriGeometryPolygon':
                                $geometry = new ArcGISPolygon($geometryJSON, $this->saveMemory);
                                break;
                        }
                    }

                    if ($geometry && $this->saveMemory) {
                        $geometry = new MapBasePoint($geometry->getCenterCoordinate());
                    }

                    $placemark = new $this->placemarkClass($geometry, $this->initArgs);
                    $placemark->addCategoryId($this->feedId);

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

                    if (isset($subtitle)) {
                        $placemark->setSubtitle($subtitle);
                    }

                    // this calls addCategoryId on the placemark.
                    // this needs to be called first for placemarks that
                    // use the category ID to do other things like get
                    // supplementary geometry data.
                    $this->currentFolder->addPlacemark($placemark);
                    // project geometry at the end in case the placemark is
                    // a subclass that requires setup work to populate its own
                    // geometry
                    if ($this->projection && $placemark->getGeometry()) {
                        $placemark->setGeometry($this->projectGeometry($placemark->getGeometry()));
                    }

                    // suppress placemarks that don't have geometry data
                    if (!$placemark->getGeometry()) {
                        $this->currentFolder->removePlacemark($placemark);
                    }
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
