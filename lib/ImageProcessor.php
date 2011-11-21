<?php

class ImageProcessor
{
    var $fileName;
    var $imagetype;
    var $width;
    var $height;
    var $aspect;
    var $bits;
    var $channels;
    
    public function getWidth() {
        return $this->width;
    }

    public function getHeight() {
        return $this->height;
    }
    
    public function getAspect() {
        return $this->height>0 ? $this->width / $this->height : 0;
    }

    public function getImageType() {
        return $this->imagetype;
    }
    
    public function transform(ImageTransformer $transformer, $imageType, $file) {
        $boundingBox = $transformer->getBoundingBox($this->width, $this->height);
        if (KurogoError::isError($boundingBox)) {
            return $boundingBox;
        }
       
        if (is_null($imageType)) {
            $imageType = $this->imagetype;
        } else {
            switch ($imageType)
            {
                case IMAGETYPE_GIF:
                case IMAGETYPE_JPEG:
                case IMAGETYPE_PNG:
                    break;
                case 'gif':
                    $imageType = IMAGETYPE_GIF;
                    break;
                case 'jpg':
                    $imageType = IMAGETYPE_JPEG;
                    break;
                case 'png':
                    $imageType = IMAGETYPE_PNG;
                    break;
            }
        }
        $width = $boundingBox[0]; $height = $boundingBox[1];
        
        if ($this->width == $width && $this->height == $height && $imageType == $this->imagetype) {
            return copy($this->fileName, $file) ? true : new KurogoError(1, "Error copying", "Error saving file");
        } 
        
        if (!function_exists('gd_info')) {
            throw new KurogoDataException("Resizing images requires the GD image library");
        }
        
        switch ($this->imagetype)
        {
            case IMAGETYPE_JPEG:
                $src = imagecreatefromjpeg($this->fileName);
                break;
            case IMAGETYPE_PNG:
                $src = imagecreatefrompng($this->fileName);
                break;
            case IMAGETYPE_GIF:
                $src = imagecreatefromgif($this->fileName);
                break;
            default:
                throw new KurogoDataException("Unable to read files of this type ($this->imagetype)");
        }

        switch ($imageType)
        {
            case IMAGETYPE_JPEG:
                $dest = imagecreatetruecolor($width, $height);
                $saveFunc = 'ImageJPEG';
                break;
            case IMAGETYPE_PNG:
                $dest = imagecreatetruecolor($width, $height);
                imagealphablending($dest, false);
                imagesavealpha($dest, true);
                $saveFunc = 'ImagePNG';
                break;
            case IMAGETYPE_GIF:
                $dest = imagecreate($width, $height);
                $saveFunc = 'ImageGIF';
                break;
            default:
                throw new KurogoDataException("Unable to save files of this type");
        }
        
        if ($this->width != $width || $this->height != $height) {
            imagecopyresampled ( $dest, $src, 0, 0, 0, 0, $width, $height, $this->width, $this->height );
        } else {
            $dest &= $src;
        }
        
        return call_user_func($saveFunc, $dest, $file);
    }
    
    
    public function __construct($fileName) {
    
        if (!is_readable($fileName)) {
            throw new KurogoDataException("Unable to read $fileName");
        }

		$this->fileName = $fileName;

		if (!$info = getimagesize($this->fileName)) {
		    throw new KurogoDataException("Not a valid image file");
		}
		
        $this->width = $info[0];
        $this->height = $info[1];
        $this->imagetype = $info[2];
        
        if (isset($info['bits'])) {
            $this->bits = $info['bits'];
        }
        
        if (isset($info['channels'])) {
            $this->channels = $info['channels'];
        }
			
    }
}
