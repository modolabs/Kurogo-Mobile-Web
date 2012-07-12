<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class MapDBDataRetriever extends DatabaseDataRetriever
{
    protected $campus; // campus
    protected $categoryId;
    protected $lat;
    protected $lon;
    protected $distance = 0;

    public function init($args) {
        parent::init($args);
        $this->campus = $args['GROUP'];
    }

    public function setCategoryId($categoryId) {
        $this->categoryId = $categoryId;
    }

    public function setDistance($distance) {
        $this->distance = $distance;
    }

    public function setCoordinates(Array $coordinates) {
        $this->lat = $coordinates['lat'];
        $this->lon = $coordinates['lon'];
    }

    public function reset() {
        $this->lat = null;
        $this->lon = null;
        $this->distance = 0;
    }

    public function getPlacemarkDetails($placemarkId) {
        $center = $placemark->getGeometry()->getCenterCoordinate();
        $sql = 'SELECT property_name, property_value FROM '.self::PLACEMARK_PROPERTIES_TABLE
           .' WHERE placemark_id = ? AND lat = ? AND lon = ?';
        $params = array($placemark->getId(), $center['lat'], $center['lon']);
        $this->setSQL($sql);
        $this->setParameters($params);
        $this->setContext('type', null);
        return $this->retrieveData();
    }

    public function getPlacemarkStyle($placemarkId) {
        $style = $placemark->getStyle();
        if (method_exists($style, 'getId')) {
            $styleId = $style->getId();
            $this->setSQL(
                .'SELECT property_name, property_value FROM '.self::PLACEMARK_STYLES_TABLE
                .' WHERE style_id = ?');
            $this->setParameters(array($styleId));
            $this->setContext('type', null);
            return $this->retrieveData();
        }
        return null;
    }

    public function setContext($type)
    {
        switch ($type) {
            case 'category':
                $this->setupCategoryContext();
                break;
            case 'categories':
                $this->setupSubcategoryContext();
                break;
            case 'placemarks':
                $this->setupPlacemarkContext();
                break;
        }
    }

    // categoryForId
    public function setupCategoryContext() {
        $this->setSQL('SELECT * FROM '.self::CATEGORY_TABLE
                     .' WHERE campus = ? AND category_id = ?');
        $this->setParameters(array($this->campus, $this->categoryId));
        $this->setContext('type', 'category');
    }

    // childrenForCategory
    public function setupSubcategoryContext() {
        $sql = 'SELECT * FROM '.self::CATEGORY_TABLE
              .' WHERE campus = ? AND parent_category_id = ?';
        $params = array($this->campus, $this->categoryId);
        $this->setSQL($sql);
        $this->setParameters($params);
        $this->setContext('type', 'category');
    }

    // featuresForCategory
    public function setupPlacemarkContext()
    {
        $sql = 'SELECT p.*, pc.category_id FROM '
              .self::PLACEMARK_TABLE.' p, '.self::PLACEMARK_CATEGORY_TABLE.' pc'
              .' WHERE p.placemark_id = pc.placemark_id'
              .'   AND p.lat = pc.lat AND p.lon = pc.lon';

        $conditions = array();
        $params = array();

        $sort = array();
        $sortParams = array();

        if (isset($this->lat, $this->lon)) {
            if ($this->distance > 0) {
                $conditions[] = 'p.lat = ? AND p.lon = ?';
                $params[] = $this->lat;
                $params[] = $this->lon;
            } else {
                $center = array('lat' => $this->lat, 'lon' => $this->lon);
                $bbox = normalizedBoundingBox($center, $tolerance, null, null);

                $conditions[] = 'p.lat >= ? AND p.lat < ? AND p.lon >= ? AND p.lon < ?';
                array_push($params,
                    $bbox['min']['lat'], $bbox['max']['lat'],
                    $bbox['min']['lon'], $bbox['max']['lon']);

                $sort[] = 'ORDER BY (p.lat - ?)*(p.lat - ?) + (p.lon - ?)*(p.lon - ?)'
                $sortParams = array(
                    $bbox['center']['lat'], $bbox['center']['lat'],
                    $bbox['center']['lon'], $bbox['center']['lon'],
                    );
            }
        }

        if (isset($this->categoryId)) {
            $conditions[] = 'pc.category_id = ?';
            $params[] = $this->categoryId;
        }

        if ($conditions) {
            $sql .= ' AND '.implode(' AND ', $conditions);
        }

        if ($sort) {
            $sql .= ' ORDER BY '.implode(', ', $sort);
            $params = array_merge($params, $sortParams);
        }

        $this->setSQL($sql);
        $this->setParameters($params);
        $this->setContext('type', 'placemark');
        return $this->retrieveData();
    }





}



