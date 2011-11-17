<?php

class PicasaRetriever extends URLDataRetriever {
    protected $DEFAULT_PARSER_CLASS = 'PicasaDataParser';
    protected $supportsSearch = true;

    public function search($searchTerms) {
        return $this->getData();
    }

    protected function init($args) {
        parent::init($args);
    }

    protected function getPath() {
        $path = "";
        if($id = $this->getOption('id')) {
            $path .= "user/" . $id;
        }
        if($album_id = $this->getOption('album_id')) {
            $path .= "/albumid/" . $album_id;
        }
        return $path;
    }

    public function url() {
        $path = $this->getPath();
        $this->setBaseUrl(sprintf("https://picasaweb.google.com/data/feed/api/%s", $path));

        $this->addFilter('kind', 'photo');
        return parent::url();
    }
}

class PicasaDataParser extends RSSDataParser {
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
            if(is_array($value)) {
                if(!array_key_exists($key, $value)) {
                    return false;
                }else {
                    return $object->$func($value[$key]);
                }
            }
            if(is_object($value)) {
                if(!method_exists($value, $key)) {
                    return false;
                }else {
                    return $object->$func($value->$key());
                }
            }
        }else {
            return $object->$func($value);
        }
    }

    public function parseData($contents) {
        $items = parent::parseData($contents);
        $photos = array();
        foreach($items as $item) {
            $photos[] = $this->parseEntry($item);
        }
        return $photos;
    }

    protected function parseEntry($entry) {
        $photo = new PicasaPhotoObject();
        //var_dump($entry);
        $this->fillWith($photo, 'setID', $entry, 'getGUID');
        $this->fillWith($photo, 'setTitle', $entry, 'getTitle');
        //$this->fillWith($photo, 'setUrl', );
        $this->fillWith($photo, 'setDescription', $entry, 'description');
        $thumbnails = $entry->getChildElement('MEDIA:GROUP')->getChildElement('MEDIA:THUMBNAIL');
        $this->fillWith($photo, 'setMUrl', $thumbnails[1]->getAttrib('URL'));
        $this->fillWith($photo, 'setTUrl', $thumbnails[0]->getAttrib('URL'));
        $this->fillWith($photo, 'setLUrl', $thumbnails[2]->getAttrib('URL'));
        $this->fillWith($photo, 'setPhotoUrl', $entry->getChildElement('MEDIA:GROUP')->getChildElement('MEDIA:CONTENT')->getAttrib('URL'));
        if($date = $entry->getPubDate()) {
            $published = new DateTime($date);
            $this->fillWith($photo, 'setPublished', $published);
        }
        //if(isset($entry['date_taken'])) {
            //$this->fillWith($photo, 'setDateTaken', new DateTime($entry['date_taken']));
        //}
        //$this->fillWith($photo, 'setAuthorName', $entry, 'author_name');
        //$this->fillWith($photo, 'setAuthorUrl', $entry, 'author_url');
        //$this->fillWith($photo, 'setAuthorId', $entry, 'author_nsid');
        //$this->fillWith($photo, 'setAuthorIcon', $entry, 'author_icon');
        $this->fillWith($photo, 'setHeight', $entry->getChildElement('GPHOTO:HEIGHT')->value());
        $this->fillWith($photo, 'setWidth', $entry->getChildElement('GPHOTO:WIDTH')->value());
        $this->fillWith($photo, 'setTags', $entry, 'tags');
        $this->fillWith($photo, 'setMimeType', $entry->getChildElement('MEDIA:GROUP')->getChildElement('MEDIA:CONTENT')->getAttrib('TYPE'));
        return $photo;
    }
}

class PicasaPhotoObject extends PhotoObject {
    protected $type = 'picasa';
}
