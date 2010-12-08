<?php

require_once realpath(MODULES_DIR.'/home/HomeModule.php');

class CustomizeModule extends HomeModule {
  protected $id = 'customize';

  protected function setHomeScreenModuleOrder($moduleIDs) {
    $lifespan = $this->getSiteVar('MODULE_ORDER_COOKIE_LIFESPAN');
    $value = implode(",", $moduleIDs);
    
    setcookie("moduleorder", $value, time() + $lifespan, COOKIE_PATH);
    $_COOKIE["moduleorder"] = $value;
    error_log(__FUNCTION__.'(): '.print_r($value, true));
  }
  
  protected function setHomeScreenVisibleModules($moduleIDs) {
    $lifespan = $this->getSiteVar('MODULE_ORDER_COOKIE_LIFESPAN');
    $value = count($moduleIDs) ? implode(",", $moduleIDs) : 'NONE';
    
    setcookie("visiblemodules", $value, time() + $lifespan, COOKIE_PATH);
    $_COOKIE["visiblemodules"] = $value;
    error_log(__FUNCTION__.'(): '.print_r($value, true));
  }

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
                $info['visible'] = $args['action'] != 'on';
              }
              if ($info['visible']) { $visibleModuleIDs[] = $id; }
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
        $module = Module::factory($moduleID);
        $info['disableable'] = $module->getModuleVar('disableable');
        $info['movable'] = $module->getModuleVar('movable');
        if ($info['primary'] ) {
          $modules[$moduleID] = $info;
          $moduleIDs[] = $moduleID;
          $activeModuleIDs[] = $moduleID; 
        }
    }
    
    
    switch($this->pagetype)  {
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
            'action' => $modules[$id]['visible'] ? 'on' : 'off',
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
