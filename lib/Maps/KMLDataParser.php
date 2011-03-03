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

class KMLPlacemark extends XMLElement implements MapFeature
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

    public function getTitle() {
        if ($this->title === null) {
            return $this->getAttrib('ID');
        }
        return $this->title;
    }
    
    public function getSubtitle() {
        $description = strip_tags($this->getDescription());
        if ($description) {
            $description = substr($description, 0, 80).'...';
            return $description;
        }
        return null;
    }

    public function getGeometry() {
        return $this->geometry;
    }
    
    public function setGeometry(MapGeometry $geometry) {
        $this->geometry = $geometry;
    }

    public function getDescription() {
        return $this->description;
    }
    
    public function getDescriptionType() {
    	return MapFeature::DESCRIPTION_TEXT;
    }

    public function setStyle(KMLStyle $style) {
        $this->style = $style;
    }
    
    public function getIndex() {
        return $this->index;
    }
    
    public function setIndex($index) {
        $this->index = $index;
    }

    public function getStyle() {
        return $this->style;
    }

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
    /*
    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();

        switch ($name)
        {
            case 'NAME':
                $this->title = $value;
                break;
            case 'DESCRIPTION':
                $this->description = $value;
                break;
            default:
                parent::addElement($element);
                break;
        }
    }
    */

    public function getTitle() {
        return $this->title;
    }
}

class KMLFolder extends KMLDocument implements MapListElement
{
    protected $items = array();
    protected $index;

    public function addItem(MapFeature $item) {
        $item->setIndex(count($this->items));
        $this->items[] = $item;
    }

    public function getItems() {
        return $this->items;
    }

    public function getSubtitle() {
        return $this->description;
    }

    public function setIndex($index) {
        $this->index = $index;
    }

    public function getIndex() {
        return $this->index;
    }
}

class KMLDataParser extends XMLDataParser
{
    protected $root;
    protected $elementStack = array();
    protected $data='';

    protected $document;
    protected $folders = array();

    // whitelists
    protected static $startElements=array(
        'DOCUMENT', 'FOLDER',
        'STYLE','STYLEMAP',//'ICONSTYLE'
        'PLACEMARK','POINT','LINESTRING', 'LINEARRING', 'POLYGON'
        );
    protected static $endElements=array(
        'DOCUMENT', 'FOLDER',
        'STYLE','STYLEMAP','STYLEURL',
        'PLACEMARK'
        );

    /*    
    public function init($args)
    {
    }
    */

    public function getTitle() {
        return $this->document->getTitle();
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
                $this->elementStack[] = new KMLFolder($name, $attribs);
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
                $this->elementStack[] = new KMLPlacemark($name, $attribs);
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
            //case 'ICONSTYLE':
            //    $this->elementStack[] = new KMLIconStyle($name, $attribs);
            //    break;
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
                $this->document = $element;
                break;
            case 'FOLDER':
                $element->setIndex(count($this->items));
                $this->items[] = $element;
                break;
            case 'STYLE':
            case 'STYLEMAP':
                $this->styles[$element->getAttrib('ID')] = $element;
                break;
            case 'PLACEMARK':
                $element->setIndex(count($this->items));
                if (get_class($parent) == 'KMLFolder') {
                    $parent->addItem($element);
                } else {
                    $element->setIndex(count($this->items));
                    $this->items[] = $element;
                }
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



