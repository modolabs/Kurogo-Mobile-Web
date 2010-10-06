<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

require_once realpath(LIB_DIR.'/DiskCache.php');

// TODO: make these into configurable parameters
define('ARCGIS_SEARCH_LAYERS', '0');

ArcGISServer::init();

class ArcGISServer {

  private static $defaultCollection = NULL;
  private static $defaultSearchFields = 'Address,Building Name';

  private static $diskCache = NULL;
  private static $wkidCache = NULL;
  private static $bldgCache = NULL;
  private static $collections = array();

  public static function getCollection($name=NULL) {
    if ($name === NULL)
      return self::$defaultCollection;

    elseif (array_key_exists($name, self::$collections)) {
      return self::$collections[$name];
    }

    return NULL;
  }

  public static function getCollections() {
    $result = array();
    foreach (self::$collections as $id => $collection) {
      $result[$id] = $collection->getMapName();
    }
    return $result;
  }

  public static function getLayers() {
    $result = array();
    foreach (self::$collections as $id => $collection) {
      
      foreach ($collection->getLayerNames() as $layerId => $name) {
        $result["$id.$layerId"] = $name;
        // suppress all further disaggregations but housing for now
        if ($id != 'Housing')
	  break;
      }
    }
    return $result;
  }

  public static function getLayer($collectionLayer) {
    $parts = explode('.', $collectionLayer);
    $collection = self::getCollection($parts[0]);
    if (count($parts) == 2) {
      $layer = $collection->getLayer(intval($parts[1]));
      return $layer;
    } else {
      return $collection;
    }
  }

  // deprecate this
  public static function getCapabilities($name=NULL) {
    return self::getCollection($name)->getCapabilities();
  }

  public static function getWkidProperties($wkid) {
    if (!self::$wkidCache->isFresh($wkid)) {
      $url = "http://spatialreference.org/ref/epsg/$wkid/proj4/";
      $data = file_get_contents($url);
      self::$wkidCache->write($data, $wkid);
    } else {
      $data = self::$wkidCache->read($wkid);
    }

    return array('properties' => $data);
  }

  // only works on CampusMap layer
  public static function getBldgByNumber($number) {
    if (!self::$bldgCache->isFresh($number)) {
      $collection = self::getCollection();
      $searchFields = "Building Number";

      $queryBase = $collection->getURL() . '/find?';
      $query = http_build_query(array(
        'searchText'     => $number,
        'searchFields'   => $searchFields,
        'contains'       => 'false',
        'sr'             => '', // i hope this means use the default
        'layers'         => 0,
        'returnGeometry' => 'true',
        'f'              => 'json',
        ));

      $json = file_get_contents($queryBase . $query);
      $jsonObj = json_decode($json);

      if ($jsonObj->results) {
        foreach ($jsonObj->results as $result) {
          foreach ($result->attributes as $name => $value) {
            $result->attributes->{$name} = $value;
          }
        }

        self::$bldgCache->write($jsonObj, $number);

      } else {
        error_log("could not find building $number", 0);
      }
    }

    $result = self::$bldgCache->read($number);
    return $result;
  }

  public static function search($searchText, $collectionName=NULL) {
    $layerId = 0;
    if (!$collectionName) {
      $collection = self::getCollection();
      $searchFields = self::$defaultSearchFields;
    } else {
      $collection = self::getLayer($collectionName);
      if ($collection->isLayer()) {
        if ($collection->getGeometryType()) {
          $results = $collection->query($searchText);
          $obj = new stdClass();
          $obj->results = array();
          foreach ($results->features as $id => $result) {
            $result->geometryType = $collection->getGeometryType();
            $obj->results[] = $result;
          }
          return $obj;
        }
        $parts = explode('.', $collectionName);
        $collection = self::getLayer($parts[0]);
      }
      $searchFields = $collection->getDefaultSearchFields();
    }

    if ($collection === NULL)
      return FALSE;

    $searchText = strtoupper(str_replace('.', '', $searchText));

    $queryBase = $collection->getURL() . '/find?';
    $query = http_build_query(array(
      'searchText'     => $searchText,
      'searchFields'   => $searchFields,
      'sr'             => '', // i hope this means use the default
      'layers'         => $layerId,
      'returnGeometry' => 'true',
      'f'              => 'json',
      ));

    $url = str_replace('+', '%20', $queryBase . $query);
    $json = file_get_contents($url);
    $jsonObj = json_decode($json);

    foreach ($jsonObj->results as $result) {
      foreach ($result->attributes as $name => $value) {
        if ($value != 'Null')
          $result->attributes->{$name} = $value;
      }
    }

    return $jsonObj;
  }

