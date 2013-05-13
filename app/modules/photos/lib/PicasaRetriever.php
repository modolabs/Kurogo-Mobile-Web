<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class PicasaRetriever extends URLDataRetriever {
    protected $DEFAULT_PARSER_CLASS = 'PicasaDataParser';

    protected function init($args) {

        parent::init($args);

        $url = "https://picasaweb.google.com/data/feed/api/";

        if (isset($args['USER'], $args['ALBUM'])) {
            $url .= sprintf("user/%s/albumid/%s", $args['USER'], $args['ALBUM']);
        } else {
            throw new KurogoConfigurationException("USER and ALBUM values must be set for Picasa albums");
        }
                
        $this->setBaseURL($url);
        $this->addFilter('kind', 'photo');
        switch (Kurogo::getPagetype()) {
            case 'tablet':
                $this->addFilter('thumbsize', '150c');
                break;
            default:
                $this->addFilter('thumbsize', '72c');
                break;
        }
        $this->addFilter('alt', 'json');
    }

}

