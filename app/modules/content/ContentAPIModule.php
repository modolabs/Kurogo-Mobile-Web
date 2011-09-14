<?php

class ContentAPIModule extends APIModule {

    protected $id = 'content';
    protected $vmin = 1;
    protected $vmax = 1;


    // From ContentWebModule.php
    protected function getContent($feedData) {

        $content_type = isset($feedData['CONTENT_TYPE']) ? $feedData['CONTENT_TYPE'] : '';
        switch ($content_type)
        {
            case 'html':
                $content = isset($feedData['CONTENT_HTML']) ? $feedData['CONTENT_HTML'] : '';
                if (is_array($content)) {
                    $content = implode("\n", $content);
                }
                return $content;
                break;
            case 'html_url':
                if (!isset($feedData['CONTROLLER_CLASS'])) {
                    $feedData['CONTROLLER_CLASS'] = 'HTMLDataController';
                }
                $controller = DataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
                if (isset($feedData['HTML_ID']) && strlen($feedData['HTML_ID'])>0) {
                    $content = $controller->getContentById($feedData['HTML_ID']);
                } elseif (isset($feedData['HTML_TAG']) && strlen($feedData['HTML_TAG'])>0) {
                    $content = $controller->getContentByTag($feedData['HTML_TAG']);
                } else {
                    $content = $controller->getContent();
                }
                
                return $content;
                break;
            case 'rss':
                if (!isset($feedData['CONTROLLER_CLASS'])) {
                    $feedData['CONTROLLER_CLASS'] = 'RSSDataController';
                }
                $controller = DataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
                if ($item = $controller->getItemByIndex(0)) {
                    return $item->getContent();
                }
                
                return '';
                break;
            default:
                throw new KurogoConfigurationException("Invalid content type $content_type");
        }
    }

     protected function initializeForCommand() {
        if (!$feeds = $this->loadFeedData()) {

            $feeds = array();
        }

        switch ($this->command) {
            case 'feeds':
                $pages = array();

                $feedCounter = 0;
                foreach ($feeds as $page => $feedData) {
                    reset($feeds);
                    $count = 0;

                    while ($count < $feedCounter) {
                        next($feeds);
                        $count++;
                    }
                    
                    $pages[] = array(
                        'key' => key($feeds),
                        'title' => $feedData['TITLE'],
                        'subtitle' => isset($feedData['SUBTITLE']) ? $feedData['SUBTITLE'] : '',
                        'url' => isset($feedData['BASE_URL']) ? $feedData['BASE_URL'] : ''//$this->buildBreadCrumbURL($page, array())
                    );

                    $feedCounter++;
                }

                $feedCount = count($feeds);

                $feedToReturn = array();
                if ($feedCount != 1) {
                    // do nothing
                } else {

                    $feedToReturn['title'] = $feedData['TITLE'];
                    $showTitle = isset($feedData['SHOW_TITLE']) ? $feedData['SHOW_TITLE'] : true;

                    $feedToReturn['showTitle'] = $showTitle;
                    $feedToReturn['contentBody'] = $this->getContent($feedData);

                }

                $response = array(
                    'totalFeeds' => $feedCount,
                    'pages' => $pages,
                    'feedData' => $feedToReturn
                );

                $this->setResponse($response);
                $this->setResponseVersion(1);

                break;

            case 'getFeed':

                if ($filter = $this->getArg('key')) {

                    $feedData = $feeds[$filter];

                    $feedToReturn['title'] = $feedData['TITLE'];
                    $showTitle = isset($feedData['SHOW_TITLE']) ? $feedData['SHOW_TITLE'] : true;

                    $feedToReturn['showTitle'] = $showTitle;
                    $feedToReturn['contentBody'] = $this->getContent($feedData);

                    $response = array ('feedData' => $feedToReturn);

                    $this->setResponse($response);
                    $this->setResponseVersion(1);
                }
                else {
                    $this->invalidCommand();
                    $this->setResponseVersion(1);
                }

                break;

            default:
                $this->invalidCommand();
                $this->setResponseVersion(1);
                break;
        }
    }
     
}

?>
