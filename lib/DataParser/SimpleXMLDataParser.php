<?php

class SimpleXMLDataParser extends DataParser {
    public function parseData($data) {
        $xml = new SimpleXMLElement($data, LIBXML_NOCDATA);
        return $this->toArray($xml);
    }

    protected function toArray($xml) {
        $array = json_decode(json_encode($xml), TRUE);
        
        foreach ( array_slice($array, 0) as $key => $value ) {
            if ( empty($value) ) $array[$key] = NULL;
            elseif ( is_array($value) ) $array[$key] = $this->toArray($value);
        }

        return $array;
    }    
}
