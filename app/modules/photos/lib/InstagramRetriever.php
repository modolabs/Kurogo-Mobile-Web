<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class InstagramRetriever extends URLDataRetriever {
    
    protected $api_key;
    protected $DEFAULT_PARSER_CLASS = 'InstagramDataParser';

    protected function initRequest() {
        parent::initRequest();

        if ($limit = $this->getOption('limit')) {
            $this->addFilter('per_page', $limit);
        }
        
        $start = $this->getOption('start');
        if (strlen($start)) {
            $start = ($start / $limit);
            $this->addFilter('page', $start+1);
        }
    }

    protected function init($args) {
        parent::init($args);
        if (!isset($args['API_KEY'])) {
            throw new KurogoConfigurationException("Instagram API_KEY required");
        }
        $this->api_key = $args['API_KEY'];

        $this->setBaseUrl('https://api.instagram.com/v1/users/self/feed');
        $this->addFilter('access_token', $this->api_key);
    }
    
}
