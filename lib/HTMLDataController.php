<?php

class HTMLDataController extends DataController
{
    protected $DEFAULT_PARSER_CLASS='DOMDataParser';
    public function getItem($id)
    {
        return $this->getContentById($id);
    }

    public function getContentById($id)
    {
        $content = '';
        if ($dom = $this->getParsedData()) {
            if ($element = $dom->getElementById($id)) {
                $content = $dom->saveXML($element);
            }
            
        }
        
        return $content;
    }

    public function getContent()
    {
        $content = '';
        if ($dom = $this->getParsedData()) {
            if ($element = $dom->getElementsByTagName('body')->item(0)) {
                $content = $dom->saveXML($element);
            }
            
        }
        
        return $content;
    }

    public static function factory($args=null)
    {
        $args['CONTROLLER_CLASS'] = isset($args['CONTROLLER_CLASS']) ? $args['CONTROLLER_CLASS'] : __CLASS__;
        $controller = parent::factory($args);
        
        return $controller;
    }

}
