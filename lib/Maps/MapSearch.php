<?php

// TODO move functions like this to a more general class
define('EARTH_RADIUS_IN_METERS', 6378100);

// http://en.wikipedia.org/wiki/Great-circle_distance
// chosen for what the page said about numerical accuracy
// but in practice the other formulas, i.e.
// law of cosines and haversine
// all yield pretty similar results
function gcd($fromLat, $fromLon, $toLat, $toLon)
{
    $radiansPerDegree = M_PI / 180.0;
    $y1 = $fromLat * $radiansPerDegree;
    $x1 = $fromLon * $radiansPerDegree;
    $y2 = $toLat * $radiansPerDegree;
    $x2 = $toLon * $radiansPerDegree;

    $dx = $x2 - $x1;
    $cosDx = cos($dx);
    $cosY1 = cos($y1);
    $sinY1 = sin($y1);
    $cosY2 = cos($y2);
    $sinY2 = sin($y2);

    $leg1 = $cosY2*sin($dx);
    $leg2 = $cosY1*$sinY2 - $sinY1*$cosY2*$cosDx;
    $denom = $sinY1*$sinY2 + $cosY1*$cosY2*$cosDx;
    $angle = atan2(sqrt($leg1*$leg1+$leg2*$leg2), $denom);

    return $angle * EARTH_RADIUS_IN_METERS;
}

class MapSearch {

    protected $searchResults;
    protected $resultCount;
    protected $feeds;
    
    public function setFeedData($feeds) {
        $this->feeds = $feeds;
    }

    public function getSearchResults() {
        return $this->searchResults;
    }
    
    public function getResultCount() {
        return $this->resultCount;
    }

    // tolerance specified in meters
    public function searchByProximity($center, $tolerance=1000, $maxItems=0) {
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

        $this->searchResults = array();
        $resultsByDistance = array();

        // please think twice before refactoring this.
        foreach ($this->feeds as $categoryID => $feedData) {
            $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $controller->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));

            if ($controller->canSearch()) { // respect config settings
                foreach ($controller->items() as $itemID => $item) {
                    if ($item instanceof MapFeature) {
                        $geometry = $item->getGeometry();
                        if ($geometry) {
                            $featureCenter = $geometry->getCenterCoordinate();
                            if ($featureCenter['lat'] <= $maxLat && $featureCenter['lat'] >= $minLat
                                && $featureCenter['lon'] <= $maxLon && $featureCenter['lon'] >= $minLon)
                            {
                                $distance = gcd($center['lat'], $center['lon'], $featureCenter['lat'], $featureCenter['lon']);
                                if ($distance > $tolerance) continue;

                                // keep keys unique; give priority to whatever came first
                                $intDist = intval($distance * 1000);
                                while (array_key_exists($intDist, $resultsByDistance)) {
                                    $intDist += 1; // one centimeter
                                }
                                $subtitle = sprintf("%s (%.0f meters away)", $item->getSubtitle(), $distance);
                                $resultsByDistance[$intDist] = array(
                                    'title'    => $item->getTitle(),
                                    'subtitle' => $subtitle,
                                    'category' => $categoryID,
                                    'index'    => $itemID,
                                    );
                            }
                        }

                    } else {
                        foreach ($item->getItems() as $featureID => $feature) {
                            $geometry = $feature->getGeometry();
                            if ($geometry) {
                                $featureCenter = $geometry->getCenterCoordinate();
                                if ($featureCenter['lat'] <= $maxLat && $featureCenter['lat'] >= $minLat
                                    && $featureCenter['lon'] <= $maxLon && $featureCenter['lon'] >= $minLon)
                                {
                                    $distance = gcd($center['lat'], $center['lon'], $featureCenter['lat'], $featureCenter['lon']);
                                    if ($distance > $tolerance) continue;

                                    // keep keys unique; give priority to whatever came first
                                    $intDist = intval($distance * 1000);
                                    while (array_key_exists($intDist, $resultsByDistance)) {
                                        $intDist += 1; // one centimeter
                                    }
                                    $subtitle = sprintf("%s (%.0f meters away)", $item->getSubtitle(), $distance);
                                    $resultsByDistance[$distance] = array(
                                        'title'       => $item->getTitle(),
                                        'subtitle'    => $subtitle,
                                        'category'    => $categoryID,
                                        'subcategory' => $itemID,
                                        'index'       => $featureID,
                                        );
                                }
                            }
                        }
                    } // instanceof MapFeature
                } // foreach items
            } // if canSearch
        } // foreach feeds

        ksort($resultsByDistance);

        if ($maxItems && count($resultsByDistance) > $maxItems) {
            array_splice($resultsByDistance, $maxItems);
        }

        $this->searchResults = array_values($resultsByDistance);
        return $this->searchResults;
    }

    public function searchCampusMap($query) {
        $this->searchResults = array();
    
    	foreach ($this->feeds as $id => $feedData) {
            $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $controller->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));
            
            if ($controller->canSearch()) {
                $results = $controller->search($query);
                $this->resultCount += count($results);
                foreach ($results as $index => $aResult) {
                    if (is_array($aResult)) {
                        foreach ($aResult as $featureID => $feature) {
                            $this->searchResults[] = array(
                                'title' => $feature->getTitle(),
                                'subtitle' => $feature->getSubtitle(),
                                'category' => $id,
                                'subcategory' => $index,
                                'index' => $featureID,
                            );
                        }
                    } else {
                        $this->searchResults[] = array(
                            'title' => $aResult->getTitle(),
                            'subtitle' => $aResult->getSubtitle(),
                            'category' => $id,
                            'index' => $index,
                            );
                    }
                }
            }
    	}
    	
    	return $this->searchResults;
    }
    
    public function getTitleForSearchResult($aResult) {
        return $aResult['title'];
    }
    
    public function getSubtitleForSearchResult($aResult) {
        if (isset($aResult['subtitle'])) {
            return $aResult['subtitle'];
        }
        return null;
    }
    
    public function getURLArgsForSearchResult($aResult) {
        return array(
            'featureindex' => $aResult['index'],
            'subcategory' => isset($aResult['subcategory']) ? $aResult['subcategory'] : null,
            'category' => $aResult['category'],
            );
    }
	
}



