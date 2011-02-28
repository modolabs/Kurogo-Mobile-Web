<?php

class MapProjector {

    private $srcProj = 4326;
    private $dstProj = 4326;
    
    private $srcProjSpec;
    private $dstProjSpec;
    
    private $baseURL; // ESRI geometry service, if any
    
    public function __construct() {
        $useServer = $GLOBALS['siteConfig']->getVar('GEOMETRY_SERVICE_ENABLED');
        if ($useServer) {
            $this->baseURL = $GLOBALS['siteConfig']->getVar('GEOMETRY_SERVICE');
        }
        
        if (!$this->baseURL) {
            $projEnabled = $GLOBALS['siteConfig']->getVar('PROJ_EXTENSION_ENABLED');
            if (!$projEnabled || !function_exists('project_from_latlon')) {
                die('No projection support found.');
            }
        }
    }
    
    public static function getXYFromPoint(Array $point) {
        if (isset($point['lon']) && $point['lat']) {
            $x = $point['lon'];
            $y = $point['lat'];
        } elseif (isset($point['x'])) {
            $x = $point['x'];
            $y = $point['y'];
        } else {
            $x = $point[1];
            $y = $point[0];
        }
        return array($x, $y);
    }
    
    public function projectGeometry(MapGeometry $geometry) {
        if ($geometry instanceof MapPolygon) {
             $rings = $geometry->getRings();
             $projectedRings = array();
             foreach ($rings as $ring) {
                 if ($ring instanceof MapPolyline) {
                     $ring = $ring->getPoints();
                 }
                 $projectedRings[] = $this->projectPoints($ring);
             }
             return new EmptyMapPolygon($projectedRings);
            
        } elseif ($geometry instanceof MapPolyline) {
             $points = $geometry->getPoints();
             $projectedPoints = $this->projectPoints($points);
             return new EmptyMapPolyline($projectedPoints);

        } else { // point
             $point = $geometry->getCenterCoordinate();
             $projectedPoint = $this->projectPoint($point);
             return new EmptyMapPoint($projectedPoint['lat'], $projectedPoint['lon']);
        }
    }

    // TODO this is only supported for service-based projections right now
    public function projectPoints(Array $points, $outputXY=false) {
        $geometries = array();
        foreach ($points as $point) {
            list($x, $y) = self::getXYFromPoint($point);
            $geometries[] = array(
                'x' => $x,
                'y' => $y,
                );
        }
        if ($this->baseURL !== NULL) {
            $params = array(
                'inSR' => $this->srcProj,
                'outSR' => $this->dstProj,
                'geometries' => '{"geometryType":"esriGeometryPoint","geometries":'
                                .json_encode($geometries).'}',
                'f' => 'json',
                );
            $query = $this->baseURL.'?'.http_build_query($params);
//var_dump($query);
            $response = file_get_contents($query);
            $json = json_decode($response, true);
            if ($json && isset($json['geometries']) && is_array($json['geometries'])) {
                if ($outputXY) {
                    return $json['geometries'];
                } else {
                    $result = array();
                    foreach ($json['geometries'] as $geometry) {
                        $result[] = array('lat' => $geometry['y'], 'lon' => $geometry['x']);
                    }
                }
                return $result;
            }
        }

    }
    
    public function projectPoint($point) {
        list($x, $y) = self::getXYFromPoint($point);
        if ($this->srcProj == $this->dstProj) {
            return array('lon' => $x, 'lat' => $y);
        }
    
        if ($this->baseURL !== NULL) {
            $params = array(
                'inSR' => $this->srcProj,
                'outSR' => $this->dstProj,
                'geometries' => '{"geometryType":"esriGeometryPoint","geometries":[{"x":'.$x.',"y":'.$y.'}]}',
                'f' => 'json',
                );
            $query = $this->baseURL.'?'.http_build_query($params);
//var_dump($query);
            $response = file_get_contents($query);
            $json = json_decode($response, true);
            if ($json && isset($json['geometries']) && is_array($json['geometries'])) {
                $geometry = $json['geometries'][0];
                return array('lat' => $geometry['y'], 'lon' => $geometry['x']);
            }
            var_dump($response);
        }
        else {
            if ($this->srcProj != 4326) {
                $latlon = project_to_latlon($this->srcProjSpec, $x, $y);
                $x = $latlon[1]; // lon
                $y = $latlon[0]; // lat
            }
            if ($this->dstProj != 4326) {
                $xy = project_from_latlon($this->dstProjSpec, $y, $x); // they will have passed in lat, lon
                $x = $xy[0]; // point x
                $y = $xy[1]; // point y
            }
            return array('lon' => $x, 'lat' => $y);
        }
    }
    
    public function getSrcProj() {
        return $this->srcProj;
    }
    
    public function getDstProj() {
        return $this->dstProj;
    }
    
    public function setSrcProj($proj) {
        if ($proj != $this->srcProj) {
            if ($this->baseURL === NULL) {
//var_dump('setting src '.$proj);
                $projspecs = self::getProjSpecs($proj);
                if ($projspecs) {
                    $this->srcProjSpec = trim($projspecs);
                    $this->srcProj = $proj;
                }
            }
            else {
                $this->srcProj = $proj;
            }
        }
    }
    
    public function setDstProj($proj) {
        if ($proj != $this->dstProj) {
            if ($this->baseURL === NULL) {
//var_dump('setting dst '.$proj);
                $projspecs = self::getProjSpecs($proj);
                if ($projspecs) {
                    $this->dstProjSpec = trim($projspecs);
                    $this->dstProj = $proj;
                }
            }
            else {
                $this->dstProj = $proj;
            }
        }
    }
    
    public static function getProjSpecs($wkid) {
        $wkid = self::convertWkid($wkid);
    
        $projCache = new DiskCache($GLOBALS['siteConfig']->getVar('PROJ_CACHE'), null, true);
        $projCache->setSuffix('.proj4');
        $projCache->preserveFormat();
        $filename = $wkid;
        if (!$projCache->isFresh($filename)) {
            $url = 'http://spatialreference.org/ref/epsg/'.$wkid.'/proj4/';
            $contents = file_get_contents($url);
            if ($contents)
                $projCache->write($contents, $filename);
        } else {
            $contents = $projCache->read($filename);
        }
        
        return $contents;
    }
    
    private static function convertWkid($wkid) {
        
        // hack to convert ESRI-specific web mercator code
        // to standard code with the same spec.
        if ($wkid == 102113) $wkid = 3785;
        
        return $wkid;
    }

}

