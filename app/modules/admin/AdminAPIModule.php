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
    
    private function getAdminData($type, $section, $subsection=null) {
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
        if ($subsection) {
            if (!isset($configData[$section]['sections'][$subsection])) {
                throw new Exception("Invalid subsection $subsection for section $section");
            }

           $sectionData = $configData[$section]['sections'][$subsection];
        }
        
        $sectionData['section'] = $section;

        switch ($sectionData['sectiontype'])
        {
            case 'fields':
                foreach ($sectionData['fields'] as $key=>&$field) {
                    if (isset($field['valueMethod'])) {
                        $field['value'] = call_user_func(array($module, $field['valueMethod']));
                    } elseif ($type=='site') {
                        if (isset($field['config'])) {
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
    
                    if (isset($field['value'])) {
                        $field['value'] = $this->getUnconstantedValue($field['value'], $constant);
                        if ($constant) {
                            $field['constant'] = $constant;
                        }
                    }
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
                } elseif ($type=='site') {
                    throw new Exception("Can't get sections for site");
                } else {
                    $sectionData['sections'] = $module->getModuleSections($sectionData['config'], Config::NO_EXPAND_VALUE);
                }
        
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
                break;
            case 'sections':
                foreach ($sectionData['sections'] as $subsection=>&$_sectionData) {

                    $subsectionData = $this->getAdminData($type, $section, $subsection);
                    if (isset($subsectionData['showIfSiteVar'])) {
                        if (Kurogo::getOptionalSiteVar($subsectionData['showIfSiteVar'][0], '') != $subsectionData['showIfSiteVar'][1]) {
                            unset($sectionData['sections'][$subsection]);
                            continue;
                        }
                    }

                    if (isset($subsectionData['showIfModuleVar'])) {
                        if ($type->getOptionalModuleVar($subsectionData['showIfModuleVar'][0], '') != $subsectionData['showIfModuleVar'][1]) {
                            unset($sectionData['sections'][$subsection]);
                            continue;
                        }
                    }

                    $sectionData['sections'][$subsection] = $subsectionData;

                }
                break;
            default:
                throw new Exception("Section type " . $sectionData['sectiontype'] . " not understood for section $section");
            
        }         
    
        return $sectionData;
    }
    
    private function getAdminConfig($type, $config, $opts=0) {

        $opts = $opts | ConfigFile::OPTION_IGNORE_LOCAL | ConfigFile::OPTION_IGNORE_MODE;

        if ($type=='site') {
            $configKey = "site-$config";
            if (isset($this->configs[$configKey])) {
                $config = $this->configs[$configKey];
            } elseif ($config = ConfigFile::factory($config, 'site', $opts)) {
                $this->configs[$configKey] = $config;
            }
            
        } elseif ($type instanceOf Module) {
            $configKey = 'module-' . $type->getConfigModule() . '-' . $config;
            if (isset($this->configs[$configKey])) {
                $config = $this->configs[$configKey];
            } elseif ($config = $type->getConfig($config, $opts)) {
                $this->configs[$configKey] = $config;
            }
        } else {
            throw new Exception("Invalid type $type");
        }
        
        return $config;
    }
    
    private function setSectionOrder($type, $section, $subsection, $order) {

        $sectionData = $this->getAdminData($type, $section, $subsection);
        if ($sectionData['sectiontype']!='section') {
            throw new Exception("Cannot set the order of $section $subsection");
        }
        
        $config = $this->getAdminConfig($type, $sectionData['config'], ConfigFile::OPTION_CREATE_EMPTY);
        if (!$config->setSectionOrder($order, $changed)) {
            throw new Exception("Error setting the order of " . $sectionData['config']);
        }
        
        if ($changed) {    
            if (!in_array($config, $this->changedConfigs)) {
                $this->changedConfigs[] = $config;
            }
        }
    }
    
    private function setConfigVar($type, $section, $subsection, $key, $value) {

        $sectionData = $this->getAdminData($type, $section, $subsection);
        $changed = false;
            
        switch ($sectionData['sectiontype'])
        {
            case 'fields':
                if (!isset($sectionData['fields'][$key])) {
                    throw new Exception("Invalid key $key for $type section $section");
                }
                
                $fieldData = $sectionData['fields'][$key];
                break;
            
            case 'section':
                $fieldData = $sectionData;
                break;
            default:
                throw new Exception("Unable to handle $type $section. Invalid section type " . $sectionData['sectiontype']);
        }
        
        $config = $this->getAdminConfig($type, $fieldData['config'], ConfigFile::OPTION_CREATE_EMPTY);

        //remove blank values before validation
        if (is_array($value)) {
            foreach ($value as $k=>$v) {
                if (isset($fieldData['fields'][$k]['omitBlankValue']) && $fieldData['fields'][$k]['omitBlankValue'] && strlen($v)==0) {
                    $changed = $changed || $config->clearVar($key, $k);
                    unset($value[$k]);
                }
            }
        }
        
        if (isset($sectionData['sectionvalidatemethod'])) {
            $result = call_user_func($sectionData['sectionvalidatemethod'], $key, $value);
            if (KurogoError::isError($result)) {
                throw new Exception($result->getMessage());
            }
        }
        
        if (is_array($value)) {
            $result = true;
            foreach ($value as $k=>$v) {
                if (preg_match("/^(.*?)_prefix$/", $k,$bits)) {
                    continue;
                } 

                if (!isset($fieldData['fields'][$k])) {
                    throw new Exception("Invalid key $k for $type:" . $fieldData['config'] . " section $key");
                }
                
                $prefix = isset($value[$k . '_prefix']) ? $value[$k . '_prefix'] : '';
                if ($prefix && defined($prefix)) {
                    $v = constant($prefix) . '/' . $v;
                }
                
                if ($fieldData['fields'][$k]['type']=='paragraph') {
                    $v = explode("\n\n", str_replace(array("\r\n","\r"), array("\n","\n"), $v));
                }
                if (!$config->setVar($key, $k, $v, $c)) {
                    $result = false;
                }
                $changed = $changed || $c;
            }
        } else {
            if (isset($fieldData['omitBlankValue']) && $fieldData['omitBlankValue'] && strlen($value)==0) {
                $changed = $config->clearVar($fieldData['section'], $key);
            } else {
                if ($fieldData['type']=='paragraph') {
                    $value = explode("\n\n", str_replace(array("\r\n","\r"), array("\n","\n"), $value));
                }
            
                $result = $config->setVar($fieldData['section'], $key, $value, $changed);

                if (!$result) {
                    throw new Exception("Error setting $config $section $key $value");
                }
            }
        }
        
        if ($changed) {    
            if (!in_array($config, $this->changedConfigs)) {
                $this->changedConfigs[] = $config;
            }
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
            
            case 'clearcaches':

                $result = Kurogo::clearCaches();                
                if ($result===0) {
                    $this->setResponse(true);
                    $this->setResponseVersion(1);
                } else {
                    $this->throwError(KurogoError(1, "Error clearing caches", "There was an error ($result) clearing the caches"));
                }
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
        
                        $sections = $module->getModuleAdminSections();
                        break;
                    case 'site':
                        throw new Exception("getconfigsections for site not handled yet");
                }
                
                $this->setResponse($sections);
                $this->setResponseVersion(1);
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
                        break;
                }
                
                $this->setResponse($adminData);
                $this->setResponseVersion(1);
                break;
                
            case 'setconfigdata':
                $type = $this->getArg('type');
                $data = $this->getArg('data', array());
                $section = $this->getArg('section','');
                $subsection = null;
                if (empty($data)) {
                    $data = array();
                } elseif (!is_array($data)) {
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
                                    
                                    $this->setConfigVar($module, 'general', $subsection, $key, $value);
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

                            $type = $module;
                        }

                        break;
        
                    case 'site':
                        break;
                    default:
                        throw new Exception("Invalid type $type");
                }
                
                foreach ($data as $section=>$fields) {
                    $adminData = $this->getAdminData($type, $section);
                    if ($adminData['sectiontype']=='sections') {
                        $subsection = key($fields);
                        $fields = current($fields);
                        $adminData = $this->getAdminData($type, $section, $subsection);
                    }
                    $fields = is_array($fields) ? $fields : array();
                    
                    foreach ($fields as $key=>$value) {
                        
                        if ($adminData['sectiontype']=='section' && isset($adminData['sectionclearvalues']) && $adminData['sectionclearvalues']) {
                            if ($config = $this->getAdminConfig($type, $adminData['config'], ConfigFile::OPTION_DO_NOT_CREATE)) {
                                $config->removeSection($key);
                            }
                        }
                        
                        // ignore prefix values. We'll put it back together later
                        if (preg_match("/^(.*?)_prefix$/", $key,$bits)) {
                            continue;
                        } 

                        $prefix = isset($fields[$key . '_prefix']) ? $fields[$key . '_prefix'] : '';
                        if ($prefix && defined($prefix)) {
                            $value = constant($prefix) . '/' . $value;
                        }
                        
                        $this->setConfigVar($type, $section, $subsection, $key, $value);
                    }

                }
                
                if ($sectionorder = $this->getArg('sectionorder')) {
                    foreach ($sectionorder as $section=>$order) {
                        $this->setSectionOrder($type, $section, $subsection, $order);
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
                        $subsection = $this->getArg('subsection',null);
                        $sectionData = $this->getAdminData($type, $section, $subsection);
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
                        $config = $module->getConfig($sectionData['config']);
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
                    throw new Exception("Error removing item $key from config '" . $sectionData['config'] ."'");
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