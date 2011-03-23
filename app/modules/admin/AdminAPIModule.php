<?php

class AdminAPIModule extends APIModule
{
    protected $id = 'admin';
    protected $vmin = 1;
    protected $vmax = 1;
    public function availableVersions() {
        return array(1);
    }
    
    private function getUnconstantedValue($value, &$constant) {
        $constCheck = array(
            'FULL_URL_BASE'=>FULL_URL_BASE,
            'LOG_DIR'=>LOG_DIR,
            'LIB_DIR'=>LIB_DIR,
            'CACHE_DIR'=>CACHE_DIR,
            'DATA_DIR'=>DATA_DIR,
            'SITE_DIR'=>SITE_DIR,
            'ROOT_DIR'=>ROOT_DIR
        );
        
        $constant = '';
        foreach ($constCheck as $const=>$constValue) {
            $i = strpos($value, $constValue);
            if ($i !== false) {
                if ($i==0) {
                    $value = substr($value, $i+strlen($constValue)+1);
                    $constant = $const;
                }
            }
        }
        
        return $value;
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

    private function getModuleAdminData(Module $module) {
        $configData = $module->getModuleAdminConfig();

        foreach ($configData as $section=>&$sectionData) {
            $sectionVars = $module->getModuleSection($section);
            foreach ($sectionData['fields'] as &$field) {
                switch ($field['config'])
                {
                    case 'module':
                        $field['value'] = isset($sectionVars[$field['key']]) ? $sectionVars[$field['key']] : $module->getModuleVar($field['key']);
                        break;
                }
                
                switch ($field['type']) 
                {
                    case 'select':
                        if (isset($field['optionsMethod'])) {
                            $field['options'] = call_user_func($field['optionsMethod']);
                            unset($field['optionsMethod']);
                        }
                }
            }
        }
    
        return $configData;
    }
    
    private function getSiteAdminData($section) {
        $configData = $this->getSiteAdminConfig();
        if (!isset($configData[$section])) {
            throw new Exception("Invalid section $section");
        }
        
        $sectionData = $configData[$section];
        $sectionData['section'] = $section;
        
        foreach ($sectionData['fields'] as &$field) {
            switch ($field['config'])
            {
                case 'config':
                    $field['value'] = $this->getUnconstantedValue($this->getSiteVar($field['key']), $constant);
                    if ($constant) {
                        $field['constant'] = $constant;
                    }
                    break;
                case 'strings':
                    $field['value'] = $this->getSiteString($field['key']);
                    break;
            }
            
            switch ($field['type']) 
            {
                case 'select':
                    if (isset($field['optionsMethod'])) {
                        $field['options'] = call_user_func($field['optionsMethod']);
                        unset($field['optionsMethod']);
                    }
            }
        }

        return $sectionData;
    }
    
    public function initializeForCommand() {  
        $this->requiresAdmin();
        
        switch ($this->command) {
            case 'getmoduledata':
                $moduleID = $this->getArg('module','');
                try {
                    $module = WebModule::factory($moduleID);
                } catch (Exception $e) {
                    throw new Exception('Module ' . $moduleID . ' not found');
                }

                $moduleData = $this->getModuleAdminData($module);
                
                $this->setResponse($moduleData);
                $this->setResponseVersion(1);
                break;
                
            case 'getsitedata':
                $section = $this->getArg('section');
                $sectionData = $this->getSiteAdminData($section);                
                
                $this->setResponse($sectionData);
                $this->setResponseVersion(1);
                break;
                
            default:
                $this->invalidCommand();
                break;
        }
    }
   
}