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

            if (!isset($this->linkGroups[$group]['description'])) {
                $this->linkGroups[$group]['description'] = $this->getModuleVar('description','strings');
            }
            
            return $this->linkGroups[$group];            
        } else {
            throw new KurogoConfigurationException($this->getLocalizedString('ERROR_LINK_GROUP', $group));
        }
    }

    public function getLinks($group=null) {
        return $group ? $this->getModuleSections('links-' . $group) : $this->getModuleSections('links');
    }
    
    protected function getLinkData($group=null) {
        $links = $this->getLinks($group);
                
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
                $groupSection = $this->getArg('group');
                $group = $this->getLinkGroup($groupSection);
                if (isset($group['title'])) {
                    $this->setPageTitles($group['title']);
                }
                                
                $displayType = isset($group['display_type']) ? $group['display_type'] : $this->getModuleVar('display_type');

                $this->assign('links', $this->getLinkData($groupSection));
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
