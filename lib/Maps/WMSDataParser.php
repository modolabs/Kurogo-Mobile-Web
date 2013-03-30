<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

if (!function_exists('xml_parser_create')) {
    throw new KurogoException('XML Parser PHP extension is not installed. http://www.php.net/manual/en/intro.xml.php');
}

class WMSLayer extends XMLElement
{
    const GEOGRAPHIC_PROJECTION = 'WGS84'; // something unique

    protected $name = 'LAYER';
    private $layerName; // "name" in feed
    private $queryable;
    private $title;
    private $abstract;
    private $projections = array();
    private $bboxes = array(); // bboxes in available projections
    private $styles = array();
    private $minScaleDenom = null;
    private $maxScaleDenom = null;
    
    public function canDrawAtScale($scale)
    {
        // add a small buffer for rounding errors
        // TODO figure out if this is necessary
        // 0.752 is the largest value that works for Harvard's WMS server
        if ($this->minScaleDenom !== null && $scale < $this->minScaleDenom * 0.752)
            return false;
        if ($this->maxScaleDenom !== null && $scale > $this->maxScaleDenom * 1.1)
            return false;
        return true;
    }
    
    public function getProjections()
    {
        return array_keys($this->projections);
    }
    
    public function getBBoxForProjection($proj) {
        if ($proj === null)
            $proj = self::GEOGRAPHIC_PROJECTION;
        if (isset($this->projections[$proj]))
            $proj = $this->projections[$proj];
        return $this->bboxes[$proj];
    }
    
    public function getLayerName() {
        return $this->layerName;
    }
    
    public function getTitle() {
        return $this->title;
    }
    
    public function getDefaultStyle() {
        if (count($this->styles))
            return $this->styles[0];
        return null;
    }

    public function __construct($name, $attribs)
    {
        $this->setAttribs($attribs);
    }

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();

        switch ($name)
        {
            case 'NAME':
                $this->layerName = $value;
                break;
            case 'TITLE':
                $this->title = $value;
                break;
            case 'ABSTRACT':
                $this->abstract = $value;
                break;
            case 'MAXSCALEDENOMINATOR':
                $this->maxScaleDenom = $value;
                break;
            case 'MINSCALEDENOMINATOR':
                $this->minScaleDenom = $value;
                break;
            case 'CRS':
                $projNumber = end(explode(':', $value));
                if ($projNumber == '4326') {
                    // 4326 flips x and y cooordinates (x = lat, y = lon)
                    // TODO we need a better way to deal with this special case
                    $this->projections[$projNumber] = self::GEOGRAPHIC_PROJECTION;
                } else {
                    $this->projections[$projNumber] = $value;
                }
                break;
            case 'EX_GEOGRAPHICBOUNDINGBOX':
                $this->bboxes[self::GEOGRAPHIC_PROJECTION] = array(
                    'xmin' => floatval($element->getProperty('WESTBOUNDLONGITUDE')),
                    'xmax' => floatval($element->getProperty('EASTBOUNDLONGITUDE')),
                    'ymin' => floatval($element->getProperty('SOUTHBOUNDLATITUDE')),
                    'ymax' => floatval($element->getProperty('NORTHBOUNDLATITUDE')),
                    );
                break;
            case 'BOUNDINGBOX':
                $this->bboxes[$element->getAttrib('CRS')] = array(
                    'xmin' => floatval($element->getAttrib('MINX')),
                    'xmax' => floatval($element->getAttrib('MAXX')),
                    'ymin' => floatval($element->getAttrib('MINY')),
                    'ymax' => floatval($element->getAttrib('MAXY')),
                    );
                break;
            case 'STYLE':
                $this->styles[] = $element;
                break;
            default:
                parent::addElement($element);
                break;
        }
    }
}

class WMSStyle extends XMLElement
{
    protected $name = 'STYLE';
    private $styleName; // "name" in feed
    private $title;
        
    public function __construct($name, $attribs)
    {
        $this->setAttribs($attribs);
    }
    
