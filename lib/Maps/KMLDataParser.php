<?php

// http://schemas.opengis.net/kml/2.2.0/ogckml22.xsd
// http://portal.opengeospatial.org/files/?artifact_id=27810

require_once(LIB_DIR . '/XMLDataParser.php');

class KMLStyle extends XMLElement implements MapStyle
{
    protected $styleID;
    protected $isSimpleStyle = true;

    protected $iconStyle; // color, colorMode, scale, heading, hotSpot, icon>href
    protected $balloonStyle; // bgColor, textColor, text, displayMode
    protected $lineStyle; // color, colorMode, width
    protected $labelStyle;
    protected $listStyle;
    protected $polyStyle;

    // pointers to simple style objects
    protected $normalStyle;
    protected $highlightStyle;
    protected $styleContainer; // pointer to whoever owns the lookup table of simple styles

    public function getIconStyle() {
        return $this->iconStyle;
    }

    public function getLineStyle() {
        return $this->lineStyle;
    }

    public function getPolyStyle() {
        return $this->polyStyle;
    }

    private function getStyleForType($type) {
        $style = null;
        if ($this->isSimpleStyle) {
            switch ($type) {
                case MapStyle::POINT: $style = $this->iconStyle; break;
                case MapStyle::LINE: $style = $this->lineStyle; break;
                case MapStyle::POLYGON: $style = $this->polyStyle; break;
            }
        } else {
            $styleRef = $this->styleContainer->getStyle($this->normalStyle);
            switch ($type) {
                case MapStyle::POINT: $style = $styleRef->getIconStyle(); break;
                case MapStyle::LINE: $style = $styleRef->getLineStyle(); break;
                case MapStyle::POLYGON: $style = $styleRef->getPolyStyle(); break;
            }
        }
        return $style;
    }

    public function getStyleForTypeAndParam($type, $param) {
        $style = $this->getStyleForType($type);
        if (!$style) return null;

        if (isset($style[$param])) {
            return $style[$param];
        } else if ($type == MapStyle::POLYGON
            && $this->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::SHOULD_OUTLINE))
        {
            $outlineStyle = $this->getStyleForType(MapStyle::LINE);
            if (isset($outlineStyle[$param])) {
                return $outlineStyle[$param];
            }
        }
        return null;
    }

    // xml parsing

    public function setStyleContainer($container) {
        $this->styleContainer = $container;
    }

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
        {
            case 'ICONSTYLE':
                $iconChild = $element->getChildElement('ICON');
                $this->iconStyle = array(
                    MapStyle::ICON => $iconChild->getProperty('HREF'),
                    MapStyle::WIDTH => $iconChild->getProperty('W'),
                    MapStyle::HEIGHT => $iconChild->getProperty('H'),
                    MapStyle::SCALE => $element->getProperty('SCALE'),
                    );
                break;
            case 'BALLOONSTYLE':
                $this->balloonStyle = array(
                    MapStyle::FILLCOLOR => $element->getProperty('BGCOLOR'),
                    MapStyle::COLOR => $element->getProperty('TEXTCOLOR'),
                    );
                break;
            case 'LINESTYLE':
                $this->lineStyle = array(
                    MapStyle::COLOR => $element->getProperty('COLOR'),
                    MapStyle::WEIGHT => $element->getProperty('WEIGHT'),
                    );
                break;
            case 'POLYSTYLE':
                // if OUTLINE == 1, keep track and use lineStyle for outlines
                // if FILL == 1, use supplied color, otherwise just ignore color
                $shouldFill = $element->getProperty('FILL');
                $color = $shouldFill ? $element->getProperty('COLOR') : null;
                $this->polyStyle = array(
                    MapStyle::FILLCOLOR => $color,
                    MapStyle::SHOULD_OUTLINE => $element->getProperty('OUTLINE'),
                    );
                break;
            case 'LABELSTYLE':
                $this->labelStyle = array();
                break;
            case 'LISTSTYLE':
                $this->listStyle = array();
                break;
            case 'PAIR':
                $state = $element->getProperty('KEY');
                if ($state == 'normal') {
                    $this->normalStyle = substr($element->getProperty('STYLEURL'), 1);
                } else if ($state == 'highlighted') {
                    $this->highlightStyle = substr($element->getProperty('STYLEURL'), 1);
                }
                break;
            default:
                parent::addElement($element);
                break;
        }
        
    }
    
    public function __construct($name, $attribs)
    {
        $this->isSimpleStyle = ($name === 'STYLE');
        $this->setAttribs($attribs);
    }
}

class KMLPlacemark extends XMLElement implements Placemark
{
    protected $name = 'Placemark';
    // placemarks have no guaranteed unique identifiers (id is optional)
    // so we assign this based on its position in the feed
    protected $index;
    protected $title; // use this for "name" element
    protected $description;
    protected $address;
    protected $snippet;
    protected $style;
    protected $geometry;
    protected $category;
    
    private $fields;

    private static $elementMap = array(
        'NAME' => 'title',
        'DESCRIPTION' => 'description',
        'ADDRESS' => 'address',
        'SNIPPET' => 'snippet',
        );

