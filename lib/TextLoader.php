<?php

class TextLoader extends FileLoader
{
    protected static function subDirectory() {
        return 'text';
    }
    
    public static function precache($file, $contents) {
        return self::generateURL($file, $contents, self::subDirectory());
    }
}
