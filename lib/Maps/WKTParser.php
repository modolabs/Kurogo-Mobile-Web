<?php

/* very partial implementation of 
 * http://www.opengeospatial.org/docs/01-009.pdf
 * Chapter 7
 */

class WKTParser
{

    public static function parseWKTString($string) {
        $chars = str_split($string);
        $keywordStack = array();
        $argStack = array();
        $currentArg = '';
        $inQuotes = false;
        $result = null;
        foreach ($chars as $c) {
            switch ($c) {
                case '[':
                    $keywordStack[] = $currentArg;
                    $argStack[] = array();
                    $currentArg = '';
                    break;
                case '"':
                    $inQuotes = !$inQuotes;
                    if ($currentArg) {
                        $argStack[count($argStack) - 1]['name'] = $currentArg;
                        $currentArg = '';
                    }
                    break;
                case ']':
                    $keyword = array_pop($keywordStack);
                    $currentArgs = array_pop($argStack);

                    if ($currentArg) {
                        $currentArgs[count($currentArgs)] = $currentArg;
                        $currentArg = '';
                    }

                    if ($keyword) {
                        $result = self::parseWKTKeyword($keyword, $currentArgs);
                        if ($argStack) {
                            $parentArgs = end($argStack);
                            if (isset($parentArgs[$keyword])) {
                                $parentArgs[$keyword] = array_merge(
                                    $parentArgs[$keyword], $result);
                            } else {
                                $parentArgs[$keyword] = $result;
                            }
                            $argStack[count($argStack) - 1] = $parentArgs;
                        }
                    }
                    break;
                case ',':
                    if ($currentArg) {
                        $currentArgs = end($argStack);
                        $currentArgs[count($currentArgs)] = $currentArg;
                        $argStack[count($argStack) - 1] = $currentArgs;
                        $currentArg = '';
                    }
                    break;
                default:
                    $currentArg .= $c;
                    break;
            }
        }

        return array($keyword => $result);
    }

    private static function parseWKTKeyword($keyword, $args) {
        $result = $args;

        switch ($keyword) {
            case 'PARAMETER':
                $result[$args['name']] = floatval($args[1]);
                break;

            case 'SPHEROID':
                $result['semiMajorAxis'] = floatval($args[1]);
                $result['inverseFlattening'] = floatval($args[2]);

            case 'PRIMEM':
                $result['longitude'] = floatval($args[1]);
                break;

            case 'UNIT':
                $result['unitsPerMeter'] = floatval($args[1]);
                break;
            
            case 'AUTHORITY':
                $result['code'] = $args[1];
                break;
        }

        return $result;
    }


}

