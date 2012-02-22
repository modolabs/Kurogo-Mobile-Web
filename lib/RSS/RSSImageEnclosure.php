<?php
/**
  * @package ExternalData
  * @subpackage RSS
  */

class RSSImageEnclosure extends RSSEnclosure implements KurogoImage
{
    protected $setImageLoaderURL = false;
    protected $imageOptions = array();

    public function init($args) {
        parent::init($args);
        
        // set image resize/crop parameters
        if (isset($args['THUMB_MAX_WIDTH'])) {
            $this->imageOptions['max_width'] = intval($args['THUMB_MAX_WIDTH']);
        }
        if (isset($args['THUMB_MAX_HEIGHT'])) {
            $this->imageOptions['max_height'] = intval($args['THUMB_MAX_HEIGHT']);
        }
        if (isset($args['THUMB_CROP'])) {
            $this->imageOptions['crop'] = (boolean)$args['THUMB_CROP'];
        }
        if (isset($args['THUMB_BACKGROUND_RGB'])) {
            $this->imageOptions['rgb'] = strval($args['THUMB_BACKGROUND_RGB']);
        }
    }

    public function getURL() {
        if (!$this->setImageLoaderURL) {
            $this->url = ImageLoader::cacheImage($this->url, $this->imageOptions);
            $this->setImageLoaderURL = true;
        }
        return parent::getURL();
    }

    public function getWidth() {
    }

    public function getHeight() {
    }
}
