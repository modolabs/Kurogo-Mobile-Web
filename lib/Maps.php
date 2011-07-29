<?php

define('GEOGRAPHIC_PROJECTION', 4326);
define('EARTH_RADIUS_IN_METERS', 6378100);
define('MAP_CATEGORY_DELIMITER', ':');

includePackage('Maps/Abstract');
includePackage('Maps/Base');

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

function euclideanDistance($fromLat, $fromLon, $toLat, $toLon)
{
    $dx = $toLon - $fromLon;
    $dy = $toLat - $fromLat;
    return sqrt($dx*$dx + $dy*$dy);
}

function normalizedBoundingBox($center, $tolerance, $fromProj=null, $toProj=null)
{
    if ($fromProj !== null || $toProj !== null) {
        $projector = new MapProjector();
    }

    // create the bounding box in lat/lon first
    if ($fromProj !== null) {
        $projector->setSrcProj($fromProj);
        $center = $projector->projectPoint($center);
    }

    // approximate upper/lower bounds for lat/lon before calculating GCD
    $dLatRadians = $tolerance / EARTH_RADIUS_IN_METERS;
    // by haversine formula
    $dLonRadians = 2 * asin(sin($dLatRadians / 2) / cos($center['lat'] * M_PI / 180));

    $dLatDegrees = $dLatRadians * 180 / M_PI;
    $dLonDegrees = $dLonRadians * 180 / M_PI;

    $min = array('lat' => $center['lat'] - $dLatDegrees, 'lon' => $center['lon'] - $dLonDegrees);
    $max = array('lat' => $center['lat'] + $dLatDegrees, 'lon' => $center['lon'] + $dLonDegrees);

    if ($toProj !== null) {
        $projector->setSrcProj(GEOGRAPHIC_PROJECTION);
        $projector->setDstProj($toProj);
        $min = $projector->projectPoint($min);
        $max = $projector->projectPoint($max);
    }

    return array('min' => $min, 'max' => $max, 'center' => $center);
}

function mapIdForFeedData(Array $feedData) {
    if (!isset($feedData['BASE_URL'])) {
        throw new Exception("missing BASE_URL for map feed");
    }
    $baseURL = $feedData['BASE_URL'];
    return substr(md5($baseURL), 0, 10);
}

function shortArrayFromMapFeature(Placemark $feature) {
    $category = current($feature->getCategoryIds());
    return array(
        'featureindex' => $feature->getId(),
        'category' => $category,
        );
}

function htmlColorForColorString($colorString) {
    return substr($colorString, strlen($colorString)-6);
}

function isValidURL($urlString)
{
    // There is a bug in some versions of filter_var where it can't handle hyphens in hostnames
    return filter_var(strtr($urlString, '-', '.'), FILTER_VALIDATE_URL);
}

class MapsAdmin
{
    public static function getMapControllerClasses() {
        return array(
            'KMLDataController'=>'KML',
            'ArcGISDataController'=>'ArcGIS',
            'ShapefileDataController'=>'Shapefile'
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

function debug_dump($variable=null, $message='') {
    $backtrace = debug_backtrace();
    $currentCall = current($backtrace); // who is calling debug_dump
    $lastCall = next($backtrace); // what debug_dump is being called in
    $file = end(explode('/', $currentCall['file']));
    $line = $currentCall['line'];
    $function = $lastCall['function'];
    if ($variable !== null) {
        $varRep = spl_object_hash($variable);
        $trace = "$file($line):$function [".get_class($variable)." $varRep] $message";
    } else {
        $trace = "$file($line):$function $message";
    }
    error_log($trace);
}

