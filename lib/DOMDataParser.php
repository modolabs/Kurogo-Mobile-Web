<?php

if (!class_exists('DOMDocument')) {
    die('DOMDocument Functions not available (php-xml)');
}

class DOMDataParser extends DataParser
{
    
    public function parseData($data)
    {
        $dom = new DOMDocument();
        /* there might be errors, who knows what we're getting */
        if (!@$dom->loadHTML(mb_convert_encoding($data, 'HTML-ENTITIES', $this->encoding))) {
            $dom = false;
        }
        return $dom;
    }
    


}