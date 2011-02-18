<?php

// for KML, each KML file is a category
// for ArcGIS, each layer (or service instance?) is a category

define('GEOGRAPHIC_PROJECTION', 4326);

class MapDataController extends DataController
{
    const SEARCH_RESULTS = -1;

    protected $parser = null;
    protected $DEFAULT_PARSER_CLASS = 'KMLDataParser';
    protected $DEFAULT_MAP_CLASS = 'GoogleStaticMap';
    protected $items = null;
    protected $staticMapBaseURL = null;
    protected $dynamicMapBaseURL = null;
    protected $searchable = false;
    protected $defaultZoomLevel = 16;
    
    // in theory all map images controllers should use the same
    // zoom level, but if certain image servers (e.g. Harvard ArcGIS)
    // have different definitions for zoom level, we need another
    // field to specify this
    protected $dynamicZoomLevel = null;
    
    const COMMON_WORDS = 'the of to and in is it you that he was for on are with as his they be at one have this from or had by hot but some what there we can out other were all your when up use word how said an each she which do their time if will way about many then them would write like so these her long make thing see him two has look more day could go come did my no most who over know than call first people may down been now find any new take get place made where after back only me our under';

    protected $staticMapClass;
    protected $dynamicMapClass = null;

    protected function cacheFolder()
    {
        return CACHE_DIR . "/Maps";
    }

    protected function cacheLifespan()
    {
        // TODO add config so the following line works instead
        //return $GLOBALS['siteConfig']->getVar('MAP_CACHE_LIFESPAN');
        return 86400;
    }

    protected function cacheFileSuffix()
    {
        return null;
    }

    public function canSearch()
    {
        return $this->searchable;
    }

    private static function featureMatchesTokens(MapFeature $feature, Array $tokens)
    {
        $matched = true;
        $title = $feature->getTitle();
        foreach ($tokens as $token) {
            if (!preg_match($token, $title)) {
                $matched = false;
            }
        }
        return $matched;
    }

    // default search implementation loops through all relevant features
    public function search($searchText)
    {
        $results = array();
        if ($this->searchable) {
            $tokens = explode(' ', $searchText);
            $validTokens = array();
            foreach ($tokens as $token) {
                if (strlen($token) <= 1)
                    continue;
                $pattern = "/\b$token\b/i";
                if (!preg_match($pattern, self::COMMON_WORDS)) {
                    $validTokens[] = $pattern;
                }
            }
            if (count($validTokens)) {
                $id = 0;
                foreach ($this->items() as $id => $item) {
                    if ($item instanceof MapFeature) {
                        if (self::featureMatchesTokens($item, $validTokens)) {
                            $results[$id] = $item;
                        }
                    } else {
                        $subCategoryResults = array();
                        foreach ($item->getItems() as $featureID => $feature) {
                            if (self::featureMatchesTokens($feature, $validTokens)) {
                                $subCategoryResults[$featureID] = $feature;
                            }
                            if (count($subCategoryResults)) {
                                $results[$id] = $subCategoryResults;
                            }
                        }
                    }
                }
            }
        }
        return $results;
    }

    public function getListItems($subCategory=null) {
        if ($subCategory === null) {
            return $this->items();
        } else {
            $folder = $this->getItem($subCategory);
            return $folder->getItems();
        }
    }

    public function getFeature($name, $subCategory=null) {
        if ($subCategory !== null) {
            $folder = $this->getItem($subCategory);
            $itemList = $folder->getItems();
            if (isset($itemList[$name])) {
                return $itemList[$name];
            }
        }
        return $this->getItem($name);
    }
    
    public function getProjection() {
        return GEOGRAPHIC_PROJECTION;
    }

    public function getItem($name)
    {
        $items = $this->items();
        if (isset($items[$name]))
            return $items[$name];

        return null;
    }

    public function getTitle() {
        if (!$this->items) {
            $data = $this->getData();
            $this->items = $this->parseData($data);
        }
        return $this->parser->getTitle();
    }

    public function items() {
        if (!$this->items) {
            $data = $this->getData();
            $this->items = $this->parseData($data);
        }
        return $this->items;
    }
    
    public function getDefaultZoomLevel() {
        return $this->defaultZoomLevel;
    }

    public function getStaticMapController() {
        $controller = MapImageController::factory($this->staticMapClass, $this->staticMapBaseURL);
        return $controller;
    }

    public function supportsDynamicMap() {
        //return false;
        return ($this->dynamicMapClass !== null);
    }

    public function getDynamicMapController() {
        if (is_array($this->dynamicMapBaseURL)) {
            $baseURL = $this->dynamicMapBaseURL[0];
            $moreLayers = $this->dynamicMapBaseURL;
            array_splice($moreLayers, 0, 1);
        } else {
            $baseURL = $this->dynamicMapBaseURL;
            $moreLayers = array();
        }
        $controller = MapImageController::factory($this->dynamicMapClass, $baseURL);
        if ($this->dynamicMapClass == 'ArcGISJSMap') {
            $controller->addLayers($moreLayers);
            if ($this->dynamicZoomLevel !== null) {
                $controller->setPermanentZoomLevel($this->dynamicZoomLevel);
            }
        }
        return $controller;
    }

    protected function init($args)
    {
        parent::init($args);
        // static map support required; dynamic optional
        if (isset($args['STATIC_MAP_CLASS']))
            $this->staticMapClass = $args['STATIC_MAP_CLASS'];
        else
            $this->staticMapClass = $this->DEFAULT_MAP_CLASS;

        // other optional fields
        if (isset($args['JS_MAP_CLASS']))
            $this->dynamicMapClass = $args['JS_MAP_CLASS'];
        
        if (isset($args['STATIC_MAP_BASE_URL']))
            $this->staticMapBaseURL = $args['STATIC_MAP_BASE_URL'];
        
        if (isset($args['DYNAMIC_MAP_BASE_URL']))
            $this->dynamicMapBaseURL = $args['DYNAMIC_MAP_BASE_URL'];
        
        $this->searchable = isset($args['SEARCHABLE']) ? ($args['SEARCHABLE'] == 1) : false;

        if (isset($args['DEFAULT_ZOOM_LEVEL']))
            $this->defaultZoomLevel = $args['DEFAULT_ZOOM_LEVEL'];
    }
}

