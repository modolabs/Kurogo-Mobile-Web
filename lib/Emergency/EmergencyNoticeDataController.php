<?php

class EmergencyNoticeDataController extends DataController
{
    protected $DEFAULT_PARSER_CLASS = 'RSSDataParser';
    protected $NOTICE_EXPIRATION = NULL;
    protected $NOTICE_MAX_COUNT = NULL; // unlimited
    protected $emergencyNotices = NULL;
    protected $cacheFolder = "Emergency";

    protected $cacheLifetime = 60; // emergency notice should have a short cache time

    public static function getEmergencyNoticeDataControllers() {
        return array(
            'EmergencyNoticeDataController'=>'Default'
        );
    }

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

    /*
     *   Not sure this should be used
     */
    public function getItem($id) 
    {
        return NULL;
    }

    public function getLatestEmergencyNotice()
    {
        $items = $this->getAllEmergencyNotices();
        
        return reset($items);
    }

    public function getAllEmergencyNotices() {
        if ($this->emergencyNotices === NULL) {
            $now = time();
            
            $this->emergencyNotices = array();
            
            $items = $this->items();
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
