<?php

// http://help.arcgis.com/EN/webapi/javascript/arcgis/help/jshelp_start.htm
// http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi_start.htm

class ArcGISJSMap extends JavascriptMapImageController {
    
    protected $markers = array();
    protected $paths = array();
    protected $polygons = array();

	private $moreLayers = array();
    
    private $apiVersion = '2.1';
    private $themeName = 'claro'; // claro, tundra, soria, nihilo
    
    private $permanentZoomLevel = null;
    
    public function __construct($baseURL)
    {
        $this->baseURL = $baseURL;

        // TODO find a better way to reuse JSON parsing code for ArcGIS-related data
        $url = $this->baseURL.'?'.http_build_query(array('f' => 'json'));
        $content = file_get_contents($url);
        $data = json_decode($content, true);
        if (isset($data['spatialReference'], $data['spatialReference']['wkid'])) {
            $wkid = $data['spatialReference']['wkid'];
            $this->setMapProjection($wkid);
        }
    }

    public function setPermanentZoomLevel($zoomLevel)
    {
        $this->permanentZoomLevel = $zoomLevel;
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
    
    // TODO make the following two functions more concise

    public function addAnnotation($marker, $style=null, $title=null)
    {
        $filteredStyles = array();
        if ($style !== null) {
            // http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi/simplemarkersymbol.htm
            // either all four of (color, size, outline, style) are set or zero
            $color = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::COLOR)
                or $color = 'FF0000';
            $filteredStyles[] = 'color=#'.htmlColorForColorString($color);

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
                or $consistency = 'esri.symbol.SimpleFillSymbol.STYLE_SOLID';
            $filteredStyles[] = 'style='.$consistency;

            $color = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::COLOR)
                or $color = 'FF0000';
            $filteredStyles[] = 'color=#'.htmlColorForColorString($color);

            $weight = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::WEIGHT)
                or $weight = 4;
            $filteredStyles[] = 'weight='.strval($weight);
        }
        $styleString = implode('|', $filteredStyles);
        
        if (!isset($this->paths[$styleString])) {
        	$this->paths[$styleString] = array();
        }
        $this->paths[$styleString][] = $this->collapseAssociativePoints($points);
    }
    
    public function addPolygon($rings, $style=null) {
        $collapsedRings = array();
        foreach ($rings as $ring) {
            // TODO: change addPlacemark, addPoint, etc. to make the
            // incoming arguments more consistent
            if ($ring instanceof MapPolyline) {
                $ring = $ring->getPoints();
            }
            $collapsedRings[] = $this->collapseAssociativePoints($ring);
        }
        // no style support for now
        $this->polygons[] = $collapsedRings;
    }

    ////////////// output ///////////////

    private function getPolygonJS()
    {
        $template = $this->prepareJavascriptTemplate('ArcGISPolygons');
        foreach ($this->polygons as $rings) {
            $jsonParams = array(
                'rings' => $rings,
                'spatialReference' => array('wkid' => $this->mapProjection),
                );
            $json = json_encode($jsonParams);

            $template->appendValues(array('___POLYGON_SPEC___' => $json));
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
        $template = $this->prepareJavascriptTemplate('ArcGISPaths');
        foreach ($this->paths as $styleString => $paths) {
            // http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi/polyline.htm
            $jsonObj = array(
                'points' => $paths,
                'spatialReference' => array('wkid' => $this->mapProjection)
                );
            
            $json = json_encode($jsonObj);

            $templateValues = array('___POLYLINE_SPEC___' => $json);

            $styleParams = explode('|', $styleString);
            $styles = array();
            foreach ($styleParams as $styleParam) {
                $styleParts = explode('=', $styleParam);
                $styles[$styleParts[0]] = $styleParts[1];
            }
            if (count($styles)) {
                $templateValues['___SYMBOL_SPEC___']
                    = $styles['style'].','
                     .'new dojo.Color("'.$styles['color'].'"),'
                     .$styles['weight'];
            }
            
            $template->appendValues($templateValues);
        }

        return $template->getScript();
    }
    
    private function getMarkerJS()
    {
        $template = $this->prepareJavascriptTemplate('ArcGISPoints');
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
                $templateValues = array(
                    '___SYMBOL_TYPE___' => 'PictureMarkerSymbol',
                    '___SYMBOL_ARGS___' => '"'.$styles['icon'].'",20,20'
                    ); // TODO allow size (20, 20) above to be set
            
            } else {
                $templateValues = array('___SYMBOL_TYPE___' => 'SimpleMarkerSymbol');

                if (count($styles)) {
                    $templateValues['___SYMBOL_ARGS___']
                        = $styles['style'].','.$styles['size'].','
                         .'new dojo.Color("'.$styles['color'].'"),'
                         .'new esri.symbol.SimpleLineSymbol()';
                }
            }

            foreach ($points as $point) {
                if (isset($this->mapProjector)) {
                    $point = $this->mapProjector->projectPoint($point);
                    list($x, $y) = MapProjector::getXYFromPoint($point);
                    $templateValues['___X___'] = $x;
                    $templateValues['___Y___'] = $y;

                } else {
                    $templateValues['___X___'] = $point['lat'];
                    $templateValues['___Y___'] = $point['lon'];
                }

                $template->appendValues($templateValues);
            }
        }

        return $template->getScript();
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
        return $header->getScript();
    }
    
    function getFooterScript() {
        // put dojo stuff in the footer since the header script
        // gets loaded before the included script
        
        $zoomLevel = $this->permanentZoomLevel ? $this->permanentZoomLevel : $this->zoomLevel;
        $moreLayersJS = '';
        foreach ($this->moreLayers as $anotherLayer) {
            $moreLayersJS .= <<<JS
    map.addLayer(new esri.layers.ArcGISDynamicMapServiceLayer("{$anotherLayer}", 1.0));
JS;
        }

        $footer = $this->prepareJavascriptTemplate('ArcGISJSMapFooter');
        $footer->setValues(array(
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
