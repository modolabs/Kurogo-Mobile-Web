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
 * A class to handle the parsing of data detail configuration and prepare navlist style structures to display the data
 * expects a module as a parameter. Public method is formatDetails()
 */
class DataObjectDetailsController
{
    protected $module;
    protected $configModule;

    /**
     * Constructor
     * @param Module $module The module owning this object. This class may call pageLinkForValue()
     */
    public function __construct(Module $module) {
        $this->module = $module;
        $this->configModule = $module->getConfigModule();
    }

    public function formatTabs(KurogoDataObject $object, $page) {

        $tabSections = Kurogo::getModuleSections($page . 'tabs', $this->configModule);
        $tabs = array();
        foreach ($tabSections as $tab => $tabData) {
            $details = $this->formatDetails($object, $page . '-' . $tab);
            $tabs[$tab] = $details;
        }
        return $tabs;
    }
    
    /**
     * Public method to parse the configuration file for a given object
     * @param KurogoDataObject $object the object to format
     * @param string configName the name of the config area that contains the basic configuration
     */
    public function formatDetails(KurogoDataObject $object, $configName) {
        
        $groups = $this->getDetailGroups($object, $configName);

        $details = array();
        foreach ($groups as $group => $groupData) {
            $detailGroup = array(
                'heading'   => Kurogo::arrayVal($groupData, 'groupheading', ''),
                'subheading'=> Kurogo::arrayVal($groupData, 'groupsubheading', ''),
                'type'      => $groupData['type'],
                'group'     => $group,
                'class'     => 'detailgroup-' . Kurogo::arrayVal($groupData, 'groupkey', $group),
            );    
            
            switch ($groupData['type'])
            {
                case 'html':
                    if ($groupData['html']) {
                        $detailGroup['html'] =  $groupData['html'];
                        $detailGroup['htmlclass'] =  trim(Kurogo::arrayVal($groupData, 'class') . " detailfield-" . $groupData['field']);
                    } else {
                        $detailGroup = null;
                    }
                    break;
                    
                case 'navlist':
                case 'list':        
                    if ($items = $this->getGroupItems($object, $groupData)) {
                        $detailGroup['subTitleNewline'] = Kurogo::arrayVal($groupData, 'subTitleNewline', 0);
                        $detailGroup['items'] = $items;
                    } else {
                        $detailGroup = null;
                    }
                    break;
                default:
                    throw new KurogoException("Unknown grouptype ". $groupData['type'] . "for $group");
            }
            if ($detailGroup) {
                $details[$group]= $detailGroup;
            }
        }

        return $details;
    }

    /* retrieves the detail groups for a particular config */
    protected function getDetailGroups(KurogoDataObject $object, $configName) {

        $detailFields = Kurogo::getModuleSections($configName, $this->configModule);
        $groups = array();

        foreach ($detailFields as $key => $keyData) {

            $group = Kurogo::arrayVal($keyData, 'group', 0);
            $grouptype = Kurogo::arrayVal($keyData, 'grouptype', 'navlist');

            //get the object we're using for this field
            $keyObject = $this->getObject($object, $keyData);

            if ($listType = Kurogo::arrayVal($keyData, 'list-type')) {

                $groupObjects = $this->getObjectField($keyObject, $key);
                if (!is_array($groupObjects)) {
                    throw new KurogoException("Result of " . get_class($keyObject) . " $key did not return an array");
                }
                foreach ($groupObjects as $objectKey => $groupObject) {
                    $_groups = $this->getDetailGroups($groupObject, 'list-' . $listType);
                    foreach ($_groups as $_groupKey => $_groupData) {
                        $groupObjectKey = $objectKey . '-' . $_groupKey;
                        $groupOptions = array('object'=>$groupObject, 'group'=>$groupObjectKey, 'groupkey'=>$_groupKey);
                        $groups[$groupObjectKey] = array_merge($groupOptions, $_groupData);
                    }
                }
                break;
            } else {

                if (!isset($groups[$group])) {
                    $groups[$group] = array();
                }
                
                if (isset($keyData['groupheading'])) {
                    $groups[$group]['groupheading'] = $keyData['groupheading'];
                    unset($keyData['groupheading']);
                } elseif (isset($keyData['groupheadingfield'])) {
                    $groups[$group]['groupheading'] = $this->getObjectField($keyObject, $keyData['groupheadingfield']);
                    unset($keyData['groupheadingfield']);
                }

                if (isset($keyData['groupsubheading'])) {
                    $groups[$group]['groupsubheading'] = $keyData['groupsubheading'];
                    unset($keyData['groupsubheading']);
                } elseif (isset($keyData['groupsubheadingfield'])) {
                    $groups[$group]['groupsubheading'] = $this->getObjectField($keyObject, $keyData['groupsubheadingfield']);
                    unset($keyData['groupsubheadingfield']);
                }

                if (isset($keyData['subTitleNewline'])) {
                    $groups[$group]['subTitleNewline'] = $keyData['subTitleNewline'];
                    unset($keyData['subTitleNewline']);
                }

                switch ($grouptype) 
                {
                    case 'html':
                        $groups[$group] = array_merge($groups[$group], $keyData);
                        $groups[$group]['html'] = $this->getObjectField($keyObject, $key);
                        $groups[$group]['type'] = $grouptype;
                        $groups[$group]['field'] = $key;
                        break;

                    case 'navlist':
                    case 'list':
                        $groups[$group]['type'] = $grouptype;
                        $groups[$group]['fields'][$key] = $keyData;
                        break;
                    
                    default:
                        throw new KurogoConfigurationException("Invalid grouptype $grouptype for $key");
                }
            }
            
            
        }
        return $groups;
    }

