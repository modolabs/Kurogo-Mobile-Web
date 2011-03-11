<?php
/**
 * @package Config
 */

/**
 * Class to load and parse ini files for modules
 * @package Config
 */
class ModuleConfigFile extends ConfigFile {

    // loads a config object from a file/type combination  
    public static function factory($id, $type, $options=0) {
        $config = new ModuleConfigFile();
        
        if (!$result = $config->loadFileType($id, $type, $options)) {
            die("FATAL ERROR: cannot load $type configuration file: " . self::getfileByType($id, $type));
        }
    
        return $config;
    }
  
    protected function getFileByType($id, $type)
    {
        if (preg_match("/-default$/", $type)) {
            $files = array( 
                sprintf('%s/%s/config/%s.ini', SITE_MODULES_DIR, $id, $type),
                sprintf('%s/%s/config/%s.ini', MODULES_DIR, $id, $type),
                sprintf('%s/common/config/%s.ini', APP_DIR, $type)
            );
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    return $file;
                }
            }
            
            return false;
        } else {
            $file = sprintf('%s/%s/%s.ini', SITE_CONFIG_DIR, $id, $type);
        }
        
        return $file;
    }
    

}
