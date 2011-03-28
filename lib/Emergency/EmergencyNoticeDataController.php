<?php

class EmergencyNoticeDataController extends DataController
{
    protected $DEFAULT_PARSER_CLASS = 'RSSDataParser';
    protected $emergencyNotice = NULL;
    protected $cacheFolder = "Emergency";
    protected $cacheFileSuffix = "rss";

    protected $cacheLifetime = 60; // emergency notice should have a short cache time

    /*
     *   Not sure this should be used
     */
    public function getItem($id) 
    {
        return NULL;
    }

    public function getLatestEmergencyNotice()
    {
        if($this->emergencyNotice === NULL) {
            $data = $this->getData();
            $items = $this->parseData($data);
            if(count($items) > 0) {
                $this->emergencyNotice = array(
                   'title' => $items[0]->getTitle(),
                   'text' => $items[0]->getDescription(),
                   'date' => $items[0]->getPubDate(),
                   'unixtime' => strtotime($items[0]->getPubDate()),
                );
            } 
        }

        return $this->emergencyNotice;
    }

}