    /* gets data from the object. Either calls a method or gets an attribute */
    protected function getObjectField($object, $field) {
        if (!$object instanceOf KurogoDataObject) {
            return null;
        }
        
        $method = "get" . $field;
        if (is_callable(array($object,$method))) {
            return $object->$method();                    
        }

        return $object->getAttribute($field);
    }

    /* prepare a particular group's items */
    protected function getGroupItems(KurogoDataObject $object, $groupData) {

        $groupobject = $this->getObject($object, $groupData);         
        $items = array();

        foreach ($groupData['fields'] as $field=>$fieldData) {

             if ($fieldobject = $this->getObject($groupobject, $fieldData)) {
                
                $value = $this->getObjectField($fieldobject, $field);

                //if we got an array and there is a delimiter option then implode the answers
                if (is_array($value) && isset($fieldData['delimiter'])) {
                    $value = implode($fieldData['delimiter'], $value);
                }

                //if the value is an array, then return an item for each item in the array
                if (is_array($value)) {
                    foreach ($value as $val) {
                        if (is_array($val)) {
                            throw new KurogoDataException(get_class($object) . " $field returned an array of arrays");
                        }

                        if (is_object($val)) {
                            $valueobject = $val;
                            $val = strval($val);
                        } else {
                            $valueobject = $fieldobject;
                        }
                        
                        if ($item = $this->formatDetailFieldInfo($val, $fieldData, $valueobject)) {
                            $item['class'] = Kurogo::arrayVal($item, 'class','') . " detailfield-$field";
                            $items[] = $item;
                        }
                    }
                } else {
                    
                    if ($item = $this->formatDetailFieldInfo($value, $fieldData, $fieldobject)) {
                        $item['listclass'] = trim(Kurogo::arrayVal($item, 'listclass') . " detailfield-$field");
                        $items[] = $item;
                    }
                }
             } else {
                // could not find an object for the field
             }
        }
        
        return $items;
    }
    
    /* retrieves the object based on the base object and the configuration
       if an object key is set then it could either be an object or see if there is a getXXX method to
       retrieve an object
     */
    protected function getObject(KurogoDataObject $object, $objectData) {
        if (isset($objectData['object'])) {
            if (is_object($objectData['object'])) {
                $object = $objectData['object'];
            } else {
                $method = "get" . $objectData['object'];
                if (is_callable(array($object,$method))) {
                    if ($obj = $object->$method()) {
                        if (!is_object($obj)) {
                            throw new KurogoException("$method on " . get_class($object) . " does not return an object");
                        }
                        $object = $obj;
                    } else {
                        $object = null;
                    }
                }
            }
        }
        return $object;

    }

