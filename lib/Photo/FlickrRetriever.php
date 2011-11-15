<?php

class FlickrRetriever extends URLDataRetriever {
    protected $DEFAULT_PARSER_CLASS = 'FlickrDataParser';
    protected $supportsSearch = true;

    private function setStandardFilters() {
        $this->setBaseUrl('http://api.flickr.com/services/feeds/photos_public.gne');

        /**
         * return feed type
         * available values:
         * rss2, atom, rss, rss091, rss_200_enc, rdf
         * json, php_serial, php, csv are also available
         */
        $this->addFilter('format', 'php_serial');
    }

    public function search($searchTerms) {
        $this->setStandardFilters();
        return $this->getData();
    }

    protected function init($args) {
        $this->setStandardFilters();
        parent::init($args);
    }

    public function url() {
        if ($id = $this->getOption('id')) {
            $this->addFilter('id', $id);
        }
        return parent::url();
    }

    protected function isValidID($id) {
        return preg_match("/^[A-Za-z0-9_-]+$/", $id);
    }

    // retrieves photo
    public function getItem($id) {
        if (!$this->isValidID($id)) {
            return false;
        }
        $this->setBaseUrl("http://gdata.youtube.com/feeds/mobile/videos/$id");
        $this->addFilter('alt', 'jsonc'); //set the output format to json
        $this->addFilter('format', 6); //only return mobile videos
        $this->addFilter('v', 2); // version 2

        return $this->getParsedData();
    }
}

class FlickrDataParser extends DataParser {
    protected function parseEntry($entry) {
        $photo = new FlickrPhotoObject();
        $photo->setID($entry['guid']);
        $photo->setTitle($entry['title']);
        $photo->setUrl($entry['url']);
        $photo->setDescription($entry['description']);
        $photo->setMUrl($entry['m_url']);
        $photo->setTUrl($entry['t_url']);
        $photo->setLUrl($entry['l_url']);
        $photo->setPhotoUrl($entry['photo_url']);
        $published = new DateTime();
        $published->setTimestamp($entry['date']);
        $photo->setPublished($published);
        $photo->setDateTaken(new DateTime($entry['date_taken']));
        $photo->setAuthorName($entry['author_name']);
        $photo->setAuthorUrl($entry['author_url']);
        $photo->setAuthorId($entry['author_nsid']);
        $photo->setAuthorIcon($entry['author_icon']);
        $photo->setHeight($entry['height']);
        $photo->setWidth($entry['width']);
        $photo->setTags($entry['tags']);
        $photo->setMimeType($entry['photo_mime']);
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
