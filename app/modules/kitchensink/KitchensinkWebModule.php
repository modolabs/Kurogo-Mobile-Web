<?php

class KitchensinkWebModule extends WebModule {
    protected $id = 'kitchensink';
    
    protected function getListsForPage($page) {
        $lists = array();
    
        $supportedFields = array(
            'titles'    => 'title',
            'subtitles' => 'subtitle',
            'urls'      => 'url',
            'imgs'      => 'img',
            'classes'   => 'class',
        );
        
        $configs = $this->loadPageConfigFile($page, false);
        foreach ($configs as $config) {
            if (!isset($config['titles'])) { continue; }
            
            $list = array(
                'description' => self::argVal($config, 'description', 'list'),
                'items' => array(),
            );
            foreach ($config['titles'] as $i => $title) {
                $item = array();
                foreach ($supportedFields as $fieldArray => $supportedField) {
                    if (isset($config[$fieldArray], $config[$fieldArray][$i])) {
                        $item[$supportedField] = $config[$fieldArray][$i];
                    }
                }
                if (!isset($item['url'])) {
                    $item['url'] = "#";
                }
                $list['items'][] = $item;
            }
            $lists[] = $list;
        }
        
        return $lists;
    }
    
    protected function initializeForWebBridgePage() {
        // Native template support
        // specify anything that goes into the header or footer here
        // and force the appearance of assets so they get loaded into the template
        
        // All the data in this module is static!
        $this->initializeForPage();
    }
    
    protected function initializeForPage() {
        switch ($this->page) {
            case 'index':
                $this->assign('links', array(
                    array(
                        'title' => 'Text',
                        'url'   => $this->buildBreadcrumbURL('text', array()),
                    ),
                    array(
                        'title' => 'Navigation Lists',
                        'url'   => $this->buildBreadcrumbURL('nav', array()),
                    ),
                    array(
                        'title' => 'Results Lists',
                        'url'   => $this->buildBreadcrumbURL('results', array()),
                    ),
                    array(
                        'title' => 'Search',
                        'url'   => $this->buildBreadcrumbURL('search', array()),
                    ),
                    array(
                        'title' => 'Detail',
                        'url'   => $this->buildBreadcrumbURL('detail', array()),
                    ),
                ));
                break;
                
            case 'text':
                break;
                
            case 'search':
                break;
                
            case 'nav':
            case 'results':
                $this->assign('lists', $this->getListsForPage($this->page));
                break;
                
            case 'articles':
                $this->setWebBridgePageRefresh(true);
                $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                $this->addOnLoad('setupListing();');
                $this->assign('articles', $this->getListsForPage($this->page));
                break;
                
            case 'detail':
                $detailConfig = $this->loadPageConfigFile('detail', 'detailConfig');
                
                if ($this->getOptionalModuleVar('SHARING_ENABLED', 1)) {
                    $this->assign('shareTitle', $this->getLocalizedString('SHARE_THIS_ITEM'));
                    $this->assign('shareEmailURL', 'john.smith@gmail.com');
                    $this->assign('shareRemark',   'This is a share remark');
                    $this->assign('shareURL',      'This is a share URL');
                }
                if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $this->generateBookmarkOptions('fakeid');
                }
                $this->enableTabs(array_keys($detailConfig['tabs']));
                break;

        }
    }
}
