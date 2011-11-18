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
        $this->addFilter('alt', 'json');
        return parent::url();
    }
}

class PicasaDataParser extends DataParser {
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
        if(is_object($value)) {
            if(!$key) {
                $key = '$t';
            }
            if(property_exists($value, $key)) {
                return $object->$func($value->$key);
            }else {
                return false;
            }
        }else {
            return $object->$func($value);
        }
    }

    public function parseData($data) {
        if($data = json_decode($data)) {
            if(property_exists($data, 'feed') && property_exists($data->feed, 'entry') && is_array($data->feed->entry)) {
                $photos = array();
                if (property_exists($data->feed, 'author')) {
                    $this->setOption('author', $data->feed->author{0});
                }
                if (property_exists($data->feed, 'gphoto$user')) {
                    $this->setOption('author_id', $data->feed->{'gphoto$user'});
                }
                foreach($data->feed->entry as $entry) {
                    $photos[] = $this->parseEntry($entry);
                }
                return $photos;
            }
        }

        return array();
    }

    protected function parseEntry($entry) {
        $photo = new PicasaPhotoObject();
        $this->fillWith($photo, 'setID', $entry->id);
        $this->fillWith($photo, 'setTitle', $entry->title);
        $this->fillWith($photo, 'setUrl', $entry->link{1}, 'href');
        $this->fillWith($photo, 'setDescription', $entry->{'media$group'}->{'media$description'});
        $thumbnails = $entry->{'media$group'}->{'media$thumbnail'};
        $this->fillWith($photo, 'setMUrl', $thumbnails[1], 'url');
        $this->fillWith($photo, 'setTUrl', $thumbnails[0], 'url');
        $this->fillWith($photo, 'setLUrl', $thumbnails[2], 'url');
        $this->fillWith($photo, 'setPhotoUrl', $entry->content, 'src');
        $this->fillWith($photo, 'setMimeType', $entry->content, 'type');
        $this->fillWith($photo, 'setHeight', $entry->{'gphoto$height'});
        $this->fillWith($photo, 'setWidth', $entry->{'gphoto$width'});
        if($date = $entry->published->{'$t'}) {
            $published = new DateTime($date);
            $photo->setPublished($published);
        }
        $this->fillWith($photo, 'setAuthorName', $this->getOption('author')->name);
        $this->fillWith($photo, 'setAuthorUrl', $this->getOption('author')->uri);
        $this->fillWith($photo, 'setAuthorId', $this->getOption('author_id'));
        //if(isset($entry['date_taken'])) {
            //$this->fillWith($photo, 'setDateTaken', new DateTime($entry['date_taken']));
        //}
        //$this->fillWith($photo, 'setAuthorIcon', $entry, 'author_icon');
        //$this->fillWith($photo, 'setTags', $entry, 'tags');
        return $photo;
    }
}

class PicasaPhotoObject extends PhotoObject {
    protected $type = 'picasa';
}
