<?php

abstract class ContentWebModule extends WebModule {
    protected $id = 'content';

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
                throw new Exception("Invalid content type $content_type");
        }
        
    }
    
    public static function getContentTypes() {
        return array(
            'html'=>'Static HTML',
            'html_url'=>'External HTML',
            'rss'=>'External RSS'
        );
    }
  
    protected function initializeForPage() {
    
        if (!$feeds = $this->loadFeedData()) {
            $feeds = array();
        }
        
        switch ($this->page) {
            case 'index':
                if (count($feeds)==1) {
                    $this->page = key($feeds);
                } else {
                
                    $pages = array();
                    foreach ($feeds as $page=>$feedData) {
                        $pages[] = array(
                            'title'=>$feedData['TITLE'],
                            'subtitle'=>isset($feedData['SUBTITLE']) ? $feedData['SUBTITLE'] : '',
                            'url'=>$this->buildBreadCrumbURL($page, array())
                        );
                    }
                    
                    $this->assign('contentPages', $pages);
                    break;
                }
            default:
                if (!isset($feeds[$this->page])) {
                    $this->redirectTo('index');
                } 
                
                $feedData = $feeds[$this->page];
                
                $this->setPageTitle($feedData['TITLE']);
                $this->setTemplatePage('content');
                $showTitle = isset($feedData['SHOW_TITLE']) ? $feedData['SHOW_TITLE'] : true;
                if ($showTitle) {
                    $this->assign('contentTitle', $feedData['TITLE']);
                }
                $this->assign('contentBody', $this->getContent($feedData));
                break;
        }
    }
  
}