    protected function elementMap() { return self::$elementMap; }
    
    public function __construct($name, $attribs)
    {
        $this->setAttribs($attribs);
    }

    // MapListElement interface

    public function getTitle() {
        if ($this->title === null) {
            return $this->getAttrib('ID');
        }
        return $this->title;
    }
    
    public function getSubtitle() {
        if (isset($this->address)) {
            return $this->address;
        }
        return $this->snippet;
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
    
    // Placemark interface

    public function getAddress() {
        return null;
    }

    public function getCategoryIds() {
        return array($this->category);
    }

    public function getGeometry() {
        return $this->geometry;
    }

    public function getDescription() {
        return $this->description;
    }
    
    public function getDescriptionType() {
    	return Placemark::DESCRIPTION_TEXT;
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
    
    // setters

    public function setStyle(KMLStyle $style) {
        $this->style = $style;
    }
    
    // this is set to the sequence where the placemark is found in the KML file
    // because KML has no unique id's
    public function setIndex($index) {
        $this->index = $index;
    }

    // xml

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        
        switch ($name)
        {
            case 'POINT':
            case 'LINESTRING':
            case 'LINEARRING':
            case 'POLYGON':
                $this->geometry = $element;
                break;
            case 'MULTIGEOMETRY':
            case 'MODEL':
            case 'GX:TRACK':
            case 'GX:MULTITRACK':
                throw new Exception("Geometry type $name not implemented yet");
                break;
            default:
                parent::addElement($element);
                break;
        }
    }
}

class KMLPoint extends XMLElement implements MapGeometry
{
    private $coordinate;

    public function getCenterCoordinate()
    {
        return $this->coordinate;
    }

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
       {
            // more tags see
            // http://code.google.com/apis/kml/documentation/kmlreference.html#point
            case 'COORDINATES':
                $xyz = explode(',', $value);
                $this->coordinate = array(
                    'lon' => trim($xyz[0]),
                    'lat' => trim($xyz[1]),
                    'altitude' => isset($xyz[2]) ? trim($xyz[2]) : null,
                    );
                break;
            default:
                parent::addElement($element);
                break;
        }
    }
}

class KMLLineString extends XMLElement implements MapPolyline
{
    private $coordinates = array();

    public function getCenterCoordinate()
    {
        $lat = 0;
        $lon = 0;
        $n = 0;
        foreach ($this->coordinates as $coordinate) {
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
        return $this->coordinates;
    }

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        switch ($name)
        {
            // more tags see
            // http://code.google.com/apis/kml/documentation/kmlreference.html#linestring
            case 'COORDINATES':
                foreach (preg_split('/\s/', trim($value)) as $line) {
                    $xyz = explode(',', trim($line));
                    if (count($xyz) >= 2) {
                        $this->coordinates[] = array(
                            'lon' => $xyz[0],
                            'lat' => $xyz[1],
                            'altitude' => isset($xyz[2]) ? $xyz[2] : null,
                            );
                    }
                }
                break;
            default:
                parent::addElement($element);
                break;
        }
    }
}

// basically the same thing as LineString but forms a closed loop
class KMLLinearRing extends KMLLineString {}

class KMLPolygon extends XMLElement implements MapPolygon
{
    private $outerBoundary;
    private $innerBoundaries;

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

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
        {
            // more tags see
            // http://code.google.com/apis/kml/documentation/kmlreference.html#polygon
            case 'OUTERBOUNDARYIS':
                $this->outerBoundary = $element->getChildElement('LINEARRING');
                break;
            case 'INNERBOUNDARYIS':
                $this->innerBoundaries = $element->getChildElement('LINEARRING');
                break;
            default:
                parent::addElement($element);
                break;
        }
    }
}

class KMLDocument extends XMLElement
{
    protected $description;
    protected $title; // use this for "name" element

    private static $elementMap = array(
        'NAME' => 'title',
        'DESCRIPTION' => 'description',
        );
    protected function elementMap() { return self::$elementMap; }

    public function __construct($name, $attribs)
    {
        $this->setAttribs($attribs);
    }

    public function getTitle() {
        return $this->title;
    }
}

class KMLFolder extends KMLDocument implements MapListElement, MapFolder
{
    protected $items = array();
    protected $index;
    protected $category;

    protected $folders = array();
    protected $features = array();

    public function addItem(MapListElement $item) {
        if ($item instanceof Placemark) {
            //$item->setIndex(count($this->items));
            //$item->setCategory($this->category);
            $this->features[] = $item;
        } elseif ($item instanceof MapFolder) {
            $this->folders[] = $item;
        }
        $this->items[] = $item;
    }

    public function setIndex($index) {
        $this->index = $index;
    }
    
    // MapFolder interface

    public function getChildCategories()
    {
        return $this->folders;
    }

    public function getAllFeatures()
    {
        return $this->features;
    }
    
    public function getListItems() {
        return $this->items;
    }

    public function getProjection() {
        return null;
    }
    
    // MapListElement interface

