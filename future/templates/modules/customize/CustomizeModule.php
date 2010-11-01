<?php

require_once realpath(LIB_DIR.'/Module.php');

class CustomizeModule extends Module {
  protected $id = 'customize';

  private function handleRequest($args) {
    if (isset($args['action'])) {
      $currentModules = $this->getHomeScreenModules();
      
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
            
            $this->setHomeScreenModuleOrder($currentIDs);
          }
          break;
          
        case 'on':
        case 'off':
          if (isset($args['module'])) {
            $visibleModuleIDs = array();
            
            foreach ($currentModules as $id => &$info) {
              if ($id == $args['module']) {
                $info['disabled'] = $args['action'] != 'on';
              }
              if (!$info['disabled']) { $visibleModuleIDs[] = $id; }
            }
            
            $this->setHomeScreenVisibleModules($visibleModuleIDs);
          }
          break;
        
        default:
          error_log(__FUNCTION__."(): Unknown action '{$_REQUEST['action']}'");
          break;
      }
    }
  }

  protected function initializeForPage() {
    $this->handleRequest($this->args);

    $modules = array();
    $moduleIDs = array();
    $activeModuleIDs = array();
    $newCount = 0;

    foreach ($this->getHomeScreenModules() as $moduleID => $info) {
      if ($info['primary']) {
        $modules[$moduleID] = $info;
        
        $moduleIDs[] = $moduleID;
        if (!$info['disabled']) { 
          $activeModuleIDs[] = $moduleID; 
        }
        
        if ($info['new']) { 
          $newCount++; 
        }
      }
    }
    
    switch($this->pagetype) {
      case 'compliant':
        $this->addInlineJavascript('var httpRoot = "'.COOKIE_PATH.'"');
        $this->addInlineJavascriptFooter('init();');
        
        switch ($GLOBALS['deviceClassifier']->getPlatform()) {
          case 'iphone':
            break;
          
          default:
            $this->addInlineJavascript(
              'var modules = '.json_encode($moduleIDs).';'.
              'var activeModules = '.json_encode($activeModuleIDs).';'
            );
            break;
        }
        break;
        
      case 'basic':
        foreach ($moduleIDs as $index => $id) {
          $modules[$id]['toggleDisabledURL'] = 'index.php?'.http_build_query(array(
            'action' => $modules[$id]['disabled'] ? 'on' : 'off',
            'module' => $id,
          ));
          
          if ($index > 0) {
            $modules[$id]['swapUpURL'] = 'index.php?'.http_build_query(array(
              'action'    => 'swap',
              'module1'   => $id,
              'module2'   => $moduleIDs[$index-1],
            ));
          }
          if ($index < (count($moduleIDs)-1)) {
            $modules[$id]['swapDownURL'] = 'index.php?'.http_build_query(array(
              'action'    => 'swap',
              'module1'   => $id,
              'module2'   => $moduleIDs[$index+1],
            ));
          }
        }
        break;
        
      default:
        break;
    }    
    
    $this->assignByRef('modules', $modules);
    $this->assignByRef('newCount', $newCount);
  }
}
