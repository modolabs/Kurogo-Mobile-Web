<?php

abstract class VideoDataController extends DataController
{
    protected $cacheFolder='Video';

    public static function getVideoDataControllers() {
        return array(
            'BrightcoveVideoController'=>'Brightcove',
            'KalturaVideoController'=>'Kaltura',
            'YouTubeVideoController'=>'YouTube'
        );
    }

    abstract public function search($q, $start, $limit);
}
