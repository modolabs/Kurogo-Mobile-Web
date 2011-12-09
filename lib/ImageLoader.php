<?php

class ImageLoader extends FileLoader {
    protected static function subDirectory() {
        return 'images';
    }
    
    protected static function processImageFile($path, $loaderInfo) {
        // do nothing by default
    }

    public static function precache($url, $width=null, $height=null, $file=null) {
        $loaderInfo = array(
            'width' => $width,
            'height' => $height,
            'url' => $url,
            'processMethod'=>array(__CLASS__, 'processImageFile')
            );

        if (!$file) {
            $extension = pathinfo($url, PATHINFO_EXTENSION);
            $file = md5($url) . '.'. $extension;
        }    

        return self::generateLazyURL($file, json_encode($loaderInfo), self::subDirectory());
    }
}
