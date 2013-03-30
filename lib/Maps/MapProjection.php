<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/*
http://trac.osgeo.org/proj/wiki/GenParms

+a         Semimajor radius of the ellipsoid axis
+alpha     ? Used with Oblique Mercator and possibly a few others
+axis      Axis orientation (new in 4.8.0)
+b         Semiminor radius of the ellipsoid axis
+datum     Datum name (see `proj -ld`)
+ellps     Ellipsoid name (see `proj -le`)
+k         Scaling factor (old name)
+k_0       Scaling factor (new name)
+lat_0     Latitude of origin
+lat_1     Latitude of first standard parallel
+lat_2     Latitude of second standard parallel
+lat_ts    Latitude of true scale
+lon_0     Central meridian
+lonc      ? Longitude used with Oblique Mercator and possibly a few others
+lon_wrap  Center longitude to use for wrapping (see below)
+nadgrids  Filename of NTv2 grid file to use for datum transforms (see below)
+no_defs   Don't use the /usr/share/proj/proj_def.dat defaults file
+over      Allow longitude output outside -180 to 180 range, disables wrapping (see below)
+pm        Alternate prime meridian (typically a city name, see below)
+proj      Projection name (see `proj -l`)
+south     Denotes southern hemisphere UTM zone
+to_meter  Multiplier to convert map units to 1.0m
+towgs84   3 or 7 term datum transform parameters (see below)
+units     meters, US survey feet, etc.
+x_0       False easting
+y_0       False northing
+zone      UTM zone

*/

class MapProjection
{
    private $proj;
    private $specs; // string used to create this object
    private $format;

    // raw cartesian points (-pi, pi), intermediate products between
    // adjustedX, adjustedY and phi, lambda
    private $x = NULL;
    private $y = NULL;

    // x and y after accounting for ellipsoid, false easting/northing, and units
    private $adjustedX;
    private $adjustedY;
    
    private $phi = NULL; // latitude in radians
    private $lambda = NULL; // longitude in radians

    // ellipsoid properties
    // our current formulas assume the earth is spheroid with radius equal to seimimajor axis
    private $semiMajorAxis = 1;
    private $semiMinorAxis = 1;
    private $eccentricity = 0;

    private $falseEasting = 0;
    private $falseNorthing = 0;

    private $centralMeridian = 0;
    private $originLatitude = 0;

    private $standardParallel1 = 0;
    private $standardParallel2 = 0;

    private $unitsPerMeter = 1;
    private $scaleFactor = 1;

    public function getUnitsPerMeter()
    {
        return $this->unitsPerMeter;
    }

    public function isGeographic()
    {
        return $this->proj == 'longlat';
    }

    public function setXY(Array $xy)
    {
        if (isset($xy['x'], $xy['y'])) {
            $this->adjustedX = $xy['x'];
            $this->adjustedY = $xy['y'];
            $this->phi = null;
            $this->lambda = null;
        }
    }

    public function setLatLon(Array $latlon)
    {
        if (isset($latlon['lat'], $latlon['lon'])) {
            $this->phi = $latlon['lat'] / 180 * M_PI;
            $this->lambda = $latlon['lon'] / 180 * M_PI;
            $this->adjustedX = null;
            $this->adjustedY = null;
        }
    }
    
    private function lccM($phi)
    {
        return cos($phi) / sqrt(1 - pow($this->eccentricity * sin($phi), 2));
    }
    
    private function lccT($phi)
    {
        return tan(M_PI_4 - $phi / 2) / $this->lccESAdjustment($phi);
    }
    
    private function lccESAdjustment($phi)
    {
        $eSinPhi = $this->eccentricity * sin($phi);
        return pow((1 - $eSinPhi) / (1 + $eSinPhi), $this->eccentricity / 2);
    }

    // called unit meridional arc because meridional arc is this times
    // semi major axis
    // $e2 is eccentricity squared, $phi is latitude in radians
    private static function getUnitMeridionalArc($e2, $phi) {
        if ($phi == 0) {
            return 0;
        }

        $e4 = $e2*$e2;
        $e6 = $e2*$e4;
        return (1 - $e2/4 - 3*$e4/64 - 5*$e6/ 256) * $phi
             - (3*$e2/8 + 3*$e4/32 + 45*$e6/1024) * sin(2 * $phi)
             + (15*$e4/256 + 45*$e6/1024) * sin(4 * $phi)
             - (35*$e6/3072) * sin(6 * $phi);
    }

