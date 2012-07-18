<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
                    $args = $this->args;
                    if (isset($item['title'])) {
                        $args['title'] = $item['title'];
                    }
                    $item['url'] = $this->buildBreadcrumbURL($this->page, $args);
                }
                $list['items'][] = $item;
            }
            $lists[] = $list;
        }
        
        return $lists;
    }
    
    protected function initializeForPage() {
        switch ($this->page) {
            case 'index':
                $links = array(
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
                );
                if ($this->browser == 'native') {
                    $links[] = array(
                        'title' => 'AppQ Dialogs',
                        'url'   => $this->buildBreadcrumbURL('dialogs', array()),
                    );
                    $links[] = array(
                        'title' => 'Truncation Form Post',
                        'url'   => $this->buildBreadcrumbURL('truncate', array()),
                    );
                }
                $this->assign('links', $links);
                break;
                
            case 'text':
                break;
                
            case 'search':
                $formFields = $this->loadPageConfigFile($this->page, false);
                foreach ($formFields as $i => $formField) {
                    if (isset($formField['option_keys'])) {
                        $options = array();
                        foreach ($formField['option_keys'] as $j => $optionKey) {
                            $options[$optionKey] = $formField['option_values'][$j];
                        }
                        $formFields[$i]['options'] = $options;
                        unset($formFields[$i]['option_keys']);
                        unset($formFields[$i]['option_values']);
                    }
                }
                $this->assign('formFields', $formFields);
                break;
                
            case 'results':
                if ($title = $this->getArg('title')) {
                    $this->setPageTitles($title);
                }
                $this->assign('next',    'Next');
                $this->assign('prev',    'Prev');
                $this->assign('nextURL', $this->buildBreadcrumbURL($this->page, $this->args, false));
                $this->assign('prevURL', $this->buildBreadcrumbURL($this->page, $this->args, false));
                $this->assign('lists',   $this->getListsForPage($this->page));
                break;

            case 'nav':
                if ($title = $this->getArg('title')) {
                    $this->setPageTitles($title);
                }
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
                
            case 'dialogs':
                $buttons = array();
                
                $configs = $this->loadPageConfigFile($this->page, false);
                foreach ($configs as $config) {
                    if (!isset($config['title'], 
                               $config['description'], 
                               $config['api'], 
                               $config['arguments'])) {
                        continue;
                    }
                    
                    $buttons[] = array(
                        'title'       => $config['title'],
                        'description' => $config['description'],
                        'javascript'  => "kgoBridge.{$config['api']}(".implode(', ', $config['arguments']).
                            ", null, function (error, params) { alert('You clicked button type \''+params['button']+'\''); }); return false;",
                    );
                }
                
                $this->assign('buttons', $buttons);
                break;
                
            case 'truncate':
                $this->assign('action', $this->buildBreadcrumbURL('truncated', array()));
                break;
                
            case 'truncated':
                $length = $this->getArg('length', 0);
                $margin = $this->getArg('margin', 0);
                $minLineLength = $this->getArg('minLineLength', 40);
                $html = $this->getArg('html', '');
                if ($length && $margin && $html) {
                    $html = Sanitizer::sanitizeAndTruncateHTML($html, $truncated, $length, $margin, $minLineLength);
                }
                $this->assign('html', $html);
                break;
        }
    }
    
    protected function initializeForNativeTemplatePage() {
        // Native template support
        // specify anything that goes into the header or footer here
        // and force the appearance of assets so they get loaded into the template
        
        // All the data in this module is static!
        $this->initializeForPage();
    }
}
