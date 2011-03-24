<?php

class AdminAPIModule extends APIModule
{
    protected $id = 'admin';
    protected $vmin = 1;
    protected $vmax = 1;
    private $configs = array();
    private $changedConfigs = array();
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
    
    private function setSiteVar($config, $section, $var, $value) {
        if (!isset($this->configs[$config])) {
            $this->configs[$config] = ConfigFile::factory($config, 'site', ConfigFile::OPTION_IGNORE_LOCAL | ConfigFile::OPTION_IGNORE_MODE);
        }
        
        if (!$this->configs[$config]->setVar($section, $var, $value, $changed)) {
            throw new Exception("Error setting $config $section $var $value");
        }

        if ($changed) {    
            $this->changedConfigs[$config] = $this->configs[$config];
        }
        
        return true;
    }

    private function getSiteAdminData($section) {
        $configData = $this->getSiteAdminConfig();
        if (!isset($configData[$section])) {
            throw new Exception("Invalid section $section");
        }
        
        $sectionData = $configData[$section];
        $sectionData['section'] = $section;
        
        foreach ($sectionData['fields'] as $key=>&$field) {
            switch ($field['config'])
            {
                case 'config':
                    $field['value'] = $this->getUnconstantedValue($this->getSiteVar($key), $constant);
                    if ($constant) {
                        $field['constant'] = $constant;
                    }
                    break;
                case 'strings':
                    $field['value'] = $this->getSiteString($key);
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
                
            case 'setconfigdata':
                $type = $this->getArg('type');
                $data = $this->getArg('data', array());
                
                switch ($type)
                {
                    case 'site':
                        $section = $this->getArg('section');
                        if (!$sectionData = $this->getSiteAdminData($section)) {
                            throw new Exception("Invalid site section $section");
                        }
                        
                        foreach ($data as $key=>$value) {

                            // ignore filePrefix values. We'll put it back together later
                            if (preg_match("/^(.*?)_filePrefix$/", $key,$bits)) {
                                continue;
                            } 
                            
                            if (!isset($sectionData['fields'][$key])) {
                                throw new Exception("Invalid key $key for site section $section");
                            }
                            
                            $fieldData = $sectionData['fields'][$key];
                            switch ($fieldData['type'])
                            {
                                // see if there's a file prefix
                                case 'file':
                                    $prefix = isset($data[$key . '_filePrefix']) ? $data[$key . '_filePrefix'] : '';
                                    if ($prefix && defined($prefix)) {
                                        $value = constant($prefix) . '/' . $value;
                                    }
                                    break;
                            }
                            
                            
                            $this->setSiteVar($fieldData['config'], $fieldData['section'], $key, $value);
                        }
                        
                        foreach ($this->changedConfigs as $config) {
                            $config->saveFile();
                        }
                        break;
                    case 'module':
                        $this->throwError(new KurogoError(0, "Error", "Not yet implemented"));
                        break;
                    default:
                        throw new Exception("Invalid type $type");
                }
                
                $this->setResponse(true);
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