<?php

class INIFileParser extends DataParser
{
    public function parseFile($file) {
        $data = parse_ini_file($file, true);
        return $data;
    }
    
    public function parseData($data) {
        if (function_exists('parse_ini_stringz')) {
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