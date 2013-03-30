<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

Kurogo::includePackage('Maps');

class MapWebModule extends WebModule {

    protected $id = 'map';

    protected $mapURL = null;
    protected $feedGroup = null;
    protected $feedGroups = null;
    protected $dataModel = null;
    protected $numGroups;
    protected $feeds;
    protected $placemarkId;
    protected $mapDevice = null;
    protected $selectedPlacemarks;
    protected $redirectSearch = true; // whether or not to redirect to detail page if there is one search result
    protected $mapImageElementId = 'mapimage'; // set in initialize()

    ////// inherited and conventional module functions
    
    public function getFeedGroups() {
        return $this->getModuleSections('feedgroups');
    }
     
    // overrides function in Module.php
    protected function loadFeedData($requestedFeedId=null) {
        $data = array();
        $feedConfigFile = NULL;

        if ($this->feedGroup !== NULL) {
            $configName = "feeds-{$this->feedGroup}";
            foreach ($this->getModuleSections($configName) as $id => $feedData) {
                // get aliases if any
                $feedData['group'] = $this->feedGroup;
                $aliases = $this->getOptionalModuleSection($id, "aliases-{$this->feedGroup}");
                if ($aliases) {
                    $feedData['ALIASES'] = $aliases;
                }
                $feedId = mapIdForFeedData($feedData);
                $data[$feedId] = $feedData;
            }

        } else {

            if ($requestedFeedId === null) {
                $requestedFeedId = $this->getArg('feed', null);
            }
            // if no feed group and category are specified, load whole list
            foreach ($this->feedGroups as $groupID => $groupSettings) {
                $configName = "feeds-$groupID";
                $groupData = array();
                foreach ($this->getModuleSections($configName) as $id => $feedData) {
                    $feedData['group'] = $groupID;
                    $aliases = $this->getOptionalModuleSection($id, "aliases-{$this->feedGroup}");
                    if ($aliases) {
                        $feedData['ALIASES'] = $aliases;
                    }
                    $feedId = mapIdForFeedData($feedData);
                    $groupData[$feedId] = $feedData;
                    if ($requestedFeedId == $feedId) {
                        $this->feedGroup = $groupID;
                    }
                }

                if ($this->feedGroup !== null) {
                    $data = $groupData;
                    break;
                } else {
                    $data = array_merge($data, $groupData);
                }
            }
        }

        return $data;
    }

    protected function getModuleAdminSections() {
        $sections = parent::getModuleAdminSections();
        
        foreach ($this->getFeedGroups() as $feedgroup=>$data) {
            $sections['feeds-'.$feedgroup] = array(
                'title'=>$data['title'],
                'type'=>'module hidden'
            );
        }
        
        return $sections;
    }
    
    protected function getModuleAdminConfig() {
        $configData = parent::getModuleAdminConfig();
        
        foreach ($this->getFeedGroups() as $feedgroup=>$data) {
            $feedData = $configData['feed'];
            $feedData['title'] = $data['title'];
            $feedData['config'] = 'feeds-' . $feedgroup;
            $feedData['configMode'] = Config::OPTION_CREATE_EMPTY;
            $configData['feeds-'.$feedgroup] = $feedData;
        }
        unset($configData['feed']);
        
        return $configData;
    }

    public function searchItems($searchTerms, $limit=null, $options=null)
    {
        $mapSearch = $this->getSearchClass($options);
        $searchResults = array_values($mapSearch->searchCampusMap($searchTerms));
        if ($limit) {
            return array_slice($searchResults, 0, $limit);
        }
        return $searchResults;
    }

    public function linkForValue($value, Module $callingModule, $otherValue=null)
    {
        $external = !$callingModule instanceof MapWebModule;

        $latLon = filterLatLon($value);
        if ($latLon !== false) {
            $page = 'detail';
            $urlParams = $latLon;
        } else {
            $page = 'search';
            $urlParams = array(
                'filter'   => $value,
                'external' => $external, // determines which search engine to use
                );
        }

        return array(
            'title' => $value,
            'class' => 'map', // css class to show map icon in table rows
            'url'   => $this->buildBreadcrumbURL($page, $urlParams, false),
        );
    }

    protected function pageForPlacemark(Placemark $placemark) {
        $page = 'detail';
        $params = $placemark->getURLParams();
        // only if the placemark is searched, and there are 2 feed groups
        // the feedGroup is mandartory
        if(empty($this->feedGroup) && isset($params['group'])) {
            $this->feedGroup = $params['group'];
        }
        if (isset($params['feed']) && $this->isMapDrivenUI($params['feed'])) {
            //$fullscreen = ($this->numGroups > 1) ? 'campus' : 'index';
            //if ($this->page != $fullscreen) { // use detail page if we're already on a fullscreen map
            //    $page = $fullscreen;
            //}
            if (($this->page != 'campus' || $this->getArg('listview'))
                && !$this->getArg('mapview'))
            { // use detail page if we're already on a fullscreen map
                $page = 'campus';
            }
        }
        return $page;
    }

