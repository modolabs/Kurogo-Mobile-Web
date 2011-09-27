<?php

class ImageTransformer
{
    protected $rules=array();
    
    public function __construct($params) {
        $params = is_array($params) ? $params : array();
        foreach ($params as $param=>$value) {
            switch (strtolower($param))
            {
                case 'width':
                case 'height':
                case 'min_width':
                case 'min_height':
                case 'max_width':
                case 'max_height':
                case 'aspect':
                case 'min_aspect':
                case 'max_aspect':
                    $this->rules[$param] = $value;
                    break;
            }
        }
    }
    
    public function getBoundingBox($width, $height) {

        $aspect = round($width/$height, 4);
        if (isset($this->rules['height'])) {
            if ($height != $this->rules['height']) {
                return new KurogoError(1, 'Invalid Height', sprintf("Image must be %d pixels in height (is %d x %d)",  $this->rules['height'], $width, $height));
            }
        } 

        if (isset($this->rules['width'])) {
            if ($width != $this->rules['width']) {
                return new KurogoError(1, 'Invalid width', sprintf("Image must be %d pixels in width (is %d x %d)",  $this->rules['width'], $width, $height));
            }
        }

        if (isset($this->rules['aspect'])) {
            $_aspect = round($this->rules['aspect'], 4);            
            if ( $aspect != $_aspect) {
                return new KurogoError(1, 'Invalid aspect ratio', sprintf("Image must have an aspect ration (w / h) of %f (is %f)",  $_aspect, $aspect));
            }
        }

        if (isset($this->rules['min_aspect'])) {
            $_aspect = round($this->rules['min_aspect'], 4);            
            if ( $aspect < $_aspect) {
                return new KurogoError(1, 'Invalid aspect ratio', sprintf("Image must have an aspect ration (w / h) of at least %f (is %f)",  $_aspect, $aspect));
            }
        }            

        if (isset($this->rules['max_aspect'])) {
            $_aspect = round($this->rules['max_aspect'], 4);            
            if ( $aspect > $_aspect) {
                return new KurogoError(1, 'Invalid aspect ratio', sprintf("Image must have an aspect ration (w / h) no more than %f (is %f)",  $_aspect, $aspect));
            }
        }            
        
        if (isset($this->rules['min_height'])) {
            if ($height < $this->rules['min_height']) {
                return new KurogoError(1, 'Insufficient Height', sprintf("Image must be at least %d pixels in height (is %d x %d)",  $this->rules['height'], $width, $height));
            }
        }
        
        if (isset($this->rules['min_width'])) {
            if ($width < $this->rules['min_width']) {
                return new KurogoError(1, 'Insufficient width', sprintf("Image must be at least %d pixels in width (is %d x %d)",  $this->rules['width'], $width, $height));
            }
        }

        if (isset($this->rules['max_width']) || isset($this->rules['max_height'])) {
            if (isset($this->rules['max_width'])) {
                if (isset($this->rules['max_height'])) {
                    // width & height    
                    $_aspect = $this->rules['max_width'] / $this->rules['max_height'];
                    if ($height < $this->rules['max_height'] && $width < $this->rules['max_width']) {
                        $newWidth = $width;
                        $newHeight = $height;
                    } elseif ($width < $this->rules['max_width']) {
                        $newHeight = $this->rules['max_height'];
                    } elseif ($height < $this->rules['max_height']) {
                        $newWidth = $this->rules['max_width'];
                    } else if ($aspect > $_aspect) {
                        $newWidth = $this->rules['max_width'];
                    } else {
                        $newHeight = $this->rules['max_height'];
                    }
                } else {
                    // only width
                    if ($width > $this->rules['max_width']) {
                        $newWidth = $this->rules['max_width'];
                    } else {
                        $newWidth = $width;
                        $newHeight = $height;
                    }
                }
            } else {
                // only height
                if ($height > $this->rules['max_height']) {
                    $newHeight = $this->rules['max_height'];
                } else {
                    $newHeight = $height;
                    $newWidth = $width;
                }
            }
        }
        
        if (isset($newWidth) && isset($newHeight)) {
            // already set
        } elseif (isset($newWidth)) {
            $newHeight = round($newWidth / $aspect);
        } elseif (isset($newHeight)) {
            $newWidth = round($newHeight * $aspect);
        } else {
            // probably shouldn't happen
            throw new KurogoException("Unable to determine width and height");
        }
        
        return array($newWidth, $newHeight);
    }
}
