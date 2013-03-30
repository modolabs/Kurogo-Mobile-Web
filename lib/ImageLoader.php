<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class ImageLoader extends FileLoader {
    protected static function subDirectory() {
        return 'images';
    }
    
    protected static function processImageFile($inputFile, $loaderInfo) {
        // if no gd support, do nothing
        if (!function_exists('gd_info')) {
            return false;
        }
        // if there is only url and processMethod, do nothing
        if(count($loaderInfo) == 2) {
            return false;
        }
        // make inputFile as temprary file
        $outputFile = $inputFile;
        $processor = new ImageProcessor($inputFile);
        $transformer = new ImageTransformer($loaderInfo);

        //maintain original image type 
        $imageType = null;
        $processor->transform($transformer, $imageType, $outputFile);
    }

    // retained for compatibility
    public static function precache($url, $width=null, $height=null, $file=null) {
        $loaderInfo = array(
            'width' => $width,
            'height' => $height,
            'file'=>$file
        );
        
        return self::cacheImage($url, $loaderInfo);
    }

    public static function cacheImage($url, $options) {
        $loaderInfo = array(
            'url' => $url,
            'processMethod'=>array(__CLASS__, 'processImageFile')
        );
        foreach($options as $key => $option) {
            switch($key) {
                case 'width':
                case 'height':
                case 'max_width':
                case 'max_height':
                case 'crop':
                case 'rgb':
                    if($option) {
                        $loaderInfo[$key] = $option;
                    }
                    break;
            }
        }

        $file = isset($options['file']) ? $options['file'] : '';
        if (!$file) {
            $urlparts = parse_url($url);
            // if there is no path and query, it should not be an image
            if(!isset($urlparts['path']) && !isset($urlparts['query'])) {
                return false;
            }
            // use $urlparts['path'] to find extension only if path and query exists
            if (isset($urlparts['query']) && isset($urlparts['path'])) {
                $extension = pathinfo($urlparts['path'], PATHINFO_EXTENSION);
            } else {
                $extension = pathinfo($url, PATHINFO_EXTENSION);
            }

            // if no extension found, do not use extension
            $file = md5($url.json_encode($loaderInfo));
            if ($extension) {
                $file .= '.'.$extension;
            }
        }    
        return self::generateLazyURL($file, json_encode($loaderInfo), self::subDirectory());
    }
}
