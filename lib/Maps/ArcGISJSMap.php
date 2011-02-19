<?php

// http://help.arcgis.com/EN/webapi/javascript/arcgis/help/jshelp_start.htm
// http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi_start.htm

require_once 'MapProjector.php';

class ArcGISJSMap extends JavascriptMapImageController {
    
    const DEFAULT_PROJECTION = 4326;
    
    // capabilities
    protected $canAddAnnotations = true;
    protected $canAddPaths = true;
    protected $canAddLayers = true;
    protected $canAddPolygons = true;
    protected $supportsProjections = true;
    
    protected $markers = array();
    protected $paths = array();
    protected $polygons = array();

	private $moreLayers = array();
    
    private $apiVersion = '2.1';
    private $themeName = 'claro'; // claro, tundra, soria, nihilo
    
    private $permanentZoomLevel = null;
    
    // map image projection data
    private $projspec = NULL;
    private $mapProjector;
    
    public function __construct($baseURL)
    {
        $this->baseURL = $baseURL;
        $arcgisParser = ArcGISDataController::parserFactory($this->baseURL);
        $wkid = $arcgisParser->getProjection();
        $this->mapProjector = new MapProjector();
        $this->mapProjector->setDstProj($wkid);
    }
    
    public function setDataProjection($proj)
    {
        $this->mapProjector->setSrcProj($proj);
    }
    
    public function setPermanentZoomLevel($zoomLevel)
    {
        $this->permanentZoomLevel = $zoomLevel;
    }

    ////////////// overlays ///////////////
    
    public function addLayers($moreLayers) {
        $this->moreLayers = array_merge($this->moreLayers, $moreLayers);
    }
    
    // TODO make the following two functions more concise

    public function addAnnotation($marker, $style=null, $title=null)
    {
        $filteredStyles = array();
        if ($style !== null) {
            // http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi/simplemarkersymbol.htm
            // either all four of (color, size, outline, style) are set or zero
            $color = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::COLOR)
                or $color = 'FF0000';
            $filteredStyles[] = 'color=#'.substr($color, 0, 6);

            $size = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::SIZE)
                or $size = 12;
            $filteredStyles[] = 'size='.strval($size);

