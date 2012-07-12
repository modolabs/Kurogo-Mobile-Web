<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
  * @package Module
  * @subpackage Customize
  */

/**
  * @package Module
  * @subpackage Customize
  */
class CustomizeWebModule extends WebModule {
    protected $id = 'customize';
    protected $canBeHidden = false;
    protected $defaultAllowRobots = false; // Require sites to intentionally turn this on
    
    private function getModuleCustomizeList() {    
        $navModules = $this->getAllModuleNavigationData(self::INCLUDE_DISABLED_MODULES);
        return $navModules['primary'];
    }

  private function handleRequest($args) {
    if (isset($args['action'])) {
      $currentModules = $this->getModuleCustomizeList();
      
      switch ($args['action']) {
        case 'swap':
          $currentIDs = array_keys($currentModules);
          
          if (isset($args['module1'], $args['module2']) && 
              in_array($args['module1'], $currentIDs) && 
              in_array($args['module2'], $currentIDs)) {
              
            foreach ($currentIDs as $index => &$id) {
              if ($id == $args['module1']) {
                $id = $args['module2'];
              } else if ($id == $args['module2']) {
                $id = $args['module1'];
              }
            }
            
            $this->setNavigationModuleOrder($currentIDs);
          }
          break;
          
        case 'on':
        case 'off':
          if (isset($args['module'])) {
            $disabledModuleIDs = array();
            
            foreach ($currentModules as $id => &$info) {
              if ($id == $args['module']) {
                $info['disabled'] = $args['action'] != 'on';
              }
              if ($info['disabled']) { $disabledModuleIDs[] = $id; }
            }
            
            $this->setNavigationHiddenModules($disabledModuleIDs);
          }
          break;
        
        default:
          Kurogo::log(LOG_WARNING,__FUNCTION__."(): Unknown action '{$_REQUEST['action']}'",'module');
          break;
      }
    }
  }

  protected function initializeForPage() {
    $this->handleRequest($this->args);

    $modules = $this->getModuleCustomizeList();
    $moduleIDs = array();
    $disabledModuleIDs = array();
    
    foreach ($modules as $id => $info) {
      $moduleIDs[] = $id; 
      if ($info['disabled']) { 
        $disabledModuleIDs[] = $id; 
      }
    }
    
    switch($this->pagetype) {
      case 'compliant':
      case 'tablet':
         $this->addInlineJavascript(
          'var modules = '.json_encode($moduleIDs).';'.
          'var disabledModules = '.json_encode($disabledModuleIDs).';'.
          'var MODULE_ORDER_COOKIE = "'.self::MODULE_ORDER_COOKIE.'";'.
          'var DISABLED_MODULES_COOKIE = "'.self::DISABLED_MODULES_COOKIE.'";'.
          'var MODULE_ORDER_COOKIE_LIFESPAN = '.Kurogo::getSiteVar('MODULE_ORDER_COOKIE_LIFESPAN').';'.
          'var COOKIE_PATH = "'.COOKIE_PATH.'";'
        );
        $this->addInlineJavascriptFooter('init();');
        break;
      
      case 'touch':
      case 'basic':
        foreach ($moduleIDs as $index => $id) {
          $modules[$id]['toggleDisabledURL'] = $this->buildBreadcrumbURL('index', array(
            'action' => $modules[$id]['disabled'] ? 'on' : 'off',
            'module' => $id,
          ), false);
          
          if ($index > 0) {
            $modules[$id]['swapUpURL'] = $this->buildBreadcrumbURL('index', array(
              'action'    => 'swap',
              'module1'   => $id,
              'module2'   => $moduleIDs[$index-1],
            ), false);
          }
          if ($index < (count($moduleIDs)-1)) {
            $modules[$id]['swapDownURL'] = $this->buildBreadcrumbURL('index', array(
              'action'    => 'swap',
              'module1'   => $id,
              'module2'   => $moduleIDs[$index+1],
            ), false);
          }
        }
        break;
        
      default:
        break;
    }    
    
    $this->assignByRef('modules', $modules);
  }
}