  public static function init() {
    if (!self::$collections) {
      self::$diskCache = new DiskCache($GLOBALS['siteConfig']->getVar('ARCGIS_CACHE'), 86400 * 7, TRUE);
      self::$bldgCache = new DiskCache($GLOBALS['siteConfig']->getVar('ARCGIS_BLDG_CACHE'), 86400 * 30, TRUE);

      self::$wkidCache = new DiskCache($GLOBALS['siteConfig']->getVar('ARCGIS_CACHE'), 86400 * 30, TRUE);
      self::$wkidCache->setSuffix('.wkid');
      self::$wkidCache->preserveFormat();

      // TODO: make service names an external data source

      $url = $GLOBALS['siteConfig']->getVar('ARCGIS_REST_SERVER') . '/CampusMap/MapServer';
      self::$defaultCollection = new ArcGISCollection('CampusMap', $url);

      $names = array(
        'Libraries',
        'Museums',
        'Housing',
        'Dining',
        'LEED',
        'PublicSafety',
        //'WirelessLAN',
        //'Accessibility',
        //'AlternativeEnergy', 
        //'BikeFacilities',
        //'GreenCampus',
        //'NetComm', 
        );

      self::$collections = array();
      foreach ($names as $name) {
        $url = $GLOBALS['siteConfig']->getVar('ARCGIS_REST_SERVER') . '/' . $name . '/MapServer';
        self::$collections[$name] = new ArcGISCollection($name, $url);
      }
    }
  }

}

class ArcGISCollection {
  public $singleFusedMapCache; // indicates whether we have map tiles
  public $initialExtent;
  public $fullExtent;
  public $serviceDescription;
  public $spatialRef;

  private $url;
  private $mapName;
  private $id;
  private $layers = array();
  private $diskCache;

  public function getURL() {
    return $this->url;
  }

  public function isLayer() {
    return FALSE;
  }

  public function getMapName() {
    if (!$this->mapName) {
      $this->getCapabilities();
    }
    return $this->mapName;
  }

  // dispatch a query to layer zero.
  public function query($text='', $layerId=0) {
    if (!$this->layers) {
      $this->getCapabilities();
    }
    return $this->getLayer($layerId)->query($text);
  }

  public function getFeatureList($layerId=0) {
    if (!$this->layers) {
      $this->getCapabilities();
    }
    return $this->getLayer($layerId)->getFeatureList();
  }

  public function getDefaultSearchFields() {
    if (!$this->layers) {
      $this->getCapabilities();
    }
    return $this->getLayer(0)->getDisplayField();
  }

  public function getLayerNames() {
    if (!$this->layers) {
      $this->getCapabilities();
    }
    return $this->layers;
  }

  public function getLayer($layerId) {
    if (is_int($layerId)) {
      $layerNames = array_keys($this->getLayerNames());
      if ($layerId < count($layerNames))
        $layerId = $layerNames[$layerId];
    }

    if (array_key_exists($layerId, $this->layers)) {
      $layer = $this->layers[$layerId];
      if (is_string($layer)) {
        $url = $this->url . '/' . $layerId;
        $layer = new ArcGISLayer($this->id, $layerId, $url);
      }
      return $layer;
    }
  }

  public function __construct($id, $url) {
    $this->id = $id;
    $this->url = $url;
    $filename = $GLOBALS['siteConfig']->getVar('ARCGIS_CACHE')."/$id";
    $this->diskCache = new DiskCache($filename, 86400 * 7);
  }

  // TODO: make this private and return null
  public function getCapabilities() {
    $data = NULL;
    if ($this->diskCache->isFresh()) {
      $data = $this->diskCache->read();
    }

    if (!$data) {
      $contents = file_get_contents($this->url . '?f=json');
      // make sure this is legitimate JSON so we don't cache garbage
      if ($data = json_decode($contents)) {
        $this->diskCache->write($data);
      }
    }

    $this->serviceDescription = $data->serviceDescription;
    $this->mapName = $data->mapName;

    $this->spatialRef = $data->spatialReference;
    $this->initialExtent = $data->initialExtent;
    unset($this->initialExtent->spatialReference);

    $this->fullExtent = $data->fullExtent;
    unset($this->fullExtent->spatialReference);

    // TODO: merge map tile download script into this class
    $this->singleFusedMapCache = $data->singleFusedMapCache;

    foreach ($data->layers as $layerData) {
      $id = $layerData->id;
      // populate array with placeholders; initialize on demand
      $this->layers[$id] = $layerData->name;
    }

    return $data;
  }

}

