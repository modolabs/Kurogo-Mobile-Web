<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('DateTime');
class EmergencyNoticeDataModel extends DataModel
{
    protected $DEFAULT_PARSER_CLASS = 'RSSDataParser';
    protected $NOTICE_EXPIRATION = 604800; // 1 week
    protected $NOTICE_MAX_COUNT = NULL; // unlimited
    protected $emergencyNotices = NULL;
    protected $cacheFolder = "Emergency";

    protected $DEFAULT_CACHE_LIFETIME = 60; // emergency notice should have a short cache time

    protected function init($args) {
        // Present variable if not set before init (the retriever reads it)
        if (!isset($args['CACHE_LIFETIME'])) {
            $args['CACHE_LIFETIME'] = $this->DEFAULT_CACHE_LIFETIME;
        }

        parent::init($args);

        if (isset($args['NOTICE_EXPIRATION'])) {
            $this->NOTICE_EXPIRATION = $args['NOTICE_EXPIRATION'];
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
                if ($this->NOTICE_EXPIRATION && (($now - $item->getPubTimestamp()) > $this->NOTICE_EXPIRATION)) {
                    break; // items too old
                }
                
                $this->emergencyNotices[] = array(
                   'title' => $item->getTitle(),
                   'text' => Sanitizer::htmlStripTags2UTF8($item->getDescription()),
                   'body' => $item->getContent(false),
                   'link' => $item->getLink(),
                   'date' => $item->getPubDate() ? DateFormatter::formatDate($item->getPubDate(), DateFormatter::MEDIUM_STYLE, DateFormatter::MEDIUM_STYLE) : '',
                   'unixtime' => $item->getPubTimestamp(),
                );
                
                if (isset($this->NOTICE_MAX_COUNT) && count($this->emergencyNotices) >= $this->NOTICE_MAX_COUNT) {
                    break;  // hit max count
                }
            }
        }
        return $this->emergencyNotices;
    }
}
