<?php

if (!function_exists('xml_parser_create')) {
    die('XML Parser commands not available.');
}

require_once(LIB_DIR . '/RSS.php');

class RSSDataParser extends DataParser
{
    protected $root;
    protected $channel;
    protected $elementStack = array();
    protected $channelClass='RSSChannel';
    protected $itemClass='RSSItem';
    protected $imageClass='RSSImage';
    protected $enclosureClass='RSSEnclosure';
    protected $items=array();
    protected $data='';
    
    public function items()
    {
        return $this->items;
    }

    public function init($args)
    {
        if (isset($args['CHANNEL_CLASS'])) {
            $this->setChannelClass($args['CHANNEL_CLASS']);
        }

        if (isset($args['ITEM_CLASS'])) {
            $this->setItemClass($args['ITEM_CLASS']);
        }

        if (isset($args['IMAGE_CLASS'])) {
            $this->setImageClass($args['IMAGE_CLASS']);
        }

        if (isset($args['ENCLOSURE_CLASS'])) {
            $this->setEnclosureClass($args['ENCLOSURE_CLASS']);
        }
    }

    protected function startElement($xml_parser, $name, $attribs)
    {
        $this->data = '';
        switch ($name)
        {
            case 'RSS':
                break;
            case 'RDF:RDF':
                break;
            case 'CHANNEL':
            case 'FEED': //for atom feeds
                $this->elementStack[] = new $this->channelClass($attribs);
                break;
            case 'ITEM':
            case 'ENTRY': //for atom feeds
                $this->elementStack[] = new $this->itemClass($attribs);
                break;
            case 'ENCLOSURE':
            case 'MEDIA:CONTENT':
                $this->elementStack[] = new $this->enclosureClass($attribs);
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
    	if ($channelClass) {
    		if (!class_exists($channelClass)) {
    			throw new Exception("Cannot load class $channelClass");
    		}
			$this->channelClass = $channelClass;
		}
    }

    public function setItemClass($itemClass)
    {
    	if ($itemClass) {
    		if (!class_exists($itemClass)) {
    			throw new Exception("Cannot load class $itemClass");
    		}
			$this->itemClass = $itemClass;
		}
    }

    public function setEnclosureClass($enclosureClass)
    {
    	if ($enclosureClass) {
    		if (!class_exists($enclosureClass)) {
    			throw new Exception("Cannot load class $enclosureClass");
    		}
			$this->enclosureClass = $enclosureClass;
		}
    }

    public function setImageClass($imageClass)
    {
    	if ($imageClass) {
    		if (!class_exists($imageClass)) {
    			throw new Exception("Cannot load class $imageClass");
    		}
			$this->imageClass = $imageClass;
		}
    }
    
    protected function shouldStripTags($element)
    {
        $strip_tags = true;
        switch ($element->name())
        {
            case 'CONTENT:ENCODED':
            case 'CONTENT':
            case 'BODY':
                $strip_tags = false;
                break;
        }
        
        return $strip_tags;
    }

    protected function endElement($xml_parser, $name)
    {
        if ($element = array_pop($this->elementStack)) {

            $element->setValue($this->data, $this->shouldStripTags($element));
            $parent = end($this->elementStack);
            switch ($name)
            {
                case 'FEED': //for atom feeds
                case 'CHANNEL':
                    $this->channel = $element;
                    break;
                case 'ITEM':
                case 'ENTRY': //for atom feeds
                    $this->items[] = $element;
                    break;
                case 'DESCRIPTION':
                    /* dupe description to content if content is not defined */
                    if (is_a($parent, 'RSSItem') && !$parent->getContent()) {
                        $contentElement = clone($element);
                        $contentElement->setName('CONTENT');
                        $contentElement->setValue($this->data, $this->shouldStripTags($contentElement));
                        $parent->addElement($contentElement);
                    }
                default:
                    if ($parent) {
                        $parent->addElement($element);
                    } else {
                        $this->root = $element;
                    }
            }
        }
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
            throw new Exception(sprintf("XML error: %s at line %d",
                        xml_error_string(xml_get_error_code($xml_parser)),
                        xml_get_current_line_number($xml_parser)));
        }
        xml_parser_free($xml_parser);
        return $this->items;
       }
}

