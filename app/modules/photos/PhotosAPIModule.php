<?php

Kurogo::includePackage('Photos');

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
                    $albums[$id]['title'] = $feed['TITLE'];
                    $controller = $this->getFeed($id);
                    $defaultPhoto = $controller->getDefaultPhoto();

                    $photo = array();
                    $photo['title'] = $controller->getTitle();
                    $photo['type'] = $defaultPhoto->getType();
                    $photo['album_count'] = $controller->getAlbumSize();
                    $photo['img'] = $defaultPhoto->getTUrl();
                    $albums['photos'][] = $photo;
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
                $limit = $this->getArg('limit', 4);
                $totalItems = $controller->getAlbumSize();
                $totalPage = ceil($totalItems / $limit);
                $page = $this->getArg('page');
                if($page < 0) {
                    $page = 0;
                }
                if($page > $totalPage) {
                    $page = $totalPage;
                }
                $start = $page * $limit;
                $items = $controller->getPhotosByIndex($start, $limit);
        		$photos = array();
        		foreach($items as $item){
        			$photo['id'] = $item->getID();
        			$photo['title'] = $item->getTitle();
        			$photo['album_id'] = $id;
                    $photo['m_img'] = $item->getMUrl();
                    $photo['t_img'] = $item->getTUrl();
                    $photo['l_img'] = $item->getLUrl();
                    $photo['origin_img'] = $item->getPhotoUrl();
                    $photos[] = $photo;
        		}
                $albumTitle = $controller->getTitle();
                $response = array(
                    'photos' => $photos,
                    'totalItems' => $totalItems,
                    'totalPage' => $totalPage,
                    'currentPage' => $page,
                    'albumTitle' => $albumTitle,
                );
                $this->setResponse($response);
                break;
            default:
                $this->invalidCommand();
                break;
        }
    }
}
