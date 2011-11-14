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
    
    protected function addPolygon(Placemark $placemark)
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

    private function getMarkerJS()
    {
        $template = $this->prepareJavascriptTemplate('GoogleJSMapMarkers', true);
        foreach ($this->markers as $i => $marker) {
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
                    $options .= "icon: '$icon',\n";
                }
            }

            $fields = $marker->getFields();
            $subtitle = $marker->getSubtitle();

            if (!$subtitle) {
                $subtitle = ''; // "null" will show up on screen
            }

            $template->appendValues(array(
                '___IDENTIFIER___' => $i,
                '___LATITUDE___' => $coord['lat'],
                '___LONGITUDE___' => $coord['lon'],
                '___TITLE___' => json_encode($marker->getTitle()),
                '___OPTIONS___' => $options,
                '___SUBTITLE___' => json_encode($subtitle),
                ));
        }

        $calloutScript = '';
        if (count($this->markers) + count($this->paths) + count($this->polygons) == 1) {
            // FIXME: this will break if there are no markers
            $calloutScript = "\nshowCalloutForPlacemark(placemark0);\n";
        }
        return $template->getScript() . $calloutScript;
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
        $footer = $this->prepareJavascriptTemplate('GoogleJSMapFooter');
        $footer->setValues(array(
            '___FULL_URL_PREFIX___' => FULL_URL_PREFIX,
            '___MARKER_SCRIPT___' => $this->getMarkerJS(),
            '___POLYGON_SCRIPT___' => $this->getPolygonJS(),
            '___PATH_SCRIPT___' => $this->getPathJS()));
        return $footer->getScript();
    }

}

