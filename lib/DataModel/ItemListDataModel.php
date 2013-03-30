<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
 * @package DataModel
 */

/**
 * A subclass of DataModel to handle the general data.
 *
 * @package DataModel
 */
 
abstract class ItemListDataModel extends DataModel {
    
    protected $totalItems = 0;
    protected $items = array();
    
    public function setStart($start) {
        $this->setOption('start', $start);
    }

    public function getStart() {
        return $this->getOption('start');
    }

    public function setLimit($limit) {
        $this->setOption('limit', $limit);
    }
    
    public function getLimit() {
        return $this->getOption('limit');
    }
    
    public function search($searchTerms) {
        if ($this->retriever->canSearch()) {
            $items = $this->retriever->search($searchTerms, $response);
            if ($totalItems = $response->getContext('totalItems')) {
                $this->setTotalItems($totalItems);
            }
            
        } else {
            //save the start/limit settings, we have to get back all the entries before filtering
            $start = $this->getOption('start');
            $limit = $this->getOption('limit');
            $this->setStart(0);
            $this->setLimit(null);
            
            // get all the items
            $_items = $this->items();
            
            //restore start/limit settings
            $this->setStart($start);
            $this->setLimit($limit);
            
            $items = array();
            foreach ($_items as $item) {
                if ($item->filterItem(array('search'=>$searchTerms))) {
                    $items[] = $item;
                }
            }
            
            $this->setTotalItems(count($items));
        }

        // the result has not been limited        
        if (count($items) == $this->getTotalItems()) {
            $items = $this->limitItems($items, $this->getStart(), $this->getLimit());
        }

        return $items;

    }

    /**
     * This method should return a single item based on the id
     * @param mixed $id the id to retrieve. The value of this id is data dependent.
	 * @return KurogoObject The return value is data dependent. Subclasses should return false or null if the item could not be found
     */
    public function getItem($id) {
        if ($this->retriever instanceOf ItemDataRetriever) {
            $item = $this->retriever->getItem($id, $response);
            return $item;
        }

        if (!$id) {
            return null;
        }
        
        $items = $this->items();
        
        foreach ($items as $item) {
            if ($item->getID()==$id) {
                return $item;
            }
        }
        
        return null;
    }
    
    /**
     * Sets the total number of items in the request. 
     * @param int
     */
    public function setTotalItems($totalItems) {
        $this->totalItems = $totalItems;
    }
    
    /**
     * Returns the total number of items in the request
     * @return int
     */
    public function getTotalItems() {
        return $this->totalItems;
    }

    /**
     * Clears the internal cache of data. Subclasses can override this method to clean up any necessary
     * state, if necessary. Subclasses should call parent::clearInternalCache()
     */
    public function clearInternalCache() {
        $this->setTotalItems(null);
        parent::clearInternalCache();
    }

    /**
     * Utility function to return a subset of items. Essentially is a robust version of array_slice.
     * @param array items
     * @param int $start 0 indexed value to start
     * @param int $limit how many items to return (use null to return all items beginning at $start)
     * @return array
     */
    public function limitItems($items, $start=0, $limit=null) {
        $start = intval($start);
        $limit = is_null($limit) ? null : intval($limit);
        
        if (!is_array($items)) {
            return array();
        }
        
        if ($start>0 || !is_null($limit)) {
            $items = array_slice($items, $start, $limit);
        }
        
        return $items;
        
    }

    /**
     * Returns an item at a particular index
     * @param int index
     * @return mixed the item or false if it's not there
     */
    public function getItemByIndex($index) {
        if ($items = $this->items()) {
            if(isset($items[$index])){
                return $items[$index];
            }
        }
        return false;
    }
    
    /**
     * Default implementation of items. Will retrieve the parsed items based on the current settings
     * and return a filtered list of items
     */
    public function items() {
        $items = $this->retriever->getData($response);
        if ($totalItems = $response->getContext('totalItems')) {
            $this->setTotalItems($totalItems);
        }

        // the result has not been limited        
        if (count($items)== $this->getTotalItems()) {
            $items = $this->limitItems($items, $this->getStart(), $this->getLimit());
        }
        
        return $items;
    }
}

