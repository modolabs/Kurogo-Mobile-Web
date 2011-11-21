<?php

includePackage('Maps', 'MapDB');

class MapDBSearch extends MapSearch
{
    private function getSearchResultsForQuery($sql, $params, $maxItems=0)
    {
        $result = MapDB::connection()->query($sql, $params);
        $displayableCategories = MapDB::getAllCategoryIds();
        // eliminate dupe placemarks if they appear in multiple categories
        $this->resultCount = 0;
        $uniqueResults = array();
        while (($row = $result->fetch()) && (!$maxItems || $this->resultCount <= $maxItems)) {
            if (in_array($row['category_id'], $displayableCategories)) {
                $ukey = $row['placemark_id'].$row['lat'].$row['lon'];
                if (isset($uniqueResults[$ukey])) {
                    $uniqueResults[$ukey]->addCategoryId($row['category_id']);
                } else {
                    $placemark = new MapDBPlacemark($row, true);
                    $placemark->addCategoryId($row['category_id']);
                    $uniqueResults[$ukey] = $placemark;
                    $this->resultCount++;
                }
            }
        }
        $this->searchResults = array_values($uniqueResults);
        return $this->searchResults;
    }

    // tolerance specified in meters
    public function searchByProximity($center, $tolerance=1000, $maxItems=0, $dataController=null)
    {
        $bbox = normalizedBoundingBox($center, $tolerance, null, null);

        $params = array(
            $bbox['min']['lat'], $bbox['max']['lat'],
            $bbox['min']['lon'], $bbox['max']['lon'],
            $bbox['center']['lat'], $bbox['center']['lat'],
            $bbox['center']['lon'], $bbox['center']['lon'],
            );

        $sql = 'SELECT p.*, pc.category_id FROM '
              .MapDB::PLACEMARK_TABLE.' p, '.MapDB::PLACEMARK_CATEGORY_TABLE.' pc'
              .' WHERE p.placemark_id = pc.placemark_id'
              .'   AND p.lat = pc.lat AND p.lon = pc.lon'
              .'   AND p.lat >= ? AND p.lat < ? AND p.lon >= ? AND p.lon < ?'
              .' ORDER BY (p.lat - ?)*(p.lat - ?) + (p.lon - ?)*(p.lon - ?)';
        $this->getSearchResultsForQuery($sql, $params, $maxItems);

        $resultsByDistance = array();
        foreach ($this->searchResults as $result) {
            $rCenter = $result->getGeometry()->getCenterCoordinate();
            $distance = greatCircleDistance(
                $center['lat'], $center['lon'], 
                $rCenter['lat'], $rCenter['lon']);
            $result->setField('distance', $distance);

            // avoid distance collisions
            while(isset($resultsByDistance[$distance])) {
                $distance++;
            }
            $resultsByDistance[$distance] = $result;
        }
        ksort($resultsByDistance);

        $this->searchResults = array_values($resultsByDistance);
        return $this->searchResults;
    }

    public function searchCampusMap($query)
    {
        $this->searchResults = array();

        $sql = 'SELECT p.*, pc.category_id FROM '
              .MapDB::PLACEMARK_TABLE.' p, '.MapDB::PLACEMARK_CATEGORY_TABLE.' pc'
              .' WHERE p.placemark_id = pc.placemark_id'
              .'   AND p.lat = pc.lat AND p.lon = pc.lon'
              // TODO this substring pattern might need tweaking
              .'   AND (p.name like ? OR p.name like ?)';
        $params = array("$query%", "% $query%");

        return $this->getSearchResultsForQuery($sql, $params);
    }
}



