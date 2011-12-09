<?php

includePackage('DataModel');
class ContentDataModel extends ItemListDataModel
{
    protected $cacheFolder = 'Content';

    public function getContentById($id)
    {
        $content = '';
        if ( ($dom = $this->getData()) && ($dom instanceOf DOMDocument)) {
            if ($element = $dom->getElementById($id)) {
                $content = $dom->saveXML($element);
            }
            
        }
        
        return $content;
    }

    public function getContentByTag($tag) {
        $content = '';
        if ( ($dom = $this->getData()) && ($dom instanceOf DOMDocument)) {
            $elements = $dom->getElementsByTagName($tag);
            for ($i=0; $i < $elements->length; $i++) {
                $element = $elements->item($i);
                $content .= $dom->saveXML($element);
            }
            if (strtolower($tag)=='body') {
                //strip body tag
                $content = preg_replace("#</?body.*?>#", "", $content);
            }
        }
        
        return $content;
    }
    
    protected function getData() {
        return $this->retriever->getData($response);
    }

    public function getContent()
    {
        $content = '';
        if ( ($dom = $this->getData()) && ($dom instanceOf DOMDocument)) {
            if ($element = $dom->getElementsByTagName('body')->item(0)) {
                $content = $dom->saveXML($element);
                $content = preg_replace("#</?body.*?>#", "", $content);
            } else {
                $content = $this->getResponse();
            }
        }
        
        return $content;
    }
    
    
}