<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class INIFileParser extends DataParser
{
    public function parseFile($file) {
        $data = parse_ini_file($file, true);
        return $data;
    }
    
    public function parseData($data) {
        if (function_exists('parse_ini_string')) {
            $data = parse_ini_string($data, true);
        } else {
            $file = tempnam(CACHE_DIR, 'INIFileParser');
            if (file_put_contents($file, $data, LOCK_EX)===false) {
                throw new KurogoDataException("Error saving temporary INI file");
            }
            $data = parse_ini_file($file, true);
            unlink($file);
        }
        
       return $data;
    }
}
