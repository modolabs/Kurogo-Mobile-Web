<?php

class MapProjector {

    private $srcProjId = 4326;
    private $dstProjId = 4326;
    
    private $srcProjSpec;
    private $dstProjSpec;

    private $fromProjection;
    private $toProjection;
    
    public function __construct() {

    }

    public static function needsConversion($a, $b) {
        // this will return true if either projection is 
        // wkt-based.  TODO: be able to determine equality of
        // wkt-based projections.
        return !is_int($a) || !is_int($b) || $a != $b;
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
            if (!isset($this->fromProjection)) {
                $this->fromProjection = new MapProjection($this->srcProjSpec);
            }
            if (!$this->fromProjection->isGeographic()) {
                $newPoints = array();
                foreach ($result as $point) {
                    list($x, $y) = self::getXYFromPoint($point);
                    $this->fromProjection->setXY(array('x' => $x, 'y' => $y));
                    $newPoints[] = $this->fromProjection->getLatLon();
                }
                $result = $newPoints;
            }
        }

        if ($this->dstProjSpec) {
            if (!isset($this->toProjection)) {
                $this->toProjection = new MapProjection($this->dstProjSpec);
            }
            if (!$this->toProjection->isGeographic()) {
                $newPoints = array();
                foreach ($result as $point) {
                    list($x, $y) = self::getXYFromPoint($point);
                    $this->toProjection->setLatLon(array('lon' => $x, 'lat' => $y));
                    $newPoints[] = $this->toProjection->getXY();
                }
                $result = $newPoints;
            }
        }

        return $result;
    }
    
    public function projectPoint($point) {
        list($x, $y) = self::getXYFromPoint($point);
        error_log("projecting $x, $y");

        if (isset($this->srcProjId, $this->dstProjId)
            && $this->srcProjId == $this->dstProjId
        ) {
            return array('lon' => $x, 'lat' => $y);
        }

        $result = $point;
        if ($this->srcProjSpec && !isset($this->fromProjection)) {
            $this->fromProjection = new MapProjection($this->srcProjSpec);
        }
        if (isset($this->fromProjection) && !$this->fromProjection->isGeographic()) {
            $this->fromProjection->setXY(array('x' => $x, 'y' => $y));
            $result = $this->fromProjection->getLatLon();
        }

        if ($this->dstProjSpec && !isset($this->toProjection)) {
            $this->toProjection = new MapProjection($this->dstProjSpec);
        }
        if (isset($this->toProjection) && !$this->toProjection->isGeographic()) {
            list($x, $y) = self::getXYFromPoint($result);
            $this->toProjection->setLatLon(array('lon' => $x, 'lat' => $y));
            $result = $this->toProjection->getXY();
        }

        list($x, $y) = self::getXYFromPoint($result);
        error_log("result: $x, $y");

        return $result;
    }

    public function getSrcProj() {
        return $this->srcProjId;
    }
    
    public function setSrcProj($proj) {
        if ($proj instanceof MapProjection) {
            $this->fromProjection = $proj;
            $this->srcProjId = null;

        } else {
            $projspecs = self::getProjSpecs($proj);
            if ($projspecs) {
                $this->srcProjSpec = trim($projspecs);
                $this->srcProjId = $proj;
            }
        }
    }

    public function getDstProj() {
        return $this->dstProjId;
    }
    
    public function setDstProj($proj) {
        if ($proj instanceof MapProjection) {
            $this->toProjection = $proj;
            $this->dstProjId = null;

        } else {
            $projspecs = self::getProjSpecs($proj);
            if ($projspecs) {
                $this->dstProjSpec = trim($projspecs);
                $this->dstProjId = $proj;
            }
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

