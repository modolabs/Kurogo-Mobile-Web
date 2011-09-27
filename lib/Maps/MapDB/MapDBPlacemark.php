<?php

class MapDBPlacemark extends BasePlacemark
{
    private $centroid = null;

    public function setStyle(MapStyle $style)
    {
        $this->style = $style;
    }

    public function __construct($dbFields) {
        if (isset($dbFields['placemark_id'])) {
            $this->id = $dbFields['placemark_id'];
        }

        if (isset($dbFields['name'])) {
            $this->title = $dbFields['name'];
        }

        if (isset($dbFields['address'])) {
            $this->address = $dbFields['address'];
        }

        if (isset($dbFields['lat'], $dbFields['lon'])) {
            $this->centroid = array('lat' => $dbFields['lat'], 'lon' => $dbFields['lon']);
        }

        if (isset($dbFields['geometry'])) {
            $this->geometry = WKTParser::parseWKTGeometry($dbFields['geometry']);
            if (!$this->centroid) {
                $this->centroid = $this->geometry->getCenterCoordinate();
            }
        }

        if (isset($dbFields['style_id'])) {
            $this->style = MapDB::styleForId($dbFields['style_id']);
        } else {
            $this->style = new MapBaseStyle();
        }

        if (isset($dbFields['category_id'])) {
            $this->addCategoryId($dbFields['category_id']);
        }
    }

    public function getGeometry()
    {
        if (isset($this->geometry)) {
            return $this->geometry;
        } else {
            return new MapBasePoint($this->centroid);
        }
    }

    public function dbFields()
    {
        $styleId = (isset($this->style) && $this->style instanceof MapDBStyle) ? $this->style->getId() : null;

        $fields = array(
            'placemark_id' => $this->id,
            'name' => $this->title,
            'address' => $this->address,
            'lat' => $this->centroid['lat'],
            'lon' => $this->centroid['lon'],
            'geometry' => WKTParser::wktFromGeometry($this->geometry),
            'style_id' => $styleId,
            );

        return $fields;
    }

    public function getFields()
    {
        if (!$this->fields) {
            $results = MapDB::propertiesForFeature($this);
            foreach ($results as $row) {
                $this->fields[$row['property_name']] = $row['property_value'];
            }
        }
        return $this->fields;
    }
}
