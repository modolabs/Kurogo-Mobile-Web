<?php

if (!function_exists('xml_parser_create')) {
    die('XML Parser commands not available.');
}

abstract class XMLDataParser extends DataParser
{
    protected $root;
    protected $elementStack = array();
    protected $data='';
    protected $items = array();
    
    abstract protected function shouldStripTags($element);

    abstract protected function shouldHandleStartElement($name);
    abstract protected function handleStartElement($name, $attribs);

    protected function startElement($xml_parser, $name, $attribs)
    {
        $this->data = '';
        if ($this->shouldHandleStartElement($name)) {
            $this->handleStartElement($name, $attribs);
        } else {
            $this->elementStack[] = new XMLElement($name, $attribs, $this->getEncoding());
        }
    }

    abstract protected function shouldHandleEndElement($name);
    abstract protected function handleEndElement($name, $element, $parent);

    protected function endElement($xml_parser, $name)
    {
        if ($element = array_pop($this->elementStack)) {

            $element->setValue($this->data, $this->shouldStripTags($element));
            $parent = end($this->elementStack);

            if ($this->shouldHandleEndElement($name)) {
                $this->handleEndElement($name, $element, $parent);
            } else if ($parent) {
                $parent->addElement($element);
            } else {
                $this->root = $element;
            }
        }
    }

    protected function characterData($xml_parser, $data)
    {
        $this->data .= $data;
    }
    
    protected function clearInternalCache() {
        $this->root = null;
        $this->elementStack = array();
        $this->data='';
        $this->items = array();
    }
    
    public function parseData($contents) {
        $this->clearInternalCache();
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
        $this->setTotalItems(count($this->items));
        return $this->items;
    }
}

