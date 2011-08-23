<?php

class ArcGISPolygon implements MapPolygon
{
    private $rings;
    private $centerCoordinate;

    public function __construct($geometry)
    {
        foreach ($geometry['rings'] as $currentRing) {
            $currentRingInLatLon = array();
            foreach ($currentRing as $xy) {
                $currentRingInLatLon[] = array('lon' => $xy[0], 'lat' => $xy[1]);
            }
            
            $this->rings[] = new ArcGISPolyline($currentRingInLatLon);
        }
    }

    public function getCenterCoordinate()
    {
        reset($this->rings);
        $outerRing = current($this->rings);
        return $outerRing->getCenterCoordinate();
    }
    
    public function getRings() {
        return $this->rings;
    }
}
