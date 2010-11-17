<?php

require_once realpath(LIB_DIR.'/Module.php');

class AdminModule extends Module {
  protected $id = 'admin';
  
  protected function initialize() {

  }

  protected function initializeForPage() {
        $tabs= array();
        $tabJavascripts = array();
        
        $allModules = $this->getAllModules();
        foreach ($allModules as $moduleID=>$moduleData) {
            try {
                $module = Module::factory($moduleID);
                $tabs[] = $moduleID;
            } catch(Exception $e) {
            }
        }

        $this->enableTabs($tabs, null, $tabJavascripts);
        
  }

}
