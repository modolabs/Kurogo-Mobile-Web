<?php

require_once 'ArcGISServer.php';

function searchCampusMap($query) {

    $locs = array('bldg', 'dept', 'lib', 'museum', 'room');
    $resultObj = new stdClass();
    $resultObj->results = array();
    $bldgIds = array();

    foreach ($locs as $loc) {

        $params = array(
            'str' => $query,
            'loc' => $loc,
            );
        
        $url = MAP_SEARCH_URL . '?' . http_build_query($params);
        $rawContent = file_get_contents($url);
        $rawContent = preg_replace('/:\s*"?([\-\.\d]+[A-Z]*)"?\s*([,\}])/', ':"${1}"${2}', $rawContent);
        $content = json_decode($rawContent);
        
        foreach ($content->results as $resultObj) {
            if (!in_array($resultObj->bld_num, $bldgIds))
                $bldgIds[] = $resultObj->bld_num;
        }

    }

    if ($bldgIds) {
        foreach ($bldgIds as $bldgId) {
            $obj = ArcGISServer::getBldgByNumber($bldgId);
            foreach ($obj->results as $result) {
                $resultObj->results[] = $result;
            }
        }
    }

    // if map.harvard.edu can't find anything
    // we'll try ArcGIS directly
    if (!$resultObj->results) {
        $resultObj = ArcGISServer::search($query);
    }

    return $resultObj;
}




