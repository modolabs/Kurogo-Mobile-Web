<?php

interface MapGeometry extends Serializable
{
    // must return an array of the form {'lat' => 2.7182, 'lon' => -3.1415}
    public function getCenterCoordinate();
}

