<?php

class JSONDataParser extends DataParser
{
    public function parseData($data)
    {
        return json_decode($data);
    }
}

