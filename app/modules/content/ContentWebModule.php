<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('DataModel');
class ContentWebModule extends WebModule {
    protected $id = 'content';
    protected $pageId;
    protected $group;
	protected $contentGroups;
	protected $feedGroups = null;
	protected static $defaultModel = 'ContentDataModel';

   protected function getContent($feedData) {
   
        $content_type = isset($feedData['CONTENT_TYPE']) ? $feedData['CONTENT_TYPE'] : 'html_url';
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
                } elseif (isset($feedData['HTML_CLASS']) && strlen($feedData['HTML_CLASS'])>0) {
                    $content = $controller->getContentByClass($feedData['HTML_CLASS']);
                } elseif (isset($feedData['KUROGO_READER']) && $feedData['KUROGO_READER']) {
                    $reader = new KurogoReader($feedData['BASE_URL']);
                    $content = $reader->getContent();
                } else {
                    $content = $controller->getContent();
                }
                
                $content = $this->filterContentByPage($content, $this->pageId, $this->group);
                return $content;
                break;
            case 'rss':
                if (!isset($feedData['PARSER_CLASS'])) {
                    $feedData['PARSER_CLASS'] = 'RSSDataParser';
                }

                $controller = ContentDataModel::factory($modelClass, $feedData);
                if ($item = $controller->getItemByIndex(0)) {
                    return $item->getContent();
                }
                
                return '';
                break;
            default:
                throw new KurogoConfigurationException("Invalid content type $content_type");
        }
        
    }

    protected function filterContentByPage($content, $page, $group) {
        return $content;
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
				$this->pageId = $this->getArg('page');
				$this->group = $this->getArg('group', false);
				$items = ($this->group === false) ? $this->loadFeedData() : $this->loadFeedData($this->group);

				if (!isset($items[$this->pageId])){
					$this->redirectTo('index');
				}
				
				$feedData = $items[$this->pageId];
				
				$showTitle = isset($feedData['SHOW_TITLE']) ? $feedData['SHOW_TITLE'] : true;
				if ($showTitle) {
					$this->assign('contentTitle', $feedData['TITLE']);
				}
				
				$this->setPageTitle($feedData['TITLE']);
				$this->assign('contentBody', $this->getContent($feedData));
				
				break;
			case 'group':
				$this->group = $this->getArg('group');
				$groupData = $this->getDataForGroup($this->group);
				
                if (isset($groupData['TITLE'])) {
                    $this->setPageTitles($groupData['TITLE']);
                }
				
				$feedData = $this->loadFeedData($this->group);
				if(count($feedData)==1){
					reset($feedData);
					$this->redirectTo('page', array('group' => $this->group, 'page' => key($feedData)));
				}
				
                $this->assign('contentPages', $feedData);
                $this->assign('description', $groupData['DESCRIPTION']);

				break;
        }
    }
}
