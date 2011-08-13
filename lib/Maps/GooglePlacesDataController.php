<?php

// TODO: make sure "powered by Google" logo is displayed on any page that uses this

class GooglePlacesDataController extends MapDataController
{
    protected $DEFAULT_PARSER_CLASS = 'GooglePlacesParser';
    private $apiKey;
    private $defaultCenter;
    private $defaultRadius = 5000;

    protected $useCache = false;

    public function init($args)
    {
        parent::init($args);

        $this->apiKey = Kurogo::getSiteVar('GOOGLE_PLACES_API_KEY', 'maps');
        $this->defaultCenter = Kurogo::getSiteVar('DEFAULT_CENTER', 'maps');

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

    public function searchByProximity($center, $tolerance, $maxItems)
    {
        $this->removeAllFilters();
        $this->addFilter('key', $this->apiKey);
        // TODO: add projection support
        $this->addFilter('location', $center['lat'].','.$center['lon']);
        $this->addFilter('radius', $tolerance);
        $this->addFilter('sensor', 'false');

        return $this->items();
    }

    public function selectFeature($featureId)
    {
        // featureId must be a reference from a previous Google search
        $this->removeAllFilters();
        $url = $this->baseURL;
        $this->setBaseURL('https://maps.googleapis.com/maps/api/place/details/json');

        $this->addFilter('reference', $featureId);
        $this->addFilter('sensor', 'false');
        $this->addFilter('key', $this->apiKey);

        $this->selectedFeatures = $this->items();
        $this->setBaseURL($url);

        return current($this->selectedFeatures);
    }
}
