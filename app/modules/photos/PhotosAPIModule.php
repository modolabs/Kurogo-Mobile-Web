<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class PhotosAPIModule extends APIModule {
    protected $id = 'photos';
    protected $vmin = 1;
    protected $vmax = 1;
    protected $feeds = array();
    protected $feedIndex = 0;
    
    protected $showAuthor = 1;
    protected $showDate = 1;
    protected $showDescription = 1;

    protected function getFeed($feed) {
        if(!isset($this->feeds[$feed])) {
            throw new KurogoException(get_class($this) . ": Invalid Album id: $feed");
            return false;
        }
        $feedData = $this->feeds[$feed];

        $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'PhotosDataModel';
        $controller = DataModel::factory($modelClass, $feedData);

        return $controller;
    }

    private function arrayForAlbum($feed, $controller) {
        if ($defaultPhoto = $controller->getDefaultPhoto()) {
            $album = array(
                'id'            => strval($feed),
                'title'         => $controller->getTitle(),
                'type'          => $defaultPhoto->getType(),
                'totalItems'    => $controller->getAlbumSize(),
                'img'           => $defaultPhoto->getThumbnailUrl()
            );
            return $album;
        }
        return null;
    }

    private function arrayForPhoto($albumId, $photoData) {
        $photo = array(
            'id'            => strval($photoData->getID()),
            'title'         => $photoData->getTitle(),
            'albumId'       => strval($albumId),
            'thumbnailUrl'  => $photoData->getThumbnailUrl(),
            'imgUrl'        => $photoData->getUrl(),
            'description'   => Sanitizer::htmlStripTags2UTF8($photoData->getDescription()),
            'author'        => $photoData->getAuthor(),
            'published'     => $photoData->getPublished()->getTimestamp()   
        );
        return $photo;
    }

    private function setViewOptions($feedId) {
        $this->feedIndex = $feedId;
        if (!isset($this->feeds[$this->feedIndex])) {
            $this->feedIndex = key($this->feeds);
        }

        $feedData = $this->feeds[$this->feedIndex];

        $this->showDescription = isset($feedData['SHOW_DESCRIPTION']) ? $feedData['SHOW_DESCRIPTION'] : 1;
        $this->showAuthor = isset($feedData['SHOW_AUTHOR']) ? $feedData['SHOW_AUTHOR'] : 1;
        $this->showDate = isset($feedData['SHOW_DATE']) ? $feedData['SHOW_DATE'] : 1;

        // Check if the feed config section overrides the module config 
        // for the following options
        $controller = $this->getFeed($feedId);
        $feedShowDescription = $controller->getInitArg('SHOW_DESCRIPTION');
        $feedShowAuthor = $controller->getInitArg('SHOW_AUTHOR');
        $feedShowDate = $controller->getInitArg('SHOW_DATE');   

        $showDescription = (isset($feedShowDescription)) ? (int) $feedShowDescription : $this->showDescription;
        $showAuthor = (isset($feedShowAuthor)) ? (int) $feedShowAuthor : $this->showAuthor;
        $showDate = (isset($feedShowDate)) ? (int) $feedShowDate : $this->showDate;

        return array(
            'show_description' => $showDescription,
            'show_author' => $showAuthor,
            'show_date' => $showDate
        );
    }

    public function  initializeForCommand() {
        // don't know how to use version?
        $this->setResponseVersion(1);
        $this->feeds = $this->loadFeedData();
        
        if (count($this->feeds)==0) {
            return;
        }

        switch ($this->command) {
            case 'albums':
                // get albums, output all available feeds
                $albums = array();
                foreach($this->feeds as $feed => $feedData) {
                    $controller = $this->getFeed($feed);
                    
                    if ($albumData = $this->arrayForAlbum($feed, $controller)) {
                        $albums['albums'][] = $albumData;
                    }
                }
                $this->setResponse($albums);
                break;
            case 'album':
            case 'list':
                // get photos list for an album..
                $feed = $this->getArg(array('feed','id'));
                $viewOptions = $this->setViewOptions($feed);
                $controller = $this->getFeed($feed);
                if (!$controller) {
                    return false;
                }
                $limit = $this->getArg('limit', 10);
                $start = $this->getArg('start', 0);
                $controller->setStart($start);
                $controller->setLimit($limit);
                $items = $controller->getPhotos();

                $album = $this->arrayForAlbum($feed, $controller);

                $photos = array();
                foreach ($items as $item) {
                    $photos[] = $this->arrayforPhoto($feed, $item);
                }
                $albumTitle = $controller->getTitle();
                
                $response = array(
                    'album' => $album,
                    'photos' => $photos,
                    'totalItems' => $controller->getAlbumSize(),
                    'show_description' => $viewOptions['show_description'],
                    'show_author' => $viewOptions['show_author'],
                    'show_date' => $viewOptions['show_date']
                );
                $this->setResponse($response);
                break;

            default:
                $this->invalidCommand();
                break;
        }
    }
}