    private function forwardProject()
    {
        switch ($this->proj) {

            case 'longlat': // 429 SRIDs
                $this->x = $this->lambda * 180 / M_PI;
                $this->y = $this->phi * 180 / M_PI;
                break;

            case 'lcc': // 716 SRIDs; http://www.linz.govt.nz/geodetic/conversion-coordinates/projection-conversions/lambert-conformal-conic/index.aspx
                $m1 = $this->lccM($this->standardParallel1);
                $t1 = $this->lccT($this->standardParallel1);
                $n = (log($m1) - log($this->lccM($this->standardParallel2))) / (log($t1) - log($this->lccT($this->standardParallel2)));
                $F = $m1 / ($n * pow($t1, $n));
                $rho = $this->semiMajorAxis * $F * pow($this->lccT($this->phi), $n);
                $rho0 = $this->semiMajorAxis * $F * pow($this->lccT($this->originLatitude), $n);

                $gamma = $n * ($this->lambda - $this->centralMeridian);
                $this->x = $rho * sin($gamma);
                $this->y = $rho0 - $rho * cos($gamma);
                
                break;

            case 'merc': // 15 SRIDs; http://en.wikipedia.org/wiki/Mercator_projection
                $this->x = ($this->lambda - $this->centralMeridian) * $this->semiMajorAxis;
                $this->y = (log(tan(M_PI_4 + $this->phi / 2))) * $this->semiMajorAxis;
                break;

            case 'utm': // http://en.wikipedia.org/wiki/Universal_Transverse_Mercator_coordinate_system
                // ellipsoid formulae at http://www.uwgb.edu/dutchs/usefuldata/utmformulas.htm
            case 'tmerc': // http://en.wikipedia.org/wiki/Transverse_Mercator_projection
                $sin_phi = sin($this->phi);
                $cos_phi = cos($this->phi);
                $e2 = $this->eccentricity * $this->eccentricity;

                // the doc actually has nu as semimajor axis times this
                // we just multiply $a at the end
                $nu = 1 / (sqrt(1 - $e2 * $sin_phi * $sin_phi));

                $A = ($this->lambda - $this->centralMeridian) * $cos_phi;

                $s = self::getUnitMeridionalArc($e2, $this->phi);
                // info for handling origin latitude from 
                // http://www.linz.govt.nz/geodetic/conversion-coordinates/projection-conversions/transverse-mercator-preliminary-computations/index.aspx
                $s_0 = self::getUnitMeridionalArc($e2, $this->originLatitude);

                $T = pow($sin_phi/$cos_phi, 2);
                $C = $e2 * $cos_phi * $cos_phi / (1 - $e2);

                $ka = $this->scaleFactor * $this->semiMajorAxis;
                $this->x = $ka * $nu * ($A + (1 - $T + $C)*pow($A,6)/6 + (5 - 18*$T + $T*$T)*pow($A,5)/120);

                $omega = $this->lambda - $this->centralMeridian;
                $o2 = $omega*$omega;
                $cos2phi = $cos_phi * $cos_phi;
                $psi = (1 - $e2 * $sin_phi * $sin_phi);
                $psi2 = $psi * $psi;
                $this->y = $ka * ($s - $s_0 +
                    $nu * $sin_phi * $cos_phi * (
                        $o2 / 2
                        + $cos2phi * (4 * $psi*$psi + $psi - $T) * $o2*$o2 / 24
                        + $cos2phi * $cos2phi * (8*$psi2*$psi2 * (11 - 24*$T) - 28*$psi2*$psi * (1 - 6*$T) + $psi2*(1 - 32*$T) - $psi*2*$T + $T*$T) * $o2*$o2*$o2 / 720
                        )
                    );

                break;

            case 'stere': // 29
            case 'cass': // 20
            case 'aea': // 20 SRIDs; http://en.wikipedia.org/wiki/Albers_projection
            case 'omerc': // 17
            case 'laea': // 10 SRIDs; http://en.wikipedia.org/wiki/Lambert_azimuthal_equal-area_projection
            case 'somerc': // 5
            case 'poly': // 2
            case 'eqc': // 2
            case 'cea': // 2 SRIDs; http://en.wikipedia.org/wiki/Cylindrical_equal-area_projection
            case 'nzmg': // 1
            case 'krovak': // 1
            default:
                throw new KurogoConfigurationException("projection not implemented for {$this->proj}");
                break;
        }
    }

