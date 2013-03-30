<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

// http://schemas.opengis.net/kml/2.2.0/ogckml22.xsd
// http://portal.opengeospatial.org/files/?artifact_id=27810
// http://code.google.com/apis/kml/documentation/kmlreference.html

class KMLDataParser extends XMLDataParser implements MapDataParser
{
    protected $elementStack = array();
    protected $feedId;

    protected $document;
    protected $folders = array();
    protected $placemarks = array();
    protected $title;
    // aliases for placemark searching
    protected $aliases;

    protected $parseMode=self::PARSE_MODE_STRING;
    protected $trimWhiteSpace = true;
    
    // whitelists
    protected static $startElements=array(
        'DOCUMENT', 'FOLDER',
        'STYLE', 'STYLEMAP',
        'PLACEMARK', 'POINT', 'LINESTRING', 'LINEARRING', 'POLYGON'
        );
    protected static $endElements=array(
        'DOCUMENT', 
        'STYLE', 'STYLEMAP', 'STYLEURL',
        );
    
    public function init($args) {
        parent::init($args);
        $this->feedId = mapIdForFeedData($args);
        if(isset($args['ALIASES'])) {
            $this->aliases = $args['ALIASES'];
        }
    }

    /////// MapDataParser

    public function placemarks() {
        return $this->placemarks;
    }

    public function categories() {
        return $this->folders;
    }

    public function getProjection() {
        return null;
    }

    /////

    protected function addPlacemark(Placemark $placemark)
    {
        $placemark->addCategoryId($this->getId());
        $placemark->setId(count($this->placemarks));
        $this->placemarks[] = $placemark;
    }

    protected function addFolder(MapFolder $folder)
    {
        $folder->setParent($this);
        $this->folders[] = $folder;
    }

    public function getId()
    {
        return $this->feedId;
    }

    public function getTitle() {
        return $this->title;
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
                $parent = end($this->elementStack);
                $folder = new KMLFolder($name, $attribs);
                $this->elementStack[] = $folder;
                if ($parent instanceof KMLFolder) {
                    $parentCategory = $parent->getId();
                    $newFolderIndex = count($parent->categories());
                    $parent->addItem($folder);
                } else {
                    $parentCategory = $this->getId();
                    $newFolderIndex = count($this->folders);
                    //$newFolderIndex = $this->itemCount;
                    $this->addFolder($folder);
                }
                $folder->setId(substr(md5($parentCategory.$newFolderIndex), 0, strlen($parentCategory)-1)); // something unique
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
                $this->elementStack[] = $placemark;

                if ($parent instanceof KMLFolder) {
                    $parent->addItem($placemark);
                } else {
                    $this->addPlacemark($placemark);
                }
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
                break;
            case 'STYLE':
            case 'STYLEMAP':
                $this->styles[$element->getAttrib('ID')] = $element;
                break;
            case 'STYLEURL':
                $value = $element->value();
                if ($parent instanceof Placemark) {
                    if ($style = $this->getStyle($value)) {
                        $parent->setStyle($this->getStyle($value));
                    } else {
                        Kurogo::log(LOG_WARNING, "Style $value was not found", 'map');
                    }
                } else {
                    $parent->addElement($element);
                }
                break;
        }
    }

    public function clearInternalCache()
    {
        $this->document = null;
        $this->folders = array();
        $this->placemarks = array();
        $this->otherCategory = null;
    }

    public function parseData($content)
    {
        $this->clearInternalCache();
        $this->parseXML($content);
        $items = array_merge($this->categories(), $this->placemarks());
        return $items;
    }
}



