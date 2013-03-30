<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class ContentAPIModule extends APIModule {

    protected $id = 'content';
    protected $vmin = 1;
    protected $vmax = 1;
    protected $pageId;
    protected $group;
    protected $feedGroups = null;
	protected static $defaultModel = 'ContentDataModel';
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
			}
		}

        return $items;
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
    // From ContentWebModule.php
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

    protected function filterContentByPage($content, $page, $group) {
        return $content;
    }

     protected function initializeForCommand() {
        if (!$feeds = $this->loadFeedData()) {

            $feeds = array();
        }

        switch ($this->command) {
            case 'feeds': 
            case 'pages': 
            	$group = $this->getArg('group', false);
                $pages = array();
                
				if(isset($group)  && $group){
					// return specified group 
					$feeds = $this->loadFeedData($group);
					foreach ($feeds as $page => $feedData) {
		                    $pages[] = array(
		                        'key' => $page,
		                        'title' => $feedData['TITLE'],
		                        'subtitle' => isset($feedData['SUBTITLE']) ? $feedData['SUBTITLE'] : '',
		                        'group' => $group,
		                    );
					}
				} else{
					// return all feeds
	                foreach ($feeds as $page => $feedData) {
	                	if(isset($feedData['GROUP'])  && $feedData['GROUP']){
		                    $pages[] = array(
		                        'key' => $page,
		                        'title' => $feedData['TITLE'],
		                        'subtitle' => isset($feedData['SUBTITLE']) ? $feedData['SUBTITLE'] : '',
		                        'group' => $feedData['GROUP'],
		                    );
	                	} else {
		                    $pages[] = array(
		                        'key' => $page,
		                        'title' => $feedData['TITLE'],
		                        'subtitle' => isset($feedData['SUBTITLE']) ? $feedData['SUBTITLE'] : '',
		                        'showTitle' => isset($feedData['SHOW_TITLE']) ? $feedData['SHOW_TITLE'] : false,
		                        //'url' => isset($feedData['BASE_URL']) ? $feedData['BASE_URL'] : ''//$this->buildBreadCrumbURL($page, array())
		                    );
	                	}
	                }
				}
	            
				$response = array(
						'totalFeeds'	=> count($pages),
						'pages'	=> $pages,
					
				);
                $this->setResponse($response);
                $this->setResponseVersion(1);

                break;
            case 'groups':
				$feedGroups = $this->getModuleSections('feedgroups');
				
                foreach ($feedGroups as $groupName => $groupData) {
	                    $groups[] = array(
	                        'key' => $groupName,
	                        'title' => $groupData['TITLE'],
	                        'description' => isset($groupData['DESCRIPTION']) ? $groupData['DESCRIPTION'] : '',
	                    );
                }
                
                $response = array(
                		'groups'	=> $groups,
                );
                $this->setResponse($response);
                $this->setResponseVersion(1);
            	break;
            case 'page': 
            case 'getFeed': 
        		$this->pageId = $this->getArg('key');
				$this->group = $this->getArg('group', false);
				$items = ($this->group === false) ? $this->loadFeedData() : $this->loadFeedData($this->group);
				
				$feedData = $items[$this->pageId];
				
				$title = $feedData['TITLE']?$feedData['TITLE']:'';
				
				$feedbody = $this->getContent($feedData);
				
                $this->setResponse($feedbody);
                $this->setResponseVersion(1);
				break;
            default:
                $this->invalidCommand();
                $this->setResponseVersion(1);
                break;
        }
    }
     
}

?>
