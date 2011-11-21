<?php

includePackage('db');

class MapDB
{
    const PLACEMARK_TABLE = 'map_placemarks';
    const PLACEMARK_STYLES_TABLE = 'map_styles';
    const PLACEMARK_PROPERTIES_TABLE = 'map_placemark_properties';
    const CATEGORY_TABLE = 'map_categories';
    const PLACEMARK_CATEGORY_TABLE = 'map_placemark_categories';

    private static $db = null;

    private static $categoryIds = null;

    public static function getAllCategoryIds()
    {
        if (self::$categoryIds === null) {
            $sql = 'SELECT * FROM '.self::CATEGORY_TABLE
                  .' WHERE parent_category_id IS NULL';
            $results = self::connection()->query($sql);
            while (($row = $results->fetch(PDO::FETCH_ASSOC))) {
                self::$categoryIds[] = $row['category_id'];
            }
        }
        return self::$categoryIds;
    }

    public static function updateCategory(MapFolder $category, $items, $projection=null, $parentCategoryId=null) {
        $categoryId = $category->getId();
        $name = $category->getTitle();
        $description = $category->getSubtitle();

        $isStored = $category instanceof MapDBCategory && $category->isStored();
        if (!$isStored) {
            $sql = 'SELECT * FROM '.self::CATEGORY_TABLE.' WHERE category_id=?';
            $params = array($categoryId);
            $results = self::connection()->query($sql, $params);
            if ($results->fetch()) {
                $isStored = true;
            }
        }

        if (!$isStored) {
            if ($parentCategoryId === null) {
                $sql = 'INSERT INTO '.self::CATEGORY_TABLE.' (category_id, name, description) VALUES (?, ?, ?)';
                $params = array($categoryId, $name, $description);
            } else {
                $sql = 'INSERT INTO '.self::CATEGORY_TABLE.' (category_id, name, description, parent_category_id) VALUES (?, ?, ?, ?)';
                $params = array($categoryId, $name, $description, $parentCategoryId);
            }

        } else {
            if ($parentCategoryId === null) {
                $sql = 'UPDATE '.self::CATEGORY_TABLE.'   SET name=?, description=? WHERE category_id=?';
                $params = array($name, $description, $categoryId);
            } else {
                $sql = 'UPDATE '.self::CATEGORY_TABLE.'   SET name=?, description=?, parent_category_id=? WHERE category_id=?';
                $params = array($name, $description, $parentCategoryId, $categoryId);
            }
        }

        self::connection()->query($sql, $params);

        if ($projection === null || $projection instanceof MapProjector) {
            $projector = $projection;
        } else {
            $projector = new MapProjector();
            $projector->setSrcProj($projection);
        }
        foreach ($items as $item) {
            if ($item instanceof MapFolder) {
                self::updateCategory($item, $item->getListItems(), $projector, $categoryId);
            } elseif ($item instanceof Placemark) {
                self::updateFeature($item, $categoryId, $projector);
            }
        }
    }