    public function linkForItem(KurogoObject $mapItem, $options=null)
    {
        $result = array(
            'title'    => $mapItem->getTitle(),
            'subtitle' => $mapItem->getSubtitle(),
            );

        if ($mapItem instanceof Placemark) {
            $result['class'] = 'placemark';

            if ($mapItem instanceof BasePlacemark && ($url = $mapItem->getURL())) {
                // if url was set via setURL -- only applies to campus placemarks on worldmap
                $result['url'] = $url;

            } else {
                $urlArgs = $mapItem->getURLParams();
                $addBreadcrumb = $options && isset($options['addBreadcrumb']) && $options['addBreadcrumb'];
                $result['url'] = $this->buildBreadcrumbURL(
                    $this->pageForPlacemark($mapItem), $urlArgs, $addBreadcrumb);
            }

            if (($distance = $mapItem->getField('distance')) && $this->getOptionalModuleVar('SHOW_DISTANCES', true)) {
                $result['subtitle'] = $this->displayTextFromMeters($distance);
            }

        } else {
            $external = $options && isset($options['external']) && $options['external'];
            $feedId = $options && isset($options['feed']) ? $options['feed'] : null;
            $groupId = $options && isset($options['group']) ? $options['group'] : null;
            $category = implode(MAP_CATEGORY_DELIMITER, $mapItem->getIdStack());
            $result['url'] = $this->categoryURL($category, $feedId, $groupId, $external);
        }

        return $result;
    }

    ////// private data retrieval
    
    protected function getDataForGroup($group) {
        return isset($this->feedGroups[$group]) ? $this->feedGroups[$group] : array();
    }

    protected function getFeedData()
    {
        if (!$this->feeds) {
            $this->feeds = $this->loadFeedData();
        }
        return $this->feeds;
    }

    // assumes feeds are loaded
    protected function getDataModel($feedId=null)
    {
        // re-instantiate DataModel if a different feed is requested.
        if ($this->dataModel && $feedId !== $this->dataModel->getFeedId()) {
            $this->dataModel = null;
        }
        if ($this->dataModel === null) {
            $feedData = $this->getCurrentFeed($feedId);
            $this->dataModel = mapModelFromFeedData($feedData);
        }

        return $this->dataModel;
    }

    protected function getCurrentFeed($feedId=null) {
        $this->getFeedData();
        if ($feedId === null || $feedId === '') {
            $feedId = $this->getArg('feed');
        }
        if ($feedId) {
            if (isset($this->feeds[$feedId])) {
                return $this->feeds[$feedId];
            }
        }
        Kurogo::log(LOG_WARNING,"Warning: unable to find feed data for feed $feedId",'maps');
        return null;
    }

    protected function getMergedConfigData($feedId=null) {
        if ($this->getArg('worldmap')) {
            return array();
        }

        // allow individual feeds to override values in the feed group
        if ($this->feedGroup === null) {
            // putting this to see if/when this happens
            throw new Exception("feed group not set");

            Kurogo::log(LOG_WARNING,"Warning: feed group not set when initializing image controller, using first group",'maps');
            $this->feedGroup = key($this->feedGroups);
        }

        $configData = $this->getDataForGroup($this->feedGroup);

        // allow individual feeds to override group value
        $feedData = $this->getCurrentFeed($feedId);
        if ($feedData) {
            foreach ($feedData as $key => $value) {
                $configData[$key] = $value;
            }
        }
        return $configData;
    }

    protected function getSearchClass($options=array()) {
        $mapSearchClass = $this->getOptionalModuleVar('MAP_SEARCH_CLASS', 'MapSearch');
        if (isset($options['external']) && $options['external']) {
            // use the same search class by default
            $mapSearchClass = $this->getOptionalModuleVar('MAP_EXTERNAL_SEARCH_CLASS', $mapSearchClass);
        }
        $mapSearch = new $mapSearchClass($this->getFeedData());
        $group = isset($options['group']) ? $options['group'] : $this->feedGroup;
        $mapSearch->setFeedGroup($group);
        $mapSearch->init($this->getDataForGroup($group));
        if ($mapSearch instanceof GoogleMapSearch && $mapSearch->isPlaces()) {
            $this->assign('poweredByGoogle', true);
            $this->redirectSearch = false;
        }
        return $mapSearch;
    }