    private function reverseProject()
    {
        switch ($this->proj) {

            case 'longlat': // 429 SRIDs
                $this->lambda = $this->x / 180 * M_PI;
                $this->phi = $this->y / 180 * M_PI;
                break;

            case 'lcc': // 716 SRIDs; http://www.linz.govt.nz/geodetic/conversion-coordinates/projection-conversions/lambert-conformal-conic/index.aspx
                $m1 = $this->lccM($this->standardParallel1);
                $t1 = $this->lccT($this->standardParallel1);
                $n = (log($m1) - log($this->lccM($this->standardParallel2))) / (log($t1) - log($this->lccT($this->standardParallel2)));
                $F = $m1 / ($n * pow($t1, $n));
                $rho0 = $this->semiMajorAxis * $F * pow($this->lccT($this->originLatitude), $n);
            
                // different from the forward projection
                $rhoPrime = sqrt(pow($this->x, 2) + pow($rho0 - $this->y, 2));
                if ($n < 0) $rhoPrime = -$rhoPrime;
                if ($n == 0) $rhoPrime = 0;
                
                $tPrime = pow($rhoPrime / ($F * $this->semiMajorAxis), 1 / $n);
                $gammaPrime = atan($this->x / ($rho0 - $this->y));
                
                $this->lambda = $gammaPrime / $n + $this->centralMeridian;
                $this->phi = M_PI_2 - 2 * atan($tPrime);
                for ($i = 0; $i < 2; $i++) {
                    $this->phi = M_PI_2 - 2 * atan($tPrime * $this->lccESAdjustment($this->phi));
                }
            
                break;

            case 'merc': // 15 SRIDs; http://en.wikipedia.org/wiki/Mercator_projection
                $this->lambda = $this->x / $this->semiMajorAxis + $this->centralMeridian;
                $this->phi = 2 * (atan(exp($this->y / $this->semiMajorAxis)) - M_PI / 4);
                break;

            case 'utm': // http://www.uwgb.edu/dutchs/usefuldata/utmformulas.htm
            case 'tmerc':
                $M = $this->y / $this->scaleFactor;  // meridional arc
                $e2 = $this->eccentricity * $this->eccentricity;
                $mu = $M / ($this->semiMajorAxis * (1 - $e2/4 - 3*$e2*$e2/64 - 5*pow($e2,6)/256));
                $e_1 = (1 - sqrt(1 - $e2)) / (1 + sqrt(1 - $e2));

                if ($this->originLatitude == 0) {
                    $fp = $mu + (3*$e_1/2 - 27*pow($e_1,3)/32) * sin(2 * $mu)
                              + (21*$e_1*$e_1/16 - 55*pow($e_1,4)/32) * sin(4 * $mu)
                              + (151*pow($e_1,3)/96) * sin(6 * $mu); // footprint latitude
                } else {
                    // http://badc.nerc.ac.uk/help/coordinates/OSGB.pdf
                    $fp = $this->originLatitude;
                    $M = 0; // iterate until this matches northing

                    $iterations = 10; // prevent infinite loops in case we screwed up
                    while ($this->y - $M > 0.01) {
                        $fp += ($this->y - $M) / ($this->semiMajorAxis * $this->scaleFactor);
                        $M = self::getLatitudeCorrection(
                            $this->semiMajorAxis,
                            $this->semiMinorAxis,
                            $this->scaleFactor,
                            $this->originLatitude, $fp);

                        if ($iterations-- <= 0) break;
                    }
                }

                $e2_ = $e2 / (1 - $e2);
                $cos_fp = cos($fp);
                $sin_fp = sin($fp);
                $tan_fp = $sin_fp / $cos_fp;
                $C1 = $e2_ * $cos_fp * $cos_fp;
                $T1 = $tan_fp * $tan_fp;
                $R1 = $this->semiMajorAxis * (1 - $e2) / pow(1 - $e2*$sin_fp*$sin_fp, 1.5);
                $N1 = $this->semiMajorAxis / sqrt(1 - $e2*$sin_fp*$sin_fp);
                $D = $this->x / ($N1 * $this->scaleFactor);
                $this->phi = $fp - $N1*$tan_fp/$R1
                           * ($D*$D/2
                             - (5 + 3*$T1 + 10*$C1 - 4*$C1*$C1 - 9*$e2_)*pow($D,4)/24
                             + (61 + 90*$T1 + 298*$C1 + 45*$T1*$T1 - 3*$C1*$C1 - 252*$e2_)*pow($D,6)/720
                             );
                $this->lambda = $this->centralMeridian
                              + ($D 
                                -(1 + 2*$T1 + $C1)*pow($D,3)/6
                                +(5 - 2*$C1 + 28*$T1 - 3*$C1*$C1 + 8*$e2_ + 24*$T1*$T1)*pow($D,5)/120)
                               /$cos_fp;
                break;

            case 'stere': // 29
            case 'cass': // 20
            case 'aea': // 20 SRIDs; http://en.wikipedia.org/wiki/Albers_projection
            case 'omerc': // 17
            case 'laea': // 10 SRIDs; http://en.wikipedia.org/wiki/Lambert_azimuthal_equal-area_projection
            case 'somerc': // 5
            case 'poly': // 2
            case 'eqc': // 2
            case 'cea': // 2 SRIDs; http://en.wikipedia.org/wiki/Cylindrical_equal-area_projection
            case 'nzmg': // 1
            case 'krovak': // 1
            default:
                throw new KurogoConfigurationException("reverse projection not implemented for {$this->proj}");
                break;
        }
    }

