<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
