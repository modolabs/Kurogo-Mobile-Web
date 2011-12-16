<?php

class FlickrDataParser extends DataParser {

    protected function parseEntry($entry) {
        $photo = new FlickrPhotoObject();
        
        switch ($this->response->getContext('retriever')) {
            case 'feed':
                $photo->setID($entry['guid']);
                $photo->setAuthor($entry['author_name']);
                $photo->setMimeType($entry['photo_mime']);
                $photo->setURL($entry['photo_url']);
                $photo->setHeight($entry['height']);
                $photo->setWidth($entry['width']);
                $photo->setThumbnailURL($entry['thumb_url']);
                $published = new DateTime($entry['date_taken']);
                $photo->setPublished($published);
                $photo->setDescription($entry['description']);
                break;
            case 'api':
                $photo->setID($entry['id']);
                $photo->setFarm($entry['farm']);
                $photo->setServer($entry['server']);
                $photo->setSecret($entry['secret']);
                $photo->setDescription($entry['description']['_content']);
                $photo->setAuthor($entry['ownername']);

                $published = new DateTime($entry['datetaken']);
                $photo->setPublished($published);

                $photo->setThumbnailURL($photo->getFlickrUrl('s'));
                $photo->setURL($photo->getFlickrUrl('z'));
                
                break;
        }
        $photo->setTitle($entry['title']);
        $photo->setTags($entry['tags']);
        return $photo;
    }

    public function parseData($data) {
        if ($data = unserialize($data)) {
            $items = array();
            //api and feed return data in different formats
            switch ($this->response->getContext('retriever')) {
                case 'feed':
                    $items = isset($data['items']) ? $data['items'] : array();
                    $totalItems = count($items);
                    break;
                case 'api':
                    $type = $this->response->getContext('type');
                    $items = isset($data[$type]['photo']) ? $data[$type]['photo'] : array();
                    $totalItems = isset($data['total']) ? $data['total'] : count($items);
                    break;
            }

            if ($items) {
                $photos = array();
                $this->setTotalItems($totalItems);

                foreach ($items as $entry) {
                    $photos[] = $this->parseEntry($entry);
                }
                return $photos;
            }
        }

        return array();
    }
}

class FlickrPhotoObject extends PhotoObject {
    protected $type = 'flickr';
    protected $secret;
    protected $farm;
    protected $server;

    public function setFarm($farm) {
        $this->farm = $farm;
    }

    public function setServer($server) {
        $this->server = $server;
    }

    public function setSecret($secret) {
        $this->secret = $secret;
    }
    
    //http://www.flickr.com/services/api/misc.urls.html
    public function getFlickrUrl($type) {
        return sprintf("http://farm%s.staticflickr.com/%s/%s_%s_%s.jpg", $this->farm, $this->server, $this->id, $this->secret, $type);

    }
}
