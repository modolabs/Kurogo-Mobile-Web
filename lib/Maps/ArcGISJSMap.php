<?php

// http://help.arcgis.com/EN/webapi/javascript/arcgis/help/jshelp_start.htm
// http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi_start.htm

class ArcGISJSMap extends JavascriptMapImageController {
    
    protected $markers = array();
    protected $paths = array();
    protected $polygons = array();

	private $moreLayers = array();
    
    private $apiVersion = '2.4';
    private $themeName = 'claro'; // claro, tundra, soria, nihilo

    protected $levelsOfDetail = array();    
    
    // TODO: fix zoom level problem
    public function __construct($baseURL)
    {
        if (is_array($baseURL)) {
            $this->baseURL = array_shift($baseURL);
        } else {
            $this->baseURL = $baseURL;
        }

        // TODO find a better way to reuse JSON parsing code for ArcGIS-related data
        $url = $this->baseURL.'?'.http_build_query(array('f' => 'json'));
        $content = file_get_contents($url);
        $data = json_decode($content, true);
        if (isset($data['spatialReference'], $data['spatialReference']['wkid'])) {
            $wkid = $data['spatialReference']['wkid'];
            $this->setMapProjection($wkid);
        }

        if (isset($data['tileInfo'], $data['tileInfo']['lods'])) {
            $this->levelsOfDetail = $data['tileInfo']['lods'];
        }

        if (is_array($baseURL)) {
            $this->addLayers($baseURL);
        }
    }
    
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

    ////////////// overlays ///////////////
    
    public function addLayers($moreLayers) {
        $this->moreLayers = array_merge($this->moreLayers, $moreLayers);
    }

