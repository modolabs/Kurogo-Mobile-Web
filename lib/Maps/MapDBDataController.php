<?php

includePackage('db');

class MapDBPlacemark extends BasePlacemark
{
    private $centroid = null;

    public function setStyle($style)
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
            $this->style = MapDBDataParser::styleForId($dbFields['style_id']);
        } else {
            $this->style = new MapBaseStyle();
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
        $styleId = isset($this->style) ? $this->style->getId() : null;

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
            $results = MapDBDataParser::propertiesForFeature($this);
            foreach ($results as $row) {
                $this->fields[$row['property_name']] = $row['property_value'];
            }
        }
        return $this->fields;
    }

    public function addCategoryId($id)
    {
        if (!in_array($id, $this->categories)) {
            $this->categories[] = $id;
        }
    }
}

class MapDBStyle implements MapStyle
{
    private $id;
    private $color;
    private $fillColor;
    private $strokeColor;
    private $strokeWidth;
    private $height;
    private $width;
    private $icon;
    private $style;
    private $scale;
    private $shape;
    private $consistency;

    private static function fieldMap() {
        return array(
            'strokeColor' => 'stroke_color',
            'fillColor' => 'fill_color',
            'strokeWidth' => 'stroke_width',
            'id' => 'style_id',
            );
    }

    public function getStyleForTypeAndParam($type, $param)
    {
        $style = array();

        switch ($type) {
            case MapStyle::LINE:
                if (isset($this->strokeWidth)) {
                    $style[MapStyle::WEIGHT] = $this->strokeWidth;
                }
                if (isset($this->strokeColor)) {
                    $style[MapStyle::COLOR] = $this->strokeColor;
                }
                if (isset($this->consistency)) {
                    $style[MapStyle::CONSISTENCY] = $this->consistency;
                }
                break;
            case MapStyle::POLYGON:
                if (isset($this->fillColor)) {
                    $style[MapStyle::FILLCOLOR] = $this->fillColor;
                }
                if (isset($this->strokeWidth)) {
                    $style[MapStyle::WEIGHT] = $this->strokeWidth;
                    if ($this->strokeWidth > 0) {
                        $style[MapStyle::SHOULD_OUTLINE] = 1;
                    }
                }
                if (isset($this->strokeColor)) {
                    $style[MapStyle::COLOR] = $this->strokeColor;
                }
                if (isset($this->consistency)) {
                    $style[MapStyle::CONSISTENCY] = $this->consistency;
                }
                break;
            case MapStyle::CALLOUT:
                // TODO: we probably want separate properties for these
                // instead of sharing with polygons
                if (isset($this->fillColor)) {
                    $style[MapStyle::FILLCOLOR] = $this->fillColor;
                }
                if (isset($this->fillColor)) {
                    $style[MapStyle::COLOR] = $this->color;
                }
                break;
            case MapStyle::POINT:
            default:
                if (isset($this->icon)) {
                    $style[MapStyle::ICON] = $this->icon;
                } else if (isset($this->color)) {
                    $style[MapStyle::COLOR] = $this->color;
                }

                if (isset($this->width)) {
                    $style[MapStyle::WIDTH] = $this->width;
                }
                if (isset($this->height)) {
                    $style[MapStyle::HEIGHT] = $this->height;
                }
                if (isset($this->scale)) {
                    $style[MapStyle::SCALE] = $this->scale;
                }

                break;
        }

        return $style;
    }

    public function getId()
    {
        return $this->id;
    }
    
    public function __construct($dbFields)
    {
        $fieldMap = self::fieldMap();
        foreach (get_class_vars(get_class($this)) as $name => $value) {
            $mappedName = isset($fieldMap[$name]) ? $fieldMap[$name] : $name;
            if (isset($dbFields[$mappedName])) {
                $this->{$nmae} = $dbFields[$name];
            }
        }
    }

    public function dbFields()
    {
        $fields = array();
        $fieldMap = self::fieldMap();
        foreach (get_class_vars(get_class($this)) as $name => $value) {
            $mappedName = isset($fieldMap[$name]) ? $fieldMap[$name] : $name;
            $fields[$mappedName] = $value;
        }
        return $fields;
    }
}


class MapDBCategory extends MapCategory
{
    private $parentCategoryId;
    private $features;
    private $childCategories = array();
    private $stored = false;
    private $projection;

    public function __construct($dbFields, $fromDB=false)
    {
        $this->id = $dbFields['category_id'];
        $this->stored = $fromDB;
        if (isset($dbFields['parent_category_id'])) {
            $this->parentCategoryId = $dbFields['parent_category_id'];
        }
        if (isset($dbFields['name'])) {
            $this->name = $dbFields['name'];
        }
        if (isset($dbFields['description'])) {
            $this->description = $dbFields['description'];
        }
        if (isset($dbFields['projection'])) {
            $this->projection = $dbFields['projection'];
        }
    }

