<?php

class GoogleJSMap extends JavascriptMapImageController {

// http://code.google.com/apis/maps/documentation/javascript/overlays.html

    private $locatesUser = false;

    // these aren't arrays of actual objects;
    // they're mostly JavaScript snippets that will be concatenated later
    // which means once placemarks are added they can't be removed, at least right now
    protected $markers = array();
    protected $paths = array();
    protected $polygons = array();

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

    public function addPoint(Placemark $placemark)
    {
        parent::addPoint($placemark);

        $geometry = $placemark->getGeometry();
        $coord = $geometry->getCenterCoordinate();
        if (isset($this->mapProjector)) {
            $coord = $this->mapProjector->projectPoint($coord);
        }

        // http://code.google.com/apis/maps/documentation/javascript/reference.html#MarkerOptions
        $options = '';
        $style = $placemark->getStyle();
        if ($style) {
            if (($icon = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::ICON)) != null) {
                $options .= "icon: '$icon',\n";
            }
        }

        $fields = $placemark->getFields();
        if ($fields && isset($fields['description'])) {
            // TODO truncate overly long descriptions
            $subtitle = $fields['description'];
        } else {
            $subtitle = $placemark->getSubtitle();
        }

        if (!$subtitle) {
            $subtitle = ''; // "null" will show up on screen
        }

        $values = array(
            '___IDENTIFIER___' => count($this->markers),
            '___LATITUDE___' => $coord['lat'],
            '___LONGITUDE___' => $coord['lon'],
            '___TITLE___' => json_encode($placemark->getTitle()),
            '___OPTIONS___' => $options,
            '___SUBTITLE___' => json_encode($subtitle),
            );

        $this->markers[] = $values;
    }

    public function addPath(Placemark $placemark)
    {
        parent::addPath($placemark);

        $geometry = $placemark->getGeometry();
        $coordString = $this->coordsToGoogleArray($geometry->getPoints());

        $properties = array('path: coordinates');
        $style = $placemark->getStyle();
        if ($style) {
            if (($color = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::COLOR)) !== null) {
                $properties[] = 'strokeColor: "#'.htmlColorForColorString($color).'"';
                if (strlen($color) == 8) {
                    $alphaHex = substr($color, 0, 2);
                    $alpha = hexdec($alphaHex) / 256;
                    $properties[] = 'strokeOpacity: '.round($alpha, 2);
                }
            }
            if ($style && ($weight = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::WEIGHT)) !== null) {
                $properties[] = "strokeWeight: $weight";
            }
        }
        $propString = implode(',', $properties);

        $this->paths[] = <<<JS

coordinates = [{$coordString}];
path = new google.maps.Polyline({{$propString}});
path.setMap(map);

JS;
    }
    
    public function addPolygon(Placemark $placemark)
    {
        parent::addPolygon($placemark);

        $rings = $placemark->getGeometry()->getRings();
        $polyStrings = array();
        foreach ($rings as $ring) {
            $polyString[] = '['.$this->coordsToGoogleArray($ring->getPoints()).']';
        }
        $multiPathString = implode(',', $polyString);

        $properties = array('paths: polypaths');

        $style = $placemark->getStyle();
        if ($style !== null) {
            if (($color = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::COLOR)) !== null) {
                $properties[] = 'strokeColor: "#'.htmlColorForColorString($color).'"';
                if (strlen($color) == 8) {
                    $alphaHex = substr($color, 0, 2);
                    $alpha = hexdec($alphaHex) / 256;
                    $properties[] = 'strokeOpacity: '.round($alpha, 2);
                }
            }
            if (($color = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::FILLCOLOR)) !== null) {
                $properties[] = 'fillColor: "#'.htmlColorForColorString($color).'"';
                if (strlen($color) == 8) {
                    $alphaHex = substr($color, 0, 2);
                    $alpha = hexdec($alphaHex) / 256;
                    $properties[] = 'fillOpacity: '.round($alpha, 2);
                }
            }
            if (($weight = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::WEIGHT)) !== null) {
                $properties[] = "strokeWeight: $weight";
            }
        }

        $propString = implode(',', $properties);

        $this->polygons[] = <<<JS

polypaths = [{$multiPathString}];
polygon = new google.maps.Polygon({{$propString}});
polygon.setMap(map);

JS;
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
    
    private function getPolygonJS() {
        if (!$this->polygons) {
            return '';
        }
        return "var polypaths;\nvar polygon;" . implode('', $this->polygons);
    }

    private function getPathJS() {
        if (!$this->paths) {
            return '';
        }
        return "var coordinates;\nvar path;" . implode('', $this->paths);
    }

    ////////////// output ///////////////

    // url of script to include in <script src="...
    public function getIncludeScripts() {
        return array('http://maps.google.com/maps/api/js?sensor='
             . ($this->locatesUser ? 'true' : 'false'));
    }

    public function getHeaderScript() {
        if (isset($this->mapProjector)) {
            $center = $this->mapProjector->projectPoint($this->center);
        } else {
            $center = $this->center;
        }
        $template = $this->prepareJavascriptTemplate('GoogleJSMapHeader');
        $template->setValues(array(
            '___FULL_URL_PREFIX___' => FULL_URL_PREFIX,
            '___INITIAL_LATITUDE___' => $center['lat'],
            '___INITIAL_LONGITUDE___' => $center['lon'],
            '___MAPELEMENT___' => $this->mapElement,
            '___CENTER_LATITUDE___' => $center['lat'],
            '___CENTER_LONGITUDE___' => $center['lon'],
            //'___IMAGE_WIDTH___' => $this->imageWidth,
            //'___IMAGE_HEIGHT___' => $this->imageHeight,
            '___ZOOMLEVEL___' => $this->zoomLevel,
            ));
        
        return $template->getScript();
    }

    public function getFooterScript() {
        $markers = $this->prepareJavascriptTemplate('GoogleJSMapMarkers', true);
        foreach ($this->markers as $index => $marker) {
            $markers->appendValues($marker);
        }

        $footer = $this->prepareJavascriptTemplate('GoogleJSMapFooter');
        $footer->setValues(array(
            '___FULL_URL_PREFIX___' => FULL_URL_PREFIX,
            '___MARKER_SCRIPT___' => $markers->getScript(),
            '___POLYGON_SCRIPT___' => $this->getPolygonJS(),
            '___PATH_SCRIPT___' => $this->getPathJS()));
        return $footer->getScript();
    }

}

