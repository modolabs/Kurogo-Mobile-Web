<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class MapDevice
{
    protected $pagetype;
    protected $platform;
    
    public function __construct($pagetype, $platform) {
        $this->pagetype = $pagetype;
        $this->platform = $platform;
    }
    
    // TODO: use $mapClass to differentiate 
    // if we fine Google or ArcGIS works badly for certain browsers
    public function pageSupportsDynamicMap($mapClass=null)
    {
        return $this->pagetype == 'tablet' ||
            ($this->pagetype == 'compliant' &&
             $this->platform != 'blackberry' &&
             $this->platform != 'bbplus' &&
             $this->platform != 'winphone7' && // not reliable
             $this->platform != 'webos');
    }

    public function staticMapImageFormat()
    {
        if ($this->pagetype == 'basic') {
            return 'gif';
        }
        return null; // use image controller default (png bit depth may differ)
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