    private static function getLatitudeCorrection($semiMajorAxis, $semiMinorAxis, $scaleFactor, $originLatitude, $phi) {
        $n = ($semiMajorAxis - $semiMinorAxis) / ($semiMajorAxis + $semiMinorAxis);
        $n2 = $n * $n;
        $n3 = $n2 * $n;
        $phi_sum = $phi + $originLatitude;
        $phi_diff = $phi - $originLatitude;
        return $semiMinorAxis * $scaleFactor * (
            (1 + $n + 1.25 * $n2 + 1.25 * $n3) * $phi_diff
            - (3 * $n + 3 * $n2 + 21 / 8 * $n3) * sin($phi_diff) * cos($phi_sum)
            + (15 / 8 * $n2 + 15 / 8 * $n3) * sin(2 * $phi_diff) * cos(2 * $phi_sum)
            - 35 * $n3 * sin(3 * $phi_diff) * cos(3 * $phi_sum)
            );
    }

    public function getXY()
    {
        if ($this->adjustedX === NULL || $this->adjustedY === NULL) {
            if ($this->lambda === NULL || $this->phi === NULL) {
                throw new KurogoConfigurationException("source points not set");
            }
            $this->forwardProject();
            $this->adjustedX = ($this->x + $this->falseEasting) * $this->unitsPerMeter;
            $this->adjustedY = ($this->y + $this->falseNorthing) * $this->unitsPerMeter;
        }
        return array(
            'lon' => $this->adjustedX,
            'lat' => $this->adjustedY,
            );
    }

    public function getLatLon()
    {
        if ($this->lambda === NULL || $this->phi === NULL) {
            if ($this->adjustedX === NULL || $this->adjustedY === NULL) {
                throw new KurogoConfigurationException("source points not set");
            }
            
            $this->x = $this->adjustedX / $this->unitsPerMeter - $this->falseEasting;
            $this->y = $this->adjustedY / $this->unitsPerMeter - $this->falseNorthing;
            $this->reverseProject();
        }
        return array(
            'lat' => $this->phi * 180 / M_PI,
            'lon' => $this->lambda * 180 / M_PI,
            );
    }

