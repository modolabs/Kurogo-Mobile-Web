<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
    
    protected function getAllTagLists(DOMNodeList $nodes) {
        $tagLists = array();

        foreach ($nodes as $node) {
            $nodeName = $node->nodeName;
            if (substr($nodeName, 0, 1) != '#') {
                $tagLists[] = $nodeName;
            }
            
            if ($node->childNodes) {
                $tagLists = array_merge($tagLists, $this->getAllTagLists($node->childNodes));
            }
        }

        return $tagLists;
    }
    
    public function getContentByClass($class) {
        $content = '';
        if ( ($dom = $this->getData()) && ($dom instanceOf DOMDocument)) {
            
            $root = $dom->documentElement; //root
            $tagLists = array();
            
            //parsed all elements tag
            if ($root->childNodes) {
                $tagLists = $this->getAllTagLists($root->childNodes);
            }
            
            //find match the class element
            if ($tagLists) {
                $tagLists = array_unique($tagLists);
                
                //cycle through all elements
                foreach ($tagLists as $tag) {
                    $elements = $dom->getElementsByTagName($tag);
                    for ($i=0; $i < $elements->length; $i++) {
                        $element = $elements->item($i);
                        //find the elements that contain the class name specified in HTML_CLASS
                        if ($element->hasAttribute('class')) {
                            if (stripos($element->getAttribute('class'), $class)!==FALSE) {
                                $content .= $dom->saveXML($element);
                            }
                        }
                    }
                }
            }
            //strip body tag
            $content = preg_replace("#</?body.*?>#", "", $content);
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
