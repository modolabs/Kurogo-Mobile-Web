<?php

// represents a top level map category
// for KML, each KML file is a top level category
// for ArcGIS, each layer (or service instance?) is a top level category

class MapDataController extends DataController implements MapFolder
{
    const SEARCH_RESULTS = -1;

    const SELECTED_FEATURES = 1;
    const FILTERED_FEATURES = 2;
    const NO_FEATURES = 3;

    // data source config options
    protected $DEFAULT_PARSER_CLASS = 'KMLDataParser';
    protected $parser = null;
    protected $searchable = false;
    protected $title = null;

    // base map config options
    protected $DEFAULT_MAP_CLASS = 'GoogleStaticMap';
    protected $staticMapClass;
    protected $staticMapBaseURL = null;
    protected $dynamicMapClass = null;
    protected $dynamicMapBaseURL = null;
    protected $defaultZoomLevel = 16;

    // in theory all map images controllers should use the same
    // zoom level, but if certain image servers (e.g. Harvard ArcGIS)
    // have different definitions for zoom level, we need another
    // field to specify this
    protected $dynamicZoomLevel = null;

    // not config variables    
    protected $items = null;
    //protected $projection = GEOGRAPHIC_PROJECTION;
    protected $selectedFeatures = array();
    protected $displaySetId = self::SELECTED_FEATURES;
    protected $drillDownPath = array();
    
    const COMMON_WORDS = 'the of to and in is it you that he was for on are with as his they be at one have this from or had by hot but some what there we can out other were all your when up use word how said an each she which do their time if will way about many then them would write like so these her long make thing see him two has look more day could go come did my no most who over know than call first people may down been now find any new take get place made where after back only me our under';

    protected function cacheFolder()
    {
        return CACHE_DIR . "/Maps";
    }

