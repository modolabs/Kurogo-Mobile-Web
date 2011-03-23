<?php

class NewsAPIModule extends APIModule {

    protected $id = 'news';
    protected $vmin = 1;
    protected $vmax = 1;
    
    protected function initializeForCommand() {
        $feeds = $this->loadFeedData();

        switch($this->command) {
            case 'stories':
                $categoryID = $this->getArg('categoryID');
                $start = $this->getArg('start');
                $limit = $this->getArg('limit');

                $feed = $this->getFeed($categoryID);
                $items = $feed->items($start, $limit);
                $totalItems = $feed->getTotalItems();

                $stories = array();
                foreach ($items as $story) {
                    $item = array(
                        'GUID'        => $story->getGUID(),
                        'link'        => $story->getLink(),
                        'title'       => $story->getTitle(),
                        'description' => $story->getDescription(),
                        'pubDate'     => self::getPubDateUnixtime($story),
                        'body'        => $story->getContent(),
                    );

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
                    $stories[] = $item;
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
                    $response[] = array('id' => strval($index), 'title' => $feedData['TITLE']);
                }
                $this->setResponse($response);
                $this->setResponseVersion(1);

                break;

            default:
                 $this->invalidCommand();
                 break;
        }
    }

    // copied from NewsWebModule.php
    public function getFeed($index) {
        $feeds = $this->loadFeedData();
        if(isset($feeds[$index])) {
            $feedData = $feeds[$index];
            if (!isset($feedData['CONTROLLER_CLASS'])) {
                $feedData['CONTROLLER_CLASS'] = 'RSSDataController';
            }
            $controller = DataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $controller->setDebugMode($this->getSiteVar('DATA_DEBUG'));
            return $controller;
        } else {
            throw new Exception("Error getting news feed for index $index");
        }
    }

    private static function getPubDateUnixtime($story) {
        return strtotime($story->getPubDate());
    }
}