    public function getStyleName()
    {
        return $this->styleName;
    }

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
        {
            case 'NAME':
                $this->styleName = $value;
                break;
            case 'TITLE':
                $this->title = $value;
                break;
            default:
                parent::addElement($element);
                break;
        }
    }
}

class WMSDataParser extends DataParser
{
    protected $serviceTitle;
    protected $serviceName;
    protected $serviceAbstract;
    protected $boundingLayer;
    protected $layers = array();
    protected $imageFormats = array();
    protected $data;
    
    // images can't exceed these dimensions
    protected $maxWidth;
    protected $maxHeight;
    
    protected $elementStack = array();
    
    public function getBBoxForProjection($proj) {
        return $this->boundingLayer->getBBoxForProjection($proj);
    }
    
    public function getLayer($layerName)
    {
        return $this->layers[$layerName];
    }

    public function getProjections()
    {
        return $this->boundingLayer->getProjections();
    }
    
    public function getLayerNames()
    {
        $layerNames = array();
        foreach ($this->layers as $name => $layer) {
            $layerNames[] = $layer->getLayerName();
        }
        return $layerNames;
    }
    
    protected function startElement($xml_parser, $name, $attribs)
    {
        $this->data = '';
        
        switch ($name) {
        case 'GETMAP':
            break;
        case 'LAYER':
            $this->elementStack[] = new WMSLayer($name, $attribs);
            break;
        case 'STYLE':
            $this->elementStack[] = new WMSStyle($name, $attribs);
            break;
        default:
            $this->elementStack[] = new XMLElement($name, $attribs);
            break;
        }
    }
    
    protected function endElement($xml_parser, $name)
    {
        if ($element = array_pop($this->elementStack)) {

            $element->setValue($this->data, false);
            $parent = end($this->elementStack);

            if (!$parent) {
                $this->root = $element;
            } else {
                switch ($name) {
                case 'NAME':
                    if ($parent->name() == 'SERVICE') {
                        $this->serviceName = $element->value();
                    } else {
                        $parent->addElement($element);
                    }
                    break;
                case 'TITLE':
                    if ($parent->name() == 'SERVICE') {
                        $this->serviceTitle = $element->value();
                    } else {
                        $parent->addElement($element);
                    }
                    break;
                case 'ABSTRACT':
                    if ($parent->name() == 'SERVICE') {
                        $this->serviceAbstract = $element->value();
                    } else {
                        $parent->addElement($element);
                    }
                    break;
                case 'MAXWIDTH':
                    $this->maxWidth = $element->value();
                    break;
                case 'MAXHEIGHT':
                    $this->maxHeight = $element->value();
                    break;
                case 'FORMAT':
                    if ($parent->name() == 'GETMAP') {
                        $this->imageFormats[] = $element->value();
                    } else {
                        $parent->addElement($element);
                    }
                    break;
                case 'LAYER':
                    if ($parent->name() == 'LAYER') {
                        $this->layers[$element->getLayerName()] = $element;
                    } else {
                        $this->boundingLayer = $element;
                    }
                default:
                    $parent->addElement($element);
                    break;
                }
            }
        }
        
        $this->data = '';
    }
    
    protected function characterData($xml_parser, $data)
    {
        $this->data .= $data;
    }
    
    public function parseData($contents) {
        $xml_parser = xml_parser_create();

        // use case-folding so we are sure to find the tag in $map_array
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
        xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, true);

        $this->setEncoding(xml_parser_get_option($xml_parser, XML_OPTION_TARGET_ENCODING));
        
        xml_set_element_handler($xml_parser, array($this,"startElement"), array($this,"endElement"));
        xml_set_character_data_handler($xml_parser, array($this,"characterData"));
        
        if (!xml_parse($xml_parser, $contents)) {
            throw new KurogoDataException(sprintf("XML error: %s at line %d",
                        xml_error_string(xml_get_error_code($xml_parser)),
                        xml_get_current_line_number($xml_parser)));
        }
        xml_parser_free($xml_parser);
    }

}

