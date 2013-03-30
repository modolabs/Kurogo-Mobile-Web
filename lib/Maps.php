<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

define('MILES_PER_METER', 0.000621371192);
define('FEET_PER_METER', 3.2808399);
define('GEOGRAPHIC_PROJECTION', 4326);
define('EARTH_RADIUS_IN_METERS', 6378100);
define('EARTH_METERS_PER_DEGREE', 111319); // very very rough
define('MAP_CATEGORY_DELIMITER', ':');

// from appendix E.4 of
// http://portal.opengeospatial.org/files/?artifact_id=35326
// this is the number of 256 pixel wide tiles at 0.28 mm per pixel
// that would cover the equater.
define('GOOGLE_COMPATIBLE_MAX_SCALE_DENOMINATOR', 559082264);

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
    if (preg_match('/(-?\d+\.\d+)\s*,\s*(-?\d+.\d+)/', $testString, $matches) == 1) {
        return array('lat' => $matches[1], 'lon' => $matches[2]);
    }
    return false;
}

function oldPixelScaleForZoomLevel($zoomLevel)
{
    return GOOGLE_COMPATIBLE_MAX_SCALE_DENOMINATOR / pow(2, $zoomLevel);
}

function oldPixelZoomLevelForScale($scale)
{
    return ceil(log(GOOGLE_COMPATIBLE_MAX_SCALE_DENOMINATOR / $scale, 2));
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

function mapModelFromFeedData($feedData) {
    if (isset($feedData['MODEL_CLASS'])) {
        $modelClass = $feedData['MODEL_CLASS'];
    }
    else {
        $modelClass = 'MapDataModel';
    }

    $model = MapDataModel::factory($modelClass, $feedData);
    return $model;
}

function mapIdForFeedData(Array $feedData) {
    $identifier = Kurogo::arrayVal($feedData, 'TITLE', '');
    if (isset($feedData['BASE_URL'])) {
        $identifier .= $feedData['BASE_URL'];
    } else {
        Kurogo::log(LOG_WARNING, "Warning: map feed for $identifier has no BASE_URL for map feed", 'maps');
    }
    return substr(md5($identifier), 0, 10);
}

// $colorString must be 6 or 8 digit hex color
function htmlColorForColorString($colorString) {
    return substr($colorString, strlen($colorString)-6);
}

// returns a value between 0 and 1
// $colorString must be valid hex color
function alphaFromColorString($colorString) {
    if (strlen($colorString) == 8) {
        $alphaHex = substr($colorString, 0, 2);
        $alpha = hexdec($alphaHex) / 256;
        return round($alpha, 2);
    }
    return 1;
}

function isValidURL($urlString)
{
    // There is a bug in some versions of filter_var where it can't handle hyphens in hostnames
    return filter_var(strtr($urlString, '-', '.'), FILTER_VALIDATE_URL);
}

/* The following three functions are from Google Maps sample code at 
 * http://gmaps-samples.googlecode.com/svn/trunk/urlsigning/UrlSigner.php-source
 */

// Sign a URL with a given crypto key
// Note that this URL must be properly URL-encoded
function signURLForGoogle($urlToSign) {
    $clientID = Kurogo::getOptionalSiteVar('GOOGLE_MAPS_CLIENT_ID', false, 'maps');
    $privateKey = Kurogo::getOptionalSiteVar('GOOGLE_MAPS_PRIVATE_KEY', false, 'maps');
    if ($clientID && $privateKey) {
        // parse the url
        $url = parse_url($myUrlToSign);
        if (strpos($url['query'], 'client=') === false) {
            $url['query'] .= "client={$clientID}";
        }
        $urlPartToSign = $url['path'] . "?" . $url['query'];

        // Decode the private key into its binary format
        $decodedKey = decodeBase64UrlSafe($privateKey);

        // Create a signature using the private key and the URL-encoded
        // string using HMAC SHA1. This signature will be binary.
        $signature = hash_hmac("sha1", $urlPartToSign, $decodedKey, true);

        $encodedSignature = encodeBase64UrlSafe($signature);
        return $urlToSign."&signature=".$encodedSignature;
    }
    return $urlToSign;
}

// Encode a string to URL-safe base64
function encodeBase64URLSafe($value) {
    return str_replace(array('+', '/'), array('-', '_'), base64_encode($value));
}

// Decode a string from URL-safe base64
function decodeBase64URLSafe($value) {
    return base64_decode(str_replace(array('-', '_'), array('+', '/'), $value));
}

function filterContent($search, $content) {
    if(stripos(strval($content), strval($search)) === false) {
        return false;
    }else {
        return true;
    }
}

function stringFilter($search, $contents) {
    $search = trim($search);
    foreach($contents as $key => $content) {
        switch($key) {
            case "title":
            case "subtitle":
                // find content in search
                // some cases, subtitle only contains 1 character
                if(strlen($content) >= 3 && filterContent($content, $search)) {
                    return true;
                }
            default:
                // find current search
                if(filterContent($search, $content)) {
                    return true;
                }
        }
    }

    return false;
}

class MapsAdmin
{
    public static function getMapControllerClasses() {
        return array(
            'MapDataModel' => 'default',
            'ArcGISDataModel' => 'ArcGIS Server',
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
