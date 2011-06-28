<?php

class MapDevice
{
    protected $pagetype;
    protected $platform;
    
    public function __construct($pagetype, $platform) {
        $this->pagetype = $pagetype;
        $this->platform = $platform;
    }
    
    public function pageSupportsDynamicMap() {
        return ($this->pagetype == 'compliant' ||
                $this->pagetype == 'tablet')
            && $this->platform != 'blackberry'
            && $this->platform != 'bbplus'
            && $this->platform != 'webos';
    }

    public function staticMapImageDimensions() {
        switch ($this->pagetype) {
            case 'tablet':
                $imageWidth = 600; $imageHeight = 350;
                break;
            case 'compliant':
                if ($this->platform == 'bbplus') {
                    $imageWidth = 410; $imageHeight = 260;
                } else {
                    $imageWidth = 290; $imageHeight = 290;
                }
                break;
            case 'touch':
            case 'basic':
                $imageWidth = 200; $imageHeight = 200;
                break;
        }
        return array($imageWidth, $imageHeight);
    }
    
    public function dynamicMapImageDimensions() {
        $imageWidth = '98%';
        switch ($this->pagetype) {
            case 'tablet':
                $imageHeight = 350;
                break;
            case 'compliant':
            default:
                if ($this->platform == 'bbplus') {
                    $imageHeight = 260;
                } else {
                    $imageHeight = 290;
                }
                break;
        }
        return array($imageWidth, $imageHeight);
    }
    
    public function fullscreenMapImageDimensions() {
        $imageWidth = '100%';
        $imageHeight = '100%';
        return array($imageWidth, $imageHeight);
    }
}
