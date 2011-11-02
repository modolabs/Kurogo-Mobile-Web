<?php

class AdminAPIModule extends APIModule
{
    protected $id = 'admin';
    protected $vmin = 1;
    protected $vmax = 1;
    private $loadedConfigs = array();
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
    
    private function getSiteAdminConfig($type) {
        static $configData;
        if (!isset($configData[$type])) {
            $files = array(
                APP_DIR . "/common/config/admin-{$type}.json",
                SITE_APP_DIR . "/common/config/admin-{$type}.json"
            );
            $data = array();
            foreach ($files as $file) {
                if (is_file($file)) {
                    if ($json = json_decode(file_get_contents($file), true)) {
                        $data = self::mergeConfigData($data, $json);
                    } else {
                        throw new KurogoDataException($this->getLocalizedString('ERROR_PARSING_FILE', $file));
                    }
                }
            }
            $configData[$type] = $data;
        }
        
        return $configData[$type];
    }
    
    private function getTypeStr($type) {
        if (in_array($type, array('site'))) {
            return $type;
        } elseif ($type instanceOf Module) {
            return $type->configModule;
        } else {
            throw new Exception("Invalid type $type");
        }
    }
    
    private function getAdminData($type, $section, $subsection=null) {
        if (in_array($type, array('site'))) {
            $configData = $this->getSiteAdminConfig($type);
            $module = $this;
            if (is_null($section)) {
                $section = key ($configData);
            }
        } elseif ($type instanceOf Module) {
            $configData = $type->getModuleAdminConfig();
            $module = $type;
        } else {
            throw new KurogoConfigurationException("Invalid type $type");
        }
        
        if (!isset($configData[$section])) {
            throw new KurogoConfigurationException("Invalid section $section");
        }
        
        $sectionData = $configData[$section];
        if ($subsection) {
            if (!isset($configData[$section]['sections'][$subsection])) {
                throw new KurogoConfigurationException("Invalid subsection $subsection for section $section");
            }

           $sectionData = $configData[$section]['sections'][$subsection];
        }
        
        $sectionData['section'] = $section;
        if (isset($sectionData['titleKey'])) {
            $sectionData['title'] = $module->getLocalizedString($sectionData['titleKey']);
            unset($sectionData['titleKey']);
        }

        if (isset($sectionData['descriptionKey'])) {
            $sectionData['description'] = $module->getLocalizedString($sectionData['descriptionKey']);
            unset($sectionData['descriptionKey']);
        }
        
        if (isset($sectionData['fieldgroups'])) {
            foreach ($sectionData['fieldgroups'] as $fieldgroup=>&$fieldgroupData) {
                if (isset($fieldgroupData['labelKey'])) {
                    $fieldgroupData['label'] = $module->getLocalizedString($fieldgroupData['labelKey']);
                    unset($fieldgroupData['labelKey']);
                }

                if (isset($fieldgroupData['descriptionKey'])) {
                    $fieldgroupData['description'] = $module->getLocalizedString($fieldgroupData['descriptionKey']);
                    unset($fieldgroupData['descriptionKey']);
                }
            }
        }
        
        switch ($sectionData['sectiontype'])
        {
            case 'fields':
                foreach ($sectionData['fields'] as $key=>&$field) {
                    if (isset($field['labelKey'])) {
                        $field['label'] = $module->getLocalizedString($field['labelKey']);
                        unset($field['labelKey']);
                    }
            
                    if (isset($field['descriptionKey'])) {
                        $field['description'] = $module->getLocalizedString($field['descriptionKey']);
                        unset($field['descriptionKey']);
                    }
                
                    if (isset($field['value'])) {
                        // value is set. used typically for hidden fields
                    } elseif (isset($field['valueMethod'])) {
                        $field['value'] = call_user_func(array($module, $field['valueMethod']));
                        unset($field['valueMethod']);
                    } elseif (isset($field['valueKey'])) {
                        $field['value'] = $module->getLocalizedString($field['valueKey']);
                        unset($field['valueKey']);
                    } elseif (in_array($type, array('site'))) {
                        if (isset($field['config'])) {
                            switch ($field['config'])
                            {
                                case 'site':
                                case 'kurogo':
                                    $field['value'] = Kurogo::getOptionalSiteVar($key, '', $field['section']);
                                    break;
                                case 'strings':
                                    $field['value'] = Kurogo::getOptionalSiteString($key);
                                    break;
                                default: 
                                    throw new KurogoConfigurationException("Unknown config " . $field['config']);
                                    break;
                            }
                        }
                    } elseif (isset($field['config'], $field['section'])) {
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
                        $value = $this->getUnconstantedValue($field['value'], $constant);
                        if ($constant) {
                            $field['value'] = $value;
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
                } elseif (in_array($type, array('site'))) {
                    throw new KurogoConfigurationException("Getting sections for $type is not written yet");
                } else {
                    $configMode = isset($sectionData['configMode']) ? $sectionData['configMode'] : 0;
                    $sectionData['sections'] = $module->getModuleSections($sectionData['config'], Config::NO_EXPAND_VALUE, $configMode);
                }
                
                if (isset($sectionData['sectionsnoneKey'])) {
                    $sectionData['sectionsnone'] = $module->getLocalizedString($sectionData['sectionsnoneKey']);
                    unset($sectionData['sectionsnoneKey']);
                }

                if (isset($sectionData['sectionaddpromptkey'])) {
                    $sectionData['sectionaddprompt'] = $module->getLocalizedString($sectionData['sectionaddpromptkey']);
                    unset($sectionData['sectionaddpromptkey']);
                }
        
                foreach ($sectionData['fields'] as $key=>&$field) {
                    if (isset($field['labelKey'])) {
                        $field['label'] = $module->getLocalizedString($field['labelKey']);
                        unset($field['labelKey']);
                    }
            
                    if (isset($field['descriptionKey'])) {
                        $field['description'] = $module->getLocalizedString($field['descriptionKey']);
                        unset($field['descriptionKey']);
                    }

                    if (isset($field['valueKey'])) {
                        $field['value'] = $module->getLocalizedString($field['valueKey']);
                        unset($field['valueKey']);
                    }
                    
                    switch ($field['type']) 
                    {
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
                }
                    
                foreach ($sectionData['sections'] as $section=>&$sectionFields) {
                    foreach($sectionFields as $key=>&$value) {
                        if (isset($sectionData['fields'][$key]['type']) && $sectionData['fields'][$key]['type']=='paragraph') {
                            $value = implode("\n\n", $value);
                        }

                        $v = $this->getUnconstantedValue($value, $constant);
                        if ($constant) {
                            $value = array($constant, $v, $value);
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
                throw new KurogoConfigurationException("Section type " . $sectionData['sectiontype'] . " not understood for section $section");
            
        }         
    
        return $sectionData;
    }
    
    private function getAdminConfig($type, $config, $opts=0) {

        $opts = $opts | ConfigFile::OPTION_IGNORE_LOCAL | ConfigFile::OPTION_IGNORE_MODE;

        if ($config=='kurogo') {
            $configKey = "kurogo";
            if (isset($this->loadedConfigs[$configKey])) {
                $config = $this->loadedConfigs[$configKey];
            } elseif ($config = ConfigFile::factory('kurogo', 'project', $opts)) {
                $this->loadedConfigs[$configKey] = $config;
            }
        } elseif (in_array($type, array('site'))) {
            $configKey = "site-$config";
            if (isset($this->loadedConfigs[$configKey])) {
                $config = $this->loadedConfigs[$configKey];
            } elseif ($config = ConfigFile::factory($config, 'site', $opts)) {
                $this->loadedConfigs[$configKey] = $config;
            }
        } elseif ($type instanceOf Module) {
            $configKey = 'module-' . $type->getConfigModule() . '-' . $config;
            if (isset($this->loadedConfigs[$configKey])) {
                $config = $this->loadedConfigs[$configKey];
            } elseif ($config = $type->getConfig($config, $opts)) {
                $this->loadedConfigs[$configKey] = $config;
            }
        } else {
            throw new KurogoConfigurationException("Invalid type $type");
        }
        
        return $config;
    }
    
    private function setSectionOrder($type, $section, $subsection, $order) {

        $sectionData = $this->getAdminData($type, $section, $subsection);
        if ($sectionData['sectiontype']!='section') {
            throw new KurogoConfigurationException("Cannot set the order of $section $subsection");
        }
        
        $config = $this->getAdminConfig($type, $sectionData['config'], ConfigFile::OPTION_CREATE_EMPTY);
        if (!$config->setSectionOrder($order, $changed)) {
            throw new KurogoConfigurationException("Error setting the order of " . $sectionData['config']);
        }
        
        if ($changed) {    
            if (!in_array($config, $this->changedConfigs)) {
                $this->changedConfigs[] = $config;
            }
        }
    }
    
    private function setConfigVar($type, $section, $subsection, $key, $value) {

        $typeStr = $this->getTypeStr($type);
        Kurogo::log(LOG_DEBUG, "Setting $key to \"$value\" in $typeStr: $section $subsection", 'admin');
        $sectionData = $this->getAdminData($type, $section, $subsection);
        $changed = false;

        switch ($sectionData['sectiontype'])
        {
            case 'fields':
                if (!isset($sectionData['fields'][$key])) {
                    throw new KurogoConfigurationException("Invalid key $key for $type section $section");
                }
                
                $fieldData = $sectionData['fields'][$key];
                break;
            
            case 'section':
                $fieldData = $sectionData;
                break;
            default:
                throw new KurogoConfigurationException("Unable to handle $type $section. Invalid section type " . $sectionData['sectiontype']);
        }
        
        if (isset($fieldData['valueSaveMethod'])) {
            if (isset($fieldData['module'])) {
                $module = WebModule::factory($fieldData['module']);
                $result = call_user_func(array($module, $fieldData['valueSaveMethod']), $key, $value);
            } else {
                $result = call_user_func($fieldData['valueSaveMethod'], $key, $value);
            }

            if ($result instanceOf Config) {
                $this->changedConfigs[] = $result;                
            } 
            
            if (KurogoError::isError($result)) {
                throw new Exception($result->getMessage());
            }
            
            return;            
        }
        
        $config = $this->getAdminConfig($type, $fieldData['config'], ConfigFile::OPTION_CREATE_EMPTY);

        //remove blank values before validation
        if (is_array($value)) {
            foreach ($value as $k=>$v) {
                $prefix = isset($value[$k . '_prefix']) ? $value[$k . '_prefix'] : '';
                if ($prefix && defined($prefix)) {
                    $value[$k] = constant($prefix) . '/' . $v;
                }
                if (isset($value[$k . '_prefix'])) {
                    unset($value[$k . '_prefix']);
                }

                if (isset($fieldData['fields'][$k]['omitBlankValue']) && $fieldData['fields'][$k]['omitBlankValue'] && strlen($v)==0) {
                    $changed = $changed || $config->clearVar($key, $k);
                    unset($value[$k]);
                }

                if ($fieldData['fields'][$k]['type']=='paragraph') {
                    $value[$k] = explode("\n\n", str_replace(array("\r\n","\r"), array("\n","\n"), $v));
                }
            }
        }
        
        if (isset($sectionData['sectionvalidatemethod'])) {
            $result = call_user_func($sectionData['sectionvalidatemethod'], $key, $value);
            if (KurogoError::isError($result)) {
                throw new KurogoException($result->getMessage());
            }
        }
        
        if (is_array($value)) {
            $result = true;
            foreach ($value as $k=>$v) {
                if (preg_match("/^(.*?)_prefix$/", $k,$bits)) {
                    continue;
                } 

                if (!isset($fieldData['fields'][$k])) {
                    throw new KurogoConfigurationException("Invalid key $k for $typeStr:" . $fieldData['config'] . " section $key");
                }
                
                $prefix = isset($value[$k . '_prefix']) ? $value[$k . '_prefix'] : '';
                if ($prefix && defined($prefix)) {
                    $v = constant($prefix) . '/' . $v;
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
                    throw new KurogoConfigurationException("Error setting $config $section $key $value");
                }
            }
        }
        
        if ($changed) {    
            if (!in_array($config, $this->changedConfigs)) {
                $this->changedConfigs[] = $config;
            }
        }
    }
    
    private function uploadFile($type, $section, $subsection, $key, $value) {
        $sectionData = $this->getAdminData($type, $section, $subsection);

        if (isset($value['error']) && $value['error'] != UPLOAD_ERR_OK) {
            throw new KurogoDataException(Kurogo::file_upload_error_message($value['error']));
        }

        if (!isset($value['tmp_name']) || !is_uploaded_file($value['tmp_name'])) {
            throw new KurogoDataException("Error locating uploaded file");
        }
        
        switch ($sectionData['sectiontype'])
        {
            case 'fields':
                if (!isset($sectionData['fields'][$key])) {
                    throw new KurogoConfigurationException("Invalid key $key for $type section $section");
                }
                
                $fieldData = $sectionData['fields'][$key];
                break;
            
            case 'section':
                $fieldData = $sectionData;
                throw new KurogoConfigurationException("Code not written for this type of field");
                break;
            default:
                throw new KurogoConfigurationException("Unable to handle $type $section. Invalid section type " . $sectionData['sectiontype']);
        }

        if (!isset($fieldData['destinationType'])) {
            throw new KurogoConfigurationException("Unable to determine destination type");
        }
        
        switch ($fieldData['destinationType'])
        {
            case 'file':
                if (!isset($fieldData['destinationFile'])) {
                    throw new KurogoConfigurationException("Unable to determine destination location");
                }
                
                $destination = $fieldData['destinationFile'];
                break;
                
            case 'folder':
                if (!isset($fieldData['destinationFile'])) {
                    throw new KurogoConfigurationException("Unable to determine destination location");
                }
                
                if (!isset($fieldData['destinationFolder'])) {
                    throw new KurogoConfigurationException("Unable to determine destination location");
                }
                $destination = rtrim($fieldData['destinationFolder'], '/') . '/' . ltrim($fieldData['destinationFile'],'/');
                
                break;
        }

        $prefix = isset($fieldData['destinationPrefix']) ? $fieldData['destinationPrefix'] : '';
        if ($prefix && defined($prefix)) {
            $destination = constant($prefix) . '/' . $destination;
        }
                    
        if (isset($fieldData['fileType'])) {
            switch ($fieldData['fileType'])
            {
                case 'image':

                    $this->setResponseVersion(1);
                    try {                
                        $imageData = new ImageProcessor($value['tmp_name']);
                        $transformer = new ImageTransformer($fieldData);
                        $imageType = isset($fieldData['imageType']) ? $fieldData['imageType'] : null;
                        
                        $result = $imageData->transform($transformer, $imageType, $destination);
                        if (KurogoError::isError($result)) {
                            $this->throwError($result);
                        }
                    } catch (KurogoException $e) {
                        throw new KurogoException("Uploaded file must be a valid image (" . $e->getMessage() . ")");
                    }
                    break;
                default:
                    throw new KurogoConfigurationException("Unknown fileType " . $fieldData['fileType']);
            }
        } else {
            if (!move_uploaded_file($value['tmp_name'], $destination)) {
                $this->throwError(new KurogoError(1, "Cannot save file", "Unable to save uploaded file"));
            }
        }
    }
    
    public function initializeForCommand() {  
        $this->requiresAdmin();
        
        switch ($this->command) {
            case 'checkversion':
                $current = Kurogo::sharedInstance()->checkCurrentVersion();
                Kurogo::log(LOG_INFO, sprintf("Checking version. This site: %s Current Kurogo Version: %s", $current, KUROGO_VERSION), 'admin');
                $uptodate = version_compare(KUROGO_VERSION, $current,">=");
                $messageKey = $uptodate ? 'KUROGO_VERSION_MESSAGE_UPTODATE' : 'KUROGO_VERSION_MESSAGE_NOTUPDATED';

                $data = array(
                    'current'=>$current,
                    'local'  =>KUROGO_VERSION,
                    'uptodate' =>$uptodate,
                    'message'=>$this->getLocalizedString($messageKey, $current, KUROGO_VERSION)
                );
                
                $this->setResponse($data);
                $this->setResponseVersion(1);
                
                break;
            
            case 'getlocalizedstring':
                $key = $this->getArg('key');
                $data = array();
                if (is_array($key)) {
                    foreach ($key as $k) {
                        $data[$k] = $this->getLocalizedString($k);
                    }
                } else {
                    $data[$key] = $this->getLocalizedString($key);
                }
                $this->setResponse($data);
                $this->setResponseVersion(1);
                break;

            case 'clearcaches':

                Kurogo::log(LOG_NOTICE, "Clearing Site Caches", 'admin');
                $result = Kurogo::sharedInstance()->clearCaches();
                if ($result===0) {
                    $this->setResponse(true);
                    $this->setResponseVersion(1);
                } else {
                    $this->throwError(new KurogoError(1, "Error clearing caches", "There was an error ($result) clearing the caches"));
                }
                break;
                
            case 'upload':
                $type = $this->getArg('type');
                $section = $this->getArg('section','');
                $subsection = null;
                
                switch ($type) 
                {
                    case 'module':
                        $moduleID = $this->getArg('module','');
                        $module = WebModule::factory($moduleID);
                        $type = $module;
                        break;
                    case 'site':
                        break;
                    default:
                        throw new KurogoConfigurationException("Invalid type $type");
                }
                
                if (count($_FILES)==0) {
                    throw new KurogoException("No files uploaded");
                }
                
                foreach ($_FILES as $key=>$uploadData) {
                    $this->uploadFile($type, $section, $subsection, $key, $uploadData);
                }

                $this->setResponseVersion(1);
                $this->setResponse(true);
                break;
                
            case 'getconfigsections':
                $type = $this->getArg('type');
                switch ($type) 
                {
                    case 'module':
                        $moduleID = $this->getArg('module','');
                        $module = WebModule::factory($moduleID);
                        $sections = $module->getModuleAdminSections();
                        break;
                    case 'site':
                        throw new KurogoConfigurationException("getconfigsections for site not handled yet");
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
                        $module = WebModule::factory($moduleID);
                        $adminData = $this->getAdminData($module, $section);
                        break;
                    case 'site':
                        $adminData = $this->getAdminData('site', $section);
                        break;
                    default:
                        throw new KurogoConfigurationException("Invalid config type $type");
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
                    throw new KurogoConfigurationException("Invalid data for $type $section");
                }
                
                switch ($type)
                {
                    case 'module':
                    
                        if ($section == 'overview') {
                            foreach ($data as $moduleID=>$props) {
                                $module = WebModule::factory($moduleID);
                                
                                if (!is_array($props)) {
                                    throw new KurogoConfigurationException("Invalid properties for $type $section");
                                }
                                
                                $valid_props = array('protected','secure','disabled','search');
                                foreach ($props as $key=>$value) {
                                    if (!in_array($key, $valid_props)) {
                                        throw new KurogoConfigurationException("Invalid property $key for module $module");
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
                            $module = WebModule::factory($moduleID);
                            $type = $module;
                        }

                        break;
        
                    case 'site':
                        break;
                    default:
                        throw new KurogoConfigurationException("Invalid type $type");
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
                        $module = WebModule::factory($moduleID);
                        $sectionData = $this->getAdminData($module, $section);
                        $config = $module->getConfig($sectionData['config']);
                        break;
                    default:
                        throw new KurogoConfigurationException("Invalid type $type");
                }
                        
                if (!isset($sectionData['sections']) || (!isset($sectionData['sectiondelete']) || !$sectionData['sectiondelete'])) {
                    throw new KurogoConfigurationException("Config '$section' of module '$moduleID' does not permit removal of items");
                }

                if (!isset($sectionData['sections'][$key])) {
                    throw new KurogoConfigurationException("Section $key not found in config '$section' of module '$moduleID'");
                }

                Kurogo::log(LOG_NOTICE, "Removing section $section from ". $this->getTypeStr($type) . " $subsection", 'admin');
                if (!$result = $config->removeSection($key)) {
                    throw new KurogoException("Error removing item $key from config '" . $sectionData['config'] ."'");
                } else {
                    $config->saveFile();
                }
                
                $this->setResponse(true);
                $this->setResponseVersion(1);
                break;

            case 'setmodulelayout':
                
                Kurogo::log(LOG_NOTICE, "Updating module layout", 'admin');
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
