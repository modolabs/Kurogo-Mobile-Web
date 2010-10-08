<?php

require_once(LIB_DIR . '/RSS.php');

class RSSDataParser extends DataParser
{
    protected $root;
    protected $elementStack = array();
    protected $channelClass='RSSChannel';
    protected $itemClass='RSSItem';
    protected $imageClass='RSSImage';

    protected function startElement($xml_parser, $name, $attribs)
    {
        switch ($name)
        {
            case 'RSS':
                break;
            case 'CHANNEL':
                $this->elementStack[] = new $this->channelClass($attribs);
                break;
            case 'ITEM':
                $this->elementStack[] = new $this->itemClass($attribs);
                break;
            case 'IMAGE':
                $this->elementStack[] = new $this->imageClass($attribs);
                break;
            default:
                $this->elementStack[] = new RSSElement($name, $attribs);
        }
    }

    public function setChannelClass($channelClass)
    {
        $this->channelClass = $channelClass;
    }

    public function setItemClass($itemClass)
    {
        $this->itemClass = $itemClass;
    }

    public function setImageClass($imageClass)
    {
        $this->imageClass = $imageClass;
    }

    protected function endElement($xml_parser, $name)
    {
        if ($element = array_pop($this->elementStack)) {
            if ($parent = end($this->elementStack)) {
                $parent->addElement($element);
            } else {
                $this->root = $element;
            }
        }
    }

    protected function characterData($xml_parser, $data)
    {
        $data = trim($data);
        if ($data) {
            if ($element = end($this->elementStack)) {
                $element->appendValue($data);
            }
        }
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
            throw new Exception(sprintf("XML error: %s at line %d",
                        xml_error_string(xml_get_error_code($xml_parser)),
                        xml_get_current_line_number($xml_parser)));
        }
        xml_parser_free($xml_parser);
        return $this->root;
       }
}

