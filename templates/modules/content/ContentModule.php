<?php

abstract class ContentModule extends Module {
   protected $id = 'content';
   
   protected function getContent($feedData)
   {
        $content_type = isset($feedData['CONTENT_TYPE']) ? $feedData['CONTENT_TYPE'] : '';
        
        switch ($content_type)
        {
            case 'html':
                $controller = HTMLDataController::factory($feedData);
                if (isset($feedData['HTML_ID'])) {
                    $content = $controller->getContentById($feedData['HTML_ID']);
                } else {
                    $content = $controller->getContent();
                }
                
                return $content;
                break;
            case 'rss':
                $controller = RSSDataController::factory($feedData);
                if ($item = $controller->getItemByIndex(0)) {
                    return $item->getContent();
                }
                
                return '';
                break;
            default:
                throw new Exception("Invalid content type $content_type");
        }
        
   }
  
  protected function initializeForPage() {
    
    $feeds = $this->loadFeedData();

    switch ($this->page) {
        case 'index':
            if (count($feeds)==1) {
                $this->redirectTo(key($this->page));
            } 
            
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
        default:
            if (!isset($feeds[$this->page])) {
                $this->redirectTo('index');
            } 
            
            $feedData = $feeds[$this->page];
            
            $this->setPageTitle($feedData['TITLE']);
            $this->setTemplatePage('content');
            $this->assign('contentTitle', $feedData['TITLE']);
            $this->assign('contentBody', $this->getContent($feedData));
            break;
    }
  }
  
}
