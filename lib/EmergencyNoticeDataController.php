<?php

class EmergencyNoticeDataController extends DataController
{
    protected $DEFAULT_PARSER_CLASS = 'RSSDataParser';
    protected $emergencyNotice = NULL;
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
            $this->emergencyNotice = array(
                'title' => $items[0]->getTitle(),
                'text' => $items[0]->getDescription(),
                'date' => $items[0]->getPubDate()
            );
        }

        return $this->emergencyNotice;
    }

    // the next 3 methods can be removed when
    // we upgrade to Kurogo
    protected function cacheFolder()
    {
        return CACHE_DIR . "/EmergencyNotice";
    }

    protected function cacheLifespan()
    {
        return $GLOBALS['siteConfig']->getVar('EMERGENCY_NOTICE_CACHE_TIMEOUT');
    }

    protected function cacheFileSuffix() {
        return '.xml';
    }

    public static function factory($args)
    {
        $args = array(
	    'BASE_URL' => $args['notice']['RSS_URL'],
            'CONTROLLER_CLASS' => __CLASS__,
        );
        return parent::factory($args);
    }
}