    public static function updateFeature(Placemark $feature, $parentCategoryId, $projector=null) {
        $style = $feature->getStyle();
        if (method_exists($style, 'getId')) {
            $styleId = $style->getId();
        } else {
            $styleId = null;
        }

        $geometry = $feature->getGeometry();
        if ($geometry) {
            if ($projector) {
                $geometry = $projector->projectGeometry($geometry);
            }
            $centroid = $geometry->getCenterCoordinate();
            $wkt = WKTParser::wktFromGeometry($geometry);
        } else {
            // TODO: handle this instead of throwing exception
            throw new KurogoDataException("feature has no geometry");
        }

        $placemarkId = $feature->getId();

        // placemark table
        $isStored = $feature instanceof MapDBPlacemark && $feature->isStored();
        if (!$isStored) {
            $sql = 'SELECT * FROM '.self::PLACEMARK_TABLE.' WHERE placemark_id=? AND lat=? AND lon=?';
            $params = array($placemarkId, $centroid['lat'], $centroid['lon']);
            $results = self::connection()->query($sql, $params);
            if ($results->fetch()) {
                $isStored = true;
            }
        }

        $params = array(
            $feature->getTitle(), $feature->getAddress(), $styleId, $wkt,
            $placemarkId, $centroid['lat'], $centroid['lon'],
            );

        if ($isStored) {
            $sql = 'UPDATE '.self::PLACEMARK_TABLE
                  .'   SET name=?, address=?, style_id=?, geometry=?'
                  .' WHERE placemark_id=? AND lat=? AND lon=?';
        } else {
            $sql = 'INSERT INTO '.self::PLACEMARK_TABLE
                  .' (name, address, style_id, geometry, placemark_id, lat, lon)'
                  .' VALUES (?, ?, ?, ?, ?, ?, ?)';
        }

        self::connection()->query($sql, $params);
        if ($placemarkId === null) {
            // TODO: check db compatibility for this function
            $placemarkId = self::connection()->lastInsertId();
        }

        // categories
        $categories = $feature->getCategoryIds();
        if (!is_array($categories)) {
            $categories = array();
        }
        if (!in_array($parentCategoryId, $categories)) {
            $categories[] = $parentCategoryId;
        }
        foreach ($categories as $categoryId) {
            $sql = 'INSERT INTO '.self::PLACEMARK_CATEGORY_TABLE
                  .' (placemark_id, lat, lon, category_id)'
                  .' VALUES (?, ?, ?, ?)';
            $params = array($placemarkId, $centroid['lat'], $centroid['lon'], $categoryId);
            self::connection()->query($sql, $params, db::IGNORE_ERRORS);
        }

        // properties
        $sql = 'DELETE FROM '.self::PLACEMARK_PROPERTIES_TABLE.' WHERE placemark_id=?';
        $params = array($placemarkId);
        self::connection()->query($sql, $params);

        $properties = $feature->getFields();
        foreach ($properties as $name => $value) {
            $sql = 'INSERT INTO '.self::PLACEMARK_PROPERTIES_TABLE
                  .' (placemark_id, lat, lon, property_name, property_value)'
                  .' VALUES (?, ?, ?, ?, ?)';
            $params = array($placemarkId, $centroid['lat'], $centroid['lon'], $name, $value);
            self::connection()->query($sql, $params);
        }
    }

    public static function getFeatureByIdAndCategory($featureId, $categoryIds)
    {
        $sql = 'SELECT p.*, pc.category_id FROM '
              .self::PLACEMARK_TABLE.' p, '.self::PLACEMARK_CATEGORY_TABLE.' pc'
              .' WHERE p.placemark_id = ?'
              .'   AND p.placemark_id = pc.placemark_id'
              .'   AND p.lat = pc.lat AND p.lon = pc.lon';

        $orClauses = array();
        $params = array($featureId);
        foreach ($categoryIds as $categoryId) {
            $orClauses[] = ' pc.category_id = ?';
            $params[] = $categoryId;
        }
        if ($orClauses) {
            $sql .= ' AND ('.implode(' OR ', $orClauses).')';
        }

        $result = self::connection()->query($sql, $params);

        $placemark = null;
        $row = $result->fetch();
        if ($row) {
            $placemark = new MapDBPlacemark($row, true);
        }
        return $placemark;
    }

    public static function styleForId($styleId) {
        $sql = 'SELECT * FROM '.self::PLACEMARK_STYLE_TABLE
              .' WHERE style_id = ?';
        $params = array($styleId);
        $results = self::connection()->query($sql, $params);
        if ($results && $row = $results->fetch(PDO::FETCH_ASSOC)) {
            return new MapDBStyle($row, $this);
        } else {
            return new MapDBStyle(array('style_id' => $styleId));
        }
    }

    public static function categoryForId($categoryId)
    {
        $sql = 'SELECT * FROM '.self::CATEGORY_TABLE
              .' WHERE category_id = ?';
        $params = array($categoryId);
        $results = self::connection()->query($sql, $params);
        if ($results && $row = $results->fetch()) {
            return new MapDBCategory($row, true);
        } else {
            return new MapDBCategory(array('category_id' => $categoryId));
        }
    }
    
