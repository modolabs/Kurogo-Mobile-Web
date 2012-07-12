<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
    public function init($args) {
        parent::init($args);

        $baseURL = $args['BASE_URL'];

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
        $this->markers[] = $placemark;
    }

    public function addPath(Placemark $placemark)
    {
        parent::addPath($placemark);
        $this->paths[] = $placemark;
    }
    
    public function addPolygon(Placemark $placemark)
    {
        parent::addPolygon($placemark);
        $this->polygons[] = $placemark;
    }

    ////////////// output ///////////////

    private function dojoColorFromHex($hex) {
        $colorParts = array();
        foreach (str_split($hex) as $colorPart) {
            $colorParts[] = hexdec($colorPart);
        }
        if (count($colorParts) == 4) {
            // move alpha to the end
            array_unshift($colorParts, array_pop($colorParts));
        }
        return implode(',', $colorParts);
    }

    private function getPolygonJS()
    {
        $template = $this->prepareJavascriptTemplate('ArcGISPolygons', true);

        foreach ($this->polygons as $placemark) {

            $style = $placemark->getStyle();
            if ($style !== null) {
                if (($color = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::COLOR)) !== null) {
                    $strokeColor = $this->dojoColorFromHex($color);
                }
                if (($color = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::FILLCOLOR)) !== null) {
                    $fillColor = $this->dojoColorFromHex($color);
                }
                if (($weight = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::WEIGHT)) !== null) {
                    $strokeWeight = $weight;
                }
            }

            if (!isset($strokeColor)) {
                $strokeColor = '[0, 0, 0]';
            }
            if (!isset($fillColor)) {
                $fillColor = '[0, 0, 0, 0.5]';
            }
            if (!isset($strokeWeight)) {
                $strokeWeight = 2;
            }

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
            $template->appendValues(array(
                '___ID___' => $placemark->getId(),
                '___POLYGON_SPEC___' => $json,
                '___FILL_COLOR___' => $fillColor,
                '___STROKE_COLOR___' => $strokeColor,
                '___STROKE_WEIGHT___' => $strokeWeight,
                '___TITLE___' => json_encode($placemark->getTitle()),
                '___SUBTITLE___' => json_encode($placemark->getSubtitle()),
                '___URL___' => $this->urlForPlacemark($placemark),
                ));
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
        $template = $this->prepareJavascriptTemplate('ArcGISPaths', true);
        foreach ($this->paths as $placemark) {
            $polyline = $placemark->getGeometry();
            $style = $placemark->getStyle();

            // http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi/polyline.htm
            $jsonObj = array(
                'paths' => array($this->collapseAssociativePoints($polyline->getPoints())),
                'spatialReference' => array('wkid' => $this->mapProjection)
                );

            $templateValues = array(
                '___ID___' => $placemark->getId(),
                '___POLYLINE_SPEC___' => json_encode($jsonObj),
                '___TITLE___' => json_encode($placemark->getTitle()),
                '___SUBTITLE___' => json_encode($placemark->getSubtitle()),
                '___URL___' => $this->urlForPlacemark($placemark),
                '___SYMBOL_SPEC___' => '',
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
            $template->appendValues($templateValues);
        }

        return $template->getScript();
    }
    
    private function getMarkerJS()
    {
        $template = $this->prepareJavascriptTemplate('ArcGISPoints', true);
        foreach ($this->markers as $placemark) {
            $point = $placemark->getGeometry()->getCenterCoordinate();
            $style = $placemark->getStyle();

            // defaults
            $templateValues = array(
                '___SYMBOL_TYPE___' => 'SimpleMarkerSymbol',
                '___SYMBOL_ARGS___' => '',
                '___URL___' => $this->urlForPlacemark($placemark),
                );

            if ($style !== null) {
                // two ways to style markers:
                if (($icon = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::ICON)) !== null) {
                    // 1. icon image
                    // http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi/picturemarkersymbol.htm
                    $templateValues['___SYMBOL_TYPE___'] = 'PictureMarkerSymbol';
                    $width = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::WIDTH)
                        or $width = 20;
                    $height = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::HEIGHT)
                        or $height = 20;
                    $templateValues['___SYMBOL_ARGS___'] = "'$icon',$width,$height";

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
            $templateValues['___ID___'] = $placemark->getId();
            $templateValues['___TITLE___'] = json_encode($placemark->getTitle());
            $templateValues['___SUBTITLE___'] = json_encode($placemark->getSubtitle());
            $templateValues['___URL___'] = $this->urlForPlacemark($placemark);

            $template->appendValues($templateValues);
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

    function getInternalScripts() {
        return array('/common/javascript/maps.js');
    }
    
    function getIncludeStyles() {
        return 'http://serverapi.arcgisonline.com/jsapi/arcgis/'
               .$this->apiVersion.'/js/dojo/dijit/themes/'
               .$this->themeName.'/'.$this->themeName.'.css';
    }

    function getMinimumLatSpan() {
        return oldPixelScaleForZoomLevel($this->maxZoomLevel);
    }

    function getMinimumLonSpan() {
        return oldPixelScaleForZoomLevel($this->maxZoomLevel);
    }
    
    function getFooterScript() {
        $zoomLevel = $this->zoomLevel;
        $targetScale = oldPixelScaleForZoomLevel($zoomLevel);
        if ($this->levelsOfDetail) {
            // TODO: if all zoom levels fail this test, the zoom level will
            // revert to the internal powers-of-two style zoom level i.e. not 
            // necessarily what we want
            foreach ($this->levelsOfDetail as $levelData) {
                if ($levelData['scale'] < $targetScale) {
                    break;
                } else {
                    $zoomLevel = $levelData['level'];
                }
            }
        }

        $moreLayers = array();
        foreach ($this->moreLayers as $layer) {
            $moreLayers[] = '"'.$layer.'"';
        }

        $footer = $this->prepareJavascriptTemplate('ArcGISJSMapFooter');
        $footer->setValues(array(
            '___WKID___' => $this->mapProjection,
            '___MAPELEMENT___' => $this->mapElement,
            '___IMAGE_WIDTH___' => $this->imageWidth,
            '___IMAGE_HEIGHT___' => $this->imageHeight,
            '___ZOOMLEVEL___' => $zoomLevel,
            '___BASE_URL___' => $this->baseURL,
            '___MORE_LAYER_SCRIPT___' => '['.implode(',', $moreLayers).']',
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
