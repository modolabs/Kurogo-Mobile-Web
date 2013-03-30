<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class MapProjector {

    private $srcProjId = GEOGRAPHIC_PROJECTION;
    private $dstProjId = GEOGRAPHIC_PROJECTION;
    
    private $srcProjSpec;
    private $dstProjSpec;

    private $fromProjection;
    private $toProjection;

    const FORMAT_LATLON = 0;
    const FORMAT_XY = 1;
    const FORMAT_ARRAY = 2;
    
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
            $fmt = self::FORMAT_LATLON;
        } elseif (isset($point['x'])) {
            $x = $point['x'];
            $y = $point['y'];
            $fmt = self::FORMAT_XY;
        } else {
            $x = $point[1];
            $y = $point[0];
            $fmt = self::FORMAT_ARRAY;
        }
        return array($x, $y, $fmt);
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
             return new MapBasePolygon($projectedRings);
            
        } elseif ($geometry instanceof MapPolyline) {
             $points = $geometry->getPoints();
             $projectedPoints = $this->projectPoints($points);
             return new MapBasePolyline($projectedPoints);

        } else { // point
             $point = $geometry->getCenterCoordinate();
             $projectedPoint = $this->projectPoint($point);
             return new MapBasePoint($projectedPoint);
        }
    }

    private function formatLatLon($latlon, $fmt) {
        switch ($fmt) {
            case self::FORMAT_XY:
                return array('x' => $latlon['lon'], 'y' => $latlon['lat']);
            case self::FORMAT_ARRAY:
                return array($latlon['lat'], $latlon['lon']);
            case self::FORMAT_LATLON:
            default:
                return $latlon;
        }
    }

    public function projectPoints(Array $points) {
        if ($this->srcProjSpec === $this->dstProjSpec) {
            return $points;
        }

        $result = $points;
        if ($this->srcProjSpec) {
            if (!isset($this->fromProjection)) {
                $this->fromProjection = new MapProjection($this->srcProjSpec);
            }
            if (!$this->fromProjection->isGeographic()) {
                $newPoints = array();
                foreach ($result as $point) {
                    list($x, $y, $fmt) = self::getXYFromPoint($point);
                    $this->fromProjection->setXY(array('x' => $x, 'y' => $y));
                    $newPoints[] = $this->formatLatLon(
                        $this->fromProjection->getLatLon(), $fmt);
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
                    list($x, $y, $fmt) = self::getXYFromPoint($point);
                    $this->toProjection->setLatLon(array('lon' => $x, 'lat' => $y));
                    $newPoints[] = $this->formatLatLon(
                        $this->toProjection->getXY(), $fmt);
                }
                $result = $newPoints;
            }
        }

        return $result;
    }

    public function reverseProject($point) {
        $oldSrc = $this->srcProjSpec;
        $oldDest = $this->dstProjSpec;
        $this->srcProjSpec = $oldDest;
        $this->dstProjSpec = $oldSrc;

        // these may be set up already
        $swap = $this->fromProjection;
        $this->fromProjection = $this->toProjection;
        $this->toProjection = $swap;

        $result = $this->projectPoint($point);

        $this->srcProjSpec = $oldSrc;
        $this->dstProjSpec = $oldDest;

        $swap = $this->fromProjection;
        $this->fromProjection = $this->toProjection;
        $this->toProjection = $swap;

        return $result;
    }
    
    public function projectPoint($point, $format=NULL) {
        if ($this->srcProjSpec === $this->dstProjSpec) {
            return $point;
        }
        list($x, $y, $fmt) = self::getXYFromPoint($point);
        Kurogo::log(LOG_DEBUG, "projecting $x, $y", 'maps');

        if (isset($this->srcProjId, $this->dstProjId)
            && $this->srcProjId == $this->dstProjId
        ) {
            return $point;
        }

        $result = $point;
        if ($this->srcProjSpec && !isset($this->fromProjection)) {
            $this->fromProjection = new MapProjection($this->srcProjSpec);
        }
        if (isset($this->fromProjection) && !$this->fromProjection->isGeographic()) {
            $this->fromProjection->setXY(array('x' => $x, 'y' => $y));
            $result = $this->formatLatLon($this->fromProjection->getLatLon(), $fmt);
        }
        if ($this->dstProjSpec && !isset($this->toProjection)) {
            $this->toProjection = new MapProjection($this->dstProjSpec);
        }
        if (isset($this->toProjection) && !$this->toProjection->isGeographic()) {
            list($x, $y, $fmt) = self::getXYFromPoint($result);
            $this->toProjection->setLatLon(array('lon' => $x, 'lat' => $y));
            $result = $this->formatLatLon($this->toProjection->getXY(), $fmt);
        }

        list($x, $y, $fmt) = self::getXYFromPoint($result);
        Kurogo::log(LOG_DEBUG, "result: $x, $y", 'maps');

        return $result;
    }

    public function getSrcProj() {
        return $this->srcProjId;
    }
    
    public function setSrcProj($proj) {
        if ($proj instanceof MapProjection) {
            $this->fromProjection = $proj;
            $this->srcProjSpec = $proj->getSpecs();
            $this->srcProjId = null;

        } else if ($proj) {
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
            $this->dstProjSpec = $proj->getSpecs();
            $this->dstProjId = null;

        } else if ($proj) {
            $projspecs = self::getProjSpecs($proj);
            if ($projspecs) {
                $this->dstProjSpec = trim($projspecs);
                $this->dstProjId = $proj;
            }
        }
    }
    
    protected static function getProjFile() {
        $files = array(
            DATA_DIR.'/maps/proj_list.txt',
            SHARED_DATA_DIR.'/maps/proj_list.txt',
            LIB_DIR.'/Maps/proj_list.txt'
        );
        foreach ($files as $file) {
            if (is_file($file)) {
                return $file;
            }
        }
    }
    
    public static function getProjSpecs($wkid) {
        $contents = null;
        $projCacheDir = CACHE_DIR.DIRECTORY_SEPARATOR.'MapProjection';
        $projCache = new DiskCache($projCacheDir, null, true);
        $projCache->setSuffix('.proj4');
        $projCache->preserveFormat();
        $filename = $wkid;
        if (!$projCache->isFresh($filename)) {
            $file = fopen(self::getProjFile(), 'r');
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

            if ($contents) {
                $projCache->write($contents, $filename);
            } else {
                // TODO get config for logging
                Kurogo::LOG(LOG_WARNING, "$wkid is not a known projection", 'maps');
            }

        } else {
            $contents = $projCache->read($filename);
        }
        return $contents;
    }

}

