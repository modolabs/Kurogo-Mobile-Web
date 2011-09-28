<?php

class ArcGISDataController extends MapDataController
{
    protected $DEFAULT_PARSER_CLASS = 'ArcGISParser';
    protected $filters = array('f' => 'json');

    protected function cacheFolder() 
    {
        return Kurogo::getSiteVar('ARCGIS_CACHE','maps');
    }

    public function search($searchText)
    {
        $this->parser->clearSearchFilters();

        $this->parser->addSearchFilter('text', $searchText);
        
        $items = $this->items();
        $this->parser->clearSearchFilters();
        return $items;
    }
    
    public function searchByProximity($center, $tolerance, $maxItems)
    {
        $this->parser->clearSearchFilters();

        // TODO: these units are completely wrong (but work for harvard b/c
        // their units are in feet); we should use MapProjector to get
        // a decent range
        $dLatDegrees = $tolerance;
        $dLonDegrees = $tolerance;

        $maxLat = $center['lat'] + $dLatDegrees;
        $minLat = $center['lat'] - $dLatDegrees;
        $maxLon = $center['lon'] + $dLonDegrees;
        $minLon = $center['lon'] - $dLonDegrees;
        
        $this->parser->addSearchFilter('geometry', "$minLon,$minLat,$maxLon,$maxLat");
        $this->parser->addSearchFilter('geometryType', 'esriGeometryEnvelope');
        $this->parser->addSearchFilter('spatialRel', 'esriSpatialRelIntersects');
        $this->parser->addSearchFilter('returnGeometry', 'false');

        $items = $this->items();
        $this->parser->clearSearchFilters();
        return $items;
    }
}

