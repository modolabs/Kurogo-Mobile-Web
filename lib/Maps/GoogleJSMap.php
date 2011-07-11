<?php

class GoogleJSMap extends JavascriptMapImageController {

// http://code.google.com/apis/maps/documentation/javascript/overlays.html

    private $locatesUser = false;

    protected $markers = array();
    protected $paths = array();
    protected $polygons = array();

    protected $mapProjection = 4326;
    
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

    public function addAnnotation($marker, $style=null, $title=null)
    {
        if ($title) {
            $marker['title'] = $title;
        }

        $this->markers[] = $marker;
    }

    public function addPath($points, $style=null)
    {
        if ($style === null) {
            $style = new EmptyMapStyle();
        }
        
        $path = array('coordinates' => $points);
        
        $pathStyle = array();
        if (($color = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::COLOR)) !== null) {
            $pathStyle['strokeColor'] = '"#'.htmlColorForColorString($color).'"';
            if (strlen($color) == 8) {
                $alphaHex = substr($color, 0, 2);
                $alpha = hexdec($alphaHex) / 256;
                $pathStyle['strokeOpacity'] = round($alpha, 2);
            }
        }
        if (($weight = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::WEIGHT)) !== null) {
            $pathStyle['strokeWeight'] = $weight;
        }

        $path['style'] = $pathStyle;
        
        $this->paths[] = $path;
    }
    
    public function addPolygon($rings, $style=null)
    {
        if ($style === null) {
            $style = new EmptyMapStyle();
        }
        
    	$polygon = array('rings' => $rings);

        $pathStyle = array();
        if (($color = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::COLOR)) !== null) {
            $pathStyle['strokeColor'] = '"#'.htmlColorForColorString($color).'"';
            if (strlen($color) == 8) {
                $alphaHex = substr($color, 0, 2);
                $alpha = hexdec($alphaHex) / 256;
                $pathStyle['strokeOpacity'] = round($alpha, 2);
            }
        }
        if (($color = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::FILLCOLOR)) !== null) {
            $pathStyle['fillColor'] = '"#'.htmlColorForColorString($color).'"';
            if (strlen($color) == 8) {
                $alphaHex = substr($color, 0, 2);
                $alpha = hexdec($alphaHex) / 256;
                $pathStyle['fillOpacity'] = round($alpha, 2);
            }
        }
        if (($weight = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::WEIGHT)) !== null) {
            $pathStyle['strokeWeight'] = $weight;
        }
        $polygon['style'] = $pathStyle;
        
    	$this->polygons[] = $polygon;
    }

    private static function coordsToGoogleArray($coords) {
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

        $js = "var polypaths;\nvar polygon;";

        foreach ($this->polygons as $polygon) {
            $polyStrings = array();
            foreach ($polygon['rings'] as $ring) {
                $polyString[] = '['.self::coordsToGoogleArray($ring).']';
            }
            $multiPathString = implode(',', $polyString);

            $properties = array('paths: polypaths');
            foreach ($polygon['style'] as $attrib => $value) {
                $properties[] = "$attrib: $value";
            }
            $propString = implode(',', $properties);

            $js .= <<<JS

polypaths = [{$multiPathString}];
polygon = new google.maps.Polygon({{$propString}});
polygon.setMap(map);

JS;
        }

        return $js;
    }

    private function getPathJS() {
        if (!$this->paths) {
            return '';
        }

        $js = "var coordinates;\nvar path;";
        foreach ($this->paths as $path) {
            $coordString = self::coordsToGoogleArray($path['coordinates']);

            $properties = array('path: coordinates');
            foreach ($path['style'] as $attrib => $value) {
                $properties[] = "$attrib: $value";
            }
            $propString = implode(',', $properties);

            $js .= <<<JS

coordinates = [{$coordString}];
path = new google.maps.Polyline({{$propString}});
path.setMap(map);

JS;

        }
        return $js;
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
            '___INITIAL_LATITUDE___' => $center['lat'],
            '___INITIAL_LONGITUDE___' => $center['lon'],
            '___MAPELEMENT___' => $this->mapElement,
            '___CENTER_LATITUDE___' => $center['lat'],
            '___CENTER_LONGITUDE___' => $center['lon'],
            '___IMAGE_WIDTH___' => $this->imageWidth,
            '___IMAGE_HEIGHT___' => $this->imageHeight,
            '___ZOOMLEVEL___' => $this->zoomLevel,
            ));
        
        return $template->getScript();
    }

    public function getFooterScript() {
        $markers = $this->prepareJavascriptTemplate('GoogleJSMapMarkers');
        foreach ($this->markers as $index => $marker) {
            $title = 'marker';
            if (isset($marker['title'])) {
                $title = $marker['title'];
            }

            if (isset($this->mapProjector)) {
                $coord = $this->mapProjector->projectPoint($marker);
            } else {
                $coord = $marker;
            }

            $markers->appendValues(array(
                '___IDENTIFIER___' => $index,
                '___LATITUDE___' => $coord['lat'],
                '___LONGITUDE___' => $coord['lon'],
                '___TITLE___' => $title));
        }

        $footer = $this->prepareJavascriptTemplate('GoogleJSMapFooter');
        $footer->setValues(array(
            '___MARKER_SCRIPT___' => $markers->getScript(),
            '___POLYGON_SCRIPT___' => $this->getPolygonJS(),
            '___PATH_SCRIPT___' => $this->getPathJS()));
        return $footer->getScript();
    }

}

