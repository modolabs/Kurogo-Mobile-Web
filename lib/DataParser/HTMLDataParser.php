<?php

includePackage('readability');

class HTMLDataParser extends DataParser
{
    /**
     * baseurl 
     * the base url for this article
     * with protocol prefix, without slash suffix
     *
     * @var mixed
     * @access private
     */
    private $baseUrl;

    /**
     * tidyEnabled
     * flag to mark if tidy function available
     * 
     * @var mixed
     * @access private
     */
    private $tidyEnabled;

    private $readabilityEnabled;

    /**
     * article vars
     * 
     * @var mixed
     * @access private
     */
    private $title;
    private $content;
    private $charset;

    public function init($args)
    {
        // if tidy is available, and not explicitly disabled, use tidy
        if(
            function_exists('tidy_parse_string') &&
            !(
                isset($args['TIDY']) && 
                !$args['TIDY']
            )
        ){
            $this->tidyEnabled = true;
        }
        if(isset($args['READABILITY']) && $args['READABILITY'])
        {
            $this->readabilityEnabled = true;
        }
        if(isset($args['BASE_URL']))
        {
            $this->baseUrl = $args['BASE_URL'];
        }
        if(isset($args['START_DELIM']))
        {
           $this->startDelim = $args['START_DELIM'];
        }
        if(isset($args['END_DELIM']))
        {
           $this->endDelim = $args['END_DELIM'];
        }
    }
    public function parseData($data)
    {
        $html = $this->tidyClean($data);
        $html = $this->readabilityClean($html);
        $html = $this->tidyClean($html);
        
        $html = $this->getDelimContent($html);

        $this->content = $this->fixRelativeUrls($html);

        $this->writeCache();
        return $this->content;
    }

    protected function getDelimContent($html)
    {
        if(empty($this->startDelim) && empty($this->endDelim))
        {
            return $html;
        }
        
        $searchPattern = '/'.preg_quote($this->startDelim, '/').'(.*?)'.preg_quote($this->endDelim, '/').'/s';
        
        $matches = array();
        preg_match_all($searchPattern, $html, $matches);
        $content = implode('', $matches[1]);

        return $content;
    }
   
    /**
     * tidyClean 
     * clean html source code
     * 
     * @param mixed $html 
     * @param array $options 
     * @access private
     * @return void
     */
    private function tidyClean($html, $options = array()) {
        if(!$this->tidyEnabled) {
            return $html;
        }
        if(empty($options)) {
            $options = array(
                'indent' => true,
                'show-body-only' => true
            );
        }
        $tidy = tidy_parse_string($html, $options, "utf8");
        $tidy->cleanRepair();
        if($this->needsTitle())
	{
		$title = $this->getTidyChildValue($tidy->head(), "title");
	        $this->setTitle($title);
	}
        $body = $this->getTidyChildValue($tidy->body()->getParent(), "body");
        return $body;
    }
    
    private function getTidyChildValue($parent, $tagName)
    {
        foreach($parent->child as $child)
        {
            if($child->name == $tagName)
            {
                $value = "";
                foreach($child->child as $subchild)
                {
                    $value .= $subchild->value;
                }
            }
        }
        return $value;
    }

    private function readabilityClean($html)
    {
        if(!$this->readabilityEnabled)
        {
            return $html;
        }
        $readability = new Readability($html);
        $readability->init();
        $this->setTitle($readability->getTitle()->textContent);
        $content = $readability->getContent()->innerHTML;
        
        return $content;
    }

    private function setTitle($title)
    {
        $this->title = $title;
    }

    private function needsTitle()
    {
        if(empty($this->title))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    private function fixRelativeUrls($content) {
        /**
         * if there is no libxml support, then we can't fix the relative urls
         */
        if(!class_exists("DOMDocument")) {
            return $content;
        }
        $doc = new DOMDocument();
        /**
         * convert utf-8 to html-entities, otherwise the none acsii codes will get messed up
         * like chinese and 2 bytes symbols
         */
        $content = mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8");
        /**
         * there will be some waring disable them
         */
        @$doc->loadHTML($content);

        /**
         * fix <img /> relative urls
         */
        $imgNodes = $doc->getElementsByTagName('img');
        for($i = 0;$i < $imgNodes->length;$i ++) {
            $imgSrc = $imgNodes->item($i)->getAttribute("src");
            $urlArray = parse_url($imgSrc);
            if(!isset($urlArray['host'])) {
                if(strpos($imgSrc, '/') !== 0) {
                    $imgSrc = "/" . $imgSrc;
                }
                $imgNodes->item($i)->setAttribute("src", $this->baseUrl . $imgSrc);
            }
        }
        /**
         * fix <a></a> tag relative urls
         */
        $hrefNodes = $doc->getElementsByTagName('a');
        for($i = 0;$i < $hrefNodes->length;$i ++) {
            $href = $hrefNodes->item($i)->getAttribute("href");
            $urlArray = parse_url($href);
            if(!isset($urlArray['host'])) {
                if(strpos($href, '/') !== 0) {
                    $href = "/" . $href;
                }
                $hrefNodes->item($i)->setAttribute("href", $this->baseUrl . $href);
            }
        }

        $html = $doc->saveHTML();

        // saved DOM document always has header. remove it
        $body = preg_replace(
            array(
                "/^\<\!DOCTYPE.*?<html><body>/si",
                "!</body></html>$!si"
            ), "", $html);
        return $body;
    }

    protected function writeCache()
    {
        //TODO: Caching Implementation
        return;
    }
}
