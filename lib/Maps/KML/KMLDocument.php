<?php

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
