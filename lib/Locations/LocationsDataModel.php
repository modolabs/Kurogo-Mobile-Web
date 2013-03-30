<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('DataModel');
class LocationsDataModel extends ItemListDataModel {

  protected $DEFAULT_PARSER_CLASS='XMLLocationDataParser';
  protected $locations = array();
  protected $filteredLocations = array();
  
  protected function init($args) {
    parent::init($args);
    $this->locations = $this->items();
    $this->filteredLocations = $this->locations;
  }
  
  public function getLocations() {
    return $this->locations;
  }
  
  public function getFilteredLocations() {
    return $this->filteredLocations;
  }
  
  public function clearFilters() {
    $this->filteredLocations = $this->locations;
  }
  
  public function getLocation($id) {
    foreach ($this->locations as $location) {
        if ($location->getID() == $id) {
            return $location;
        }
    }
    return false;
  }
  
  public function setFilterHasAmenity($amenity, $clear = false) {
    if ($clear) {
      $this->clearFilters();
    }
    $filteredLocations = array();
    if (is_array($amenity)) {
      foreach ($amenity as $aAmenity) {
        $this->setFilterHasAmenity($aAmenity);
      }
    } else {
      foreach ($this->filteredLocations as $location) {
        if ($location->hasAmenity($amenity)) {
          $filteredLocations[] = $location;
        }
      }
      $this->filteredLocations = $filteredLocations;
    }
  }
  
  public function setFilterIsOpen($timeRange = null, $clear = false) {
    if ($clear) {
      $this->clearFilters();
    }
    
    $filteredLocations = array();
    foreach ($this->filteredLocations as $location) {
      if ($location->isOpen($timeRange)) {
        $filteredLocations[] = $location;
      }
    }
    $this->filteredLocations = $filteredLocations;
  }
  
  public function setFilterWithinDistance($myGeolocation, $targetDistance, $clear = false) {
    if ($clear) {
      $this->clearFilters();
    }
    $filteredLocations = array();
    foreach ($this->locations as $location) {
      if ($location->hasValidCoordinates()) {
        $distance = $location->getDistance($myGeolocation);
        if ($distance <= $targetDistance) {
          $filteredLocations[] = $location; 
        }
      }
    }
    $this->filteredLocations = $filteredLocations;
  }
  
  public function setSortByOpen() {
    $open = array();
    $closed = array();
    $unknown = array();
    foreach ($this->locations as $location) {
      $isOpen = $location->isOpen();
      if ($isOpen === true) {
        $open[] = $location;
      } elseif ($isOpen === false) {
        $closed[] = $location;
      } else {
        $unknown[] = $location;
      }
    }
    $this->locations = array_merge($open, array_merge($closed, $unknown));
  }
  
  public function setSortByDistance($myGeolocation) {
    $withCoordinates = array();
    $withoutCoordinates = array();
    foreach ($this->locations as $location) {
      if ($location->hasValidCoordinates()) {
        $withCoordinates[] = $location;
      } else {
        $withoutCoordinates[] = $location; 
      }
    }
    $distanceSort = function($a, $b) use ($myGeolocation) {
      return $a->getDistance($myGeolocation) - $b->getDistance($myGeolocation);
    };
    usort($withCoordinates, $distanceSort);
    $this->locations = array_merge($withCoordinates, $withoutCoordinates);
  }
  
  public function setSortByTitle() {
    $titleSort = function($a, $b) {
      return strcasecmp($a->getTitle(), $b->getTitle());
    };
    usort($this->locations, $titleSort);
  }

  public function getAllAmenities() {
    $amenities = array();
    foreach ($this->locations as $location) {
      $amenities = array_merge($amenities, $location->getAmenities());
    }
    ksort($amenities);
    return $amenities;
  }

}