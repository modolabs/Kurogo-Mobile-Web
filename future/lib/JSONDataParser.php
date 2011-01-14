<?php

class JSONDataParser extends DataParser
{
    protected $assoc=true;
    public function parseData($data)
    {
        return json_decode($data, $this->assoc);
    }
}