    public static function childrenForCategory($categoryId) {
        $sql = 'SELECT * FROM '.self::CATEGORY_TABLE
              .' WHERE parent_category_id = ?';
        $params = array($categoryId);
        $results = self::connection()->query($sql, $params);
        $categories = array();
        if ($results) {
            while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
                $categories[] = new MapDBCategory($row);
            }
        }
        return $categories;
    }
    

    public static function featuresForCategory($categoryId)
    {
        $sql = 'SELECT p.*, pc.category_id FROM '
              .self::PLACEMARK_TABLE.' p, '.self::PLACEMARK_CATEGORY_TABLE.' pc'
              .' WHERE p.placemark_id = pc.placemark_id'
              .'   AND pc.category_id = ?'
              .'   AND p.lat = pc.lat AND p.lon = pc.lon';
        $params = array($categoryId);
        $results = self::connection()->query($sql, $params);
        $features = array();
        if ($results) {
            while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
                $features[] = new MapDBPlacemark($row);
            }
        }
        return $features;
    }
    
    public static function propertiesForFeature(MapDBPlacemark $feature) {
        $sql = 'SELECT property_name, property_value FROM '.self::PLACEMARK_PROPERTIES_TABLE
              .' WHERE placemark_id = ? AND lat = ? AND lon = ?';
        $center = $feature->getGeometry()->getCenterCoordinate();
        $params = array($feature->getId(), $center['lat'], $center['lon']);
        $results = self::connection()->query($sql, $params);
        if ($results) {
            return $results->fetchAll();
        }
        return array();
    }

    public static function connection()
    {
        if (self::$db === null) {
            // TODO: get other db config values
            self::$db = SiteDB::connection();
            self::createTables();
        }

        return self::$db;
    }

    private static function createTables() {
        if (!self::checkTableExists(self::PLACEMARK_TABLE)) {
            $sql = 'CREATE TABLE '.self::PLACEMARK_TABLE.' (
                placemark_id CHAR(32) NOT NULL,
                name VARCHAR(128),
                address TEXT,
                style_id CHAR(32),
                lat DOUBLE,
                lon DOUBLE,
                geometry_type VARCHAR(16),
                geometry TEXT,
                CONSTRAINT placemark_id_pk PRIMARY KEY (placemark_id, lat, lon) )';
            self::$db->query($sql);
        }

        if (!self::checkTableExists(self::PLACEMARK_STYLES_TABLE)) {
            $sql = 'CREATE TABLE '.self::PLACEMARK_STYLES_TABLE.' (
                style_id CHAR(32) NOT NULL,
                color CHAR(8),
                fill_color CHAR(8),
                stroke_color CHAR(8),
                stroke_width DOUBLE,
                height DOUBLE,
                width DOUBLE,
                icon VARCHAR(128),
                scale DOUBLE,
                shape VARCHAR(32),
                consistency VARCHAR(32),
                CONSTRAINT style_id_pk PRIMARY KEY (style_id) )';
            self::$db->query($sql);
        }

        if (!self::checkTableExists(self::PLACEMARK_PROPERTIES_TABLE)) {
            $sql = 'CREATE TABLE '.self::PLACEMARK_PROPERTIES_TABLE.' (
                placemark_id CHAR(32) NOT NULL,
                lat DOUBLE,
                lon DOUBLE,
                property_name VARCHAR(32),
                property_value TEXT,
                CONSTRAINT placemark_id_fk FOREIGN KEY (placemark_id, lat, lon)
                  REFERENCES '.self::PLACEMARK_TABLE.' (placemark_id, lat, lon)
                  ON UPDATE CASCADE ON DELETE CASCADE )';
            self::$db->query($sql);
        }

        if (!self::checkTableExists(self::CATEGORY_TABLE)) {
            $sql = 'CREATE TABLE '.self::CATEGORY_TABLE.' (
                category_id CHAR(32) NOT NULL,
                name VARCHAR(128),
                description TEXT,
                projection CHAR(16),
                parent_category_id CHAR(32),
                CONSTRAINT category_id_pk PRIMARY KEY (category_id),
                CONSTRAINT category_id_fk FOREIGN KEY (parent_category_id)
                  REFERENCES '.self::CATEGORY_TABLE.' (category_id)
                  ON UPDATE CASCADE ON DELETE SET NULL )';
            self::$db->query($sql);
        }

        if (!self::checkTableExists(self::PLACEMARK_CATEGORY_TABLE)) {
            $sql = 'CREATE TABLE '.self::PLACEMARK_CATEGORY_TABLE.' (
                placemark_id CHAR(32) NOT NULL,
                lat DOUBLE,
                lon DOUBLE,
                category_id CHAR(32) NOT NULL,
                CONSTRAINT placemark_category_fk_placemark FOREIGN KEY (placemark_id, lat, lon)
                  REFERENCES '.self::PLACEMARK_TABLE.' (placemark_id, lat, lon)
                  ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT placemark_category_fk_category FOREIGN KEY (category_id)
                  REFERENCES '.self::CATEGORY_TABLE.' (category_id)
                  ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT unique_placemark_category UNIQUE (placemark_id, lat, lon, category_id) )';
            self::$db->query($sql);
        }
    }

    private static function checkTableExists($table) {
        $sql = 'SELECT 1 FROM '.$table;
        $result = self::connection()->limitQuery($sql, array(), db::IGNORE_ERRORS);
        if (!$result) {
            return false;
        }
        return true;
    }

}


