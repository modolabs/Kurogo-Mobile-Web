<?php

class EmergencyNoticeDataModel extends DataModel
{
    protected $DEFAULT_PARSER_CLASS = 'RSSDataParser';
    protected $NOTICE_EXPIRATION = NULL;
    protected $NOTICE_MAX_COUNT = NULL; // unlimited
    protected $emergencyNotices = NULL;
    protected $cacheFolder = "Emergency";

    protected $cacheLifetime = 60; // emergency notice should have a short cache time

    protected function init($args) {
        parent::init($args);

        if (isset($args['NOTICE_EXPIRATION'])) {
            $this->NOTICE_EXPIRATION = $args['NOTICE_EXPIRATION'];
        } else {
            $this->NOTICE_EXPIRATION = 7*24*60*60; // 1 week
        }
        if (isset($args['NOTICE_MAX_COUNT'])) {
            $this->NOTICE_MAX_COUNT = $args['NOTICE_MAX_COUNT'];
        }
    }

    public function getFeaturedEmergencyNotice()
    {
        $items = $this->getAllEmergencyNotices();
        return count($items)>0 ? reset($items) : null;
    }

    public function getAllEmergencyNotices() {
        if ($this->emergencyNotices === NULL) {
            $now = time();
            
            $this->emergencyNotices = array();
            
            $items = $this->getData();
            foreach ($items as $item) {
                if ($now - strtotime($item->getPubDate()) > $this->NOTICE_EXPIRATION) {
                    break; // items too old
                }
                
                $this->emergencyNotices[] = array(
                   'title' => $item->getTitle(),
                   'text' => $item->getDescription(),
                   'date' => $item->getPubDate(),
                   'unixtime' => strtotime($item->getPubDate()),
                );
                
                if (isset($this->NOTICE_MAX_COUNT) && count($this->emergencyNotices) >= $this->NOTICE_MAX_COUNT) {
                    break;  // hit max count
                }
            }
        }
        return $this->emergencyNotices;
    }
}
