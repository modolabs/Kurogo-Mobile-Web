<?php

class ImageLoader extends FileLoader {
    protected static function subDirectory() {
        return 'images';
    }
    
    public static function precache($url, $width, $height, $file) {
        $loaderInfo = array(
            'width' => $width,
            'height' => $height,
            'url' => $url,
            );
        return self::generateLazyURL($file, json_encode($loaderInfo), self::subDirectory());
    }
}
