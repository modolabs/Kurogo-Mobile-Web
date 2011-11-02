<?php

// represents a top level map category
// for KML, each KML file is a top level category
// for ArcGIS, each layer (or service instance?) is a top level category

class MapDataController extends DataController implements MapFolder
{
    // data source config options
    protected $DEFAULT_PARSER_CLASS = 'KMLDataParser';
    protected $parser = null;
    protected $searchable = false;
    protected $title = null;

    // base map config options
    protected $defaultZoomLevel = 16;

    // not config variables    
    protected $items = null;
    protected $selectedPlacemarks = array();
    protected $allPlacemarks = array();
    protected $drillDownPath = array();

    protected $projectorReady = false;
    protected $projector = null;
    
    ////// default, slow search implementation
    
    const COMMON_WORDS = 'the of to and in is it you that he was for on are with as his they be at one have this from or had by hot but some what there we can out other were all your when up use word how said an each she which do their time if will way about many then them would write like so these her long make thing see him two has look more day could go come did my no most who over know than call first people may down been now find any new take get place made where after back only me our under';

    protected function cacheLifespan()
    {
       return Kurogo::getSiteVar('MAP_CACHE_LIFESPAN', 'maps');
    }

    public function canSearch()
    {
        return $this->searchable;
    }

    protected function featureMatchesTokens(Placemark $feature, Array $tokens)
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
                    if ( ($item->getTitle()==$searchText) || $this->featureMatchesTokens($item, $validTokens)) {
                        $results[] = $this->getProjectedFeature($item);
                    }
                }
            }
        }
        return $results;
    }

    protected function setupProjector()
    {
        if (!$this->projectorReady) {
            $sourceProjection = $this->getProjection();
            if (($sourceProjection instanceof MapProjection && !$this->getProjection()->isGeographic())
                || (!$sourceProjection instanceof MapProjection && $sourceProjection != GEOGRAPHIC_PROJECTION))
            {
                if ($this->projector === null) {
                    $this->projector = new MapProjector();
                    $this->projector->setSrcProj($this->getProjection());//$sourceProjection);
                }
            }
            $this->projectorReady = true;
        }
    }

    // argument must be lat/lon (not projected)    
    public function searchByProximity($center, $tolerance, $maxItems=null)
    {
        $this->setupProjector();

        $bbox = normalizedBoundingBox($center, $tolerance, null, null);

        $results = array();
        foreach ($this->getAllLeafNodes() as $item) {
            $geometry = $item->getGeometry();
            if ($geometry) {
                $featureCenter = $geometry->getCenterCoordinate();
                if ($this->projector) {
                    $featureCenter = $this->projector->projectPoint($featureCenter);
                }

                if ($featureCenter['lat'] <= $bbox['max']['lat']
                    && $featureCenter['lat'] >= $bbox['min']['lat']
                    && $featureCenter['lon'] <= $bbox['max']['lon']
                    && $featureCenter['lon'] >= $bbox['min']['lon']
                ) {
                    $distance = greatCircleDistance(
                        $bbox['center']['lat'], $bbox['center']['lon'],
                        $featureCenter['lat'], $featureCenter['lon']);
                    if ($distance > $tolerance) continue;

                    // keep keys unique; give priority to whatever came first
                    $intDist = intval($distance * 1000);
                    while (array_key_exists($intDist, $results)) {
                        $intDist += 1; // one centimeter
                    }
                    $item->setField('distance', $distance);
                    $item->addCategoryId($this->categoryId);
                    $results[$intDist] = $this->getProjectedFeature($item);
                }
            }
        }
        return $results;
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
    
    /////// view functions

    public function selectPlacemark($featureId)
    {
        $result = null;
        foreach ($this->getAllPlacemarks() as $feature) {
            if ($feature->getId() == $featureId) {
                $result = $this->getProjectedFeature($feature);
                break;
            }
        }
        if ($result) {
            $this->selectedPlacemarks[] = $result;
        }
        return $result;
    }

    public function setSelectedPlacemarks($features)
    {
        $this->selectedPlacemarks = $features;
    }

    public function getSelectedPlacemark()
    {
        return end($this->selectedPlacemarks);
    }

    public function getSelectedPlacemarks()
    {
        return $this->selectedPlacemarks;
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

    public function getFilteredPlacemarks()
    {
        $features = array();
        foreach ($this->getAllPlacemarks() as $feature) {
            if (!$this->drillDownPath 
                || in_array($this->drillDownPath, $feature->getCategories()))
            {
                $features[] = $this->getProjectedFeature($feature);
            }
        }
        return $features;
    }

    protected function getProjectedFeature(Placemark $placemark)
    {
        if ($placemark instanceof BasePlacemark) { // generic Placemark does not implement setGeometry
            $this->setupProjector();
            if ($this->projector !== null) {
                $geometry = $placemark->getGeometry();
                if ($geometry) {
                    $placemark->setGeometry($this->projector->projectGeometry($geometry));
                }
            }
        }
        return $placemark;
    }

    ////// MapFolder interface

    private static function listItemsAtPath(array $items, array $path=array(), $otherCateogryId='something_unique')
    {
        if (count($path)) {
            $firstItem = array_shift($path);
        }
        $folders = array();
        $features = array();
        foreach ($items as $item) {
            if ($item instanceof MapFolder) {
                $folders[$item->getId()] = $item;
            } elseif ($item instanceof Placemark) {
                $features[] = $item;
            }
        }

        if (count($folders) && count($features)) {
            // put dangling placemarks at this level into a folder called "Other"
            // since we don't have UI to handle mixed folders and placemarks
            $someUniqueId = substr(md5($otherCateogryId.count($folders)), 0, strlen($otherCateogryId)-1);
            $otherCategory = new MapBaseCategory($someUniqueId, 'Other places');
            $folders[$otherCategory->getId()] = $otherCategory;
            $otherCategory->setPlacemarks($features);
        }

        if (count($folders) >= 1) {
            // attempt to drill down if we are given a "subdirectory"
            if (isset($firstItem) && isset($folders[$firstItem])) {
                return self::listItemsAtPath(
                    $folders[$firstItem]->getListItems(), $path);
            }
            return $folders;
        }

        return $features;
    }

    public function getChildCategories()
    {
        return $this->parser->getChildCategories();
    }

    public function getAllPlacemarks()
    {
        if (!$this->allPlacemarks) {
            $this->getListItems(); // make sure we're populated
            $this->allPlacemarks = $this->parser->getAllPlacemarks();
        }
        return $this->allPlacemarks;
    }

    public function getListItems()
    {
        return self::listItemsAtPath($this->items(), $this->drillDownPath, $this->categoryId);
    }

    public function getProjection()
    {
        return $this->parser->getProjection();
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }

    // End MapFolder interface

    // implemented for compatibility with DataController
    public function getItem($name)
    {
        $this->selectPlacemark($name);
        return $this->getSelectedPlacemark();
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
            $this->items = $this->getParsedData();
        }
        return $this->parser->getTitle();
    }
    
    public function getDefaultZoomLevel() {
        return $this->defaultZoomLevel;
    }

    public static function defaultDataController()
    {
        return self::factory('GoogleGeoDataController', array());
    }

    // DataController overrides

    public function getData()
    {
        if ($this->parser instanceof ArcGISParser) {
            $this->addFilter('f', 'json');
        }
        return parent::getData();
    }

    public function getDataFile()
    {
        $url = $this->url();
        if (strpos($url, DATA_DIR) === 0) {
            return $url;
        }
        return parent::getDataFile();
    }
    
    protected function cacheFolder()
    {
        return CACHE_DIR . "/Maps";
    }

    protected function getCache() {
        $this->cache = parent::getCache();
        if ($this->parser instanceof ShapefileDataParser) {
            if (strpos($this->url(), '.zip') !== false) {
                $this->cache->setSuffix('.zip');
            }
        }
        return $this->cache;
    }

    protected function init($args)
    {
        parent::init($args);

        $this->searchable = isset($args['SEARCHABLE']) ? ($args['SEARCHABLE'] == 1) : false;

        if (isset($args['DEFAULT_ZOOM_LEVEL']))
            $this->defaultZoomLevel = $args['DEFAULT_ZOOM_LEVEL'];
        
        $this->categoryId = mapIdForFeedData($args);
    }

    protected function retrieveData($url)
    {
        if (strpos($url, '.kmz') !== false) {
            if (!class_exists('ZipArchive')) {
                throw new KurogoException("class ZipArchive (php-zip) not available");
            }
            $tmpDir = Kurogo::tempDirectory();
            if (!is_writable($tmpDir)) {
                throw new KurogoConfigurationException("Temporary directory $tmpDir not available");
            }
            $tmpFile = $tmpDir.'/tmp.kmz';

            copy($url, $tmpFile);
            $zip = new ZipArchive();
            $zip->open($tmpFile);
            $contents = $zip->getFromIndex(0);
            unlink($tmpFile);
            return $contents; // this is false on failure, same as file_get_contents
        }
        return parent::retrieveData($url);
    }
}

