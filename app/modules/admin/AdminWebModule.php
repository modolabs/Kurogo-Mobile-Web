<?php
/**
  * @package Module
  * @subpackage Admin
  */

/**
  * @package Module
  * @subpackage Admin
  */
class AdminWebModule extends WebModule {
    protected $id = 'admin';
  
    private function getNavSections() {
        $navSections = array(
            array(
                'id'=>'site',
                'title'=>'Site Configuration',
                'description'=>'',
                'url'=>$this->buildURL('site',array()),
            ),
            array(
                'id'=>'modules',
                'title'=>'Module Configuration',
                'description'=>'',
                'url'=>$this->buildURL('modules',array()),
            ),
        );
        
        return $navSections;
    }
    
    private function getSiteAdminConfig() {
        static $configData;
        if (!$configData) {
            $file = APP_DIR . "/common/config/admin-site.json";
            if (!$configData = json_decode(file_get_contents($file), true)) {
                throw new Exception("Error parsing $file");
            }
            
        }
        
        return $configData;
    }
    
    private function getSubNavSections($section) {
        $subNavSections = array();
        switch ($section) {
            case 'site':
                $configData = $this->getSiteAdminConfig();
                foreach ($configData as $id=>$data) {
                    $subNavSections[$id] = array(
                        'id'=>$id,
                        'title'=>$data['title'],
                        'url'=>$this->buildURL($section, array('section'=>$id))
                    );
                }
 
                break;
                
            case 'modules':
                $subNavSections['overview'] = array(
                    'id'=>'overview',
                    'title'=>'Modules Overview',
                    'url'=>$this->buildURL($section, array('section'=>'overview'))
                );
                $modules = array();
                foreach ($this->getAllModules() as $module) {
                    $subNavSections[$module->getConfigModule()] = array(
                        'id'=>$module->getConfigModule(),
                        'title'=>$module->getModuleName(),
                        'url'=>$this->buildURL('modules', array('section'=>$module->getConfigModule()))
                    );
                    $modules[$module->getConfigModule()] = array(
                        'id'=>$module->getConfigModule(),
                        'title'=>$module->getModuleName(),
                        'home'=>false,
                        'disabled'=>$module->getModuleVar('disabled'),
                        'protected'=>$module->getModuleVar('protected'),
                        'secure'=>$module->getModuleVar('secure'),
                        'search'=>$module->getModuleVar('search'),
                        'url'=>$this->buildURL('modules', array('section'=>$module->getConfigModule()))
                    );
                    
                }
                $this->assign('modules', $modules);
                break;
        }

        return $subNavSections;
    }

    protected function initialize() {
        $this->requiresAdmin();
    }
  
    protected function initializeForPage() {
        //make sure that only desktop devices can use the module
        if (!$GLOBALS['deviceClassifier']->isComputer() && $this->page !='index') {
            $this->redirectTo('index');
        }

        $navSections = $this->getNavSections();
        $this->assign('navSections', $navSections);
        $this->addJQuery();

        switch ($this->page)
        {
            case 'modules':
                $subNavSections = $this->getSubNavSections($this->page);
                $this->assign('subNavSections', $subNavSections);
        
                $defaultSubNavSection = key($subNavSections);
                $section = $this->getArg('section', $defaultSubNavSection);
                
                if ($section != $defaultSubNavSection) {
                    $modulePage = 'module';
                    try {
                        if ($module = WebModule::factory($section)) {
                            $this->assign('moduleName', $module->getModuleName());
                            $this->assign('moduleID', $module->getConfigModule());
                        }
                    } catch (Exception $e) {
                        $this->redirectTo($this->page, array());
                    }
                } else {
                    $modulePage = $defaultSubNavSection;
                }
                
                $this->assign('modulePage', $modulePage);
                
                break;
            case 'site':            
        
                $subNavSections = $this->getSubNavSections($this->page);
                $this->assign('subNavSections', $subNavSections);
        
                $defaultSubNavSection = key($subNavSections);
                $section = $this->getArg('section', $defaultSubNavSection);
                
                if (!isset($subNavSections[$section])) {
                    $this->redirectTo($this->page, array());
                }
                break;
                
            case 'index':
                //redirect desktop devices to the "default page"
                if ($GLOBALS['deviceClassifier']->isComputer()) {
                    $defaultSection = current($navSections);
                    $this->redirectTo($defaultSection['id'], array());
                }
                break;
            default:
                $this->redirectTo('index', array());
                break;
  
        }  

        $this->assign('section', $section);
  }

}
