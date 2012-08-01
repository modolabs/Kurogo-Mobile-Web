<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class GoogleJSMap extends JavascriptMapImageController {

// http://code.google.com/apis/maps/documentation/javascript/overlays.html

    private $locatesUser = true;

    // these aren't arrays of actual objects;
    // they're mostly JavaScript snippets that will be concatenated later
    // which means once placemarks are added they can't be removed, at least right now
    protected $markers = array();
    protected $paths = array();
    protected $polygons = array();

    protected $placemarkCount = 0; // number of placemarks we've already processed

    public function setImageWidth($width) {
        if (strpos($width, '%') === FALSE) {
            $width = $width.'px';
        }
        $this->imageWidth = $width;
    }
    
    public function setImageHeight($height) {
        if (strpos($height, '%') === FALSE) {
            $height = $height.'px';
        }
        $this->imageHeight = $height;
    }

    public function setLocatesUser($locatesUser) {
        $this->locatesUser = ($locatesUser == true);
    }

    ////////////// overlays ///////////////

    protected function addPoint(Placemark $placemark)
    {
        parent::addPoint($placemark);
        $this->markers[] = $placemark;
    }

    protected function addPath(Placemark $placemark)
    {
        parent::addPath($placemark);
        $this->paths[] = $placemark;
    }
    
    protected function addPolygon(Placemark $placemark)
    {
        parent::addPolygon($placemark);
        $this->polygons[] = $placemark;
    }

    private function coordsToGoogleArray($coords) {
        $gCoords = array();
        foreach ($coords as $coord) {
            if (isset($this->mapProjector)) {
                $coord = $this->mapProjector->projectPoint($coord);
            }

            $lat = isset($coord['lat']) ? $coord['lat'] : $coord[0];
            $lon = isset($coord['lon']) ? $coord['lon'] : $coord[1];
            $gCoords[] .= "new google.maps.LatLng({$lat},{$lon})";
        }
        return implode(',', $gCoords);
    }

    private function getMarkerJS()
    {
        $template = $this->prepareJavascriptTemplate('GoogleJSMapMarkers', true);
        foreach ($this->markers as $marker) {
            $geometry = $marker->getGeometry();
            $coord = $geometry->getCenterCoordinate();
            if (isset($this->mapProjector)) {
                $coord = $this->mapProjector->projectPoint($coord);
            }

            // http://code.google.com/apis/maps/documentation/javascript/reference.html#MarkerOptions
            $options = '';
            $style = $marker->getStyle();
            if ($style) {
                if (($icon = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::ICON)) != null) {
                    $width = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::WIDTH);
                    $height = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::HEIGHT);
                    if ($width && $height) {
                        $displaySize = "new google.maps.Size($width, $height)";
                        $options .= "icon: new google.maps.MarkerImage('$icon', null, null, null, $displaySize),\n";
                    } else {
                        $options .= "icon: '$icon',\n";
                    }
                }
            }

            // TODO: what fields should show on the index page?
            $fields = $marker->getFields();

            $template->appendValues(array(
                '___ID___' => $marker->getId(),
                '___LATITUDE___' => $coord['lat'],
                '___LONGITUDE___' => $coord['lon'],
                '___TITLE___' => json_encode($marker->getTitle()),
                '___OPTIONS___' => $options,
                '___SUBTITLE___' => json_encode($marker->getSubtitle()),
                '___URL___' => $this->urlForPlacemark($marker),
                ));
        }

        return $template->getScript();
    }
    
    private function getPolygonJS()
    {
        $template = $this->prepareJavascriptTemplate('GoogleJSMapPolygons', true);
        foreach ($this->polygons as $placemark) {
            $rings = $placemark->getGeometry()->getRings();
            $polyStrings = array();
            foreach ($rings as $ring) {
                $polyStrings[] = '['.$this->coordsToGoogleArray($ring->getPoints()).']';
            }

            $options = array('map: map');
            $style = $placemark->getStyle();
            if ($style !== null) {
                if (($color = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::COLOR)) !== null) {
                    $options[] = 'strokeColor: "#'.htmlColorForColorString($color).'"';
                    $options[] = 'strokeOpacity: '.alphaFromColorString($color);
                }
                if (($color = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::FILLCOLOR)) !== null) {
                    $options[] = 'fillColor: "#'.htmlColorForColorString($color).'"';
                    $options[] = 'fillOpacity: '.alphaFromColorString($color);
                }
                if (($weight = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::WEIGHT)) !== null) {
                    $options[] = "strokeWeight: $weight";
                }
            }

            $coord = $placemark->getGeometry()->getCenterCoordinate();
            if (isset($this->mapProjector)) {
                $coord = $this->mapProjector->projectPoint($coord);
            }
            $template->appendValues(array(
                '___ID___' => $placemark->getId(),
                '___LATITUDE___' => $coord['lat'],
                '___LONGITUDE___' => $coord['lon'],
                '___MULTIPATHSTRING___' => implode(',', $polyStrings),
                '___TITLE___' => json_encode($placemark->getTitle()),
                '___OPTIONS___' => implode(',', $options),
                '___SUBTITLE___' => json_encode($placemark->getSubtitle()),
                '___URL___' => $this->urlForPlacemark($placemark),
                ));
        }
        return $template->getScript();
    }

    private function getPathJS()
    {
        $template = $this->prepareJavascriptTemplate('GoogleJSMapPaths', true);
        foreach ($this->paths as $placemark) {
            $geometry = $placemark->getGeometry();
            $coordString = $this->coordsToGoogleArray($geometry->getPoints());

            $options = array('map: map');
            $style = $placemark->getStyle();
            if ($style) {
                if (($color = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::COLOR)) !== null) {
                    $options[] = 'strokeColor: "#'.htmlColorForColorString($color).'"';
                    $options[] = 'strokeOpacity: '.alphaFromColorString($color);
                }
                if ($style && ($weight = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::WEIGHT)) !== null) {
                    $options[] = "strokeWeight: $weight";
                }
            }
            $propString = implode(',', $options);

            $subtitle = $placemark->getSubtitle();
            if (!$subtitle) {
                $subtitle = ''; // "null" will show up on screen
            }

            $coord = $geometry->getCenterCoordinate();
            if (isset($this->mapProjector)) {
                $coord = $this->mapProjector->projectPoint($coord);
            }
            $template->appendValues(array(
                '___ID___' => $placemark->getId(),
                '___LATITUDE___' => $coord['lat'],
                '___LONGITUDE___' => $coord['lon'],
                '___PATHSTRING___' => $coordString,
                '___TITLE___' => json_encode($placemark->getTitle()),
                '___OPTIONS___' => implode(',', $options),
                '___SUBTITLE___' => json_encode($placemark->getSubtitle()),
                '___URL___' => $this->urlForPlacemark($placemark),
                ));
        }
        return $template->getScript();
    }

    ////////////// output ///////////////

    // url of script to include in <script src="...
    public function getIncludeScripts() {
        $sensor = $this->locatesUser ? 'true' : 'false';
        return array("http://maps.google.com/maps/api/js?sensor={$sensor}");
    }

    public function getInternalScripts() {
        return array(
            '/common/javascript/maps.js',
            '/common/javascript/lib/infobox-1.1.11.js',
            );
    }

    public function getFooterScript() {
        $footer = $this->prepareJavascriptTemplate('GoogleJSMapFooter');
        if (isset($this->mapProjector)) {
            $center = $this->mapProjector->projectPoint($this->center);
        } else {
            $center = $this->center;
        }
        $options = '';
        if (isset($this->initOptions["onShowCallout"]) && $this->initOptions["onShowCallout"]) {
            $options .= "onShowCallout: {$this->initOptions['onShowCallout']},";
        }
        $footer->setValues(array(
            '___MAPELEMENT___' => $this->mapElement,
            '___CENTER_LATITUDE___' => $center['lat'],
            '___CENTER_LONGITUDE___' => $center['lon'],
            '___ZOOMLEVEL___' => $this->zoomLevel,
            '___OPTIONS___' => $options,
            '___MARKER_SCRIPT___' => $this->getMarkerJS(),
            '___POLYGON_SCRIPT___' => $this->getPolygonJS(),
            '___PATH_SCRIPT___' => $this->getPathJS(),
            '___MINZOOM___' => $this->minZoomLevel,
            '___MAXZOOM___' => $this->maxZoomLevel
        ));
        return $footer->getScript();
    }

}