    public function addPoint(Placemark $placemark)
    {
        parent::addPoint($placemark);

        $point = $placemark->getGeometry()->getCenterCoordinate();
        $style = $placemark->getStyle();

        // defaults
        $templateValues = array(
            '___SYMBOL_TYPE___' => 'SimpleMarkerSymbol'
            );

        if ($style !== null) {
            // two ways to style markers:
            if (($icon = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::ICON)) !== null) {
                // 1. icon image
                // http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi/picturemarkersymbol.htm
                $templateValues = array(
                    '___SYMBOL_TYPE___' => 'PictureMarkerSymbol',
                    '___SYMBOL_ARGS___' => '"'.$icon.'",20,20'
                    ); // TODO allow size (20, 20) above to be set

            } else {
                // 2. either all four of (color, size, outline, style) are set or zero
                // http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi/simplemarkersymbol.htm
                $color = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::COLOR);
                if ($color) {
                    $color = htmlColorForColorString($color);
                } else {
                    $color = 'FF0000';
                }

                $size = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::SIZE)
                    or $size = 12;
                // TODO there isn't yet a good way to get valid values for this from outside
                $shape = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::SHAPE)
                    or $shape = 'esri.symbol.SimpleMarkerSymbol.STYLE_CIRCLE';

                $templateValues['___SYMBOL_ARGS___'] = "$shape,$size,new esri.symbol.SimpleLineSymbol(),new dojo.Color(\"#$color\")";
            }
        }

        if (isset($this->mapProjector)) {
            $point = $this->mapProjector->projectPoint($point);
            list($x, $y) = MapProjector::getXYFromPoint($point);
            $templateValues['___X___'] = $x;
            $templateValues['___Y___'] = $y;

        } else {
            // TODO this might be reversed
            // if it is then we can get rid of some code
            $templateValues['___X___'] = $point['lat'];
            $templateValues['___Y___'] = $point['lon'];
        }

        // TODO use $placemark->getFields to populate Attributes
        $templateValues['___TITLE___'] = $placemark->getTitle();
        $subtitle = $placemark->getSubtitle();
        $templateValues['___DESCRIPTION___'] = $subtitle ? $subtitle : "";
        $templateValues['___IDENTIFIER___'] = count($this->markers);

        $this->markers[] = $templateValues;
    }

    public function addPath(Placemark $placemark)
    {
        parent::addPath($placemark);

        $polyline = $placemark->getGeometry();
        $style = $placemark->getStyle();

        // http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi/polyline.htm
        $jsonObj = array(
            'paths' => array($this->collapseAssociativePoints($polyline->getPoints())),
            'spatialReference' => array('wkid' => $this->mapProjection)
            );

        $templateValues = array(
            '___POLYLINE_SPEC___' => json_encode($jsonObj),
            );

        if ($style !== null) {
            // either three or zero parameters are all set

            // TODO there isn't yet a good way to get valid values for this from outside
            $consistency = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::CONSISTENCY)
                or $consistency = 'esri.symbol.SimpleFillSymbol.STYLE_SOLID';

            $color = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::COLOR);
            if ($color) {
                $color = htmlColorForColorString($color);
            } else {
                $color = 'FF0000';
            }

            $weight = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::WEIGHT)
                or $weight = 4;

            $templateValues['___SYMBOL_SPEC___'] = "$consistency,new dojo.Color(\"#$color\"),$weight";
        }
        $this->paths[] = $templateValues;
    }
    
    public function addPolygon(Placemark $placemark)
    {
        parent::addPolygon($placemark);

        // no style support for now

        $collapsedRings = array();
        $polygon = $placemark->getGeometry();
        foreach ($polygon->getRings() as $ring) {
            $collapsedRings[] = $this->collapseAssociativePoints($ring->getPoints());
        }

        $jsonParams = array(
            'rings' => $collapsedRings,
            'spatialReference' => array('wkid' => $this->mapProjection),
            );
        $json = json_encode($jsonParams);
        $this->polygons[] = array('___POLYGON_SPEC___' => $json);
    }

    ////////////// output ///////////////

    private function getPolygonJS()
    {
        if (!$this->polygons) {
            return '';
        }

        $template = $this->prepareJavascriptTemplate('ArcGISPolygons', true);
        foreach ($this->polygons as $polygon) {
            $template->appendValues($polygon);
        }

        return $template->getScript();
    }
    
    private function collapseAssociativePoints($points)
    {
        $result = array();
        // TODO: figure out when the arguments should be lon first
        foreach ($points as $point) {
            if (isset($this->mapProjector)) {
                $latlon = $this->mapProjector->projectPoint($point);
            } else {
                $latlon = $point;
            }
            $result[] = array($latlon['lon'], $latlon['lat']);
        }
        return $result;
    }

    private function getPathJS()
    {
        if (!$this->paths) {
            return '';
        }

        $template = $this->prepareJavascriptTemplate('ArcGISPaths', true);
        foreach ($this->paths as $templateValues) {
            $template->appendValues($templateValues);
        }

        return "var lineSymbol;\nvar polyline;\n".$template->getScript();
    }
    
    private function getMarkerJS()
    {
        if (!$this->markers) {
            return '';
        }

        $template = $this->prepareJavascriptTemplate('ArcGISPoints', true);
        foreach ($this->markers as $templateValues) {
            $template->appendValues($templateValues);
        }

        return "var point;\nvar pointSymbol;\nvar infoTemplate;\n".$template->getScript();
    }
    
    private function getCenterJS() {
        if ($this->mapProjector) {
            $xy = $this->mapProjector->projectPoint($this->center);
            list($x, $y) = MapProjector::getXYFromPoint($xy);
            $xy = array('x' => $x, 'y' => $y);
        } else {
            $xy = array('x' => $this->center['lon'], 'y' => $this->center['lat']);
        }
    
        $js = 'new esri.geometry.Point('.$xy['x'].', '.$xy['y'].', spatialRef)';
    
        return $js;
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
        $header = $this->prepareJavascriptTemplate('ArcGISJSMapHeader');
        $header->setValues(array(
            '___MAPELEMENT___' => $this->mapElement,
            ));
        return $header->getScript();
    }
    
    function getFooterScript() {
        // put dojo stuff in the footer since the header script
        // gets loaded before the included script

        $zoomLevel = $this->zoomLevel;
        $targetScale = oldPixelScaleForZoomLevel($zoomLevel);
        if ($this->levelsOfDetail) {
            foreach ($this->levelsOfDetail as $levelData) {
                if ($levelData['scale'] < $targetScale) {
                    break;
                } else {
                    $zoomLevel = $levelData['level'];
                }
            }
        }
        
        $moreLayersJS = '';
        foreach ($this->moreLayers as $anotherLayer) {
            $moreLayersJS .= <<<JS
    map.addLayer(new esri.layers.ArcGISDynamicMapServiceLayer("{$anotherLayer}", 1.0));
JS;
        }

        $footer = $this->prepareJavascriptTemplate('ArcGISJSMapFooter');
        $footer->setValues(array(
            '___FULL_URL_PREFIX___' => FULL_URL_PREFIX,
            '___API_URL___' => FULL_URL_BASE.API_URL_PREFIX."/map/projectPoint", // TODO don't hard code module id
            '___WKID___' => $this->mapProjection,
            '___MAPELEMENT___' => $this->mapElement,
            '___IMAGE_WIDTH___' => $this->imageWidth,
            '___IMAGE_HEIGHT___' => $this->imageHeight,
            '___ZOOMLEVEL___' => $zoomLevel,
            '___BASE_URL___' => $this->baseURL,
            '___MORE_LAYER_SCRIPT___' => $moreLayersJS,
            '___MARKER_SCRIPT___' => $this->getMarkerJS(),
            '___POLYGON_SCRIPT___' => $this->getPolygonJS(),
            '___PATH_SCRIPT___' => $this->getPathJS()));

        if ($this->mapProjector) {
            $xy = $this->mapProjector->projectPoint($this->center);
            list($x, $y) = MapProjector::getXYFromPoint($xy);
            $footer->setValues(array('___X___' => $x, '___Y___' => $y));
        } else {
            $footer->setValues(array(
                '___X___' => $this->center['lon'],
                '___Y___' => $this->center['lat']));
        }

        return $footer->getScript();
    }

}