    public function setParentCategoryId($id)
    {
        $this->parentCategoryId = $id;
    }

    public function isStored()
    {
        return $this->stored;
    }

    public function dbFields()
    {
        $fields = array(
            'category_id' => $this->id,
            'parent_category_id' => $this->parentCategoryId,
            'name' => $this->name,
            'projection' => $this->projection,
            'description' => $this->description,
            );

        return $fields;
    }

    public function getProjection()
    {
        return $this->projection;
    }

    public function getChildCategories()
    {
        return $this->childCategories;
    }

    public function getAllFeatures()
    {
        return $this->features;
    }

    public function getListItems()
    {
        if ($this->childCategories) {
            return $this->childCategories;
        } else if ($this->features) {
            return $this->features;
        }

        // no data in memory, check db
        $this->childCategories = MapDBDataParser::childrenForCategory($this->id);
        if ($this->childCategories) {
            return $this->childCategories;
        }

        $this->features = MapDBDataParser::featuresForCategory($this->id);
        if ($this->features) {
            return $this->features;
        }
    }
}


class MapDBDataController extends MapDataController implements MapFolder
{
    protected $DEFAULT_PARSER_CLASS = "MapDBDataParser";
    private $hasDBData = false;
    private $db;
    private $subtitle;

    public function getCategoryId()
    {
        return $this->db->getCategoryId();
    }

    public function getSubtitle()
    {
        return $this->subtitle;
    }

    //////// DataController overrides

    protected function initStreamContext($args)
    {
        // no stream is required if:
        
        // data is embedded
        if (isset($args['DATA_CONTAINED']) && $args['DATA_CONTAINED']) {
            return;
        }

        // we don't need to refresh our cache
        if ($this->cacheIsFresh()) {
            return;
        }

        parent::initStreamContext($args);
    }

    protected function init($args)
    {
        parent::init($args);
        $this->db = new MapDBDataParser();
        $this->db->init($args);
    }

    public function getData() {
        if ($this->parser instanceof ShapefileDataParser) {
            return;
        }
        return parent::getData();
    }

    protected function getCacheData() {
        if ($this->db->isPopulated() && $this->db->getCategory()->getListItems()) {
            // make sure this category was populated before skipping
            $this->hasDBData = true;
        } else {
            return parent::getCacheData();
        }
    }

    protected function parseData($data, DataParser $parser=null) {
        $items = null;
        if ($this->cacheIsFresh() && $this->hasDBData) {
            $items = $this->db->getCategory()->getListItems();
        }
        if (!$items) {
            $items = parent::parseData($data, $parser);
            $this->db->updateControllerCategory($this, $items);
        }
        return $items;
    }

    ////// MapDataController methods

    public function getAllFeatures()
    {
        $this->getListItems(); // make sure we're populated
        if ($this->hasDBData) {
            return $this->db->getCategory()->getAllFeatures();
        }
        return $this->parser->getAllFeatures();
    }

    // TODO allow config of searchable fields
    public function search($searchText)
    {
        $this->setSelectedFeatures($this->db->search($searchText));
        return $this->getAllSelectedFeatures();
    }

    public function searchByProximity($center, $tolerance, $maxItems)
    {
        if (isset($projection)) {
            $projector = new MapProjector();
            $projector->setSrcProj($projection);
            $center = $projector->projectPoint($center);
        }

        $this->setSelectedFeatures(
            $this->db->searchByProximity($center, $tolerance, $maxItems));
        return $this->getAllSelectedFeatures();
    }
}


class MapDBDataParser extends DataParser //implements MapDataParser
{
    const PLACEMARK_TABLE = 'map_placemarks';
    const PLACEMARK_STYLES_TABLE = 'map_styles';
    const PLACEMARK_PROPERTIES_TABLE = 'map_placemark_properties';
    const CATEGORY_TABLE = 'map_categories';
    const PLACEMARK_CATEGORY_TABLE = 'map_placemark_categories';

    private $category = null;
    private $categoryId;

    private static $db;
    
    public function init($args) {
        parent::init($args);
        self::createTables();
        $this->categoryId = mapIdForFeedData($args);
    }

    public function parseData($data) {
        // do nothing
    }