    protected function cacheLifespan()
    {
        // TODO add config so the following line works instead
        //return Kurogo::getSiteVar('MAP_CACHE_LIFESPAN');
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
                $pattern = "/\b" . preg_quote($token, '/') . "\b/i";
                if (!preg_match($pattern, self::COMMON_WORDS)) {
                    $validTokens[] = $pattern;
                }
            }
            if (count($validTokens)) {
                foreach ($this->getAllLeafNodes() as $item) {
                    if ( ($item->getTitle()==$searchText) || self::featureMatchesTokens($item, $validTokens)) {
                        $results[] = $item;
                    }
                }
            }
        }
        return $results;
    }
    
    public function searchByProximity($center, $tolerance, $maxItems) {
        // approximate upper/lower bounds for lat/lon before calculating GCD
        $dLatRadians = $tolerance / EARTH_RADIUS_IN_METERS;
        // by haversine formula
        $dLonRadians = 2 * asin(sin($dLatRadians / 2) / cos($center['lat'] * M_PI / 180));

        $dLatDegrees = $dLatRadians * 180 / M_PI;
        $dLonDegrees = $dLonRadians * 180 / M_PI;

        $maxLat = $center['lat'] + $dLatDegrees;
        $minLat = $center['lat'] - $dLatDegrees;
        $maxLon = $center['lon'] + $dLonDegrees;
        $minLon = $center['lon'] - $dLonDegrees;

        $results = array();
        foreach ($this->getAllLeafNodes() as $item) {
            $geometry = $item->getGeometry();
            if ($geometry) {
                $featureCenter = $geometry->getCenterCoordinate();
                if ($featureCenter['lat'] <= $maxLat && $featureCenter['lat'] >= $minLat
                    && $featureCenter['lon'] <= $maxLon && $featureCenter['lon'] >= $minLon
                ) {
                    $distance = greatCircleDistance($center['lat'], $center['lon'], $featureCenter['lat'], $featureCenter['lon']);
                    if ($distance > $tolerance) continue;

                    // keep keys unique; give priority to whatever came first
                    $intDist = intval($distance * 1000);
                    while (array_key_exists($intDist, $results)) {
                        $intDist += 1; // one centimeter
                    }
                    $item->setField('distance', $distance);
                    $results[$intDist] = $item;
                }
            }
        }
        return $results;
    }
    
    public function getAllCategoryNodes() {
        return self::getCategoryNodesForItem($this);
    }
    
    protected static function getCategoryNodesForItem(MapFolder $item) {
        $nodes = array();
        foreach ($item->getListItems() as $innerItem) {
            if ($innerItem instanceof MapFolder && $innerItem instanceof MapListElement) {
                $node = array(
                    'title' => $innerItem->getTitle(),
                    'id' => $innerItem->getCategory(),
                    //'subtitle' => $innerItem->getSubtitle(),
                    );

                $subcategories = self::getCategoryNodesForItem($innerItem);
                if ($subcategories) {
                    $node['subcategories'] = $subcategories;
                }

                $nodes[] = $node;
            }
        }
        return $nodes;
    }
    
    protected function getAllLeafNodes() {
        $leafNodes = array();
        foreach ($this->items() as $item) {
            self::getLeafNodesForListItem($item, $leafNodes);
        }
        return $leafNodes;
    }
    
    protected static function getLeafNodesForListItem(MapListElement $listItem, Array &$results) {
        if ($listItem instanceof MapFolder) {
            foreach ($listItem->getListItems() as $innerItem) {
                self::getLeafNodesForListItem($innerItem, $results);
            }
        } else {
            $results[] = $listItem;
        }
    }

    public function selectFeature($featureId)
    {
        $result = null;
        foreach ($this->getAllFeatures() as $feature) {
            if ($feature->getId() == $featureId) {
                $result = $feature;
                break;
            }
        }
        if ($result) {
            $this->selectedFeatures[] = $result;
        }
        return $result;
    }

    public function setSelectedFeatures($features)
    {
        $this->selectedFeatures = $features;
    }

    public function getSelectedFeature()
    {
        return end($this->selectedFeatures);
    }

    public function getAllSelectedFeatures()
    {
        return $this->selectedFeatures;
    }

    public function addDisplayFilter($type, $value)
    {
        if ($type == 'category') {
            if ($value && !is_array($value)) {
                $value = array($value);
            }
            $this->drillDownPath = $value;
        }
    }

    public function clearDisplayFilters()
    {
        $this->drillDownPath = null;
    }

    public function getAllFilteredFeatures()
    {
        $features = array();
        foreach ($this->getAllFeatures() as $feature) {
            if (!$this->drillDownPath 
                || in_array($this->drillDownPath, $feature->getCategories()))
            {
                $features[] = $feature;
            }
        }
        return $features;
    }

    public function setDisplaySet($set=self::SELECTED_FEATURES)
    {
        $this->displaySetId = $set;
    }

    // DataController overrides

    public function getData()
    {
        if ($this->parser instanceof ArcGISParser) {
            $this->addFilter('f', 'json');
        }
        return parent::getData();
    }

    public function items()
    {
        if (!$this->items) {
            $this->items = parent::items();
        }
        return $this->items;
    }

    // MapFolder interface

    private static function listItemsAtPath(array $items, array $path=array())
    {
        if (count($path)) {
            $firstItem = array_shift($path);
        }
        $folders = array();
        $features = array();
        foreach ($items as $item) {
            if ($item instanceof MapFolder) {
                $folders[] = $item;
            } elseif ($item instanceof Placemark) {
                $features[] = $item;
            }
        }

        if (count($folders) && count($features)) {
            $otherCategory = new MapBaseCategory(count($folders), 'Other places');
            $folders[] = $otherCategory;
            $otherCategory->setFeatures($features);
        }

        if (count($folders) > 1) {
            if (isset($firstItem)) {
                if (count($folders) > $firstItem) {
                    return self::listItemsAtPath(
                        $folders[$firstItem]->getListItems(), $path);
                }
            }

            return $folders;
        }

        if (isset($firstItem)) {
            if (count($features) > $firstItem) {
                return $features[$firstItem];
            }
            return null;
        }

        return $features;
    }

    public function getChildCategories()
    {
        return $this->parser->getChildCategories();
    }

    public function getAllFeatures()
    {
        $this->getListItems(); // make sure we're populated
        return $this->parser->getAllFeatures();
    }

    public function getListItems()
    {
        return self::listItemsAtPath($this->items(), $this->drillDownPath);
    }

    public function getProjection()
    {
        if ($this->parser instanceof MapFolder) {
            return $this->parser->getProjection();
        }
        return null;
    }

    // TODO find some way to require that MapFolder objects include
    // setCategory and getCategory, even though the MapListElement
    // interface includes getCategory and would conflict with classes
    // that implement both
    
    public function setCategory($categoryPath) {
        if (!is_array($categoryPath)) {
            $categoryPath = explode(MAP_CATEGORY_DELIMITER, $categoryPath);
        }
        $this->parser->setCategory($categoryPath);
    }

    public function getCategory() {
        return $this->parser->getCategory();
    }

    // End MapFolder interface

    public function getFeature($name, $categoryPath=array()) {
        $items = $this->getListItems($categoryPath);
        if (isset($items[$name])) {
            return $items[$name];
        }
        return null;
    }

    // implemented for compatibility with DataController
    public function getItem($name)
    {
        return $this->getFeature($name);
    }

    // override what the feed says
    public function setTitle($title) {
        $this->title = $title;
    }

    public function getTitle() {
        if ($this->title !== null) {
            return $this->title;
        }

        if (!$this->items) {
            $data = $this->getData();
            $this->items = $this->parseData($data);
        }
        return $this->parser->getTitle();
    }

    /*
    public function items() {
        if (!$this->items) {
            $data = $this->getData();
            $this->items = $this->parseData($data);
        }
        return $this->items;
    }
    */
    
    public function getDefaultZoomLevel() {
        return $this->defaultZoomLevel;
    }

    public function getMapImageController(MapDevice $mapDevice)
    {
        if ($mapDevice->pageSupportsDynamicMap() && $this->dynamicMapClass !== null) {
            $imgController = $this->getDynamicMapController();
        } else {
            $imgController = $this->getStaticMapController();
        }

        // TODO pass projection info on to map image controller

        $placemarks = array();

        switch ($this->displaySetId) {
            case self::SELECTED_FEATURES:
                // add annotations via selectFeature()
                $placemarks = $this->getAllSelectedFeatures();
                break;
            case self::FILTERED_FEATURES:
                // add annotations that match displayFilters
                $placemarks = $this->getAllFilteredFeatures();
                break;
            case self::NO_FEATURES:
            default:
                // don't add any annotations, this will be done in the view
                break;
        }

        foreach ($placemarks as $placemark) {
            $imgController->addPlacemark($placemark);
        }

        // TODO better way to set default center coordinate
        if (count($placemarks)) {
            $lastPlacemark = end($placemarks);
            $imgController->setCenter($lastPlacemark->getGeometry()->getCenterCoordinate());
        }

        return $imgController;
    }

    public function getStaticMapController() {
        $controller = MapImageController::factory($this->staticMapClass, $this->staticMapBaseURL);
        return $controller;
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

    public static function defaultDataController()
    {
        $args = array(
            'JS_MAP_CLASS' => 'GoogleJSMap',
            'DEFAULT_ZOOM_LEVEL' => $module->getOptionalModuleVar(
                'DEFAULT_ZOOM_LEVEL', 10)
            );
        
        return self::factory('MapDataController', $args);
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

