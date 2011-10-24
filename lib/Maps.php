<?php

define('MILES_PER_METER', 0.000621371192);
define('FEET_PER_METER', 3.2808399);
define('GEOGRAPHIC_PROJECTION', 4326);
define('EARTH_RADIUS_IN_METERS', 6378100);
define('EARTH_METERS_PER_DEGREE', 111319); // very very rough
define('MAP_CATEGORY_DELIMITER', ':');

Kurogo::includePackage('Maps', 'Abstract');
Kurogo::includePackage('Maps', 'Base');

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
    return sqrt($dx*$dx + $dy*$dy) * EARTH_METERS_PER_DEGREE;
}

function filterLatLon($testString) {
    if (preg_match('/(-?\d+\.\d+),(-?\d+.\d+)/', $testString, $matches) == 1) {
        return array('lat' => $matches[1], 'lon' => $matches[2]);
    }
    return false;
}

// the following two functions are based on the scale, i.e. the number of 
// ground inches represented per inch on the computer screen, using the old
// pixel size of 0.28 millimeters.
// the number 559082264 is this ratio at a zoom level of 0 (showing full map).
// http://wiki.openstreetmap.org/wiki/MinScaleDenominator
// it currently works for WMS and ArcGIS maps
function oldPixelScaleForZoomLevel($zoomLevel)
{
    return 559082264 / pow(2, $zoomLevel);
}

function oldPixelZoomLevelForScale($scale)
{
    return ceil(log(559082264 / $scale, 2));
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
    $identifier = $feedData['TITLE'];
    if (isset($feedData['BASE_URL'])) {
        $identifier .= $feedData['BASE_URL'];
    } else {
        Kurogo::log(LOG_WARNING, "Warning: map feed for $identifier has no BASE_URL for map feed", 'maps');
    }
    return substr(md5($identifier), 0, 10);
}

function shortArrayFromMapFeature(Placemark $feature) {
    $category = current($feature->getCategoryIds());
    $result = array('category' => $category);

    $id = $feature->getId();
    if ($id) {
        $result['featureindex'] = $id;
    } else {
        $geometry = $feature->getGeometry();
        if ($geometry) {
            $coords = $geometry->getCenterCoordinate();
            $result['lat'] = $coords['lat'];
            $result['lon'] = $coords['lon'];
        }
        $result['title'] = $feature->getTitle();
    }

    return $result;
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
            'MapDataController' => 'default',
            'MapDBDataController' => 'database',
            //'ArcGISDataController'=>'ArcGIS',
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


