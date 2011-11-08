<?php

includePackage('DataModel');
class VideoDataModel extends ItemListDataModel
{
    protected $cacheFolder='Video';

    public static function getVideoDataControllers() {
        return array(
            'BrightcoveVideoController'=>'Brightcove',
            'VimeoVideoController'=>'Vimeo',
            'YouTubeVideoController'=>'YouTube'
        );
    }
    
    public function getTag() {
        return $this->tag;
    }

    public function getAuthor() {
        return $this->author;
    }
    
    protected function init($args) {
        parent::init($args);

        if (isset($args['TAG']) && strlen($args['TAG'])) {
            $this->setOption('tag', $args['TAG']);
        }
        
        if (isset($args['AUTHOR']) && strlen($args['AUTHOR'])) {
            $this->setOption('author', $args['AUTHOR']);
        }
    }
}
