<?php

class GoogleJSMap extends JavascriptMapImageController {

// http://code.google.com/apis/maps/documentation/javascript/overlays.html

    private $locatesUser = false;

    protected $canAddAnnotations = true;
    protected $canAddPaths = true;
    protected $canAddPolygons = true;
    protected $canAddLayers = true;

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
            $lat = isset($coord['lat']) ? $coord['lat'] : $coord[0];
            $lon = isset($coord['lon']) ? $coord['lon'] : $coord[1];
            $gCoords[] .= "new google.maps.LatLng({$lat},{$lon})";
        }
        return implode(',', $gCoords);
    }
    
    private function getPolygonJS() {
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
        $script = <<<JS

var map;
var initLat = {$this->center['lat']};
var initLon = {$this->center['lon']};

function loadMap() {
    var mapImage = document.getElementById("{$this->mapElement}");
    mapImage.style.display = "inline-block";
    mapImage.style.width = "{$this->imageWidth}";
    mapImage.style.height = "{$this->imageHeight}";

    var initCoord = new google.maps.LatLng({$this->center['lat']}, {$this->center['lon']});
    var options = {
        zoom: {$this->zoomLevel},
        center: initCoord,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        mapTypeControl: false,
        panControl: false,
        zoomControl: false,
        scaleControl: false,
        streetViewControl: false
    };

    map = new google.maps.Map(mapImage, options);

    var zoomIn = document.getElementById("zoomin");
    google.maps.event.addDomListener(zoomIn, "click", function() {
        map.setZoom(map.getZoom() + 1);
    });
    
    var zoomOut = document.getElementById("zoomout");
    google.maps.event.addDomListener(zoomOut, "click", function() {
        map.setZoom(map.getZoom() - 1);
    });
    
    var recenter = document.getElementById("recenter");
    google.maps.event.addDomListener(recenter, "click", function() {
        map.setCenter(initCoord)
    });
}

function resizeMapOnContainerResize() {
    if (map) {
        google.maps.event.trigger(map, 'resize');
    }
}

JS;

        return $script;
    }

    public function getFooterScript() {

        $script = <<<JS

loadMap();

JS;

        if ($this->polygons) {
            $script .= $this->getPolygonJS();
        }

        if ($this->paths) {
            $script .= $this->getPathJS();
        }

        foreach ($this->markers as $index => $marker) {
            $title = 'marker';
            if (isset($marker['title'])) {
                $title = $marker['title'];
            }

            $script .= <<<JS

var marker{$index} = new google.maps.Marker({
    position: new google.maps.LatLng({$marker['lat']},{$marker['lon']}),
    map: map,
    title: "{$title}"
});

JS;
        }

        return $script;
    }

}

