<?php

includePackage('Maps', 'MapDB');

class MapDBSearch extends MapSearch
{
    // tolerance specified in meters
    public function searchByProximity($center, $tolerance=1000, $maxItems=0, $dataController=null)
    {
        $this->searchResults = array();

        $bbox = normalizedBoundingBox($center, $tolerance, null, null);

        $params = array(
            $bbox['min']['lat'], $bbox['max']['lat'], $bbox['min']['lon'], $bbox['max']['lon'],
            $bbox['center']['lat'], $bbox['center']['lon']
            );

        $sql = 'SELECT p.*, pc.category_id FROM '
              .MapDB::PLACEMARK_TABLE.' p, '.MapDB::PLACEMARK_CATEGORY_TABLE.' pc'
              .' WHERE p.placemark_id = pc.placemark_id'
              .'   AND p.lat >= ? AND p.lat < ? AND p.lon >= ? AND p.lon < ?'
              .' ORDER BY (p.lat - ?)*(p.lat - ?) + (p.lon - ?)*(p.lon - ?)';

        if ($maxItems) {
            $result = MapDB::connection()->limitQuery(
                $sql, $params, false, array(), $maxItems);
        } else {
            $result = MapDB::connection()->query($sql, $params);
        }

        while ($row = $result->fetch()) {
            $placemark = new MapDBPlacemark($row, true);
            $placemark->addCategoryId($row['category_id']);
            $this->searchResults[] = $placemark;
        }
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

        $result = MapDB::connection()->query($sql, $params);

        while ($row = $result->fetch()) {
            $placemark = new MapDBPlacemark($row, true);
            $placemark->addCategoryId($row['category_id']);
            $this->searchResults[] = $placemark;
        }
        $this->resultCount = count($this->searchResults);

        return $this->searchResults;
    }
}



