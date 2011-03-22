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
            if (!$configData = json_decode(file_get_contents(MODULES_DIR . "/admin/config/admin-site.json"), true)) {
                throw new Exception("Error parsing " . MODULES_DIR . "/admin/config/admin-site.json");
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
                        'url'=>self::buildURLForModule($module->getConfigModule(), 'admin', array())
                    );
                    $modules[$module->getConfigModule()] = array(
                        'id'=>$module->getConfigModule(),
                        'title'=>$module->getModuleName(),
                        'home'=>false,
                        'disabled'=>$module->getModuleVar('disabled'),
                        'protected'=>$module->getModuleVar('protected'),
                        'secure'=>$module->getModuleVar('secure'),
                        'search'=>$module->getModuleVar('search')
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

        switch ($this->page)
        {
            case 'modules':
            case 'site':            
                $this->addJQuery();
                $this->assign('navSections', $navSections);
        
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
