<?php

class ContentWebModule extends WebModule {
    protected $id = 'content';
	protected $contentGroups;
	protected $feedGroups = null;

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
    
    public static function getContentTypes() {
        return array(
            'html'=>'Static HTML',
            'html_url'=>'External HTML',
            'rss'=>'External RSS'
        );
    }

	protected function getDataForGroup($group) {
        if (!$this->feedGroups) {
             $this->feedGroups = $this->getFeedGroups();
        }
		
		if (isset($this->feedGroups[$group])) {

            if (!isset($this->feedGroups[$group]['DESCRIPTION'])) {
                $this->feedGroups[$group]['DESCRIPTION'] = $this->getOptionalModuleVar('description','','strings');
            }
            
            return $this->feedGroups[$group];            
        } else {
            throw new KurogoConfigurationException("Unable to find link group information for $group");
        }
    }
    
    public function getFeedGroups() {
        return $this->getModuleSections('feedgroups');
    }
	
	public function getItemsForGroup($group){
		return $this->getModuleSections('feeds-'.$group);
	}
		
	// overrides function in Module.php
	protected function loadFeedData($group=null) {	
		if(!$group){
			$items = $this->getModuleSections('feeds');            
			foreach ($items as $key => &$item) {
				if (isset($item['GROUP']) && strlen($item['GROUP'])) {
					$groupData = $this->getDataForGroup($item['GROUP']);
					if (!isset($item['TITLE']) && isset($groupData['TITLE'])) {
						$item['TITLE'] = $groupData['TITLE'];
					}
					$item['url'] = $this->buildBreadcrumbURL('group', array('group'=>$item['GROUP']));
				}else{
					$item['url'] = $this->buildBreadcrumbURL('page', array('page'=>$key));
				}
				$item['title'] = $item['TITLE'];
			}
		}else{
			$items = $this->getItemsForGroup($group);
			foreach($items as $key => &$item){
				if (!isset($item['TITLE']) && isset($groupData['TITLE'])) {
					$item['title'] = $groupData['TITLE'];
				}else{
					$item['title'] = $item['TITLE'];
				}
				$item['url'] = $this->buildBreadcrumbURL('page', array('group' => $group, 'page'=>$key));
			}
		}
		
        return $items;
    }
  
    protected function initializeForPage() {
        switch ($this->page) {
		
			case 'index':
				if (!$pages = $this->loadFeedData()) {
					$pages = array();
				}
				
				if(count($pages)==1){
					$feedData = reset($pages);

					if(isset($feedData['GROUP'])){
						$this->redirectTo('group', array('group' => $feedData['GROUP']));
					}
					$showTitle = isset($feedData['SHOW_TITLE']) ? $feedData['SHOW_TITLE'] : true;
					if ($showTitle) {
						$this->assign('contentTitle', $feedData['TITLE']);
					}
					
					$this->setTemplatePage('content');
					$this->setPageTitle($feedData['TITLE']);
					$this->assign('contentBody', $this->getContent($feedData));
				}else{
					$this->assign('description', $this->getOptionalModuleVar('description','','strings'));
					$this->assign('contentPages', $pages);
				}
				
				break;
			case 'page':
				$pageId = $this->getArg('page');
				$group = $this->getArg('group', false);
				$items = ($group === false) ? $this->loadFeedData() : $this->loadFeedData($group);

				if (!isset($items[$pageId])){
					$this->redirectTo('index');
				}
				
				$feedData = $items[$pageId];
				
				$showTitle = isset($feedData['SHOW_TITLE']) ? $feedData['SHOW_TITLE'] : true;
				if ($showTitle) {
					$this->assign('contentTitle', $feedData['TITLE']);
				}
				
				$this->setTemplatePage('content');
				$this->setPageTitle($feedData['TITLE']);
				$this->assign('contentBody', $this->getContent($feedData));
				
				break;
			case 'group':
				$group = $this->getArg('group');
				$groupData = $this->getDataForGroup($group);
				
                if (isset($groupData['TITLE'])) {
                    $this->setPageTitles($groupData['TITLE']);
                }
				
				$feedData = $this->loadFeedData($group);
				if(count($feedData)==1){
					reset($feedData);
					$this->redirectTo('page', array('group' => $group, 'page' => key($feedData)));
				}
				
				$this->setTemplatePage('index');
                $this->assign('contentPages', $feedData);
                $this->assign('description', $groupData['DESCRIPTION']);

				break;
        }
    }
}