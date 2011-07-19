<?php

// http://schemas.opengis.net/kml/2.2.0/ogckml22.xsd
// http://portal.opengeospatial.org/files/?artifact_id=27810

includePackage('Maps/KML');

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
                    $parentCategory = $parent->getId();
                    $newFolderIndex = count($parent->getChildCategories());
                    //$categoryPath = $parent->getCategory();
                //} elseif ($parent instanceof KMLDocument) { // child of root element
                //    $newFolderIndex = count($this->items);
                    //$categoryPath = $this->category;
                } else {
                    $parentCategory = $this->dataController->getCategoryId();
                    $newFolderIndex = count($this->items);
                    //$categoryPath = $this->category;
                }
                //$categoryPath[] = $newFolderIndex;
                $folder->setId(substr(md5($parentCategory.$newFolderIndex), 0, strlen($parentCategory)-1)); // something unique
              //  $folder->setId($newFolderIndex);
                //$folder->setCategory($categoryPath);
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
                if ($parent instanceof KMLFolder) {
                    $placemark->addCategoryId($parent->getId());
                } else {
                    $placemark->addCategoryId($this->dataController->getCategoryId());
                }
                //if (!($parent instanceof KMLFolder)) { // child of root element
                //    $placemark->setCategory($this->category);
                //}
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
                $element->setId(count($this->items));
                if ($parent instanceof KMLFolder) {
                    $parent->addItem($element);
                } else {
                    $this->items[] = $element;
                }
                $element->setId(count($this->features));
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



