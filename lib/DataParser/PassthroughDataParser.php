<?php
/**
 * @package ExternalData
 */

/**
 * A parser that simply returns the data without processing
 * @package ExternalData
 */
class PassthroughDataParser extends DataParser
{
    public function parseData($data)
    {
        return $data;
    }
}

