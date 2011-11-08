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
        if ($this->retriever->supportsSearch()) {
            $this->response = $this->retriever->search($searchTerms);
            $items = $this->parseResponse($this->response);
            $this->setTotalItems($this->parser->getTotalItems());
            
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

    protected function parseFile($file, DataParser $parser=null) {       
        if (!$parser) {
            $parser = $this->parser;
        }
        $data = parent::parseFile($file, $parser);
        $this->setTotalItems($parser->getTotalItems());
        return $data;
    }
    
    protected function parseData($data, DataParser $parser=null) {       
        if (!$parser) {
            $parser = $this->parser;
        }

        $data = parent::parseData($data, $parser);
        $this->setTotalItems($parser->getTotalItems());
        return $data;
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
     */
    public function items() {
        $items = $this->getParsedData();
        
        // the result has not been limited        
        if (count($items)== $this->getTotalItems()) {
            $items = $this->limitItems($items, $this->getStart(), $this->getLimit());
        }
        
        return $items;
    }
}

