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
        static $configFile;
        if (!$configFile) {
            $configFile = ConfigFile::factory(MODULES_DIR . "/admin/config/admin-site.ini", 'file');
        }
        
        return $configFile;
    }
    
    private function getSubNavSections($section) {
        $subNavSections = array();
        switch ($section) {
            case 'site':
                $config = $this->getSiteAdminConfig();
                $sections = $config->getSection('sections');
                foreach ($sections as $id=>$title) {
                    $subNavSections[] = array(
                        'id'=>$id,
                        'title'=>$title,
                        'description'=>'',
                        'url'=>$this->buildURL($section, array('section'=>$id))
                    );
                }
 
                break;
            case 'modules':
                $subNavSections[] = array(
                    'id'=>'overview',
                    'title'=>'Modules Overview',
                    'description'=>'',
                    'url'=>$this->buildURL($section, array('section'=>'overview'))
                );
                break;
        }

        return $subNavSections;
    }
  
    protected function initializeForPage() {
        //make sure that only desktop devices can use the module
        if (!$GLOBALS['deviceClassifier']->isComputer() && $this->page !='index') {
            $this->redirectTo('index');
        }

        $this->addJQuery();
        $navSections = $this->getNavSections();
        $this->assign('navSections', $navSections);
        $subNavSections = $this->getSubNavSections($this->page);
        $this->assign('subNavSections', $subNavSections);
        $defaultSubNavSection = isset($subNavSections[0]) ? $subNavSections[0]['id'] : '';
        $section = $this->getArg('section', $defaultSubNavSection);

        switch ($this->page)
        {
            case 'modules':
                $section = $this->getArg('section', '');
                break;                
            case 'site':
                break;
                
            case 'index':
                //redirect desktop devices to the "default page"
                if ($GLOBALS['deviceClassifier']->isComputer()) {
                    $defaultSection = current($navSections);
                    $this->redirectTo($defaultSection['id']);
                }
                break;
            default:
                $this->redirectTo('index');
                break;
  
        }  

        $this->assign('section', $section);
  }

}
