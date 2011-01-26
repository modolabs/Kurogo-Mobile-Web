<?php
/**
 * @package ExternalData
 */

/**
 * A parser that simply returns the data without processing
 * @package ExternalData
 */
class PassthroughDataParser
{
    public function parseData($data)
    {
        return $data;
    }
}

