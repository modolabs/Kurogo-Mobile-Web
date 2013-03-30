<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
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
    
    protected $cache;

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
    
    private function initCacheIfNeeded() {
        if($this->cache) {
            return $this->cache;
        }
        // init a DiskCache to save all user form data
        $this->cache = DataCache::factory("DataCache", array());
        // cache life time set to 1 hour
        $this->cache->setCacheLifetime(3600);
        $this->cache->setCacheGroup("GoogleJSMapImageSize");
    }

    private function getImageSize($url, $scale, & $width, & $height) {
        $key = md5($url);
        $this->initCacheIfNeeded();
        if($attribs = $this->cache->get($key)) {
            $width = $attribs[0] * $scale;
            $height = $attribs[1] * $scale;
        }else {
            $attribs = getimagesize($url);
            $width = $attribs[0] * $scale;
            $height = $attribs[1] * $scale;
            $this->cache->set($key, array($attribs[0], $attribs[1]));
        }
    }

    public function jsObjectForMarker($marker) {
        $geometry = $marker->getGeometry();
        $coord = $geometry->getCenterCoordinate();
        if (isset($this->mapProjector)) {
            $coord = $this->mapProjector->projectPoint($coord);
        }

        $title = json_encode($marker->getTitle());
        $options = array(
            "position: new google.maps.LatLng({$coord['lat']},{$coord['lon']})",
            "map: map",
            "title: $title",
            );

        $style = $marker->getStyle();
        if ($style) {
            if (($icon = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::ICON)) != null) {
                $width = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::WIDTH);
                $height = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::HEIGHT);
                $scale = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::SCALE);
                // scale set, need width and height
                if ($scale && (empty($width) || empty($height))) {
                    $this->getImageSize($icon, $scale, $width, $height);
                } else {
                    $width = $width * $scale;
                    $height = $height * $scale;
                }
                if ($width && $height) {
                    $displaySize = "new google.maps.Size($width, $height)";
                    $options[] = "icon: new google.maps.MarkerImage('$icon', null, null, null, $displaySize),\n";
                } else {
                    $options[] = "icon: '$icon',\n";
                }
            }
        }

        return 'new google.maps.Marker({'.implode(",\n", $options).'})';
    }
    
    public function jsObjectForPolygon($placemark) {
        $rings = $placemark->getGeometry()->getRings();
        $polyStrings = array();
        foreach ($rings as $ring) {
            $polyStrings[] = '['.$this->coordsToGoogleArray($ring->getPoints()).']';
        }

        $options = array(
            'map: map',
            'paths: ['.implode(',', $polyStrings).']',
            );

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

        return 'new google.maps.Polygon({'.implode(",\n", $options).'})';
    }

    public function jsObjectForPath($placemark) {
        $geometry = $placemark->getGeometry();
        $coordString = $this->coordsToGoogleArray($geometry->getPoints());

        $options = array(
            'path: ['.$coordString.']',
            'map: map',
            );

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

        return 'new google.maps.Polyline({'.implode(",\n", $options).'})';
    }

    ////////////// output ///////////////

    // url of script to include in <script src="...
    public function getIncludeScripts() {
        $sensor = $this->locatesUser ? 'true' : 'false';
        return array(HTTP_PROTOCOL . "://maps.google.com/maps/api/js?sensor={$sensor}");
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
            '___PLACEMARK_SCRIPT___' => $this->getPlacemarkJS(),
            '___MINZOOM___' => $this->minZoomLevel,
            '___MAXZOOM___' => $this->maxZoomLevel
        ));
        return $footer->getScript();
    }

}

