<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KurogoStatsOption {

    protected $type = array();
    protected $fields = array();
    protected $filters = array();
    protected $group = array();
    protected $service = null;
    protected $sortField;
    protected $sortDir = SORT_DESC;
    protected $limit;
    
    public function setType($type) {
        $this->type = $type;
    }
    
    public function setSortField($field) {
        $this->sortField = $field;
    }

    public function setSortDir($dir) {
        $this->sortDir = $dir;
    }

    public function setService($service) {
        if (!in_array($service, array('web','api'))) {
            throw new Exception("Invalid service $service");
        }
        $this->service = $service;
        $this->addFilter('service','EQ',$service);
    }

    public function setLimit($limit) {
        $this->limit = intval($limit);
    }

    public function getService() {
        return $this->service;
    }
    
    
    protected function isValidField($field) {
        
    }
    
    public function setField($field) {
        if (!KurogoStats::isValidField($field)) {
            throw new Exception("Invalid field $field");
        }
        $this->fields = is_array($field) ? $field : explode(',', $field);
    }
    
    public function setFilters($filters) {
        foreach ($filters as $filter) {
            $filterObj = new KurogoStatsFilter();
            if (isset($filter['field'])) {
                $filterObj->setField($filters['field']);
            }
            if (isset($filter['comparison'])) {
                $filterObj->setComparison($filter['comparison']);
            }
            if (isset($filter['value'])) {
                $filterObj->setValue($filter['value']);
            }
            $this->filters[] = $filterObj;
        }
    }
    
    public function addFilter($field, $comparison, $value) {
        $filterObj = new KurogoStatsFilter();
        $filterObj->setField($field);
        $filterObj->setComparison($comparison);
        $filterObj->setValue($value);
        $this->filters[] = $filterObj;
    }
    
    public function setGroup($group) {
        if (KurogoStats::isValidGroup($group)) {
            $this->group = is_array($group) ? $group : explode(',', $group);
        } else {
            throw new Exception("Invalid group field $group");
        }
    }
    
    public function getType() {
        return $this->type;
    }
    
    public function getFields() {
        return $this->fields;
    }
    
    public function getFilters() {
        return $this->filters;
    }
    
    public function getGroup() {
        return $this->group;
    }

    public function getSortField() {
        return $this->sortField;
    }

    public function getSortDir() {
        return $this->sortDir;
    }

    public function getLimit() {
        return $this->limit;
    }
}


