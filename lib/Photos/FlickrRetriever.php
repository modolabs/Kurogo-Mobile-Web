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
    /**
     * fillWith 
     * fill object with array value
     * the purpose is to make sure the array has the specified key
     * 
     * @param mixed $object 
     * @param mixed $func 
     * @param mixed $array 
     * @param mixed $key 
     * @access protected
     * @return void
     */
    protected function fillWith($object, $func, $value, $key = false) {
        if($key) {
            if(!array_key_exists($key, $value)) {
                return false;
            }
            return $object->$func($value[$key]);
        }else {
            return $object->$func($value);
        }
    }
    protected function parseEntry($entry) {
        $photo = new FlickrPhotoObject();
        $this->fillWith($photo, 'setID', $entry, 'guid');
        $this->fillWith($photo, 'setTitle', $entry, 'title');
        $this->fillWith($photo, 'setUrl', $entry, 'url');
        $this->fillWith($photo, 'setDescription', $entry, 'description');
        $this->fillWith($photo, 'setMUrl', $entry, 'm_url');
        $this->fillWith($photo, 'setTUrl', $entry, 't_url');
        $this->fillWith($photo, 'setLUrl', $entry, 'l_url');
        $this->fillWith($photo, 'setPhotoUrl', $entry, 'photo_url');
        if(isset($entry['date'])) {
            $published = new DateTime();
            $published->setTimestamp($entry['date']);
            $this->fillWith($photo, 'setPublished', $published);
        }
        if(isset($entry['date_taken'])) {
            $this->fillWith($photo, 'setDateTaken', new DateTime($entry['date_taken']));
        }
        $this->fillWith($photo, 'setAuthorName', $entry, 'author_name');
        $this->fillWith($photo, 'setAuthorUrl', $entry, 'author_url');
        $this->fillWith($photo, 'setAuthorId', $entry, 'author_nsid');
        $this->fillWith($photo, 'setAuthorIcon', $entry, 'author_icon');
        $this->fillWith($photo, 'setHeight', $entry, 'height');
        $this->fillWith($photo, 'setWidth', $entry, 'width');
        $this->fillWith($photo, 'setTags', $entry, 'tags');
        $this->fillWith($photo, 'setMimeType', $entry, 'photo_mime');
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
