<?php

includePackage('DataModel');
class VideoDataModel extends ItemListDataModel
{
    protected $cacheFolder='Video';

    public static function getVideoDataRetrievers() {
        return array(
            'BrightcoveRetriever'=>'Brightcove',
            'VimeoRetriever'=>'Vimeo',
            'YouTubeRetriever'=>'YouTube'
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
