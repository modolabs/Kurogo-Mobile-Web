<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
 * @package Config
 */

/**
 * Class to load and parse ini files
 * @package Config
 */
class ConfigFile extends Config {
  protected $lastModified;
  protected $files = array();

  public function setFile($type, $file) {
    if ($result = $this->loadFile($file)) {
        $this->files[$type] = $result;
    }
        
    return $result;
  }

  public function getFile($type) {
      return isset($this->files[$type]) ? $this->files[$type] : null;
  }
  
  public function getFiles() {
      return $this->files;
  }
  
  public function getContextData($context) {
     $_file = ConfigFileStore::fileVariant($this->getFile('base'), $context);
     if (!$file = realpath_exists($_file)) {
        return array();
     }
  
     $vars = parse_ini_file($file, true);
     return $vars;
  }

  public function getLastModified() {
    if ($this->lastModified) {
        return $this->lastModified;
    }
    $this->lastModified = null;
    foreach ($this->files as $file) {
        $modified = filemtime($file);
        if ($modified > $this->lastModified) {
            $this->lastModified = $modified;
        }
    }
    return $this->lastModified;
  }

  /* load the actual file */
  protected function loadFile($_file, $safe = true) {
     if (strlen($_file)==0) {
        return false;
     }
     
     if (!$file = realpath_exists($_file, $safe)) {
        return false;
     }
  
     $vars = parse_ini_file($file, false);
     $this->addVars($vars);

     $sectionVars = parse_ini_file($file, true);
     $this->addSectionVars($sectionVars);
     
     Kurogo::log(LOG_INFO, "Loaded config file $file", 'config');
     return $file;
  }
  
  protected function saveValue($value) {
    $constCheck = array(
        'FULL_URL_BASE',
        'LOG_DIR',
        'CACHE_DIR',
        'LIB_DIR',
        'DATA_DIR',
        'SITE_DIR',
        'ROOT_DIR'
    );
        
    if (preg_match("/^\d+$/", $value)) {
        //it's numeric
        return $value;
    } else {

        //replace double quotes with a constant
        $value = str_replace('"', '"_QQ_"', $value);
        //quote the values
        $return = sprintf('"%s"', $value);

        //replace constants if they are there
        foreach ($constCheck as $const) {
            if (defined($const)) {
                $constValue = constant($const);
                $i = strpos($return, $constValue);
                if ($i !== false) {
                    if ($i==1) {
                        $return = $const . '"' . substr($return, $i+strlen($constValue));
                    }
                }
            }
        }
        
        return $return;
    }
  }
  
  public function getSaveData() {

      $string = array();
      foreach ($this->sectionVars as $section=>$sectionData) {
        if (is_array($sectionData)) {
            if (count($string)>0) {
                $string[] = '';
            }
            if (isset($sectionData[0])) {
                foreach ($sectionData as $value) {
                    $string[] = sprintf("%s[] = %s", $section, $this->saveValue($value));
                }
                $sectionData = array();
            } elseif ($section !== 'No Section') {
                $string[] = sprintf("[%s]", $section);
            }

            foreach ($sectionData as $var=>$value) {
                if (is_scalar($value)) {
                    $string[] = sprintf('%s = %s', $var, $this->saveValue($value));
                } elseif (isset($value[0])) {
                    foreach ($value as $_value) {
                        $string[] = sprintf("%s[] = %s", $var, $this->saveValue($_value));
                    }
                } else {
                    Kurogo::log(LOG_WARNING, "Error parsing non scalar value for $var in " . $this->filepath, 'config');
                }
            }
        } else {
            $string[] = sprintf('%s = %s', $section, $this->saveValue($sectionData));
        }
        
      }
      
      return implode("\n", $string);
  }

}
