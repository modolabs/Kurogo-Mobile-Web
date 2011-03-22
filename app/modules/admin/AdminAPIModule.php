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
            if (!$configData = json_decode(file_get_contents(MODULES_DIR . "/admin/config/admin-site.json"), true)) {
                throw new Exception("Error parsing " . MODULES_DIR . "/admin/config/admin-site.json");
            }
            
            foreach ($configData as $section=>&$data) {
                $data['fields'] = isset($data['fields']) ? $data['fields'] : array();
                foreach ($data['fields'] as &$field) {
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
                }
            }
        }
        
        return $configData;
    }
    
    private function getSection($type, $section) {
        switch ($type)
        {
            case 'site':
                $configData = $this->getSiteAdminConfig();
                if (!isset($configData[$section])) {
                    throw new Exception("Invalid section $section");
                }
                
                $sectionData = $configData[$section];
                foreach ($sectionData['fields'] as $key=>$data) {
                    
                }
                
                return $sectionData;
                break;
                
            default:
                throw new Exception("Invalid type $type");
            break;
        }
    }
    
    public function initializeForCommand() {  
        $this->requiresAdmin();
        
        switch ($this->command) {
            case 'getsectiondata':
                $type = $this->getArg('type');
                $section = $this->getArg('section');

                $sectionData = $this->getSection($type, $section);                
                
                $this->setResponse($sectionData);
                $this->setResponseVersion(1);
                break;
                
            default:
                $this->invalidCommand();
                break;
        }
    }
   
}