    public function __construct($projString)
    {
        $this->specs = $projString;

        if (preg_match('/^\d+$/', $projString)) {
            $this->format = 'wkid';
        } elseif (preg_match('/^\w+\[/', $projString)) {
            $this->format = 'wkt';
        } elseif (preg_match('/^\+/', $projString)) {
            $this->format = 'proj4';
        }

        switch ($this->format) {
            case 'wkid':
                $projString = MapProjector::getProjSpecs($projString);
                $params = self::parseProj4String($projString);
                $this->initFromProj4Params($params);
                break;
            case 'wkt':
                $params = WKTParser::parseWKTString($projString);
                $this->initFromWKTParams($params);
                break;
            case 'proj4':
            default:
                $params = self::parseProj4String($projString);
                $this->initFromProj4Params($params);
                break;
        }
    }

    public function getSpecs()
    {
        return $this->specs;
    }

    protected function setSpheroid($spheroid) {
        switch ($spheroid) {
            case 'GRS80': case 'GRS_1980':
                // 1252 SRIDs; http://en.wikipedia.org/wiki/GRS_80
                $this->semiMajorAxis = 6378137;
                $this->semiMinorAxis = 6356752.31414;
                $this->eccentricity = 0.08181919;
                break;
            case 'krass': // 562
            case 'intl': // 379
                break;
            case 'WGS84': // 322
                $this->semiMajorAxis = 6378137;
                $this->semiMinorAxis = 6378137;
                break;
            case 'clrk66': // 263
            case 'WGS72': // 248
            case 'bessel': // 155
            case 'clrk80': //128
            case 'aust': // 46
            case 'GRS67': // 19
            case 'helmert': // 13
            case 'evrstSS': // 7
            case 'airy': // 7
            case 'bess': // 3
            case 'WGS66': // 3
                break;
        }
    }

    protected function setDatum($datum) {
        switch ($datum) {
            case 'NAD83': case 'D_North_American_1983': // 322
                $this->semiMajorAxis = 6378137;
                $this->semiMinorAxis = 6356752.31414;
                $this->eccentricity = 0.08181919;
                break;
            case 'WGS84': // 246
                $this->semiMajorAxis = 6378137;
                $this->semiMinorAxis = 6378137;
                break;
            case 'NAD27': // 177
            case 'nzgd49': // 35
            case 'potsdam': // 11
            case 'OSGB36': // 1
                break;
        }
    }

    protected function initFromProj4Params($params) {

        $this->proj = $params['+proj'];

        if ($this->proj == 'utm') {
            // fill in some conventional values
            // wikipedia says false northing is 0 for points in N hemisphere
            // and 10M meters for south, but we can't deduce hemisphere from
            // x and y values so we should just avoid UTM for S hemisphere
            $this->scaleFactor = 0.9996;
            $this->falseEasting = 500000;
        }

        // plug in pre-calculated values for eccentricity
        // which is just sqrt((a^2 - b^2) / a^2) where a is major axis
        // and b is minor axis 
        if (isset($params['+ellps'])) {
            $this->setSpheroid($params['+ellps']);

        } elseif (isset($params['+datum'])) {
            $this->setDatum($params['+datum']);

        } else {
            if (isset($params['+a'])) {
                $this->semiMajorAxis = $params['+a'];
            }

            if (isset($params['+b'])) {
                $this->semiMinorAxis = $params['+b'];
            }
        }

        if (isset($params['+to_meter'])) {
            $this->unitsPerMeter = 1 / $params['+to_meter'];

        } else if (isset($params['+units'])) {
            switch ($params['+units']) {
                case 'us-ft':
                case 'ft':
                    $this->unitsPerMeter = 3.2808399; // both are 3.2803 in postgis
                    break;
                case 'm':
                    break;
            }
        }

        if (isset($params['+zone']) && $this->proj == 'utm') {
            // there are 60 zones, numbered from 1, each 6 degrees wide,
            // from -180 to 180. central meridians of the zone are halfway
            // between the min/max longitudes of the zone, i.e. min + 3.
            $zone = $params['+zone'];
            if ($zone >= 1 && $zone <= 60) {
                $centralLongitude = ($zone - 1) * 6 - 180 + 3;
                $this->centralMeridian = $centralLongitude / 180 * M_PI;
            }
        }

        if (isset($params['+lat_1'])) {
            $this->standardParallel1 = $params['+lat_1'] / 180 * M_PI;
        }

        if (isset($params['+lat_2'])) {
            $this->standardParallel2 = $params['+lat_2'] / 180 * M_PI;
        }

        if (isset($params['+lat_0'])) {
            $this->originLatitude = $params['+lat_0'] / 180 * M_PI;
        }

        if (isset($params['+lon_0'])) {
            $this->centralMeridian = $params['+lon_0'] / 180 * M_PI;
        }

        if (isset($params['+x_0'])) { // these are always in meters
            $this->falseEasting = $params['+x_0'];
        }
        if (isset($params['+y_0'])) { // these are always in meters
            $this->falseNorthing = $params['+y_0'];
        }
        if (isset($params['+k'])) {
            $this->scaleFactor = $params['+k'];
        }

    }

