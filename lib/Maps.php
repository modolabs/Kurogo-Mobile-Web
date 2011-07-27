<?php

define('GEOGRAPHIC_PROJECTION', 4326);
define('EARTH_RADIUS_IN_METERS', 6378100);
define('MAP_CATEGORY_DELIMITER', ':');

// http://en.wikipedia.org/wiki/Great-circle_distance
// chosen for what the page said about numerical accuracy
// but in practice the other formulas, i.e.
// law of cosines and haversine
// all yield pretty similar results
function greatCircleDistance($fromLat, $fromLon, $toLat, $toLon)
{
    $radiansPerDegree = M_PI / 180.0;
    $y1 = $fromLat * $radiansPerDegree;
    $x1 = $fromLon * $radiansPerDegree;
    $y2 = $toLat * $radiansPerDegree;
    $x2 = $toLon * $radiansPerDegree;

    $dx = $x2 - $x1;
    $cosDx = cos($dx);
    $cosY1 = cos($y1);
    $sinY1 = sin($y1);
    $cosY2 = cos($y2);
    $sinY2 = sin($y2);

    $leg1 = $cosY2*sin($dx);
    $leg2 = $cosY1*$sinY2 - $sinY1*$cosY2*$cosDx;
    $denom = $sinY1*$sinY2 + $cosY1*$cosY2*$cosDx;
    $angle = atan2(sqrt($leg1*$leg1+$leg2*$leg2), $denom);

    return $angle * EARTH_RADIUS_IN_METERS;
}

function shortArrayFromMapFeature(MapFeature $feature) {
    $category = $feature->getCategory();
    if (is_array($category)) {
        $category = implode(MAP_CATEGORY_DELIMITER, $category);
    }
    return array(
        'featureindex' => $feature->getIndex(),
        'category' => $category,
        );
}

function htmlColorForColorString($colorString) {
    return substr($colorString, strlen($colorString)-6);
}

class MapsAdmin
{
    public static function getMapControllerClasses() {
        return array(
            'KMLDataController'=>'KML',
            'ArcGISDataController'=>'ArcGIS'
        );
    }
    
    public static function getStaticMapClasses() {
        return array(
            'GoogleStaticMap'=>'Google',
            'ArcGISStaticMap'=>'ArcGIS',
            'WMSStaticMap'=>'WMS'
        );
    }
    
    public static function getDynamicControllerClasses() {
        return array(
            'GoogleJSMap'=>'Google',
            'ArcGISJSMap'=>'ArcGIS'
        );
    }
}

$config = ConfigFile::factory('maps', 'site');
Kurogo::siteConfig()->addConfig($config);
