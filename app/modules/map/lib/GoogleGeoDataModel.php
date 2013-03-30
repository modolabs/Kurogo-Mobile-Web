<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

// if using Google Places, pages must display "powered by Google" logo.
// if using Geocode, pages must display Google Maps.

class GoogleGeoDataModel extends MapDataModel
{
    const GEOCODE_BASE_URL = 'http://maps.googleapis.com/maps/api/geocode/json';
    const PLACES_BASE_URL = 'https://maps.googleapis.com/maps/api/place/search/json';
    const PLACES_DETAIL_URL = 'https://maps.googleapis.com/maps/api/place/details/json';

    protected $DEFAULT_PARSER_CLASS = 'GooglePlacesParser';
    private $apiKey = null;
    private $defaultCenter;
    private $defaultRadius = 5000;

    // the Google Places and Google Maps Geocoding APIs are
    // very very similar so we use the same parser and do all
    // the differentiating here
    private $isPlaces = false;
    protected $lastSearchText;

    protected $useCache = false;

    public function init($args)
    {
        // alter args for initializing retriever
        if (isset($args['title'])) {
            $args['TITLE'] = $args['title'];
        }
        $this->isPlaces = Kurogo::getOptionalSiteVar('USE_GOOGLE_PLACES', false, 'maps');
        if ($this->isPlaces) {
            $args['BASE_URL'] = self::PLACES_BASE_URL;
            $this->apiKey = Kurogo::getSiteVar('GOOGLE_PLACES_API_KEY', 'maps');
        } else {
            $args['BASE_URL'] = self::GEOCODE_BASE_URL;
            // the Google Maps license requires that geocode results
            // be displayed with a Google Map
            $this->staticMapClass = 'GoogleStaticMap';
            $this->dynamicMapClass = 'GoogleJSMap';
        }

        $this->defaultCenter = $args['center'];
        if (isset($args['NEARBY_THRESHOLD'])) {
            $this->defaultRadius = $args['NEARBY_THRESHOLD'];
        }

        parent::init($args);

        $this->retriever->setCacheLifetime(1);
    }

    public function getSearchText()
    {
        return $this->lastSearchText;
    }

    protected function signURL()
    {
        if ($this->isPlaces) {
            $this->addFilter('key', $this->apiKey);
        } else {
            // TODO: sign urls if using premier maps
        }
    }

    public function search($searchText)
    {
        $this->lastSearchText = $searchText;

        $this->removeAllFilters();
        if ($this->isPlaces) {
            $this->addFilter('name', $searchText);
        } else {
            if (filterLatLon($searchText)) {
                $this->addFilter('latlng', $searchText);
            } else {
                $this->addFilter('address', $searchText);
            }
        }

        // TODO: get user location
        $this->addFilter('location', $this->defaultCenter);
        $this->addFilter('radius', $this->defaultRadius);
        // TODO: set to true if user location was generated
        $this->addFilter('sensor', 'false');
        $this->signURL();

        return $this->items();
    }

    public function searchByProximity($center, $tolerance, $maxItems)
    {
        if (!$this->isPlaces) {
            return array();
        }

        $this->removeAllFilters();
        // TODO: add projection support
        $this->addFilter('location', $center['lat'].','.$center['lon']);
        $this->addFilter('radius', $tolerance);
        $this->addFilter('sensor', 'false');
        $this->signURL();

        return $this->items();
    }

    public function selectPlacemark($featureId)
    {
        if (!$this->isPlaces) {
            return null;
        }

        // featureId must be a reference from a previous Google search
        $this->removeAllFilters();
        $url = $this->baseURL;
        $this->setBaseURL(self::PLACES_DETAIL_URL);

        $this->addFilter('reference', $featureId);
        $this->addFilter('sensor', 'false');
        $this->signURL();

        $this->selectedPlacemarks = $this->items();
        $this->setBaseURL($url);

        return current($this->selectedPlacemarks);
    }
}
