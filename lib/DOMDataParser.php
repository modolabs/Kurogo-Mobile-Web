<?php

if (!class_exists('DOMDocument')) {
    die('DOMDocument Functions not available (php-xml)');
}

if (!function_exists('mb_convert_encoding')) {
    die('Multibyte String Functions not available (mbstring)');
}

class DOMDataParser extends DataParser
{
    protected $baseUrl = '';
    protected $relativeUrl = '';
    
    public function parseData($data)
    {
        $dom = new DOMDocument();
        /* there might be errors, who knows what we're getting */
        if (!@$dom->loadHTML(mb_convert_encoding($data, 'HTML-ENTITIES', $this->encoding))) {
            $dom = false;
        } else {
            $dom = $this->rewriteAbsoluteUrls($dom);
        }
        return $dom;
    }
    
    public function init($args) {
        parent::init($args);

        if (isset($args['BASE_URL']) && $args['BASE_URL']) {
            $urlArray = parse_url($args['BASE_URL']);
            $this->baseUrl = $urlArray['scheme'] . "://" . $urlArray['host'];
            $this->relativeUrl = dirname($args['BASE_URL']);
        }
    }

    //format the tag url
    protected function formatUrl($url) {
        $urlArray = parse_url($url);
        if (!isset($urlArray['host'])) {
            $url = strpos($url, '/') != 0 ? $this->relativeUrl . "/" . $url : $this->baseUrl . $url;
        }
        return $url;
    }
    
    protected function rewriteAbsoluteUrls(DOMDocument $dom) {
        
        //fix the image url
        $imgElements = $dom->getElementsByTagName('img');
        for($i = 0;$i < $imgElements->length;$i ++) {
            $imgSrc = $imgElements->item($i)->getAttribute("src");
            $imgElements->item($i)->setAttribute('src', $this->formatUrl($imgSrc));
        }

        /**
         * fix <a></a> tag relative urls
         */
        $hrefElements = $dom->getElementsByTagName('a');
        for($i = 0;$i < $hrefElements->length;$i ++) {
            $href = $hrefElements->item($i)->getAttribute("href");
            $hrefElements->item($i)->setAttribute("href", $this->formatUrl($href));
        }

        return $dom;
    }

}