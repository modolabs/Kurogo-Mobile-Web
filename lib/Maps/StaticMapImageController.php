<?php

abstract class StaticMapImageController extends MapImageController
{
    protected $bbox;

    protected $baseURL;

    protected $imageFormat = 'png';
    protected $supportedImageFormats = array('png', 'jpg');

    // final function that generates url for the img src argument
    abstract public function getImageURL();

    public function getJavascriptControlOptions() {
    }

    public function isStatic() {
        return true;
    }

    public function getHorizontalRange()
    {
        return $this->bbox['xmax'] - $this->bbox['xmin'];
    }

    public function getVerticalRange()
    {
        return $this->bbox['ymax'] - $this->bbox['ymin'];
    }

    // n, s, e, w, ne, nw, se, sw
    public function getCenterForPanning($direction) {
        $vertical = null;
        $horizontal = null;

        if (preg_match('/[ns]/', $direction, $matches)) {
            $vertical = $matches[0];
        }
        if (preg_match('/[ew]/', $direction, $matches)) {
            $horizontal = $matches[0];
        }

        $center = $this->center;

        if ($horizontal == 'e') {
            $center['lon'] += $this->getHorizontalRange() / 2;
        } else if ($horizontal == 'w') {
            $center['lon'] -= $this->getHorizontalRange() / 2;
        }

        if ($vertical == 'n') {
            $center['lat'] += $this->getVerticalRange() / 2;
        } else if ($vertical == 's') {
            $center['lat'] -= $this->getVerticalRange() / 2;
        }

        return $center;
    }

    public function getLevelForZooming($direction) {
        $zoomLevel = $this->zoomLevel;
        if ($direction == 'in') {
            if ($zoomLevel < $this->maxZoomLevel)
                $zoomLevel += 1;
        } else if ($direction == 'out') {
            if ($zoomLevel > $this->minZoomLevel)
                $zoomLevel -= 1;
        }
        return $zoomLevel;
    }

    // setters
    
    public function setCenter($center)
    {
        if (is_array($center)
            && isset($center['lat'])
            && isset($center['lon']))
        {
            $xrange = $this->getHorizontalRange();
            $yrange = $this->getVerticalRange();
            $this->center = $center;
            $this->bbox['xmin'] = $center['lon'] - $xrange / 2;
            $this->bbox['xmax'] = $center['lon'] + $xrange / 2;
            $this->bbox['ymin'] = $center['lat'] - $xrange / 2;
            $this->bbox['ymax'] = $center['lat'] + $xrange / 2;
        }
    }
    
    public function setZoomLevel($zoomLevel)
    {
        $dZoom = $zoomLevel - $this->zoomLevel;
        $this->zoomLevel = $zoomLevel;
        // dZoom > 0 means decrease range
        $newXRange = $this->getHorizontalRange() / pow(2, $dZoom);
        $newYRange = $this->getVerticalRange() / pow(2, $dZoom);
        $this->bbox['xmin'] = $this->center['lon'] - $newXRange / 2;
        $this->bbox['xmax'] = $this->center['lon'] + $newXRange / 2;
        $this->bbox['ymin'] = $this->center['lat'] - $newYRange / 2;
        $this->bbox['ymax'] = $this->center['lat'] + $newYRange / 2;
    }

    public function setImageWidth($width) {
        $ratio = $width / $this->imageWidth;
        $range = $this->getHorizontalRange();
        $this->imageWidth = $width;
        $newRange = $range * $ratio;
        $this->bbox['xmin'] = $this->center['lon'] - $newRange / 2;
        $this->bbox['xmax'] = $this->center['lon'] + $newRange / 2;
    }

    public function setImageHeight($height) {
        $ratio = $height / $this->imageHeight;
        $range = $this->getVerticalRange();
        $this->imageHeight = $height;
        $newRange = $range * $ratio;
        $this->bbox['ymin'] = $this->center['lat'] - $newRange / 2;
        $this->bbox['ymax'] = $this->center['lat'] + $newRange / 2;
    }
    
    public function setImageFormat($format) {
        if (in_array($format, $this->supportedImageFormats)) {
            $this->imageFormat = $format;
        }
    }
}


