<?php

includePackage('readability');
/**
 * KurogoReader 
 * 
 * @uses Readability
 * @package 
 * @license 
 */
class KurogoReader {
    /**
     * baseurl 
     * the base url for this article
     * with protocol prefix, without slash suffix
     *
     * @var mixed
     * @access private
     */
    private $baseurl;

    /**
     * tidyAvailable 
     * flag to mark if tidy function available
     * 
     * @var mixed
     * @access private
     */
    private $tidyAvailable;

    /**
     * cache vars
     * 
     * @var mixed
     * @access private
     */
    private $cache;
    private $cacheFileName;
    private $cacheFolder;
    private $cacheLifetime;

    /**
     * article vars
     * 
     * @var mixed
     * @access private
     */
    private $title;
    private $content;
    private $charset;

    public function __construct($url) {
        /**
         * initail DiskCache
         */
        $this->cacheFolder = "KurogoReader";
        $this->cacheLifetime = 1800;
        $this->cacheFileName = md5($url);

        if($this->isFresh()) {
            $this->getCacheData();
        }else {
            $this->readFromServer($url);
            
        }
    }

    private function readFromServer($url) {
        // check tidy function available
        if(function_exists('tidy_parse_string')) {
            $this->tidyAvailable = true;
        }

        $urlArray = parse_url($url);
        $this->baseUrl = $urlArray['scheme'] . "://" . $urlArray['host'];
        $html = $this->fetchContent($url);
        $html = $this->tidyClean($html);
        $readability = new Readability($html, $url);
        $readability->init();
        $this->title = $readability->getTitle()->textContent;
        $content = $readability->getContent()->innerHTML;
        /**
         * still need one more tidy clean, otherwise domdocument will not work properly
         */
        $content = $this->tidyClean($content);
        /**
         * use domdocument to fix relative urls
         */
        $this->content = $this->fixRelativeUrls($content);
        $article = array(
            'title' => $this->title,
            'content' => $this->content
        );
        $this->writeCache(serialize($article));
    }

    private function cacheFolder() {
        return CACHE_DIR . "/" . $this->cacheFolder;
    }

    private function getCache() {
        if ($this->cache === null) {
            $this->cache = new DiskCache($this->cacheFolder(), $this->cacheLifetime, true);
            $this->cache->setSuffix('.cache');
            $this->cache->preserveFormat();
        }
        return $this->cache;
    }

    private function getCacheData() {
        $cache = $this->getCache();
        $article = $cache->read($this->cacheFileName);
        $article = unserialize($article);
        $this->title = $article['title'];
        $this->content = $article['content'];
    }

    private function writeCache($data) {
        $cache = $this->getCache();
        $cache->write($data, $this->cacheFileName);
    }

    private function isFresh() {
        $cache = $this->getCache();
        return $cache->isFresh($this->cacheFileName);
    }

    /**
     * findCharset 
     * find charset from meta content
     * 
     * @param string $node 
     * @access private
     * @return void
     */
    private function findCharset($content) {
        preg_match("/<meta[^>]*?charset=([a-z|A-Z|0-9]*[\\-]*[0-9]*[\\-]*[0-9]*)[\\s|\\S]*/i", $content, $matches);
        if(isset($matches[1])) {
            return $matches[1];
        }else {
            return false;
        }
    }

    /**
     * parseHTTPHeader 
     * parses the HTTP headers from fetchContent
     * 
     * @param string $header
     * @access private
     * @return array
     */
    protected function parseHTTPHeader($header) {
        if (preg_match("/(.*?):\s*(.*)/", $header, $bits)) {
            return array(
                trim($bits[1]),
                trim($bits[2])
            );
        }
    }

    /**
     * fetchContent 
     * fetch content from specified url and convert source encoding if possible
     * 
     * @param mixed $url 
     * @access private
     * @return void
     */
    private function fetchContent($url) {
        $content = file_get_contents($url);
        
        //KGO-770 -  rewrite baseURL if there is a redirect
        foreach ($http_response_header as $http_header) {
            list($header, $value) = $this->parseHTTPHeader($http_header);
            if ($header=='Location') {
                $urlArray = parse_url($value);
                $this->baseUrl = $urlArray['scheme'] . "://" . $urlArray['host'];
            }
        }

        /**
         * if there is tidy support, then detect target source code from meta content
         */
        if($this->tidyAvailable) {
            $tidy = tidy_parse_string($content, array(), 'utf8');
            $head = $tidy->head();
            $charset = $this->findCharset($head->value);
            if(!empty($charset) && $charset != "utf-8") {
                $content = mb_convert_encoding($content, "utf-8", $charset);
            }
        }
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
        if(!$this->tidyAvailable) {
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
        return $tidy->value;
    }

    /**
     * getTitle 
     * main portal to fetch article title
     * 
     * @access public
     * @return void
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * getContent 
     * main portal to fetch article content
     * 
     * @access public
     * @return void
     */
    public function getContent() {
        return $this->content;
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

    /**
     * setDebug 
     * enable Readability debug information
     * 
     * @param boolean $debug 
     * @access public
     * @return void
     */
    public function setDebug($debug) {
        $this->readability->debug = (boolean) $debug;
    }

    /**
     * setFootnotes 
     * enable Readability footnotes
     * 
     * @param boolean $b 
     * @access public
     * @return void
     */
    public function setFootnotes($b) {
        $this->readability->convertLinksToFootnotes = (boolean) $b;
    }
}
