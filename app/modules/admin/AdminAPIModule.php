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
    
    private function getModuleAdminSections(Module $module) {
        $configData = $module->getModuleAdminConfig();
        $sections = array();
        foreach ($configData as $section=>$sectionData) {
            $sections[$section] = $sectionData['title'];
        }
        
        return $sections;
    }
    
    private function getAdminData($type, $section) {
        if ($type=='site') {
            $configData = $this->getSiteAdminConfig();
            $module = $this;
        } elseif ($type instanceOf Module) {
            $configData = $type->getModuleAdminConfig();
            $module = $type;
        } else {
            throw new Exception("Invalid type $type");
        }
        
        if (!isset($configData[$section])) {
            throw new Exception("Invalid section $section");
        }
        
        $sectionData = $configData[$section];
        $sectionData['section'] = $section;

        switch ($sectionData['sectiontype'])
        {
            case 'fields':
                foreach ($sectionData['fields'] as $key=>&$field) {
                    if (isset($field['valueMethod'])) {
                        $field['value'] = call_user_func(array($module, $field['valueMethod']));
                    } elseif ($type=='site') {
                        switch ($field['config'])
                        {
                            case 'site':
                                $field['value'] = $this->getUnconstantedValue(Kurogo::getOptionalSiteVar($key, $field['section']), $constant);
                                if ($constant) {
                                    $field['constant'] = $constant;
                                }
                                break;
                            case 'strings':
                                $field['value'] = Kurogo::getOptionalSiteString($key);
                                break;
                            default: 
                                throw new Exception("Unknown config " . $field['config']);
                                break;
                        }
                    } else {
                        $field['value'] = $module->getOptionalModuleVar($key, isset($field['default']) ? $field['default'] : '', $field['section'], $field['config']);
                    }
                    
                    switch ($field['type']) 
                    {
                        case 'paragraph':
                            if (is_array($field['value'])) {
                                $field['value'] = implode("\n\n", $field['value']);
                            }
                            break;
                        case 'select':
                            if (isset($field['optionsMethod'])) {
                                if (is_array($field['optionsMethod'])) {
                                    $field['options'] = call_user_func($field['optionsMethod']);
                                } else {
                                    $field['options'] = $module->$field['optionsMethod']();
                                }
                                unset($field['optionsMethod']);
                            }
                            
                            if (isset($field['optionsFirst'])) {
                                $field['options'] = array_merge(array(''=>$field['optionsFirst']), $field['options']);    
                                unset($field['optionsFirst']);
                            }
                    }
    
                    $field['value'] = $this->getUnconstantedValue($field['value'], $constant);
                    if ($constant) {
                        $field['constant'] = $constant;
                    }
                }
                break;
                
            case 'table':
                if (isset($sectionData['tablerowsmethod'])) {   
                    if (is_array($sectionData['tablerowsmethod'])) {
                        $sectionData['tablerows'] = call_user_func($sectionData['tablerowsmethod']);
                    } else {
                        $sectionData['tablerows'] = $module->$sectionData['tablerowsmethod']();
                    }
                    unset($sectionData['tablerowsmethod']);
                }
                break;
            case 'section':
                if (isset($sectionData['sectionsmethod'])) {
                    if (is_array($sectionData['sectionsmethod'])) {
                        $sectionData['sections'] = call_user_func($sectionData['sectionsmethod']);
                    } else {
                        $sectionData['sections'] = $module->$sectionData['sectionsmethod']();
                    }
                    unset($sectionData['sectionsmethod']);
        
                    foreach ($sectionData['fields'] as $key=>&$field) {
                        switch ($field['type']) 
                        {
                            case 'select':
                                if (isset($field['optionsMethod'])) {
                                    $field['options'] = call_user_func($field['optionsMethod']);
                                    unset($field['optionsMethod']);
                                }
        
                                if (isset($field['optionsFirst'])) {
                                    $field['options'] = array_merge(array(''=>$field['optionsFirst']), $field['options']);    
                                    unset($field['optionsFirst']);
                                }
                        }
                        
                    }
                    
                    foreach ($sectionData['sections'] as $section=>&$sectionFields) {
                        foreach($sectionFields as $key=>&$value) {
                            $value = $this->getUnconstantedValue($value, $constant);
                            if ($constant) {
                                $value = array($constant, $value);
                            }
                        }
                    }
                }
                break;
            case 'acl':

                if ($type=='site') {
                    $sectionData['acls'] = Kurogo::getOptionalSiteVar('acl', array(), 'authentication');
                    $sectionData['adminacls'] = Kurogo::getOptionalSiteVar('adminacl', array(), 'authentication');
                } else {
                    $sectionData['acls'] = $module->getOptionalModuleVar('acl', array(), 'authentication');
                    $sectionData['adminacls'] = $module->getOptionalModuleVar('acl', array(), 'authentication');
                }
                break;
            default:
                throw new Exception("Section type " . $sectionData['sectiontype'] . " not understood for section $section");
            
        }         
    
        return $sectionData;
    }
    
    private function setConfigVar($type, $subType, $section, $key, $value) {
        switch ($type)
        {
            case 'site':
                $sectionData = $this->getAdminData('site', $subType);
                $typeKey = 'site';
                break;
            case 'module':
                $sectionData = $this->getAdminData($subType, $section);
                $typeKey = 'module-' . $subType->getConfigModule();
                break;
            default:
                throw new Exception("Invalid type $type");
        }
            
        switch ($sectionData['sectiontype'])
        {
            case 'fields':
            
                if (!isset($sectionData['fields'][$key])) {
                    throw new Exception("Invalid key $key for $type section $section");
                }
                
                $fieldData = $sectionData['fields'][$key];
                break;
            
            case 'table':
            case 'section':
                $fieldData = $sectionData;
                break;
            case 'acl':
                throw new Exception("Haven't handled ACL saving yet");
                break;

            default:
                throw new Exception("Unable to handle $type ($subType) $section. No fields, no tablefields, no sectionfields");
        }
        
        $configKey = $typeKey . '-' . $fieldData['config'];

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
        
        if (is_array($value)) {
            $result = true;
            $changed = false;
            foreach ($value as $k=>$v) {
                if (preg_match("/^(.*?)_prefix$/", $k,$bits)) {
                    continue;
                } 

                if (!isset($fieldData['fields'][$k])) {
                    throw new Exception("Invalid key $k for $type:" . $fieldData['config'] . " section $key");
                }
                
                if (isset($fieldData['fields'][$k]['omitBlankValue']) && $fieldData['fields'][$k]['omitBlankValue'] && strlen($v)==0) {
                    $changed = $changed || $this->configs[$configKey]->clearVar($key, $k);
                    continue;
                } else {
                    $prefix = isset($value[$k . '_prefix']) ? $value[$k . '_prefix'] : '';
                    if ($prefix && defined($prefix)) {
                        $v = constant($prefix) . '/' . $v;
                    }
                    
                    if ($fieldData['fields'][$k]['type']=='paragraph') {
                        $v = explode("\n\n", str_replace(array("\r\n","\r"), array("\n","\n"), $v));
                    }
                    if (!$this->configs[$configKey]->setVar($key, $k, $v, $c)) {
                        $result = false;
                    }
                    $changed = $changed || $c;
                }
            }
        } else {
            if (isset($fieldData['omitBlankValue']) && $fieldData['omitBlankValue'] && strlen($value)==0) {
                $changed = $this->configs[$configKey]->clearVar($fieldData['section'], $key);
            } else {
                if ($fieldData['type']=='paragraph') {
                    $value = explode("\n\n", str_replace(array("\r\n","\r"), array("\n","\n"), $value));
                }
            
                $result = $this->configs[$configKey]->setVar($fieldData['section'], $key, $value, $changed);

                if (!$result) {
                    throw new Exception("Error setting $config $section $key $value");
                }
            }
        }
        

        if ($changed) {    
            $this->changedConfigs[$configKey] = $this->configs[$configKey];
        }
    }
    
    public function initializeForCommand() {  
        $this->requiresAdmin();
        
        switch ($this->command) {
            case 'checkversion':
                $data = array(
                    'current'=>Kurogo::checkCurrentVersion(),
                    'local'  =>KUROGO_VERSION
                );
                $this->setResponse($data);
                $this->setResponseVersion(1);
                
                break;
                
            case 'getconfigsections':
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
        
                        $sections = $this->getModuleAdminSections($module);
                        break;
                    case 'site':
                        throw new Exception("getconfigsections for site not handled yet");
                }
                
                $this->setResponse($sections);
                $this->setResponseVersion(1);
                break;
                
                break;
            case 'getconfigdata':
                $type = $this->getArg('type');
                $section = $this->getArg('section','');
                
                switch ($type) 
                {
                    case 'module':
                        $moduleID = $this->getArg('module','');
                        try {
                            $module = WebModule::factory($moduleID);
                        } catch (Exception $e) {
                            throw new Exception('Module ' . $moduleID . ' not found');
                        }
        
                        $adminData = $this->getAdminData($module, $section);
                        break;
                    case 'site':
                        $adminData = $this->getAdminData('site', $section);                
                }
                
                $this->setResponse($adminData);
                $this->setResponseVersion(1);
                break;
                
            case 'setconfigdata':
                $type = $this->getArg('type');
                $data = $this->getArg('data', array());
                $section = $this->getArg('section','');
                if (!is_array($data)) {
                    throw new Exception("Invalid data for $type $section");
                }
                
                switch ($type)
                {
                    case 'module':
                    
                        if ($section == 'overview') {
                            foreach ($data as $moduleID=>$props) {
                                try {
                                    $module = WebModule::factory($moduleID);
                                } catch (Exception $e) {
                                    throw new Exception('Module ' . $moduleID . ' not found');
                                }
                                
                                if (!is_array($props)) {
                                    throw new Exception("Invalid properties for $type $section");
                                }
                                
                                $valid_props = array('protected','secure','disabled','search');
                                foreach ($props as $key=>$value) {
                                    if (!in_array($key, $valid_props)) {
                                        throw new Exception("Invalid property $key for module $module");
                                    }
                                    
                                    $this->setConfigVar($type, $module, 'general', $key, $value);
                                }
                            }
                            
                            foreach ($this->changedConfigs as $config) {
                                $config->saveFile();
                            }
                            
                            $this->setResponse(true);
                            $this->setResponseVersion(1);
                            break 2;
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
                
            case 'removeconfigsection':
                $type = $this->getArg('type');
                $section = $this->getArg('section','');
                $key = $this->getArg('key', null);
                
                switch ($type)
                {
                    case 'site':
                        $sectionData = $this->getAdminData($type, $section);
                        $config = ConfigFile::factory($sectionData['config'],'site');
                        break;
                    case 'module':
                        $moduleID = $this->getArg('module','');
                        try {
                            $module = WebModule::factory($moduleID);
                        } catch (Exception $e) {
                            throw new Exception('Module ' . $moduleID . ' not found');
                        }
                        $sectionData = $this->getAdminData($module, $section);
                        $config = ModuleConfigFile::factory($moduleID, $sectionData['config']);
                        break;
                    default:
                        throw new Exception("Invalid type $type");
                }
                        
                if (!isset($sectionData['sections']) || (!isset($sectionData['sectiondelete']) || !$sectionData['sectiondelete'])) {
                    throw new Exception("Config '$section' of module '$moduleID' does not permit removal of items");
                }

                if (!isset($sectionData['sections'][$key])) {
                    throw new Exception("Section $key not found in config '$section' of module '$moduleID'");
                }

                if (!$result = $config->removeSection($key)) {
                    throw new Exception("Error removing item $key from config '$section' of module '$moduleID'");
                } else {
                    $config->saveFile();
                }
                
                $this->setResponse(true);
                $this->setResponseVersion(1);
                break;

            case 'setmodulelayout':
                
                $data = $this->getArg('data', array());
                $config = ModuleConfigFile::factory('home', 'module');
                if (!isset($data['primary_modules'])) {
                    $data['primary_modules'] = array();
                }
                
                $config->setSection('primary_modules', $data['primary_modules']);

                if (!isset($data['secondary_modules'])) {
                    $data['secondary_modules'] = array();
                }

                $config->setSection('secondary_modules', $data['secondary_modules']);

                $config->saveFile();
                $this->setResponse(true);
                $this->setResponseVersion(1);
                
                break;
            default:
                $this->invalidCommand();
                break;
        }
    }
   
}