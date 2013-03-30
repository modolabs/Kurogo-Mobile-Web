<?php

class FlickrAPIRetriever extends URLDataRetriever {
    
    protected $api_key;
    
    protected $DEFAULT_PARSER_CLASS = 'FlickrDataParser';
    protected $extraFields = array(
        'description', 'date_upload', 'date_taken', 'owner_name', 'original_format', 
        'last_update', 'geo', 'tags', 'machine_tags', 'o_dims', 'views', 'media');
    // not used
    //  'icon_server' , 'license', 'path_alias', 

    protected function initRequest() {
        parent::initRequest();

        if ($limit = $this->getOption('limit')) {
            $this->addFilter('per_page', $limit);
        }
        
        $start = $this->getOption('start');
        if (strlen($start)) {
            $start = ($start / $limit);
            $this->addFilter('page', $start+1);
        }
    }

    protected function init($args) {
        parent::init($args);
        $this->setContext('retriever','api');

        $this->setBaseUrl('http://api.flickr.com/services/rest/');
        if (!isset($args['API_KEY'])) {
            throw new KurogoConfigurationException("Flickr API_KEY required");
        }
        $this->api_key = $args['API_KEY'];
        $this->addFilter('api_key', $this->api_key);

        if (isset($args['USER'])) {
            $this->setContext('type', 'photos');
            $this->addFilter('method','flickr.photos.search');
            $this->addFilter('user_id', $args['USER']);
        }

        if (isset($args['PHOTOSET'])) {
            $this->setContext('type', 'photoset');
            $this->addFilter('method','flickr.photosets.getPhotos');
            $this->addFilter('photoset_id', $args['PHOTOSET']);
        }
        
        if (isset($args['GALLERY'])) {
            $this->setContext('type', 'photos');
            $this->addFilter('method','flickr.galleries.getPhotos');
            $this->addFilter('gallery_id', $args['GALLERY']);
        }
        
        if (isset($args['GROUP'])) {
            $this->setContext('type', 'photos');
            $this->addFilter('method','flickr.photos.search');
            $this->addFilter('group_id', $args['GROUP']);
        }

        $this->addFilter('extras', implode(',', $this->extraFields));
        $this->addFilter('format', 'php_serial');
    }
    
    public function getUserData($id){
      
      $this->setBaseUrl('http://api.flickr.com/services/rest/', true);
      
      $this->setOption('action', 'getUserData');
      $this->addFilter('api_key', $this->api_key);
      $this->addFilter('method','flickr.people.getInfo');
      $this->addFilter('user_id', $id);
      $this->addFilter('format', 'php_serial');
      
      return $this->getData();
    }
}
