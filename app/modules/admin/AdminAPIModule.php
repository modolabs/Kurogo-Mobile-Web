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
            foreach ($sectionData['fields'] as $key=>&$field) {
                if (isset($field['valueMethod'])) {
                    $field['value'] = call_user_func(array($module, $field['valueMethod']));
                } else {
                    $field['value'] = $module->getOptionalModuleVar($key,'', $field['section'], $field['config']);
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
        
        foreach ($sectionData['fields'] as $key=>&$field) {
            switch ($field['config'])
            {
                case 'config':
                    $field['value'] = $this->getUnconstantedValue(Kurogo::getOptionalSiteVar($key), $constant);
                    if ($constant) {
                        $field['constant'] = $constant;
                    }
                    break;
                case 'strings':
                    $field['value'] = Kurogo::getOptionalSiteString($key);
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
    
    private function setConfigVar($type, $subType, $section, $key, $value) {
        switch ($type)
        {
            case 'site':
                $adminData = array($subType=>$this->getSiteAdminData($subType));
                break;
            case 'module':
                $adminData = $this->getModuleAdminData($subType);
                break;
            default:
                throw new Exception("Invalid type $type");
        }
        
    
        if (!isset($adminData[$section]['fields'][$key])) {
            throw new Exception("Invalid key $key for $type section $section");
        }
    
        $fieldData = $adminData[$section]['fields'][$key];
        $configKey = $type . '-' . $fieldData['config'];

        if (!isset($this->configs[$configKey])) {
            switch ($type) 
            {
                case 'site':
                    $this->configs[$configKey] = ConfigFile::factory($fieldData['config'], 'site', ConfigFile::OPTION_IGNORE_LOCAL | ConfigFile::OPTION_IGNORE_MODE);
                    break;
                    
                case 'module':
                    $this->configs[$configKey] = ModuleConfigFile::factory($subType->getConfigModule(), $fieldData['config'], ConfigFile::OPTION_IGNORE_LOCAL | ConfigFile::OPTION_IGNORE_MODE);
                    break;
            
            }
        }

        if (!$this->configs[$configKey]->setVar($fieldData['section'], $key, $value, $changed)) {
            throw new Exception("Error setting $config $section $var $value");
        }

        if ($changed) {    
            $this->changedConfigs[$configKey] = $this->configs[$configKey];
        }
    }
    
    public function initializeForCommand() {  
        $this->requiresAdmin();
        
        switch ($this->command) {
            case 'getconfigdata':
                $type = $this->getArg('type');
                
                switch ($type) 
                {
                    case 'module':
                        $moduleID = $this->getArg('module','');
                        try {
                            $module = WebModule::factory($moduleID);
                        } catch (Exception $e) {
                            throw new Exception('Module ' . $moduleID . ' not found');
                        }
        
                        $adminData = $this->getModuleAdminData($module);
                        break;
                    case 'site':
                        $section = $this->getArg('section');
                        $adminData = $this->getSiteAdminData($section);                
                }
                
                $this->setResponse($adminData);
                $this->setResponseVersion(1);
                break;
                
            case 'setconfigdata':
                $type = $this->getArg('type');
                $data = $this->getArg('data', array());
                $section = $this->getArg('section','');
                
                switch ($type)
                {
                    case 'module':
                    
                        if ($section == 'overview') {
                            throw new Exception("Not written yet");
                        } else {

                            $moduleID = $this->getArg('module','');
                            try {
                                $module = WebModule::factory($moduleID);
                            } catch (Exception $e) {
                                throw new Exception('Module ' . $moduleID . ' not found');
                            }
    
                            $subType = $module;
                        }

                        break;
        
                    case 'site':
                        $subType = $section;
                        break;
                    default:
                        throw new Exception("Invalid type $type");
                }
                
                foreach ($data as $section=>$fields) {
                    foreach ($fields as $key=>$value) {

                        // ignore prefix values. We'll put it back together later
                        if (preg_match("/^(.*?)_prefix$/", $key,$bits)) {
                            continue;
                        } 

                        $prefix = isset($fields[$key . '_prefix']) ? $fields[$key . '_prefix'] : '';
                        if ($prefix && defined($prefix)) {
                            $value = constant($prefix) . '/' . $value;
                        }
                        
                        $this->setConfigVar($type, $subType, $section, $key, $value);
                    }

                }

                foreach ($this->changedConfigs as $config) {
                    $config->saveFile();
                }
                
                $this->setResponse(true);
                $this->setResponseVersion(1);
                
                break;
                
            default:
                $this->invalidCommand();
                break;
        }
    }
   
}