    public function isPopulated() {
        return $this->getCategory()->isStored();
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /*
    public function setCategoryId($categoryId) {
        $this->categoryId = $categoryId;
    }
    */

    public function getChild($childId) {
        return self::getChildForCategory($childId, $this->categoryId);
    }

    public function getCategory() {
        if (!$this->category) {
            $this->category = self::categoryForId($this->categoryId);
        }
        return $this->category;
    }

    public function getProjection() {
        return $this->getCategory()->getProjection();
    }

    public function updateControllerCategory(MapDBDataController $controller, $items) {
        $params = array(
            $controller->getTitle(),
            $controller->getSubtitle(),
            $this->categoryId,
            );

        if (!$this->isPopulated()) {
            $sql = 'INSERT INTO '.self::CATEGORY_TABLE
                  .' (name, description, category_id)'
                  .' VALUES (?, ?, ?)';
        } else {
            $sql = 'UPDATE '.self::CATEGORY_TABLE
                  .'   SET name=?, description=?'
                  .' WHERE category_id=?';
        }

        self::connection()->query($sql, $params);

        foreach ($items as $item) {
            if ($item instanceof MapCategory) {
                self::updateCategory($item, $this->categoryId);
            } elseif ($item instanceof Placemark) {
                self::updateFeature($item, $this->categoryId);
            }
        }
    }

    public function search($searchText)
    {
        $sql = 'SELECT p.* FROM '
              .self::PLACEMARK_TABLE.' p, '.self::PLACEMARK_CATEGORY_TABLE.' pc'
              .' WHERE p.placemark_id = pc.placemark_id'
              .'   AND pc.category_id = ?'
              // TODO this substring pattern might need tweaking
              .'   AND (p.name like ? OR p.name like ?)';

        $params = array($this->categoryId, "$searchText%", "% $searchText%");
        $result = self::connection()->query($sql, $params);

        $placemarks = array();
        while ($row = $result->fetch()) {
            $placemark = new MapDBPlacemark($row, true);
            $placemark->addCategoryId($this->categoryId);
            $placemarks[] = $placemark;
        }
        return $placemarks;
    }

    public function searchByProximity($center, $tolerance, $maxItems)
    {
        // approximate upper/lower bounds for lat/lon before calculating GCD
        $dLatRadians = $tolerance / EARTH_RADIUS_IN_METERS;
        // by haversine formula
        $dLonRadians = 2 * asin(sin($dLatRadians / 2) / cos($center['lat'] * M_PI / 180));

        $dLatDegrees = $dLatRadians * 180 / M_PI;
        $dLonDegrees = $dLonRadians * 180 / M_PI;

        $min = array('lat' => $center['lat'] - $dLatDegrees, 'lon' => $center['lon'] - $dLonDegrees);
        $max = array('lat' => $center['lat'] + $dLatDegrees, 'lon' => $center['lon'] + $dLonDegrees);

        if ($this->getProjection()) {
            $projector = new MapProjector();
            $projector->setDstProj($this->getProjection());

            $min = $projector->projectPoint($min);
            $max = $projector->projectPoint($max);
        }

        $sql = 'SELECT p.* FROM '
              .self::PLACEMARK_TABLE.' p, '.self::PLACEMARK_CATEGORY_TABLE.' pc'
              .' WHERE p.placemark_id = pc.placemark_id'
              .'   AND pc.category_id = ?'
              .'   AND p.lat >= ? AND p.lat < ? AND p.lon >= ? AND p.lon < ?'
              .' ORDER BY (p.lat - ?)*(p.lat - ?) + (p.lon - ?)*(p.lon - ?)';
        $params = array(
            $this->categoryId,
            $min['lat'], $max['lat'], $min['lon'], $max['lon'],
            //$minLat, $maxLat, $minLon, $maxLon,
            $center['lat'], $center['lon']);

        if ($maxItems) {
            $result = self::connection()->limitQuery(
                $sql, $params, false, array(), $maxItems);
        } else {
            $result = self::connection()->query($sql, $params);
        }

        $placemarks = array();
        while ($row = $result->fetch()) {
            $placemark = new MapDBPlacemark($row, true);
            $placemark->addCategoryId($this->categoryId);
            $placemarks[] = $placemark;
        }
        return $placemarks;
    }

    // static functions

    public static function updateFeature(Placemark $feature, $parentCategoryId) {
        $style = $feature->getStyle();
        if (method_exists($style, 'getId')) {
            $styleId = $style->getId();
        } else {
            $styleId = null;
        }

        $geometry = $feature->getGeometry();
        if ($geometry) {
            $centroid = $geometry->getCenterCoordinate();
            $wkt = WKTParser::wktFromGeometry($geometry);
        } else {
            // TODO: handle this instead of throwing exception
            throw new Exception("feature has no geometry");
        }

        $placemarkId = $feature->getId();

        // placemark table
        $isStored = $feature instanceof MapDBPlacemark && $feature->isStored();
        if (!$isStored) {
            $sql = 'SELECT * FROM '.self::PLACEMARK_TABLE
                  .' WHERE placemark_id=? AND lat=? AND lon=?';
            $params = array($placemarkId, $centroid['lat'], $centroid['lon']);
            $results = self::connection()->query($sql, $params);
            if ($results) {
                $isStored = true;
            }
        }

        $params = array(
            $feature->getTitle(), $feature->getAddress(), $styleId,
            $centroid['lat'], $centroid['lon'], $wkt, $placemarkId,
            );

        if ($isStored) {
            $sql = 'UPDATE '.self::PLACEMARK_TABLE
                  .'   SET name=?, address=?, style_id=?,  lat=?, lon=?, geometry=?'
                  .' WHERE placemark_id=?';
        } else {
            $sql = 'INSERT INTO '.self::PLACEMARK_TABLE
                  .' (name, address, style_id, lat, lon, geometry, placemark_id)'
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
                  .' (placemark_id, category_id)'
                  .' VALUES (?, ?)';
            $params = array($placemarkId, $categoryId);
            self::connection()->query($sql, $params, db::IGNORE_ERRORS);
        }

        // properties
        $sql = 'DELETE FROM '.self::PLACEMARK_PROPERTIES_TABLE
              .' WHERE placemark_id=?';
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

    public static function updateCategory(MapDBCategory $category, $parentCategoryId) {
        $categoryId = $category->getId();
        if ($category->isStored()) {
            $sql = 'INSERT INTO '.self::CATEGORY_TABLE
                  .' (category_id, name, description, parent_category_id)'
                  .' VALUES (?, ?, ?, ?)';
            $params = array(
                $categoryId, $category->getTitle(),
                $category->getSubtitle(), $parentCategoryId);
        } else {
            $sql = 'UPDATE '.self::CATEGORY_TABLE
                  .'   SET name=?, description=?, parent_category_id=?'
                  .' WHERE category_id=?';
            $params = array(
                $category->getTitle(), $category->getSubtitle(),
                $parentCategoryId, $categoryId);
        }
        self::connection()->query($sql, $params);

        foreach ($category->getListItems() as $item) {
            if ($item instanceof MapFolder) {
                self::updateCategory($item, $categoryId);
            } elseif ($item instanceof Placemark) {
                self::updateFeature($item, $categoryId);
            }
        }
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

    public static function categoryForId($categoryId) {
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

    public static function featureForId($featureId) {
        $sql = 'SELECT * FROM '.self::PLACEMARK_TABLE
              .' WHERE placemark_id = ?';
        $params = $featureId;
        $results = self::connection()->query($sql, $params);
        if ($results && $row = $results->fetch()) {
            return new MapDBPlacemark($row, true);
        } else {
            return new MapDBPlacemark(array('placemark_id' => $featureId));
        }
    }

    public static function categoriesForFeature($featureId) {
        $sql = 'SELECT c.* FROM '
              .self::CATEGORY_TABLE.' c, '.self::PLACEMARK_CATEGORY_TABLE.' pc'
              .' WHERE c.category_id = pc.category_id'
              .'   AND pc.placemark_id = ?';
        $params = array($featureId);
        $results = self::connection()->query($sql, $params);
        $features = array();
        if ($results) {
            while ($row = $results->fetch()) {
                $features[] = new MapDBCategory($row);
            }
        }
        return $features;
    }

    public static function childForCategory($childId, $categoryId)
    {
        // check if this is a category
        $sql = 'SELECT * FROM '.self::CATEGORY_TABLE
              .' WHERE category_id = ? AND parent_category_id = ?';
        $params = array($childId, $categoryId);
        $results = self::connection()->query($sql, $params);
        if ($results && $row = $result->fetch(PDO::FETCH_ASSOC)) {
            return new MapDBCategory($row);
        }

        // check if this is a feature
        $sql = 'SELECT p.* FROM '
              .self::PLACEMARK_TABLE.' p, '.self::PLACEMARK_CATEGORY_TABLE.' pc'
              .' WHERE p.placemark_id = ?'
              .'   AND p.placemark_id = pc.placemark_id'
              .'   AND pc.category_id = ?';
        $params = array($childId, $categoryId);
        $results = self::connection()->query($sql, $params);
        if ($results && $row = $result->fetch(PDO::FETCH_ASSOC)) {
            return new MapDBPlacemark($row);
        }

        return null;
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

    public static function featuresForCategory($categoryId) {
        $sql = 'SELECT p.* FROM '
              .self::PLACEMARK_TABLE.' p, '.self::PLACEMARK_CATEGORY_TABLE.' pc'
              .' WHERE p.placemark_id = pc.placemark_id'
              .'   AND pc.category_id = ?';
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

    private static function connection()
    {
        // TODO: get other db config values
        return SiteDB::connection();
    }

    private static function createTables() {
        $conn = self::connection();

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
            $conn->query($sql);
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
            $conn->query($sql);
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
            $conn->query($sql);
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
            $conn->query($sql);
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
                CONSTRAINT unique_placemark_category UNIQUE (placemark_id, category_id) )';
            $conn->query($sql);
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