    public function getSubtitle() {
        return $this->description;
    }

    public function getId() {
        return $this->index;
    }
    
    public function getCategory() {
        return $this->category;
    }
    
    public function setCategory($category) {
        $this->category = $category;
    }
}

class KMLDataParser extends XMLDataParser implements MapDataParser
{
    protected $root;
    protected $elementStack = array();
    protected $data='';

    protected $document;
    protected $folders = array();
    protected $features = array();
    protected $title;
    protected $category;

    // whitelists
    protected static $startElements=array(
        'DOCUMENT', 'FOLDER',
        'STYLE','STYLEMAP',
        'PLACEMARK','POINT','LINESTRING', 'LINEARRING', 'POLYGON'
        );
    protected static $endElements=array(
        'DOCUMENT', 'FOLDER',
        'STYLE','STYLEMAP','STYLEURL',
        'PLACEMARK'
        );

    /////// MapDataParser

    public function getProjection() {
        return null;
    }

    public function getAllFeatures()
    {
        return $this->features;
    }

    public function getChildCategories()
    {
        return $this->folders;
    }

    public function getListItems()
    {
    }

    /////

    public function getTitle() {
        return $this->title;
    }
    
    public function setCategory($category) {
        $this->category = $category;
    }

    public function getCategory() {
        return $this->category;
    }

    public function getStyle($id) {
        if (substr($id, 0, 1) == '#') {
            $id = substr($id, 1);
        }
        if (isset($this->styles[$id])) {
            return $this->styles[$id];
        }
        return null;
    }

    protected function shouldHandleStartElement($name)
    {
        return in_array($name, self::$startElements);
    }

    protected function handleStartElement($name, $attribs)
    {
        switch ($name)
        {
            case 'DOCUMENT':
                $this->elementStack[] = new KMLDocument($name, $attribs);
                break;
            case 'FOLDER':
                $folder = new KMLFolder($name, $attribs);
                $parent = end($this->elementStack);
                // we need to do this before the element is completed
                // since this info needs to be available for nested children
                if ($parent instanceof KMLFolder) {
                    $newFolderIndex = count($parent->getChildCategories());
                    $categoryPath = $parent->getCategory();
                } elseif ($parent instanceof KMLDocument) { // child of root element
                    $newFolderIndex = count($this->items);
                    $categoryPath = $this->category;
                } else { // no document
                    $newFolderIndex = count($this->items);
                    $categoryPath = $this->category;
                }
                $categoryPath[] = $newFolderIndex;
                $folder->setIndex($newFolderIndex);
                $folder->setCategory($categoryPath);
                $this->elementStack[] = $folder;
                break;
            case 'STYLE':
                $this->elementStack[] = new KMLStyle($name, $attribs);
                break;
            case 'STYLEMAP':
                $style = new KMLStyle($name, $attribs);
                $style->setStyleContainer($this);
                $this->elementStack[] = $style;
                break;
            case 'PLACEMARK':
                $placemark = new KMLPlacemark($name, $attribs);
                $parent = end($this->elementStack);
                if (!($parent instanceof KMLFolder)) { // child of root element
                    $placemark->setCategory($this->category);
                }
                $this->elementStack[] = $placemark;
                break;
            case 'POINT':
                $this->elementStack[] = new KMLPoint($name, $attribs);
                break;
            case 'LINESTRING':
                $this->elementStack[] = new KMLLineString($name, $attribs);
                break;
            case 'LINEARRING':
                $this->elementStack[] = new KMLLinearRing($name, $attribs);
                break;
            case 'POLYGON':
                $this->elementStack[] = new KMLPolygon($name, $attribs);
                break;
        }
    }

    protected function shouldStripTags($element)
    {
        return false;
    }

    protected function shouldHandleEndElement($name)
    {
        return in_array($name, self::$endElements);
    }

    protected function handleEndElement($name, $element, $parent)
    {
        switch ($name)
        {
            case 'DOCUMENT':
                $this->title = $element->getTitle();
                // skip each drilldown level where only one thing exists,
                while (count($this->items) == 1 && $this->items[0] instanceof KMLFolder) {
                    $this->items = $this->items[0]->getListItems();
                }
                break;
            case 'FOLDER':
                if ($parent instanceof KMLFolder) {
                    $parent->addItem($element);
                } else {
                    $this->items[] = $element;
                    $this->folders[] = $element;
                }
                break;
            case 'STYLE':
            case 'STYLEMAP':
                $this->styles[$element->getAttrib('ID')] = $element;
                break;
            case 'PLACEMARK':
                $element->setIndex(count($this->items));
                if ($parent instanceof KMLFolder) {
                    $parent->addItem($element);
                } else {
                    $this->items[] = $element;
                }
                $element->setIndex(count($this->features));
                $this->features[] = $element;

                break;
            case 'STYLEURL':
                $value = $element->value();
                if ($parent->name() == 'Placemark') {
                    $parent->setStyle($this->getStyle($value));
                } else {
                    $parent->addElement($element);
                }
                break;
        }
    }
}



