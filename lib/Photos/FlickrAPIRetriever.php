<?php

class FlickrAPIRetriever extends URLDataRetriever {
    protected $DEFAULT_PARSER_CLASS = 'FlickrDataParser';
    protected $extraFields = array(
        'description', 'date_upload', 'date_taken', 'owner_name', 'original_format', 
        'last_update', 'geo', 'tags', 'machine_tags', 'o_dims', 'views', 'media');
    // not used
    //  'icon_server' , 'license', 'path_alias', 

    protected function init($args) {
        parent::init($args);
        $this->setContext('retriever','api');

        $this->setBaseUrl('http://api.flickr.com/services/rest/');
        if (!isset($args['API_KEY'])) {
            throw new KurogoConfigurationException("Flickr API_KEY required");
        }
        $this->addFilter('api_key', $args['API_KEY']);

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
        
        if (isset($args['GROUP'])) {
            $this->setContext('type', 'photos');
            $this->addFilter('method','flickr.photos.search');
            $this->addFilter('group_id', $args['GROUP']);
        }

        $this->addFilter('extras', implode(',', $this->extraFields));
        $this->addFilter('format', 'php_serial');
    }

}
/*

        $photo->setTitle($entry['title']);
        $photo->setDescription($entry['description']);
        $photo->setAuthor($entry['author_name']);
        $photo->setMimeType($entry['photo_mime']);
        $photo->setURL($entry['photo_url']);
        $photo->setHeight($entry['height']);
        $photo->setWidth($entry['width']);
        $photo->setTags($entry['tags']);
        $photo->setThumbnailURL($entry['thumb_url']);
*/