    public static function parseProj4String($proj4String)
    {
        $params = array();
        $args = preg_split("/\s+/", $proj4String, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($args as $arg) {
            $argParts = explode('=', $arg);
            if (count($argParts) == 2) {
                $params[ $argParts[0] ] = $argParts[1];
            }
        }
        return $params;
    }

    private function initFromWKTParams($params) {

        if (isset($params['PROJCS'])) {
            $projcs = $params['PROJCS'];
        } else {
            $projcs = $params;
        }


        if (isset($projcs['GEOGCS'])) {
            $geogcs = $projcs['GEOGCS'];
            if (isset($geogcs['DATUM'])) {
                $datum = $geogcs['DATUM'];
                if (isset($datum['name'])) {
                    $this->setDatum($datum['name']);
                }
                if (isset($datum['SPHEROID'], $datum['SPHEROID']['semiMajorAxis'])) {
                    $this->semiMajorAxis = $datum['SPHEROID']['semiMajorAxis'];
                }
            }
        }

        if (isset($projcs['PROJECTION'], $projcs['PROJECTION']['name'])) {
            $projMap = array(
                'Lambert_Conformal_Conic' => 'lcc',
                );
            $this->proj = $projMap[$projcs['PROJECTION']['name']];
        } else {
            $this->proj = 'longlat';
        }
        if (isset($projcs['UNIT'], $projcs['UNIT']['unitsPerMeter'])) {
            $this->unitsPerMeter = $projcs['UNIT']['unitsPerMeter'];
        }

        if (isset($projcs['PARAMETER'])) {
            $parameters = $projcs['PARAMETER'];
            if (isset($parameters['False_Easting'])) {
                $this->falseEasting = $parameters['False_Easting'];
            }
            if (isset($parameters['False_Northing'])) {
                $this->falseNorthing = $parameters['False_Northing'];
            }
            if (isset($parameters['Central_Meridian'])) {
                $this->centralMeridian = $parameters['Central_Meridian'] / 180 * M_PI;
            }
            if (isset($parameters['Latitude_Of_Origin'])) {
                $this->originLatitude = $parameters['Latitude_Of_Origin'] / 180 * M_PI;
            }
            if (isset($parameters['Standard_Parallel_1'])) {
                $this->standardParallel1 = $parameters['Standard_Parallel_1'] / 180 * M_PI;
            }
            if (isset($parameters['Standard_Parallel_2'])) {
                $this->standardParallel2 = $parameters['Standard_Parallel_2'] / 180 * M_PI;
            }
        }
    }
    
    private function logResults() {
        error_log("adjustedX: {$this->adjustedX}, adjustedY: {$this->adjustedY}");
        error_log("x: {$this->x}, y: {$this->y}");
        error_log("phi: {$this->phi}, lambda: {$this->lambda}");
        $lat = $this->phi * 180 / M_PI; $lon = $this->lambda * 180 / M_PI;
        error_log("latitude: $lat, longitude: $lon");
    }
}



