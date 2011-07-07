<?php

class KMLDataController extends MapDataController
{
    protected $parserClass = 'KMLDataParser';

    protected function cacheFileSuffix()
    {
        return '.kml';
    }
}

