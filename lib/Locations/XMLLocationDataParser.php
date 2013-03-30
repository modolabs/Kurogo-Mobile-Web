<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class XMLLocationDataParser extends SimpleXMLDataParser {
    
    protected $DEFAULT_EVENT_MODEL_CLASS = 'EventsDataModel';
    protected $multiValueElements = array('location', 'amenity', 'day');
    
    public function init($args) {
      parent::init($args);
    }

    public function parseData($data) {
      try {
          $xml = new SimpleXMLElement($data, LIBXML_NOCDATA);
      } catch (Exception $e) {
          return null;
      }

      $locations = $this->xmlObjToLocations($xml); 
      return $locations;
    }

    protected function xmlObjToLocations($obj) {
      $locations = array();
      
      $locationsArr = $this->xmlObjToArr($obj);
      if ($locationsArr['location']) {
        foreach($locationsArr['location'] as $key => $location) {
          if (!isset($location['id'])) {
             $location['id'] = 'id'.$key;
          }
          $location = new LocationDataObject($location);

          if ($location->hasAttribute('hours')) {
            $hours = new DailyHoursDataObject($location->getAttribute('hours'));
            $location->setAttribute('hours', $hours);
          }
          if ($location->hasAttribute('amenities')) {
            $amenities = $location->getAttribute('amenities');
            $formattedAmenities = array();

            foreach ($amenities['amenity'] as $amenityData) {
              $amenity = new AmenityDataObject;
              $amenity->setTitle($amenityData['name']);
              if (isset($amenityData['image'])) {
                $amenity->setIcon($amenityData['image']);
              }
              $formattedAmenities[$amenityData['name']] = $amenity;
            }
            $location->setAttribute('amenities', $formattedAmenities);
          }
          
          if ($location->hasAttribute('events')) {
            $eventsConfig = $location->getAttribute('events');
            $eventsConfig = array_change_key_case($eventsConfig, CASE_UPPER);
            
            // If it is a kurogo relative path you need to replace the path constants
            if (isset($eventsConfig['KUROGO_PATH']) && isset($eventsConfig['BASE_URL'])) {
              $explodedBaseURL = explode('"', $eventsConfig['BASE_URL']);
              $baseURL = '';
              foreach ($explodedBaseURL as $part) {
                if (defined($part)) {
                  $baseURL .= constant($part);
                } else {
                  $baseURL .= $part;
                }
              }
              $eventsConfig['BASE_URL'] = $baseURL;
            }
            $eventModelClass = isset($eventsConfig['DATA_MODEL']) ? $eventsConfig['DATA_MODEL'] : $this->DEFAULT_EVENT_MODEL_CLASS;
            $eventModel = EventsDataModel::factory($eventModelClass, $eventsConfig);
            $location->setAttribute('events', $eventModel);
          }
          
          $locations[$location->getID()] = $location;
        }
      }
      return $locations;
    }
  
}