    protected function getTitleForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        if (isset($params['featureindex'])) {
            $index = $params['featureindex'];
            $feedId = $params['feed'];
            $controller = $this->getDataModel($feedId);
            if ($controller instanceof MapDataModel) {
                $controller->clearCategoryId();
            }
            if (isset($params['category'])) {
                $category = $controller->findCategory($params['category']);
            }
            $controller->setSelectedPlacemarks(array());
            if (!$placemark = $controller->selectPlacemark($index)) {
                $result = array('Unknown');
                if (isset($params['title'])) {
                    $result = array($params['title']);
                    if (isset($params['address'])) {
                        $result[] = $params['address'];
                    }
                }
                return $result;
            }
            
            if (is_array($placemark)) { // MapDataModel always returns arrays of placemarks
                $placemark = $placemark[0];
            }
            
            // only show the subtitle if there is more than 1 "campus"
            if (count($this->feedGroups)>1) {
                $subtitle = $controller->getTitle();
            } else {
                $subtitle = '';
            }
            return array($placemark->getTitle(), $subtitle);
        
        } else if (isset($params['title'])) {
            $result = array($params['title']);
            if (isset($params['address'])) {
                $result[] = $params['address'];
            }
            return $result;

        } else {
            return array($aBookmark);
        }
    }

    protected function bookmarkIDForPlacemark($placemark) {
        if ($placemark) {
            $cookieParams = $placemark->getURLParams();
        }
        $title = $this->getArg('title');
        if ($title) {
            $cookieParams['title'] = $title;
        } else {
            $cookieParams['title'] = $placemark->getTitle();
        }
        return http_build_query($cookieParams);
    }
    
    ///////////// url builders

    private function groupURL($group, $addBreadcrumb=false) {
        if (!$group) {
            $topPage = 'index';
            $args = array();
        } else {
            $args = $this->args;
            $args['group'] = $group;
            if (isset($args['worldmap'])) {
                unset($args['worldmap']);
            }
            if (isset($args['listview'])) {
                unset($args['listview']);
            }
            if (isset($args[self::AJAX_PARAMETER])) {
                unset($args[self::AJAX_PARAMETER]);
            }
            $topPage = ($this->numGroups > 1) ? 'campus' : 'index';
        }
        return $this->buildBreadcrumbURL($topPage, $args, $addBreadcrumb);
    }
  
    private function feedURL($feed, $group=null, $addBreadcrumb=false) {
        if (!$group) {
            $group = $this->feedGroup;
        }
        $args = array(
            'group' => $group,
            'feed' => $feed,
            );
        return $this->buildBreadcrumbURL('category', $args, $addBreadcrumb);
    }

    private function categoryURL($category, $feed=null, $group=null, $addBreadcrumb=false) {
        if (!$group) {
            $group = $this->feedGroup;
        }
        if (!$feed) {
            $feed = $this->getArg('feed');
        }
        $args = array(
            'group' => $group,
            'feed' => $feed,
            'category' => $category,
            );
        return $this->buildBreadcrumbURL('category', $args, $addBreadcrumb);
    }
    
    protected function detailURLForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        if (isset($params['featureindex']) || isset($params['lat'], $params['lon'])) {
            $feedId = $params['feed'];
            $this->loadFeedData($feedId);
            if ($this->isMapDrivenUI($feedId)) {
                if (!isset($params['group']) && $this->feedGroup) {
                    $params['group'] = $this->feedGroup;
                }
                //$mapPage = ($this->numGroups > 1) ? 'campus' : 'index';
                //return $this->buildURL($mapPage, $params);
                return $this->buildURL('campus', $params);
            }
            return $this->buildBreadcrumbURL('detail', $params, true);
        } else {
            return '#';
        }
    }

    // static maps only
    private function detailUrlForPan($direction, $imgController) {
        $args = $this->args;
        $center = $imgController->getCenterForPanning($direction);
        $args['center'] = $center['lat'] .','. $center['lon'];
        return $this->buildBreadcrumbURL('detail', $args, false);
    }

    // static maps only
    private function detailUrlForZoom($direction, $imgController) {
        $args = $this->args;
        $args['zoom'] = $imgController->getLevelForZooming($direction);
        return $this->buildBreadcrumbURL('detail', $args, false);
    }

    ////// basemap retrieval

    private function getMapDevice()
    {
        if (!$this->mapDevice) {
            $this->mapDevice = new MapDevice($this->pagetype, $this->platform);
        }
        return $this->mapDevice;
    }

    protected function isMapDrivenUI($category=null)
    {
        list($class, $static) = MapImageController::basemapClassForDevice(
            $this->getMapDevice(),
            $this->getMergedConfigData($category));
        return !$static;
    }

    private function getImageController()
    {
        return MapImageController::factory(
            $this->getMergedConfigData(),
            $this->getMapDevice());
    }

    private function assignItemsFromFeed($feedId, $searchTerms=null, $isMapView=false) {
        $dataModel = $this->getDataModel($feedId);
        $title = $dataModel->getTitle();

        $categoryId = $this->getArg('category', null);
        if ($categoryId !== null) {
            $category = $dataModel->findCategory($categoryId);
            $title = $category->getTitle();
        }

        if ($searchTerms) {
            $listItems = $dataModel->search($searchTerms);
        } else {
            $listItems = $dataModel->items();
            while (count($listItems) == 1 && end($listItems) instanceof MapFolder) {
                $categoryId = end($listItems)->getId();
                $category = $dataModel->findCategory($categoryId);
                $title = $category->getTitle();
                $listItems = $dataModel->items();
            }
        }

        if ($title) {
            $this->setPageTitles($title);
        }

        $linkOptions = array('feed' => $feedId, 'group' => $this->feedGroup);

        if (count($listItems) == 1 && !$this->getArg('listview')) {
            $link = $this->linkForItem(current($listItems), $linkOptions);
            Kurogo::redirectToURL(rtrim(URL_BASE, '/') . $link['url']);
        }

        $this->selectedPlacemarks = array();
        $placemarkLoad = 0;

        $results = array();
        foreach ($listItems as $listItem) {
            if (!$isMapView) {
                $results[] = $this->linkForItem($listItem, $linkOptions);
            }
            if ($listItem instanceof Placemark) {
                $this->selectedPlacemarks[] = $listItem;

                $geometry = $listItem->getGeometry();
                if ($geometry instanceof MapPolygon) {
                    $placemarkLoad += 4;
                } elseif ($geometry instanceof MapPolyline) {
                    $placemarkLoad += 2;
                } else {
                    $placemarkLoad += 1;
                }
            }
        }

        if ($isMapView) {
            $this->setTemplatePage('fullscreen');
            $this->initializeDynamicMap();
        } else {
            if (isset($this->feedGroups[$this->feedGroup])) {
                $feedData = $this->getCurrentFeed($feedId);
                $showCampusTitle = isset($feedData['SHOW_CAMPUS_TITLE']) ? $feedData['SHOW_CAMPUS_TITLE'] : false;
                if ($showCampusTitle) {
                    $title = $this->feedGroups[$this->feedGroup]['title'] . " " . $title;
                }
            }


            $this->assign('title',  $title);
            $this->assign('navItems', $results);
            if ($this->numGroups > 1) {
                $this->assignClearLink();
            }

            if ($this->isMapDrivenUI() && $placemarkLoad
                && $placemarkLoad <= $this->getOptionalModuleVar('placemarkLoad', 30))
            {
                $mapArgs = array_merge($this->args, $linkOptions);
                if (isset($mapArgs['listview'])) {
                    unset($mapArgs['listview']);
                }
                $mapArgs['mapview'] = true;
                $this->mapURL = $this->buildBreadcrumbURL($this->page, $mapArgs, false);
            }
        }
    }

    ///// template control

    private function assignGroups($templateArg='campuses') {
        $campusData = array();
        if ($this->numGroups > 1) {
            foreach ($this->feedGroups as $id => $groupData) {
                $data = array(
                    'id' => $id,
                    'title' => $groupData['title'],
                    'url' => $this->groupURL($id),
                    'listclass' => $id, // stupid way to sneak the id into the dom
                    );
                $campusData[] = $data;
            }
            $this->assign($templateArg, $campusData);
        }
        return $campusData;
    }
    
    private function assignFeeds() {
        $categories = array();
        $places = array();
        $feeds = $this->getFeedData();

        foreach ($this->getFeedData() as $id => $feed) {
            if (isset($feed['HIDDEN']) && $feed['HIDDEN']) {
                continue;
            }
            $subtitle = isset($feed['SUBTITLE']) ? $feed['SUBTITLE'] : null;
            $categories[] = array(
                'id'       => $id,
                'title'    => $feed['TITLE'],
                'subtitle' => $subtitle,
                'url'      => $this->feedURL($id),
                );
        }

        if (count($categories) == 1) {
            $this->assignItemsFromFeed($categories[0]['id']);
        } else {
            $this->assign('navItems', $categories);
        }
    }

    private function assignSearchResults($searchTerms) {
        // still need a way to show the Google logo if we use their search
        $searchResults = $this->searchItems($searchTerms, null, $this->args);
        $places = array();
        foreach ($searchResults as $place) {
            $places[] = $this->linkForItem($place);
        }
        $this->assign('places', $places);
        return $places;
    }

    protected function assignClearLink()
    {
        $clearLink = array(array(
            'title' => $this->getLocalizedString('ALL_MAP_GROUPS'),
            'url' => $this->groupURL(''),
            ));
        $this->assign('clearLink', $clearLink);
    }

    protected function configureUserLocation() {
        // extra javascript
        $showUserLocation = $this->getOptionalModuleVar('MAP_SHOWS_USER_LOCATION', false);
        if ($showUserLocation) {
            $this->addInlineJavascriptFooter("showUserLocation = true;");
        }
    }

    // return true on success, false on failure
    protected function generateTabForKey($tabKey, $features, &$tabJavascripts) {
        switch ($tabKey) {
            case 'map':
            {
                if ($this->isMapDrivenUI()) {
                    return false;
                } else {
                    $this->initializeStaticMap();
                    return true;
                }
            }
            case 'nearby':
            {
                if (count($features) == 1) {
                    $feature = end($features);
                    $geometry = $feature->getGeometry();
                    $center = $geometry->getCenterCoordinate();
                    $currentId = $feature->getId();
                    $currentTitle = $feature->getTitle();
                } elseif (isset($this->args['lat'], $this->args['lon'])) {
                    $center = array(
                        'lat' => $this->args['lat'],
                        'lon' => $this->args['lon']);
                } else {
                    return false;
                }

                $mapSearch = $this->getSearchClass($this->args);

                // defaults values for proximity search
                $feedData = $this->getMergedConfigData();
                $tolerance = isset($feedData['NEARBY_THRESHOLD']) ? $feedData['NEARBY_THRESHOLD'] : 1000;
                $maxItems = isset($feedData['NEARBY_ITEMS']) ? $feedData['NEARBY_ITEMS'] : 0;

                $searchResults = $mapSearch->searchByProximity($center, $tolerance, $maxItems);
                $places = array();
                if ($searchResults) {
                    foreach ($searchResults as $result) {
                        if ($result->getId() !== $currentId || $result->getTitle() !== $currentTitle) {
                            $aPlace = $this->linkForItem($result);
                            $places[] = $aPlace;
                        }
                    }
                    $this->assign('nearbyResults', $places);
                }
                return count($places) > 0;
            }
            case 'info':
            {
                if (count($features) != 1) {
                    return false;
                }
                $feature = end($features);

                // handle embedded photo
                $photoURL = $feature->getField('PhotoURL'); // embedded photo url
                if (isset($photoURL) && $photoURL && $photoURL != 'Null') {
                    $tabJavascripts[$tabKey] = "loadImage(photoURL,'photo');";
                    $this->assign('photoURL', $photoURL);
                    $this->addInlineJavascript("var photoURL = '{$photoURL}';");
                }

                $fields = $feature->getFields();
                if (count($fields) == 1) {
                    $details = current(array_values($fields));
                    $displayDetailsAsList = false;

                } else {
                    $details = array();

                    $detailConfig = $this->loadPageConfigArea('detail', 'detailConfig');
                    if (isset($detailConfig['details'], $detailConfig['details']['suppress'])) {
                        $suppress = $detailConfig['details']['suppress'];
                    }
                    foreach ($fields as $name => $value) {
                        if (!isset($suppress) || !in_array($name, $suppress)) {
                            $aDetail = array('label' => $name, 'title' => $value);
                            if (isValidURL($value)) {
                                $aDetail['url'] = $value;
                                $aDetail['class'] = 'external';
                            }
                            $details[] = $aDetail;
                        }
                    }
                    $displayDetailsAsList = true;
                }

                $this->assign('displayDetailsAsList', $displayDetailsAsList);
                $this->assign('details', $details);
                return is_array($details) ? count($details) > 0 : strlen(trim($details));
            }
            case 'links':
            {
                $externalLinks = array();
                if (count($features) == 1) {
                    $feature = end($features);
                    $geometry = $feature->getGeometry();
                    $center = $geometry->getCenterCoordinate();
                } elseif (isset($this->args['lat'], $this->args['lon'])) {
                    $center = array(
                        'lat' => $this->args['lat'],
                        'lon' => $this->args['lon']);
                } else {
                    return false;
                }
                
                $features = $this->getSelectedPlacemarks();
                $title = $feature->getTitle();
                if ($title) {
                    $title = urlencode($title);
                    $markerLabel = '+('.$title.')';
                    $directionsLabel = $title.'@';
                } else {
                    $markerLabel = '';
                    $directionsLabel = '';
                }

                $centerText = $center['lat'].','.$center['lon'];

                $externalLinks[] = array(
                    'title' => $this->getLocalizedString('VIEW_IN_GOOGLE_MAPS'),
                    'url'   => 'http://maps.google.com?q=loc:'.$centerText.$markerLabel,
                    'class' => 'external',
                    );

                $directionsURL = $this->getMapDevice()->pageSupportsDynamicMap()
                    ? 'http://maps.google.com?daddr='.$directionsLabel.$centerText
                    : 'http://maps.google.com/m/directions?daddr='.$directionsLabel.$centerText;
                
                $externalLinks[] = array(
                    'title' => $this->getLocalizedString('GET_DIRECTIONS_FROM_GOOGLE'),
                    'url'   => $directionsURL,
                    'urlID' => 'directionsLink',
                    'class' => 'external',
                    );

                $tabJavascripts[$tabKey] = "addDirectionsLink();";
                
                $this->assign('externalLinks', $externalLinks);
                return count($externalLinks) > 0;
            }
            default:
                break;
        }
        
        return false;
    }

    protected function initialize() {
        // this is in the wrong place
        $this->feedGroup = $this->getArg(array('feedgroup', 'group'), NULL);

        $this->feedGroups = $this->getFeedGroups();
        $this->numGroups = count($this->feedGroups);
        
        if ($this->numGroups === 1) {
            $this->feedGroup = key($this->feedGroups);
        }

        // clear out invalid feed group argument
        if ($this->feedGroup !== NULL && $this->getDataForGroup($this->feedGroup) === NULL) {
            $this->feedGroup = NULL;
        }
    }

    protected function initializeForPage() {

        $this->placemarkId = $this->getArg('featureindex', null);

        if ($this->feedGroup) {
            $this->assign('group', $this->feedGroup); // used in searchbar.tpl and selectcampus.tpl
        }

        if ($searchTerms = $this->getArg(array('filter', 'q'))) {
            $this->assign('searchTerms', $searchTerms);
        }

        // Create a unique mapimage id for this page so that we can ajax in 
        // multiple maps onto the same page (e.g. tablet home screen portlets)
        $pageURL = $this->buildBreadcrumbURL($this->page, $this->args, false);
        $this->mapImageElementId = 'mapimage-'.md5($pageURL);
        $this->assign('mapImageElementId', $this->mapImageElementId);

        switch ($this->page) {

            case 'index': // no breadcrumbs
                if ($this->getOptionalModuleVar('SHOW_LISTVIEW_BY_DEFAULT') && !$this->getArg('mapview')) {
                    $this->args['listview'] = 1;
                    $this->generateBookmarkLink();
                }
                // fall through to campus branch

            case 'campus': // one breadcrumb

                $this->assignGroups(); // appears in searchbar for campus.tpl or campus list in index.tpl

                // set up list view if
                $isListView = $this->getArg('listview') // user explicitly requested list view
                    || $this->feedGroup === null // multiple campuses, none selected
                        && !$this->getArg('worldmap') // user did not explictly request map view
                        && !$this->getArg('mapview')
                    || !$this->isMapDrivenUI(); // we are not able to show the map view

                if ($isListView) {

                    if ($searchTerms) {
                        // user hit the "browse" button with a query string
                        $this->setTemplatePage('browse');
                        $this->assignSearchResults($searchTerms);
                        $this->enableTabs(array('search', 'browse'), null, null);
                    }

                    $this->setupGroupPage(); // this assigns a list of campuses or categories

                } else {
                    if ($this->getArg('worldmap')) {
                        $this->feedGroup = null;
                    }
                    $this->setTemplatePage('fullscreen');
                    $this->initializeDynamicMap();
                }

                if ($this->feedGroup && $this->numGroups > 1) {
                    $data = $this->getDataForGroup($this->feedGroup);
                    if (isset($data['title'])) {
                        $this->setPageTitles($data['title']);
                    }
                }

                $this->assign('showAllCampuses', $this->getOptionalModuleVar('SHOW_ALL_CAMPUSES_LINK', true));
                break;
            
            case 'bookmarks':
                if (!$this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $this->redirectTo('index', array());
                }

                $this->assignGroups(); // appears in searchbar

                $places = array();
                foreach ($this->getBookmarks() as $aBookmark) {
                    if ($aBookmark) { // prevent counting empty string
                        $titles = $this->getTitleForBookmark($aBookmark);
                        $subtitle = count($titles) > 1 ? $titles[1] : null;

                        // TODO split up bookmarks by category
                        $places[] = array(
                            'title' => $titles[0],
                            'subtitle' => $subtitle,
                            'url' => $this->detailURLForBookmark($aBookmark),
                            );
                    }
                }
                $this->assign('navItems', $places);
                break;
            
            case 'search':
                $this->assignGroups(); // appears in searchbar
                if ($searchTerms) {
					$this->setLogData($searchTerms);
                    $searchResults = $this->searchItems($searchTerms, null, $this->args);
                    if (count($searchResults) == 1) {
                        $place = current($searchResults);
                        $this->redirectTo($this->pageForPlacemark($place), $place->getURLParams());
                    } else {
                        $places = array();
                        foreach ($searchResults as $place) {
                            $places[] = $this->linkForItem($place);
                        }
                        $this->assign('places', $places);
                    }
                } else {
                    $this->redirectTo('index');
                }
                break;
            
            case 'category':
                
                $isMapView = $this->getArg('mapview');
                $feedId = $this->getArg('feed',null);
                if (is_null($feedId)) {
                    throw new KurogoUserException('Feed ID not specified');
                }
                $this->assign('feedId', $feedId);
                $this->assignItemsFromFeed($feedId, $searchTerms, $isMapView);

                // link to "view all on map"
                $mapArgs = $this->args;
                if (isset($mapArgs['mapview']) && $mapArgs['mapview']) {
                    unset($mapArgs['mapview']);
                    $browseURL = $this->buildBreadcrumbURL($this->page, $mapArgs, false);
                    $this->assign('browseURL', $browseURL);
                }

                break;
          
            case 'detail':
                $detailConfig = $this->loadPageConfigArea('detail', 'detailConfig');        
                $tabKeys = array();
                $tabJavascripts = array();
                $features = $this->getSelectedPlacemarks();

                $title = '';
                if (count($features) == 1) {
                    $feature = end($features);
                    $title = $feature->getTitle();
                    $address = str_replace("\n", " ", $feature->getSubtitle());
                    if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                        $options = $this->bookmarkIDForPlacemark($feature);
                        $this->generateBookmarkOptions($options);
                    }
                    $link = $this->linkForItem($feature);
                    $this->mapURL = $link['url'];

                } else {
                    if ($this->getArg('worldmap')) {
                        $title = $this->getLocalizedString('ALL_MAP_GROUPS');
                    }
                    $address = $this->getArg('address');
                }

                if ($this->getArg('title')) {
                    $title = $this->getArg('title');
                }

                $this->assign('name', $title);
                $this->assign('address', $address);

                $possibleTabs = $detailConfig['tabs']['tabkeys'];
                foreach ($possibleTabs as $tabKey) {
                    if ($this->generateTabForKey($tabKey, $features, $tabJavascripts)) {
                        $tabKeys[] = $tabKey;
                    }
                }
                $this->assign('tabKeys', $tabKeys);
                $this->enableTabs($tabKeys, null, $tabJavascripts);

                break;
            
            case 'pane':
                $this->args['worldmap'] = true;
                $this->initializeDynamicMap();
                break;
        }

        if ($this->mapURL !== null) {
            $this->assign('mapURL', $this->mapURL);
        }
    }

    protected function getSelectedPlacemarks()
    {
        if ($this->selectedPlacemarks) {
            return $this->selectedPlacemarks;
        }

        // all campuses
        if ($this->getArg('worldmap')) {
            $placemarks = array();
            foreach ($this->feedGroups as $id => $groupData) {
            	$showOnWorldMap = self::argVal($groupData, 'SHOW_ON_WORLDMAP', 1);
            	if ($showOnWorldMap) {
					$point = filterLatLon($groupData['center']);
					$placemark = new BasePlacemark(
						new MapBasePoint(array(
							'lat' => $point['lat'],
							'lon' => $point['lon'],
							)));
					$placemark->setId($id);
					$placemark->setTitle($groupData['title']);
					$placemark->setURL($this->groupURL($id));
					$placemarks[] = $placemark;
				}
            }
            return $placemarks;
        }

        if (($searchTerms = $this->getArg(array('filter', 'q')))) {
            return $this->searchItems($searchTerms, null, $this->args);
        }

        // if anything was already selected by something else
        $feedId = $this->getArg('feed');
        if ($feedId) {
            $dataModel = $this->getDataModel($feedId);
            $category = $this->getArg('category', null);
            $featureIndex = $this->getArg('featureindex', null);
            if ($category !== null) {
                $dataModel->findCategory($category);
            }
            if ($this->placemarkId !== null) {
                $dataModel->setPlacemarkId($this->placemarkId);
            }
            $placemarks = $dataModel->placemarks();

            if ($featureIndex !== null && intval($featureIndex) < count($placemarks)) {
                $placemarks = array_slice($placemarks, intval($featureIndex), 1);
            }
            
            if ($placemarks) {
                return $placemarks;
            }
        }

        // make the map display arbitrary locations that aren't in any feeds
        if (isset($this->args['lat'], $this->args['lon'])) {
            $lat = $this->args['lat'];
            $lon = $this->args['lon'];
            $title = $this->getArg('title');
            if (!$title) {
                $title = "$lat,$lon";
            }
            $feature = new BasePlacemark(
                new MapBasePoint(array(
                    'lat' => $lat,
                    'lon' => $lon,
                    )));
            $feature->setTitle($title);
            return array($feature);
        }

        // TODO: add ways to show all bookmarks in a campus

        return array();
    }

    protected function setupGroupPage() // index or campus
    {
        if ($this->feedGroup !== null) {
            $groupData = $this->getDataForGroup($this->feedGroup);
            $this->assign('browseHint', $groupData['title']);
            if ($this->numGroups > 1) {
                $this->assignClearLink();
            }
            $this->assignFeeds();

        } elseif ($this->numGroups == 0) {
            $categories = array(array(
                'title' => $this->getLocalizedString('NO_MAPS_FOUND'),
                ));
            $this->assign('else', $categories);

        } else if ($this->feedGroup === null) {
            // bookmarks and view all section
            $this->generateBookmarkLink();
            $worldmapPage = $this->getMapDevice()->pageSupportsDynamicMap() ? 'index' : 'detail';
            $worldmapLink = array(array(
                'title' => $this->getLocalizedString('VIEW_ALL_GROUPS_ON_MAP'),
                'url' => $this->buildURL($worldmapPage, array('worldmap' => true)),
                ));
            $this->assign('worldmapLink', $worldmapLink);

            // feedgroups section
            $groupAlias = $this->getLocalizedString('MAP_GROUP_ALIAS');
            $this->assign('browseHint', $this->getLocalizedString('SELECT_A_MAP_GROUP', $groupAlias));
            $this->assignGroups('navItems');

            $this->addInlineJavascriptFooter("var CONFIG_MODULE = '{$this->configModule}';");
            if ($this->getOptionalModuleVar('SORT_GROUPS_BY_DISTANCE', true)) {
				$this->addOnLoad('sortGroupsByDistance();');
			}
        }

        if ($this->mapURL === null) {
            $toggleArgs = array('group' => $this->feedGroup, 'mapview' => true);
            if (($searchTerms = $this->getArg(array('filter', 'q')))) {
                $toggleArgs['filter'] = $searchTerms;
            }
            if (($feed = $this->getArg('feed'))) {
                $toggleArgs['feed'] = $feed;
            }
            if (($category = $this->getArg('category'))) {
                $toggleArgs['category'] = $category;
            }
            $this->mapURL = $this->buildBreadcrumbURL($this->page, $toggleArgs, false);
        }
    }

    protected function initializeDynamicMap()
    {
        // set up base map
        $baseMap = $this->getImageController();
        $baseMap->setWebModule($this);

        // add data
        foreach ($this->getSelectedPlacemarks() as $aPlacemark) {
            $baseMap->addPlacemark($aPlacemark);
        }

        // code for embedding base map
        $baseMap->setMapElement($this->mapImageElementId);
        $baseMap->prepareForOutput();
        foreach ($baseMap->getIncludeScripts() as $includeScript) {
            $this->addExternalJavascript($includeScript);
        }
        foreach ($baseMap->getInternalScripts() as $includeScript) {
            $this->addInternalJavascript($includeScript);
        }
        foreach ($baseMap->getIncludeCSS() as $includeCSS) {
            $this->addExternalCSS($includeCSS);
        }

        $latRange = $baseMap->getMinimumLatSpan();
        $lonRange = $baseMap->getMinimumLonSpan();
        $this->addInlineJavascriptFooter(
            //"var COOKIE_PATH = '".COOKIE_PATH."';\n".
            //"var BOOKMARK_LIFESPAN = ".$this->getBookmarkLifespan().";\n".
            "var CONFIG_MODULE = '{$this->configModule}';\n".
            "var MIN_LAT_SPAN = {$latRange};\n".
            "var MIN_LON_SPAN = {$lonRange};\n".
            'var NO_RESULTS_FOUND = "'.$this->getLocalizedString('NO_RESULTS').'";');
        $this->addInlineJavascriptFooter($baseMap->getFooterScript());

        $this->configureUserLocation();
        if ($this->page != 'pane') {
            $this->addOnLoad('addClass(document.body, "fullscreen");');
        }
        if ($this->pagetype == 'tablet') {
            $this->addOnLoad('setModuleFillScreen();');
        }
        $this->addOnOrientationChange('updateContainerDimensions();');

        // show button on search bar
        $this->generateBookmarkLink();

        // listview link
        $toggleArgs = array('group' => $this->feedGroup, 'listview' => true);
        if (($feed = $this->getArg('feed'))) {
            $toggleArgs['feed'] = $feed;
        }
        if (($category = $this->getArg('category'))) {
            $toggleArgs['category'] = $category;
        }
        if($filter = $this->getArg(array('q', 'filter'))) {
            $toggleArgs['filter'] = $filter;
        }
        $this->assign('browseURL', $this->buildBreadcrumbURL($this->page, $toggleArgs, false));
    }

    protected function initializeStaticMap()
    {
        $baseMap = $this->getImageController();
        foreach ($this->getSelectedPlacemarks() as $placemark) {
            $baseMap->addPlacemark($placemark);
        }
        $baseMap->prepareForOutput();
        
        // override point for current zoom level
        if (isset($this->args['zoom'])) {
            $zoomLevel = $this->args['zoom'];
        } elseif (!$this->getArg('worldmap')) {
            $zoomLevel = $this->getDataModel()->getDefaultZoomLevel();
        }
        
        // override point for where map should be centered
        if (isset($this->args['center'])) {
            $center = filterLatLon($this->getArg('center'));
        } elseif (isset($this->args['lat'], $this->args['lon'])) {
            $center = array('lat' => $this->getArg('lat'), 'lon' => $this->getArg('lon'));
        }

        if (isset($center)) {
            $baseMap->setCenter($center);
        }
        if (isset($zoomLevel) && $zoomLevel !== null) {
            $baseMap->setZoomLevel($zoomLevel);
        }
        
        $this->assign('imageUrl', $baseMap->getImageURL());

        $this->assign('scrollNorth', $this->detailUrlForPan('n', $baseMap));
        $this->assign('scrollEast', $this->detailUrlForPan('e', $baseMap));
        $this->assign('scrollSouth', $this->detailUrlForPan('s', $baseMap));
        $this->assign('scrollWest', $this->detailUrlForPan('w', $baseMap));

        // this may not be needed for devices that get the ajax options
        $this->assign('zoomInUrl', $this->detailUrlForZoom('in', $baseMap));
        $this->assign('zoomOutUrl', $this->detailUrlForZoom('out', $baseMap));

        $this->assign('imageWidth',  $baseMap->getImageWidth());
        $this->assign('imageHeight', $baseMap->getImageHeight());

        // ajax options for static maps
        // devices like bbplus will load a new page for each zoom/scroll
        $js = <<<JS
            mapWidth = {$baseMap->getImageWidth()};
            mapHeight = {$baseMap->getImageHeight()};
            staticMapOptions = {$baseMap->getJavascriptControlOptions()};
JS;
        $this->addInlineJavascriptFooter($js);
        $this->addOnLoad('addStaticMapControls();');

        // javascript for all static maps
        $this->addOnLoad('setTimeout(function () { window.scrollTo(0, 1); updateMapDimensions(); }, 1000);');
        if ($this->pagetype == 'tablet') {
            $this->addOnLoad('setModuleFillScreen();');
        }
        $this->addOnOrientationChange('updateMapDimensions();');

        $this->assign('isStatic', true);
    }

    /////// utilities

    protected function displayTextFromMeters($meters)
    {
        $result = null;
        $system = $this->getOptionalModuleVar('DISTANCE_MEASUREMENT_UNITS', 'Metric');
        switch ($system) {
            case 'Imperial':
                $miles = $meters * MILES_PER_METER;
                if ($miles < 0.1) {
                    $feet = $meters * FEET_PER_METER;
                    $result = $this->getLocalizedString('DISTANCE_IN_FEET', number_format($feet, 0));
                } elseif ($miles < 15) {
                    $result = $this->getLocalizedString('DISTANCE_IN_MILES', number_format($miles, 1));
                } else {
                    $result = $this->getLocalizedString('DISTANCE_IN_MILES', number_format($miles, 0));
                }
                break;
            case 'Metric':
            default:
                if ($meters < 100) {
                    $result = $this->getLocalizedString('DISTANCE_IN_METERS', number_format($meters, 0));
                } elseif ($meters < 15000) {
                    $result = $this->getLocalizedString('DISTANCE_IN_KILOMETERS', number_format($meters / 1000, 1));
                } else {
                    $result = $this->getLocalizedString('DISTANCE_IN_KILOMETERS', number_format($meters / 1000, 0));
                }
                break;
        }
        return $result;
    }

    public function getInternalJavascriptURL($path) {
        return parent::getInternalJavascriptURL($path);
    }
}