            // TODO there isn't yet a good way to get valid values for this from outside
            $shape = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::SHAPE)
                or $shape = 'esri.symbol.Simple.STYLE_CIRCLE';
            $filteredStyles[] = 'style='.$shape;

            // if they use an image
            // http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi/picturemarkersymbol.htm
            if (($icon = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::ICON)) !== null) {
                $filteredStyles[] = 'icon='.$icon;
            }
        }
        $styleString = implode('|', $filteredStyles);
        if (!isset($this->markers[$styleString])) {
        	$this->markers[$styleString] = array();
        }
        
        $this->markers[$styleString][] = $marker;
    }

    public function addPath($points, $style=null)
    {
        $filteredStyles = array();
        if ($style !== null) {
            // either three or zero parameters are all set

            // TODO there isn't yet a good way to get valid values for this from outside
            $consistency = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::CONSISTENCY)
                or $consistency = 'STYLE_SOLID';
            $filteredStyles[] = 'style='.$consistency;

            $color = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::COLOR)
                or $color = 'FF0000';
            $filteredStyles[] = 'color=#'.substr($color, 0, 6);

            $weight = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::WEIGHT)
                or $weight = 4;
            $filteredStyles[] = 'width='.strval($width);
        }
        $styleString = implode('|', $filteredStyles);
        
        if (!isset($this->paths[$styleString])) {
        	$this->paths[$styleString] = array();
        }
        $this->paths[$styleString][] = $points;
    }
    
    public function addPolygon($rings, $style=null) {
        // no style support for now
        $this->polygons[] = $rings;
    }

    ////////////// output ///////////////

    private function getPolygonJS()
    {
        $js = '';
    
        foreach ($this->polygons as $rings) {
            $jsonParams = array(
                'rings' => $rings,
                'spatialReference' => array('wkid' => $this->mapProjection),
                );
            $json = json_encode($jsonParams);

            $js .= <<<JS

    polygon = new esri.geometry.Polygon({$json});
    map.graphics.add(new esri.Graphic(polygon, fillSymbol));

JS;
        }
        
        if ($js) {
    
            $js = <<<JS

    var strokeSymbol = new esri.symbol.SimpleLineSymbol();
    var color = new dojo.Color([255, 0, 0, 0.5]);
    var fillSymbol = new esri.symbol.SimpleFillSymbol(esri.symbol.SimpleFillSymbol.STYLE_SOLID, strokeSymbol, color);
    var polygon;
    $js

JS;
        }

        return $js;
    }

    private function getPathJS()
    {
        $js = '';
    
        foreach ($this->paths as $styleString => $paths) {
            $styleParams = explode('|', $styleString);
            $styles = array();
            foreach ($styleParams as $styleParam) {
                $styleParts = explode('=', $styleParam);
                $styles[$styleParts[0]] = $styleParts[1];
            }
            if (count($styles)) {
                $symbolArgs = $styles['style'].','
                             .'new dojo.Color("'.$styles['color'].'"),'
                             .$styles['weight'];
            } else {
                $symbolArgs = '';
            }
            
            // http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi/polyline.htm
            $jsonObj = array(
                'points' => $paths,
                'spatialReference' => array('wkid' => $this->mapProjection)
                );
            
            $json = json-decode($jsonObj);

            $js .= <<<JS

    lineSymbol = new esri.symbol.SimpleLineSymbol({$symbolArgs});
    polyline = new esri.geometry.Polyline({$json});
    map.graphics.add(new esri.Graphic(polyline, lineSymbol));

JS;

        }
        
        if ($js) {
    
            $js = <<<JS

    var lineSymbol;
    var polyline;
    $js

JS;
        }

        return $js;
    }
    
    private function getMarkerJS()
    {
        $js = '';
    
        foreach ($this->markers as $styleString => $points) {
            $styles = array();
            if ($styleString) {
                $styleParams = explode('|', $styleString);
                foreach ($styleParams as $styleParam) {
                    $styleParts = explode('=', $styleParam);
                    $styles[$styleParts[0]] = $styleParts[1];
                }
            }
            if (isset($styles['icon'])) {
                $symbolType = 'PictureMarkerSymbol';
                $symbolArgs = '"'.$styles['icon'].'",20,20'; // TODO allow size to be set
            
            } else {
                $symbolType = 'SimpleMarkerSymbol';
                if (count($styles)) {
                    $symbolArgs = $styles['style'].','
                                 .$styles['size'].','
                                 .'new dojo.Color("'.$styles['color'].'"),'
                                 .'new esri.symbol.SimpleLineSymbol()';
                } else {
                    $symbolArgs = '';
                }
            }

            foreach ($points as $point) {
                if ($this->mapProjector) {
                    $point = $this->mapProjector->projectPoint($point);
                }
                else {
                    $point = array('x' => $point['lat'], 'y' => $point['lon']);
                }
            
                $js .= <<<JS

    point = new esri.geometry.Point({$point['x']}, {$point['y']}, spatialRef);
    pointSymbol = new esri.symbol.{$symbolType}({$symbolArgs});
    map.graphics.add(new esri.Graphic(point, pointSymbol));

JS;
            }
        }
        
        if ($js) {
            $js = <<<JS
    var pointSymbol;
    var point;
    $js

JS;
        }
    
        
        return $js;
    }
    
    private function getCenterJS() {
        if ($this->mapProjector) {
            $xy = $this->mapProjector->projectPoint($this->center);
        }
        else {
            $xy = array('x' => $this->center['lat'], 'y' => $this->center['lon']);
        }
    
        $js = 'new esri.geometry.Point('.$xy['x'].', '.$xy['y'].', spatialRef)';
    
        return $js;
    }
    
    private function getSpatialRefJS() {
        $wkid = $this->mapProjector->getDstProj();
        return "var spatialRef = new esri.SpatialReference({ wkid: $wkid });";
    }

    // url of script to include in <script src="...
    function getIncludeScripts() {
        return array('http://serverapi.arcgisonline.com/jsapi/arcgis/?v='.$this->apiVersion.'compact');
    }
    
    function getIncludeStyles() {
        return 'http://serverapi.arcgisonline.com/jsapi/arcgis/'
               .$this->apiVersion.'/js/dojo/dijit/themes/'
               .$this->themeName.'/'.$this->themeName.'.css';
    }

    function getHeaderScript() {
        return '';
    }

    function getFooterScript() {
        $imageWidth = $this->imageWidth;
        if (strpos($imageWidth, '%') === FALSE) {
            $imageWidth = $imageWidth.'px';
        }
        $imageHeight = $this->imageHeight;
        if (strpos($imageHeight, '%') === FALSE) {
            $imageHeight = $imageHeight.'px';
        }

        // put dojo stuff in the footer since the header script
        // gets loaded before the included script
        
        $zoomLevel = $this->permanentZoomLevel ? $this->permanentZoomLevel : $this->zoomLevel;
        $moreLayersJS = '';
        foreach ($this->moreLayers as $anotherLayer) {
            $moreLayersJS .= <<<JS
    map.addLayer(new esri.layers.ArcGISDynamicMapServiceLayer("{$anotherLayer}", 1.0));
JS;
        }

        $script = <<<JS

hideMapTabChildren();

dojo.require("esri.map");
dojo.addOnLoad(loadMap);

var map;

function loadMap() {
    var mapImage = document.getElementById("mapimage");
    mapImage.style.display = "inline-block";
    mapImage.style.width = "{$imageWidth}";
    mapImage.style.height = "{$imageHeight}";
    
    map = new esri.Map("{$this->mapElement}");
    var basemapURL = "{$this->baseURL}";
    var basemap = new esri.layers.ArcGISTiledMapServiceLayer(basemapURL);

    map.addLayer(basemap);
    {$moreLayersJS}

    dojo.connect(map, "onLoad", plotFeatures);
}

function plotFeatures() {

    {$this->getSpatialRefJS()}
{$this->getPolygonJS()}
{$this->getPathJS()}
{$this->getMarkerJS()}

    map.centerAndZoom({$this->getCenterJS()}, {$zoomLevel})
}

JS;

        return $script;
    }

}

