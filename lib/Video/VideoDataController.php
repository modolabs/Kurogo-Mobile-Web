<?php

abstract class VideoDataController extends DataController
{
    protected $cacheFolder='Video';
    protected $tag;
    protected $author;

    public static function getVideoDataControllers() {
        return array(
            'BrightcoveVideoController'=>'Brightcove',
            'VimeoVideoController'=>'Vimeo',
            'YouTubeVideoController'=>'YouTube'
        );
    }
    
    protected function init($args) {
        parent::init($args);

        if (isset($args['TAG']) && strlen($args['TAG'])) {
            $this->tag = $args['TAG'];
        }
        
        if (isset($args['AUTHOR']) && strlen($args['AUTHOR'])) {
            $this->author = $args['AUTHOR'];
        }
    }

    abstract public function search($q, $start=0, $limit=null);
}
