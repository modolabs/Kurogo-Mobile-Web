<?php

// TODO: make sure "powered by Google" logo is displayed on any page that uses this

class GooglePlacesDataController extends MapDataController
{
    protected $DEFAULT_PARSER_CLASS = 'GooglePlacesParser';
    private $apiKey;
    private $defaultCenter = '42.39462,-71.14549';
    private $defaultRadius = 1000;

    public function init($args)
    {
        parent::init($args);

        $this->apiKey = Kurogo::getSiteVar('GOOGLE_PLACES_API_KEY');

        // TODO: grab the following from config
        //   default lat/lon, radius
    }

    public function search($searchText)
    {
        $this->removeAllFilters();
        $this->addFilter('name', $searchText);
        $this->addFilter('key', $this->apiKey);
        // TODO: get user location
        $this->addFilter('location', $this->defaultCenter);
        $this->addFilter('radius', $this->defaultRadius);
        // TODO: set to true if user location was generated
        $this->addFilter('sensor', 'false');

        return $this->items();
    }

    public function searchByProximity($center, $tolerance, $maxItems, $projection=null)
    {
        $this->removeAllFilters();
        $this->addFilter('key', $this->apiKey);
        // TODO: add projection support
        $this->addFilter('location', $center['lat'].','.$center['lon']);
        $this->addFilter('radius', $tolerance);
        $this->addFilter('sensor', 'false');

        return $this->items();
    }
}
