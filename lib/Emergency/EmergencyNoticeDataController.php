<?php

class EmergencyNoticeDataController extends DataModel
{
    protected $DEFAULT_RETRIEVER_CLASS = 'URLDataRetriever';
    protected $DEFAULT_PARSER_CLASS = 'RSSDataParser';
    protected $emergencyNotice = NULL;
    protected $cacheFolder = "Emergency";
    protected $cacheLifetime = 60; // emergency notice should have a short cache time

    public static function getEmergencyNoticeDataControllers() {
        return array(
            'EmergencyNoticeDataController'=>'Default'
        );
    }

    public function getLatestEmergencyNotice()
    {
        if($this->emergencyNotice === NULL) {
            $items = $this->getParsedData();
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