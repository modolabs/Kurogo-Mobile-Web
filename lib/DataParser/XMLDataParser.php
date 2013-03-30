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

abstract class XMLDataParser extends DataParser
{
    protected $root;
    protected $elementStack = array();
    protected $data='';
    protected $items = array();
    protected $trimWhiteSpace = false;
    
    abstract protected function shouldStripTags($element);
    
    protected function shouldHTMLDecodeCDATA($element)
    {
        // Don't force subclasses to implement this.  It exists for buggy feeds
        // which have certain elements escaped with both CDATA and html entities.
        // Implemented by the RSS parser because RSS feeds are where this commonly
        // happens.
        return false;
    }

    abstract protected function shouldHandleStartElement($name);
    abstract protected function handleStartElement($name, $attribs);

    // added after 1.6.1. XMLDataParser previously expected subclasses
    // to append custom elements to $this->elementStack[] in handleStartElement
    // but this is inconvenient for classes that don't want to generate
    // XMLElement subclasses for tags that just need to be processed for
    // attribute values.
    // returning false here will make XMLDataParser behave as before,
    // returning true will allow subclasses to optionally return an XMLElement
    // subclass if it needs to be subclassed, or nothing otherwise.
    protected function shouldAddDefaultElement() {
        return false;
    }

    protected function startElement($xml_parser, $name, $attribs)
    {
        if (!is_null($this->data) && $this->data !== '' && count($this->elementStack) > 0) {
            $parent = end($this->elementStack);
            $parent->setValue($parent->value().$this->data);
        }
        $this->data = '';
        if ($this->shouldHandleStartElement($name)) {

            $element = $this->handleStartElement($name, $attribs);
            if ($element) {
                $this->elementStack[] = $element;
            } elseif ($this->shouldAddDefaultElement()) {
                $this->elementStack[] = new XMLElement($name, $attribs, $this->getEncoding());
            }

        } else {
            $this->elementStack[] = new XMLElement($name, $attribs, $this->getEncoding());
        }
    }

    abstract protected function shouldHandleEndElement($name);
    abstract protected function handleEndElement($name, $element, $parent);

    protected function endElement($xml_parser, $name)
    {
        if ($element = array_pop($this->elementStack)) {

            if (!is_null($this->data) && $this->data !== '') {
                if (!$element instanceOf XMLElement) {
                    throw new KurogoDataException("$name is not an XMLElement");
                }
                $element->shouldStripTags($this->shouldStripTags($element));
                $element->shouldHTMLDecodeCDATA($this->shouldHTMLDecodeCDATA($element));
                $element->setValue($element->value().$this->data);
                $this->data = '';
            }
            $parent = end($this->elementStack);

            if ($this->shouldHandleEndElement($name)) {
                $this->handleEndElement($name, $element, $parent);
            } else if ($parent) {
                if (!$parent instanceOf XMLElement) {
                    throw new KurogoDataException("Parent of $name is not an XMLElement");
                }
                $parent->addElement($element);
            } else {
                $this->root = $element;
            }
        }
    }

    protected function characterData($xml_parser, $data)
    {
        $data = $this->trimWhiteSpace ? trim($data) : $data;
        $this->data .= $data;
    }
    
    public function clearInternalCache() {
        parent::clearInternalCache();
        $this->root = null;
        $this->elementStack = array();
        $this->data='';
        $this->items = array();
    }
    
    protected function parseXML($xml) {
        $xml_parser = xml_parser_create();
        // use case-folding so we are sure to find the tag in $map_array
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
        xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, true);

        $this->setEncoding(xml_parser_get_option($xml_parser, XML_OPTION_TARGET_ENCODING));
        
        xml_set_element_handler($xml_parser, array($this,"startElement"), array($this,"endElement"));
        xml_set_character_data_handler($xml_parser, array($this,"characterData"));
        
        if (!xml_parse($xml_parser, $xml)) {
            throw new KurogoDataException(sprintf("XML error: %s at line %d",
                        xml_error_string(xml_get_error_code($xml_parser)),
                        xml_get_current_line_number($xml_parser)));
        }
        xml_parser_free($xml_parser);
    }
    
    public function parseData($contents) {
        $this->clearInternalCache();
        $this->parseXML($contents);
        if (is_null($this->totalItems)) {
            $this->setTotalItems(count($this->items));
        }
        return $this->items;
    }
}

