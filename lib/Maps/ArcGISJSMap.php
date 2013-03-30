<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
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

    private $tiledLayer;
    private $dynamicLayers = array();

    private $apiVersion = '3.3';
    private $themeName = 'claro'; // claro, tundra, soria, nihilo

    protected $levelsOfDetail = array();    
    
    // TODO: fix zoom level problem
    public function init($args) {
        parent::init($args);

        $baseURLs = $args['BASE_URL'];
        if (!is_array($baseURLs)) {
            $baseURLs = array($baseURLs);
        }

        foreach ($baseURLs as $baseURL) {
            // TODO find a better way to reuse JSON parsing code for ArcGIS-related data
            $url = $baseURL.'?'.http_build_query(array('f' => 'json'));
            $content = file_get_contents($url);
            $data = json_decode($content, true);

            // this is a tiled service
            if (isset($data['tileInfo'], $data['tileInfo']['lods'])) {
                $this->levelsOfDetail = $data['tileInfo']['lods'];
                $this->tiledLayer = $baseURL;
            } else {
                $this->dynamicLayers[] = $baseURL;
            }

            if (isset($data['spatialReference'], $data['spatialReference']['wkid'])) {
                $wkid = $data['spatialReference']['wkid'];
                $this->setMapProjection($wkid);
            }
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

    public function getIncludeCSS() {
        // first css is commented as we don't seem to use anything it themes
        return array(
            //'http://serverapi.arcgisonline.com/jsapi/arcgis/'.$this->apiVersion.'/js/dojo/dijit/themes/'.$this->themeName.'/'.$this->themeName.'.css',
            HTTP_PROTOCOL . '://serverapi.arcgisonline.com/jsapi/arcgis/'.$this->apiVersion.'/js/esri/css/esri.css',
            );
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

    public function jsObjectForPolygon($placemark) {
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
        $polygonJSON = json_encode($jsonParams);

        return <<<JS
new esri.Graphic(
    new esri.geometry.Polygon({$polygonJSON}),
    new esri.symbol.SimpleFillSymbol(
        esri.symbol.SimpleFillSymbol.STYLE_SOLID,
        new esri.symbol.SimpleLineSymbol(
            esri.symbol.SimpleLineSymbol.STYLE_SOLID,
            new dojo.Color({$strokeColor}),
            {$strokeWeight}
        ),
        new dojo.Color({$fillColor})
    )
)
JS;
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

    public function jsObjectForPath($placemark) {
        $polyline = $placemark->getGeometry();
        $style = $placemark->getStyle();

        // http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi/polyline.htm
        $polylineJSON = json_encode(
            array(
                'paths' => array($this->collapseAssociativePoints($polyline->getPoints())),
                'spatialReference' => array('wkid' => $this->mapProjection),
                )
            );

        $symbolSpec = '';
        if ($style !== null) {
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

            $symbolSpec = "$consistency,new dojo.Color(\"#$color\"),$weight";
        }

        return <<<JS
new esri.Graphic(
    new esri.geometry.Polyline({$polylineJSON}),
    new esri.symbol.SimpleLineSymbol({$symbolSpec})
)
JS;
    }
    
    public function jsObjectForMarker($placemark) {
        $style = $placemark->getStyle();

        $symbolType = 'SimpleMarkerSymbol';
        $symbolArgs = '';

        if ($style !== null) {
            // two ways to style markers:
            if (($icon = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::ICON)) !== null) {
                // 1. icon image
                // http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi/picturemarkersymbol.htm
                $symbolType = 'PictureMarkerSymbol';
                $width = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::WIDTH)
                    or $width = 20;
                $height = $style->getStyleForTypeAndParam(MapStyle::POINT, MapStyle::HEIGHT)
                    or $height = 20;
                $symbolArgs = "'$icon',$width,$height";

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

                $symbolArgs = "$shape,$size,new esri.symbol.SimpleLineSymbol(),new dojo.Color(\"#$color\")";
            }
        }

        $point = $placemark->getGeometry()->getCenterCoordinate();
        if (isset($this->mapProjector)) {
            $point = $this->mapProjector->projectPoint($point);
            list($x, $y) = MapProjector::getXYFromPoint($point);

        } else {
            // TODO this might be reversed
            // if it is then we can get rid of some code
            $x = $point['lat'];
            $y = $point['lon'];
        }

        return <<<JS
new esri.Graphic(
    new esri.geometry.Point({$x}, {$y}, mapLoader.spatialRef),
    new esri.symbol.{$symbolType}({$symbolArgs})
)
JS;
    }

    // url of script to include in <script src="...
    function getIncludeScripts() {
        return array(HTTP_PROTOCOL . '://serverapi.arcgisonline.com/jsapi/arcgis/?v='.$this->apiVersion.'compact');
    }

    function getInternalScripts() {
        return array('/common/javascript/maps.js');
    }
    
    function getIncludeStyles() {
        return HTTP_PROTOCOL . '://serverapi.arcgisonline.com/jsapi/arcgis/'
               .$this->apiVersion.'/js/dojo/dijit/themes/'
               .$this->themeName.'/'.$this->themeName.'.css';
    }

    function getMinimumLatSpan() {
        return oldPixelScaleForZoomLevel($this->maxZoomLevel);
    }

    function getMinimumLonSpan() {
        return oldPixelScaleForZoomLevel($this->maxZoomLevel);
    }

    protected function useCeilingForZoom() {
        return true;
    }
    
    function getFooterScript() {
        $zoomLevel = $this->zoomLevel;
        $targetScale = oldPixelScaleForZoomLevel($zoomLevel);

        if ($this->levelsOfDetail) {
            // TODO: if all zoom levels fail this test, the zoom level will
            // revert to the internal powers-of-two style zoom level i.e. not 
            // necessarily what we want
            $levelChanged = false;
            foreach ($this->levelsOfDetail as $levelData) {
                if ($levelData['scale'] < $targetScale) {
                    if (!$levelChanged) {
                        $zoomLevel = $levelData['level'];
                    }
                    break;
                } else {
                    $zoomLevel = $levelData['level'];
                    $levelChanged = true;
                }
            }
        }
        $moreLayers = array();
        foreach ($this->dynamicLayers as $layer) {
            $moreLayers[] = '"'.$layer.'"';
        }

        $footer = $this->prepareJavascriptTemplate('ArcGISJSMapFooter');
        $footer->setValues(array(
            '___WKID___' => $this->mapProjection,
            '___MAPELEMENT___' => $this->mapElement,
            '___IMAGE_WIDTH___' => $this->imageWidth,
            '___IMAGE_HEIGHT___' => $this->imageHeight,
            '___ZOOMLEVEL___' => $zoomLevel,
            '___MINZOOM___' => $this->minZoomLevel,
            '___MAXZOOM___' => $this->maxZoomLevel,
            '___SCALE___' => oldPixelScaleForZoomLevel($zoomLevel),
            '___BASE_URL___' => $this->tiledLayer,
            '___MORE_LAYER_SCRIPT___' => '['.implode(',', $moreLayers).']',
            '___PLACEMARK_SCRIPT___' => $this->getPlacemarkJS()
        ));

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
