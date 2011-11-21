<?php

class ContentAPIModule extends APIModule {

    protected $id = 'content';
    protected $vmin = 1;
    protected $vmax = 1;
	protected static $defaultModel = 'ContentDataModel';


    // From ContentWebModule.php
    protected function getContent($feedData) {

        $content_type = isset($feedData['CONTENT_TYPE']) ? $feedData['CONTENT_TYPE'] : '';
        $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultModel;

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
                if (!isset($feedData['PARSER_CLASS'])) {
                    $feedData['PARSER_CLASS'] = 'DOMDataParser';
                }
                
                $controller = ContentDataModel::factory($modelClass, $feedData);

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
                if (!isset($feedData['PARSER_CLASS'])) {
                    $feedData['PARSER_CLASS'] = 'RSSDataParser';
                }

                $controller = ContentDataModel::factory($controllerClass, $feedData);
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
            case 'feeds': // pre 1.0
            case 'pages': // 1.0
                $pages = array();

                foreach ($feeds as $page => $feedData) {
                    $pages[] = array(
                        'key' => $page,
                        'title' => $feedData['TITLE'],
                        'subtitle' => isset($feedData['SUBTITLE']) ? $feedData['SUBTITLE'] : '',
                        'showTitle' => isset($feedData['SHOW_TITLE']) ? $feedData['SHOW_TITLE'] : false,
                        //'url' => isset($feedData['BASE_URL']) ? $feedData['BASE_URL'] : ''//$this->buildBreadCrumbURL($page, array())
                    );
                }

                $response = array(
                    'totalFeeds' => count($feeds),
                    'pages' => $pages,
                );

                $this->setResponse($response);
                $this->setResponseVersion(1);

                break;

            case 'page': // 1.0
            case 'getFeed': // pre 1.0

                $filter = $this->getArg('key');
                if ($filter) {
                    $feedData = $feeds[$filter];
                    $feedBody = $this->getContent($feedData);

                    $this->setResponse($feedBody);
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
