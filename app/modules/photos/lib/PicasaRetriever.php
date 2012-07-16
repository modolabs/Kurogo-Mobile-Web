<?php

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

