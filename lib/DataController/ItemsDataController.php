<?php
/**
 * @package ExternalDataController
 */

/**
 * A subclass of ExternalDataController to handle the general data.
 *
 * @package ExternalData
 */
 
abstract class ItemsDataController extends ExternalDataController {
    
    protected $totalItems = null;
    
    /**
     * This method should return a single item based on the id
     * @param mixed $id the id to retrieve. The value of this id is data dependent.
	 * @return mixed The return value is data dependent. Subclasses should return false or null if the item could not be found
     */
    abstract public function getItem($id);
    abstract public function search($searchTerms, $start=0, $limit=null);
    
    /**
     * Sets the total number of items in the request. If subclasses override parseData() this method
     * should be called when the number of items is known. The value is usually set by retrieving the
     * the value of getTotalItems() from the DataParser.
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
     * state, if necessary. Subclasses should call parent::clearInteralCache()
     */
    protected function clearInternalCache() {
        $this->setTotalItems(null);
    }

    /**
     * Utility function to return a subset of items. Essentially is a robust version of array_slice.
     * @param array items
     * @param int $start 0 indexed value to start
     * @param int $limit how many items to return (use null to return all items beginning at $start)
     * @return array
     */
    protected function limitItems($items, $start=0, $limit=null) {
        $start = intval($start);
        $limit = is_null($limit) ? null : intval($limit);

        if ($limit && $start % $limit != 0) {
            $start = floor($start/$limit)*$limit;
        }
        
        if (!is_array($items)) {
            throw new KurogoDataException("Items list is not an array");
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
        if ($items = $this->items($index,1)) {
            return current($items); 
        } else {
            return false;
        }
    }
    
    /**
     * Default implementation of items. Will retrieve the parsed items based on the current settings
     * and return a filtered list of items
     * @param int $start 0 based index to start
     * @limit int $limit number of items to return
     */
    public function items($start=0, $limit=null) {
        Debug::die_here();
        $items = $this->getParsedData();
        return $this->limitItems($items,$start, $limit);
    }
}

