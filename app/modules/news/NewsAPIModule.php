<?php

class NewsAPIModule extends APIModule {

    protected $id = 'news';
    protected $vmin = 1;
    protected $vmax = 1;
    protected $legacyController = false;
    protected static $defaultModel = 'NewsDataModel';
    protected static $defaultController = 'RSSDataController';
    
    protected function initializeForCommand() {
        $feeds = $this->loadFeedData();

        switch($this->command) {
            case 'stories':
                $categoryID = $this->getArg('categoryID');
                $start = $this->getArg('start');
                $limit = $this->getArg('limit');
                $mode = $this->getArg('mode');

                $feed = $this->getFeed($categoryID);
                if ($this->legacyController) {
                    $items = $feed->items($start, $limit);
                } else {
                    $feed->setStart($start);
                    $feed->setLimit($limit);
                    $items = $feed->items($start, $limit);
                }
                $totalItems = $feed->getTotalItems();

                $stories = array();
                foreach ($items as $story) {
                    $stories[] = $this->formatStory($story, $mode);
                }
                $response = array(
                    'stories' => $stories,
                    'moreStories' => ($totalItems - $start - $limit),
                );
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'categories':
                $response = array();
                foreach ($feeds as $index => $feedData) {
                    $response[] = array('id' => strval($index),
                    					'title' => strip_tags($feedData['TITLE']),
                    					);
                }
                $this->setResponse($response);
                $this->setResponseVersion(1);

                break;

            case 'search':
                $categoryID = $this->getArg('categoryID');
                $searchTerms = $this->getArg('q');
                $feed = $this->getFeed($categoryID);
                if ($this->legacyController) {
                    $feed->addFilter('search', $searchTerms);
                    $start = 0;
                    $items = $feed->items($start);
                } else {
                    $items = $feed->search($searchTerms);
                }

                $stories = array();
                foreach ($items as $story) {
                    $stories[] = $this->formatStory($story, 'full');
                }
                $this->setResponse($stories);
                $this->setResponseVersion(1);
                break;

            default:
                 $this->invalidCommand();
                 break;
        }
    }

    protected function formatStory($story, $mode) {
       $item = array(
            'GUID'        => $story->getGUID(),
            'link'        => $story->getLink(),
            'title'       => strip_tags($story->getTitle()),
            'description' => $story->getDescription(),
            'pubDate'     => self::getPubDateUnixtime($story),
       );

       // like in the web module we
       // use the existance of GUID
       // to determine if we have content
       if($story->getGUID()) {
           $item['GUID'] = $story->getGUID();
           if($mode == 'full') {
                $item['body'] = $story->getContent();
           }
           $item['hasBody'] = TRUE;
       } else {
           $item['GUID'] = $story->getLink();
           $item['hasBody'] = FALSE;
       }


       $image = $story->getImage();
       if($image && $image->getURL()) {
           $item['image'] = array(
                'src'    => $image->getURL(),
                'width'  => $image->getProperty('width'),
                'height' => $image->getProperty('height'),
           );
       }
       $author = $story->getAuthor();
       $item['author'] = $author ? $author : "";
       return $item;
    }

    // copied from NewsWebModule.php
  public function getFeed($index) {
      $feeds = $this->loadFeedData();
      if (isset($feeds[$index])) {
        
        $feedData = $feeds[$index];
		try {
			$modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultModel;
			$controller = NewsDataModel::factory($modelClass, $feedData);
		} catch (KurogoException $e) {
			$controllerClass = isset($feedData['CONTROLLER_CLASS']) ? $feedData['CONTROLLER_CLASS'] : self::$defaultController;
			$controller = DataController::factory($controllerClass, $feedData);
			$this->legacyController = true;
		}
		
        return $controller;
    } else {
        throw new KurogoConfigurationException($this->getLocalizedString('ERROR_INVALID_FEED', $index));
    }
  }

    private static function getPubDateUnixtime($story) {
        return strtotime($story->getPubDate());
    }
}