    /* prepare the structure for a detail entry */
    protected function formatDetailFieldInfo($value, $info, KurogoDataObject $object) {

        // if the value is empty then see if there is a ifBlankfield
        if (is_null($value) || (is_string($value) && strlen($value)==0)) {
            if (isset($info['ifBlank'])) {
                $info['title'] = $info['ifBlank'];
                $info['titlefield'] = null;
            } else {
                return null;
            }
        }

        $type = Kurogo::arrayVal($info, 'type', 'text');
        
        if (is_array($value)) {
	    	if (isset($info['format'])) {
	            $value = vsprintf($this->replaceFormat($info['format']), $value);
	        } else {
	            $delimiter = isset($info['delimiter']) ? $info['delimiter'] : ' ';
	            $value = implode($delimiter, $value);
	        }
        } elseif (is_object($value)) {
            if ($type == 'date') {
                if (!$value instanceOf DateTime) {
                    throw new KurogoDataException("Date type must be an instance of DateTime");
                }
                $value = $value->format('U');
            } else {
                throw new KurogoDataException("Value is an object. This needs to be traced");
            }
        }

        $detail = $info;

        foreach (array('title','subtitle','label','url','class','img', 'listclass','imagealt','imageheight','imagewidth') as $attrib) {
            if (isset($info[$attrib.'field'])) {
                $detail[$attrib] = $this->getObjectField($object, $info[$attrib.'field']);
            }
        }

        if (!isset($detail['class'])) {
            $detail['class'] = '';
        }

        switch($type) {
            case 'email':
                if (!isset($detail['title'])) {
                    $detail['title'] = str_replace('@', '@&shy;', $value);
                }
                $detail['url'] = "mailto:$value";
                $detail['class'] = trim(Kurogo::arrayVal($detail, 'class', '') . ' email');
                break;

            case 'phone':
                if (!isset($detail['title'])) {
                    $detail['title'] = str_replace('-', '-&shy;', $value);
                }

                if (strpos($value, '+1') !== 0) {
                    $value = "+1$value";
                }
                $detail['url'] = PhoneFormatter::getPhoneURL($value);
                $detail['class'] = trim(Kurogo::arrayVal($detail, 'class', '') . ' phone');
                break;
            case 'currency':
                if (!isset($detail['title'])) {
                    $detail['title'] = sprintf("$%s", number_format($value, 2));
                }
                break;
            case 'date':
                if (!isset($detail['title'])) {
                    $format = Kurogo::arrayVal($detail, 'format', '%m/%d/%Y');
                    $detail['title'] =  strftime($format, $value);
                }
                break;
            case 'text':
                if (!isset($detail['title'])) {
                    $detail['title'] = nl2br(trim($value));
                }
                break;
            case 'image':
                $url = $value;
                $alt = Kurogo::arrayVal($detail, 'imagealt');
                if ($height = Kurogo::arrayVal($detail, 'imageheight')) {
                    $height = sprintf('height="%d"', $height);
                }
                if ($width = Kurogo::arrayVal($detail, 'imagewidth')) {
                    $width = sprintf('width="%d"', $width);
                }
                if (!isset($detail['title'])) {
                    $detail['title'] = sprintf('<img src="%s" alt="%s" %s %s class="detailimage" />', $value, htmlentities($alt), $height, $width);
                }
                break;
            default:
                throw new KurogoConfigurationException("Unhandled type $type");
                break;
        }

        if (isset($info['module'])) {
            $modValue = $value;
            if (isset($info['value'])) {
                $modValue = $this->getObjectField($object, $info['value']);
            }
            $moduleLink = Kurogo::moduleLinkForValue($info['module'], $modValue, $this->module, $object);
            $detail = array_merge($moduleLink, $detail);
            $detail['class'] .= " " . Kurogo::arrayVal($moduleLink, 'class');

        } elseif (isset($info['page'])) {
            $pageValue = $value;
            if (isset($info['value'])) {
                $pageValue = $this->getObjectField($object, $info['value']);
            }

            $pageLink = $this->module->pageLinkForValue($info['page'], $pageValue, $object);
            $detail = array_merge($pageLink, $detail);
            $detail['class'] .= " " . Kurogo::arrayVal($pageLink, 'class');
        }

        return $detail;
    }
}