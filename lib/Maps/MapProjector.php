<?php

class MapProjector {

    private $srcProj = 4326;
    private $dstProj = 4326;
    
    private $srcProjSpec;
    private $dstProjSpec;
    
    public function __construct() {

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

    public function projectPoints(Array $points, $outputXY=false) {
        $result = $points;
        if ($this->srcProjSpec) {
            $fromProjection = new MapProjection($this->srcProjSpec);
            if (!$fromProjection->isGeographic()) {
                $newPoints = array();
                foreach ($result as $point) {
                    list($x, $y) = self::getXYFromPoint($point);
                    $fromProjection->setXY(array('x' => $x, 'y' => $y));
                    $newPoints[] = $fromProjection->getLatLon();
                }
                $result = $newPoints;
            }
        }
        if ($this->dstProjSpec) {
            $toProjection = new MapProjection($this->dstProjSpec);
            if (!$toProjection->isGeographic()) {
                $newPoints = array();
                foreach ($result as $point) {
                    list($x, $y) = self::getXYFromPoint($point);
                    $toProjection->setLatLon(array('lon' => $x, 'lat' => $y));
                    $newPoints[] = $toProjection->getXY();
                }
                $result = $newPoints;
            }
        }

        return $result;
    }
    
    public function projectPoint($point) {
        list($x, $y) = self::getXYFromPoint($point);
        error_log("projecting $x, $y");

        if ($this->srcProj == $this->dstProj) {
            return array('lon' => $x, 'lat' => $y);
        }

        $result = $point;
        if ($this->srcProjSpec) {
            $fromProjection = new MapProjection($this->srcProjSpec);
            if (!$fromProjection->isGeographic()) {
                $fromProjection->setXY(array('x' => $x, 'y' => $y));
                $result = $fromProjection->getLatLon();
            }
        }
        if ($this->dstProjSpec) {
            $toProjection = new MapProjection($this->dstProjSpec);
            if (!$toProjection->isGeographic()) {
                list($x, $y) = self::getXYFromPoint($result);
                $toProjection->setLatLon(array('lon' => $x, 'lat' => $y));
                $result = $toProjection->getXY();
            }
        }

list($x, $y) = self::getXYFromPoint($result);
error_log("result: $x, $y");

        return $result;
    }
    
    public function getSrcProj() {
        return $this->srcProj;
    }
    
    public function getDstProj() {
        return $this->dstProj;
    }
    
    public function setSrcProj($proj) {
        $projspecs = self::getProjSpecs($proj);
        if ($projspecs) {
            $this->srcProjSpec = trim($projspecs);
            $this->srcProj = $proj;
        }
    }
    
    public function setDstProj($proj) {
        $projspecs = self::getProjSpecs($proj);
        if ($projspecs) {
            $this->dstProjSpec = trim($projspecs);
            $this->dstProj = $proj;
        }
    }
    
    public static function getProjSpecs($wkid) {
        $projCache = new DiskCache(Kurogo::getSiteVar('PROJ_CACHE'), null, true);
        $projCache->setSuffix('.proj4');
        $projCache->preserveFormat();
        $filename = $wkid;
        if (!$projCache->isFresh($filename)) {
            $file = fopen(DATA_DIR.'/maps/proj_list.txt', 'r');
            $wkidID = "<$wkid>";
            $strlen = strlen($wkidID);
            while ($line = fgets($file)) {
                if (substr($line, 0, $strlen) == $wkidID) {
                    preg_match("/<\d+> (.+) <>/", $line, $matches);
                    $contents = $matches[1];
                    break;
                }
            }
            fclose($file);

            if ($contents)
                $projCache->write($contents, $filename);
        } else {
            $contents = $projCache->read($filename);
        }
        return $contents;
    }

}

