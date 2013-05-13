<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class FlickrFeedRetriever extends URLDataRetriever {
    protected $DEFAULT_PARSER_CLASS = 'FlickrDataParser';

    protected function init($args) {
        parent::init($args);
        $this->setContext('retriever', 'feed');

        if (isset($args['USER'])) {
            $this->setBaseUrl('http://api.flickr.com/services/feeds/photos_public.gne');
            $this->addFilter('id', $args['USER']);
        }

        if (isset($args['PHOTOSET'])) {
            if (!isset($args['USER'])) {
                throw new KurogoConfigurationException("Photoset feeds must contain a USER value");
            }
            $this->setBaseURL('http://api.flickr.com/services/feeds/photoset.gne');
            $this->addFilter('set', $args['PHOTOSET']);
            $this->addFilter('nsid', $args['USER']);
        }
        
        if (isset($args['GROUP'])) {
            $this->setBaseUrl('http://api.flickr.com/services/feeds/groups_pool.gne');
            $this->addFilter('id', $args['GROUP']);
        }

        $this->addFilter('format', 'php_serial');
    }

}

