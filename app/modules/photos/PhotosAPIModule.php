<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
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

    public function  initializeForCommand() {
        // don't know how to use version?
        $this->setResponseVersion(1);
        $this->feeds = $this->loadFeedData();

        switch ($this->command) {
            case 'albums':
                // get albums, output all available feeds
                $albums = array();
                foreach($this->feeds as $id => $feed) {
                    $id = $feed['INDEX'];
                    $controller = $this->getFeed($id);
                    $defaultPhoto = $controller->getDefaultPhoto();

                    $photo = array();
                    $photo['id'] = $id;
                    $photo['title'] = $controller->getTitle();
                    $photo['type'] = $defaultPhoto->getType();
                    $photo['totalItems'] = $controller->getAlbumSize();
                    $photo['img'] = $defaultPhoto->getThumbnailUrl();
                    $albums['albums'][] = $photo;
                }
                $this->setResponse($albums);
                break;
            case 'list':
                // get photos list for an album..
                $id = $this->getArg('id');
                $controller = $this->getFeed($id);
                if(!$controller) {
                    return false;
                }
                $limit = $this->getArg('limit', 10);
                $start = $this->getArg('start', 0);
                $controller->setStart($start);
                $controller->setLimit($limit);
        		$items = $controller->getPhotos();
        		$photos = array();
        		foreach($items as $item){
        			$photo['id'] = $item->getID();
        			$photo['title'] = $item->getTitle();
        			$photo['albumId'] = $id;
                    $photo['thumbnailUrl'] = $item->getThumbnailUrl();
                    $photo['imgUrl'] = $item->getUrl();
                    $photo['description'] = $item->getDescription();
                    $photo['author'] = $item->getAuthor();
                    $photo['published'] = $item->getPublished()->getTimestamp();
                    $photos[] = $photo;
        		}
                $albumTitle = $controller->getTitle();
                $response = array(
                    'photos' => $photos,
                    'totalItems' => $controller->getAlbumSize()
                );
                $this->setResponse($response);
                break;
            default:
                $this->invalidCommand();
                break;
        }
    }
}
