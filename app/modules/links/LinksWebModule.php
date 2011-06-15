<?php
/**
  * @package Module
  * @subpackage Links
  */

/**
  * @package Module
  * @subpackage Links
  */
class LinksWebModule extends WebModule {
    protected $id = 'links';
    protected $linkGroups;

    protected function getLinkGroup($group) {
        if (!$this->linkGroups) {
            $this->linkGroups = $this->getModuleSections('links-groups');
        }
        
        if (isset($this->linkGroups[$group])) {
            if (!isset($this->linkGroups[$group]['links'])) {
                $this->linkGroups[$group]['links'] = $this->getModuleSections('links-' . $group);
            }

            if (!isset($this->linkGroups[$group]['description'])) {
                $this->linkGroups[$group]['description'] = $this->getModuleVar('description','strings');
            }
            
            return $this->linkGroups[$group];            
        } else {
            throw new Exception("Unable to find link group information for $group");
        }
    }

    public function getLinks() {
        return $this->getModuleSections('links');
    }
    
    protected function getLinkData() {
        $links = $this->getLinks();
                
        foreach ($links as &$link) {
            if (isset($link['icon']) && strlen($link['icon'])) {
                $link['img'] = "/modules/{$this->configModule}/images/{$link['icon']}{$this->imageExt}";
            }

            if (isset($link['group']) && strlen($link['group'])) {
                $group = $this->getLinkGroup($link['group']);
                if (!isset($link['title']) && isset($group['title'])) {
                    $link['title'] = $group['title'];
                }
                $link['url'] = $this->buildBreadcrumbURL('group', array('group'=>$link['group']));
            }
        }
        
        return $links;
    }
    
    protected function initializeForPage() {
    
        switch ($this->page) {
        
            case 'group':
                $group = $this->getLinkGroup($this->getArg('group'));
                if (isset($group['title'])) {
                    $this->setPageTitles($group['title']);
                }
                
                $displayType = isset($group['display_type']) ? $group['display_type'] : $this->getModuleVar('display_type');
                $this->assign('links', $group['links']);
                $this->assign('displayType', $displayType);
                $this->assign('description', $group['description']);
                break;
            
            case 'index':
            
                $links = $this->getLinkData();
                $this->assign('description', $this->getModuleVar('description','strings'));
                $this->assign('displayType', $this->getModuleVar('display_type'));
                $this->assign('links',       $links);
        }
    }
}
