<?php

class ArcGISPolygon implements MapPolygon
{
    protected $rings;
    protected $centerCoordinate;

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

    public function serialize() {
        return serialize(
            array(
                'rings' => serialize($this->rings),
                'centerCoordinate' => serialize($this->centerCoordinate),
            ));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->rings = unserialize($data['rings']);
        $this->centerCoordinate = unserialize($data['centerCoordinate']);
    }
}
