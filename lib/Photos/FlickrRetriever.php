<?php

class FlickrRetriever extends URLDataRetriever {
    protected $DEFAULT_PARSER_CLASS = 'FlickrDataParser';
    protected $supportsSearch = true;

    public function search($searchTerms) {
        return $this->getData();
    }

    protected function init($args) {
        parent::init($args);

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

class FlickrDataParser extends DataParser {

    protected function parseEntry($entry) {
        $photo = new FlickrPhotoObject();
        
        $photo->setID($entry['guid']);
        $photo->setTitle($entry['title']);
        $photo->setDescription($entry['description']);
        $photo->setAuthor($entry['author_name']);
        $photo->setMimeType($entry['photo_mime']);
        $photo->setURL($entry['photo_url']);
        $photo->setHeight($entry['height']);
        $photo->setWidth($entry['width']);
        $photo->setTags($entry['tags']);
        $photo->setThumbnailURL($entry['thumb_url']);

        $published = new DateTime($entry['date_taken']);
        $photo->setPublished($published);
        return $photo;
    }

    public function parseData($data) {
        if ($data = unserialize($data)) {
            if (isset($data['items'])) {
                $photos = array();
                $totalItems = count($data['items']);
                $this->setTotalItems($totalItems);

                foreach ($data['items'] as $entry) {
                    $photos[] = $this->parseEntry($entry);
                }
                return $photos;
            } else {
                return array();
            }
        }

        return array();
    }
}

class FlickrPhotoObject extends PhotoObject {
    protected $type = 'flickr';
}
