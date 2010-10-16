<?php

class MapPlotUtility {

    const GOOGLE_MAP_TILE_SIZE = 256;

    static function computeGoogleX($longitude, $zoom=0) {
        return (180. + $longitude)/(360.) * pow(2.0, $zoom);
    }

    static function computeGoogleY($latitude, $zoom=0) {
        // convert to radians
        $phi = (M_PI / 180.) * $latitude;

        // calculate mercator coordinate
        $mercatorY = log(tan($phi) + 1./cos($phi));

        // rescale to google coordinate
        return (M_PI - $mercatorY) / (2 * M_PI) * pow(2.0, $zoom);
    }

    static function computeLongitude($googleX, $zoomLevel=0) {
	return -180. + (360. * $googleX) / pow(2.0, $zoomLevel);
    }

    static function computeLatitude($googleY, $zoomLevel=0) {
	$mercatorY =  M_PI * ( 1 - 2 * $googleY / pow(2.0, $zoomLevel) );
	$phi = atan( sinh($mercatorY));

	// convert from radians to degrees
	return $phi * 180. / M_PI;
    }

    /*
     * $width image width in pixels
     * $height image height in pixels
     * $bounds lattitude and longitude bounds
     * $margin margin size given as a fraction of the bounding box
     */
    static function computeMapParameters($width, $height, $bounds, $margin) {
            $googleDeltaX = self::computeGoogleX($bounds['east'])
                            - self::computeGoogleX($bounds['west']);

            $googleDeltaY = self::computeGoogleY($bounds['south'])
                            - self::computeGoogleY($bounds['north']);

            $centerX = (self::computeGoogleX($bounds['east']) +
                self::computeGoogleX($bounds['west']))/2;

            $centerY = (self::computeGoogleY($bounds['south']) +
                self::computeGoogleY($bounds['north']))/2;

            $zoomX = log($width/self::GOOGLE_MAP_TILE_SIZE/$googleDeltaX/(1+$margin)) / log(2.);

            $zoomY = log($height/self::GOOGLE_MAP_TILE_SIZE/$googleDeltaY/(1+$margin)) / log(2.);
            
            return array(
                "center" => array(
                    "lat" => self::computeLatitude($centerY),
                    "lon" => self::computeLongitude($centerX)),
                "zoom" => floor(min($zoomX, $zoomY)),
                "width" => $width,
                "height" => $height,
            );
    }

    /*
     *  $x, and $y should be values between 0.0 and 1.0, with the top-left being 0.0, 0.0
     *  and the bottom-right being 1.0, 1.0
     */
    static function computeLatLon($mapParameters, $x, $y) {
        // transform to coordinate system where origin is in the center
        $xPrime = $x - 0.5;
        $yPrime = $y - 0.5;

        $xScale = $mapParameters['width']/self::GOOGLE_MAP_TILE_SIZE;
        $yScale = $mapParameters['height']/self::GOOGLE_MAP_TILE_SIZE;

        $centerX = self::computeGoogleX($mapParameters['center']['lon'], $mapParameters['zoom']);
        $centerY = self::computeGoogleY($mapParameters['center']['lat'], $mapParameters['zoom']);

        return array(
            'lat' => self::computeLatitude($centerY + $yScale * $yPrime, $mapParameters['zoom']),
            'lon' => self::computeLongitude($centerX + $xScale * $xPrime, $mapParameters['zoom']),
        );
    }

    static function URLParams($mapParameters) {
        return array(
            'size' => $mapParameters['width'] . 'x' . $mapParameters['height'],
            'center' => $mapParameters['center']['lat'] . ',' . $mapParameters['center']['lon'],
            'zoom' => $mapParameters['zoom'],
        );
    }

    static function getBounds($points) {
        // seed the output values
        
        $bounds = array(
            'north' => $points[0]['lat'],
            'south' => $points[0]['lat'],
            'west'  => $points[0]['lon'],
            'east'  => $points[0]['lon']);

        foreach($points as $latLong) {
            if($latLong['lat'] > $bounds['north']) {
                $bounds['north'] = $latLong['lat'];
            }

            if($latLong['lat'] < $bounds['south']) {
                $bounds['south'] = $latLong['lat'];
            }

            if($latLong['lon'] > $bounds['east']) {
                $bounds['east'] = $latLong['lon'];
            }

            if($latLong['lon'] < $bounds['west']) {
                $bounds['west'] = $latLong['lon'];
            }
        }

        return $bounds;
    }
}

?>