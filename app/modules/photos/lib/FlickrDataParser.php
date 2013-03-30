<?php

class FlickrDataParser extends DataParser {

    protected function parseEntry($entry) {
        switch ($this->response->getContext('retriever')) {
            case 'feed':
                $photo = new FlickrFeedPhotoObject();
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
                $photo = new FlickrAPIPhotoObject();
                $photo->setUserID(Kurogo::arrayVal($entry,'owner'));
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
        $action = $this->getOption('action');
        if ($action == 'getUserData') {
          return $this->parseUserData($data);
        } else {
          return $this->parsePhotoData($data);
        }
    }
    
    public function parseUserData($data) {
      if ($data = unserialize($data)) {
        return $data['person'];
      } else {
        return array();
      }
    }
    
    public function parsePhotoData($data) {
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
                    $totalItems = isset($data[$type]['total']) ? $data[$type]['total'] : count($items);
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
    
    //http://www.flickr.com/services/api/misc.urls.html
    public function getFlickrUrl($type) {
        return sprintf("%s://farm%s.staticflickr.com/%s/%s_%s_%s.jpg", HTTP_PROTOCOL, $this->farm, $this->server, $this->id, $this->secret, $type);

    }
}

class FlickrAPIPhotoObject extends FlickrPhotoObject {

    protected $uid;
    protected $secret;
    protected $farm;
    protected $server;
    
    public function setUserID($uid) {
      $this->uid = $uid;
    }

    public function setFarm($farm) {
        $this->farm = $farm;
    }

    public function setServer($server) {
        $this->server = $server;
    }

    public function setSecret($secret) {
        $this->secret = $secret;
    }

    public function getThumbnailUrl($pagetype = 'complaint'){
        switch ($pagetype) {
            case 'tablet':
                return $this->getFlickrUrl('q');
                break;
            default:
                return $this->getFlickrUrl('s');
                break;
        }
    }
    
    /* 
     * Override for PhotoObject::getAuthor to attempt to get the real name of the
     * the Flickr user if available. Otherwise fall back to using the username.
     */
    public function getAuthor() {
      // only attempt to get real name if the user id is set
      if (strlen($this->uid)) {
        $userData = $this->retriever->getUserData($this->uid);
        if ((isset($userData['realname'])) && (trim($userData['realname']['_content']) != '')) {
          $author = $userData['realname']['_content'];
          $this->setAuthor($author);
        }
      }
      return $this->author;
    }
}

class FlickrFeedPhotoObject extends FlickrPhotoObject {

}
