<?php

class FlickrRetriever extends URLDataRetriever {
    protected $DEFAULT_PARSER_CLASS = 'FlickrDataParser';
    protected $supportsSearch = true;

    public function search($searchTerms) {
        return $this->getData();
    }

    protected function init($args) {
        parent::init($args);
    }

    public function url() {
        $type = $this->getOption('type');
        switch($type) {
            case 'user':
                $this->setBaseUrl('http://api.flickr.com/services/feeds/photos_public.gne');
                if ($id = $this->getOption('id')) {
                    $this->addFilter('id', $id);
                }
                break;
            case 'group':
                $this->setBaseUrl('http://api.flickr.com/services/feeds/groups_pool.gne');
                if ($id = $this->getOption('group_id')) {
                    $this->addFilter('id', $id);
                }
                break;
            case 'set':
                $this->setBaseUrl('http://api.flickr.com/services/feeds/photoset.gne');
                if ($id = $this->getOption('id')) {
                    $this->addFilter('nsid', $id);
                }
                if ($setid = $this->getOption('set_id')) {
                    $this->addFilter('set', $setid);
                }
                break;
        }

        /**
         * return feed type
         * available values:
         * rss2, atom, rss, rss091, rss_200_enc, rdf
         * json, php_serial, php, csv are also available
         */
        $this->addFilter('format', 'php_serial');
        return parent::url();
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
