<?php

// TODO move general map functions to a separate file
define('GEOGRAPHIC_PROJECTION', 4326);
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

function arrayFromMapFeature(MapFeature $feature) {
    $result = array(
        'title' => $feature->getTitle(),
        'subtitle' => $feature->getSubtitle(),
        'id' => $feature->getIndex(),
        'category' => $feature->getCategory(),
        'description' => $feature->getDescription(),
        );

    $geometry = $feature->getGeometry();
    if ($geometry) {
        $center = $geometry->getCenterCoordinate();
        if ($geometry instanceof MapPolygon) {
            $geometryType = 'polygon';
            $serializedGeometry = $geometry->getRings();
        } elseif ($geometry instanceof MapPolyline) {
            $geometryType = 'polyline';
            $serializedGeometry = $geometry->getPoints();
        } elseif ($geometry) {
            $geometryType = 'point';
            $serializedGeometry = $geometry->getCenterCoordinate();
        }
        $result['geometryType'] = $geometryType;
        $result['geometry'] = $serializedGeometry;
        $result['lat'] = $center['lat'];
        $result['lon'] = $center['lon'];
    }
    
    return $result;
}

function shortArrayFromMapFeature(MapFeature $feature) {
    return array(
        'featureindex' => $feature->getIndex(),
        'category' => $feature->getCategory(),
        );
}

function htmlColorForColorString($colorString) {
    return substr($colorString, strlen($colorString)-6);
}

// implemented by map categories, which have no geometry
interface MapListElement
{
    const DESCRIPTION_TEXT = 0; // used by the majority of map data elements
    const DESCRIPTION_LIST = 1; // used by ArcGIS map features, which store attributes as an array.
                                // if we support backends like PostGIS, ESRI shapefiles/geodatabases
                                // etc. we will start seeing more of these guys.

    public function getTitle();
    public function getSubtitle();
    public function getIndex();
    public function getCategory();
}

// implemented by map data elements that can be displayed on a map
interface MapFeature extends MapListElement
{
    public function getGeometry();
    public function getDescription();
    public function getDescriptionType();
    public function getStyle();

    public function getField($fieldName);
    public function setField($fieldName, $value);
}

class EmptyMapFeature implements MapFeature {
    private $geometry;
    private $style;
    
    private $title = '';
    private $description = '';
    private $index = 0;
    private $fields = array();
    
    private $category;
    private $subcategory;
    
    public function __construct($center) {
        $this->geometry = new EmptyMapPoint($center['lat'], $center['lon']);
        $this->style = new EmptyMapStyle();
    }
    
    // MapListElement interface
    
    public function getTitle() {
        return $this->title;
    }
    
    public function getSubtitle() {
        return $this->getField('address');
    }
    
    public function getIndex() {
        return $this->index;
    }
    
    public function getCategory() {
        return $this->category;
    }
    
    public function setCategory($category) {
        $this->category = $category;
    }
    
    // MapFeature interface
    
    public function getGeometry() {
        return $this->geometry;
    }
    
    public function getDescription() {
        return $this->description;
    }

    public function getDescriptionType() {
        return MapListElement::DESCRIPTION_TEXT;
    }

    public function getStyle() {
        return $this->style;
    }
    
    public function getField($fieldName) {
        if (isset($this->fields[$fieldName])) {
            return $this->fields[$fieldName];
        }
        return null;
    }
    
    public function setField($fieldName, $value) {
        $this->fields[$fieldName] = $value;
    }
    
    // setters that get used by MapWebModule when its detail page isn't called with a feature
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function setIndex($index) {
        return $this->index;
    }
    
    public function setDescription($description) {
        $this->description = $description;
    }
}

class EmptyMapPoint implements MapGeometry {
    private $center;
    public function __construct($lat, $lon) {
        $this->center = array('lat' => $lat, 'lon' => $lon);
    }
    
    public function getCenterCoordinate() {
        return $this->center;
    }
}

class EmptyMapPolyline implements MapPolyline {
    private $points;
    public function __construct($points) {
        $this->points = $points;
    }

    public function getCenterCoordinate()
    {
        $lat = 0;
        $lon = 0;
        $n = 0;
        foreach ($this->points as $coordinate) {
            $lat += $coordinate['lat'];
            $lon += $coordinate['lon'];
            $n += 1;
        }
        return array(
            'lat' => $lat / $n,
            'lon' => $lon / $n,
            );
    }

    public function getPoints() {
        return $this->points;
    }
}

class EmptyMapPolygon implements MapPolygon {

    private $outerBoundary;
    private $innerBoundaries = array();

    public function __construct(Array $rings) {
        $this->outerBoundary = new EmptyMapPolyline($rings[0]);
        if (count($rings) > 1) {
            for ($i = 1; $i < count($rings); $i++) {
                $this->innerBoundaries[] = new EmptyMapPolyline($rings[$i]);
            }
        }
    }

    public function getCenterCoordinate()
    {
    	return $this->outerBoundary->getCenterCoordinate();
    }

    public function getRings()
    {
        $outerRing = $this->outerBoundary->getPoints();
        $result = array($outerRing);
        if (isset($this->innerBoundaries) && count($this->innerBoundaries)) {
            foreach ($this->innerBoundaries as $boundary) {
                $result[] = $boundary->getPoints();
            }
        }
        return $result;
    }
}

class EmptyMapStyle implements MapStyle {
    public function getStyleForTypeAndParam($type, $param) {
        return null;
    }
}