// sort addresses using natsort
// but move numbers to the end first
function addresscmp($addr1, $addr2) {
  $addr1 = preg_replace('/^([\d\-\.]+)(\s*)(.+)/', '${3}${2}${1}', $addr1);
  $addr2 = preg_replace('/^([\d\-\.]+)(\s*)(.+)/', '${3}${2}${1}', $addr2);
  return strnatcmp($addr1, $addr2);
}

class ArcGISLayer {
  public $id;
  public $name;

  private $fields;
  private $extent;
  private $minScale;
  private $maxScale;
  private $displayField;
  private $spatialRef;
  private $geometryType;

  private $url;
  private $diskCache;
  private $featureCache;

  public function __construct($collectionId, $layerId, $url) {
    $this->id = $layerId;
    $this->url = $url;
    $filename = $GLOBALS['siteConfig']->getVar('ARCGIS_CACHE')."/$collectionId'.$layerId";
    $this->diskCache = new DiskCache($filename, 86400 * 7);
    $this->featureCache = new DiskCache("$filename.features", 86400 * 7);
  }

  public function isLayer() {
    return TRUE;
  }

  public function getName() {
    if (!$this->name) {
      $this->getCapabilities();
    }
    return $this->name;
  }

  public function getGeometryType() {
    if (!$this->geometryType) {
      $this->getCapabilities();
    }
    return $this->geometryType;
  }

  public function getDisplayField() {
    if (!$this->displayField) {
      $this->getCapabilities();
    }
    return $this->displayField;
  }

  public function getFeatureList() {
    $displayField = $this->getDisplayField();
    $metaData = $this->query();
    $result = array();
    foreach ($metaData->features as $featureInfo) {
      $attributes = $featureInfo->attributes;
      $displayAttribs = array();
      foreach ($attributes as $attrName => $attrValue) {
        if ($attrValue != 'Null')
          $displayAttribs[$this->fields[$attrName]] = $attrValue;
      }
      $featureId = $attributes->{$displayField};
      $result[$featureId] = $displayAttribs;
    }

    uksort($result, 'addresscmp');

    return $result;
  }

  public function query($text='') {
    if ($text == '' && $this->featureCache->isFresh()) {
      return $this->featureCache->read();
    }

    if (!$this->name) {
      $this->getCapabilities();
    }

    $text = str_replace('\\\'', '\'\'', $text);

    $params = array(
      'text'           => $text,
      'geometry'       => serializeBBox($this->extent),
      'geometryType'   => 'esriGeometryEnvelope',
      'inSR'           => $this->spatialRef,
      'spatialRel'     => 'esriSpatialRelIntersects',
      'where'          => '',
      'returnGeometry' => 'true',
      'outSR'          => '',
      'outFields'      => implode(',', array_keys($this->fields)),
      'f'              => 'json',
      );

    $url = $this->url . '/query?' . http_build_query($params);

    $contents = file_get_contents($url);
    if ($data = json_decode($contents)) {
      if ($text == '') {
        $this->featureCache->write($data);
      }
      return $data;
    }
  }

  private function getCapabilities() {
    $data = NULL;
    if ($this->diskCache->isFresh()) {
      $data = $this->diskCache->read();
    }

    if (!$data) {
      $contents = file_get_contents($this->url . '?f=json');
      // make sure this is legitimate JSON so we don't cache garbage
      if ($data = json_decode($contents)) {
        $this->diskCache->write($data);
      }
    }
    
    $this->name = $data->name;
    $this->minScale = $data->minScale;
    $this->maxScale = $data->maxScale;
    $this->displayField = $data->displayField;
    $this->geometryType = $data->geometryType;

    foreach ($data->fields as $fieldInfo) {
      $this->fields[$fieldInfo->name] = $fieldInfo->alias;
    }

    $this->extent = $data->extent;
    $this->spatialRef = $data->extent->spatialReference;
    unset($this->extent->spatialReference);
  }
}

function serializeBBox($bbox) {
  return $bbox->xmin . ',' 
       . $bbox->ymin . ',' 
       . $bbox->xmax . ',' 
       . $bbox->ymax;
}

?>