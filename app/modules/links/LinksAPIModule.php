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
  * @package Module
  * @subpackage Links
  */

/**
  * @package Module
  * @subpackage Links
  */
class LinksAPIModule extends APIModule {
    protected $id = 'links';
    protected $vmin = 1;
    protected $vmax = 2;
    protected $linkGroups;

    protected function getLinkGroup($group) {
        if (!$this->linkGroups) {
            $this->linkGroups = $this->getModuleSections('links-groups');
        }

        if (isset($this->linkGroups[$group])) {
            if (!isset($this->linkGroups[$group]['links'])) {
                $this->linkGroups[$group]['links'] = $this->getLinkData($group);
            }

            if (!isset($this->linkGroups[$group]['description'])) {
                $this->linkGroups[$group]['description'] = $this->getOptionalModuleVar('description', '', 'strings');
            }

            if (!isset($this->linkGroups[$group]['description_footer'])) {
                $this->linkGroups[$group]['description_footer'] = $this->getModuleVar('description_footer', '', 'strings');
            }

            return $this->linkGroups[$group];
        } else {
            throw new KurogoConfigurationException("Unable to find link group information for $group");
        }
    }

    public function getLinks($group=null) {
        if (isset($group) && $group) {
            return $this->getModuleSections('links-' . $group);
        } else {
            return $this->getModuleSections('links');
        }
    }

    protected function getLinkData($group=null) {
        $links = $this->getLinks($group);

        foreach ($links as &$link) {
            if (isset($link['icon']) && strlen($link['icon'])) {
                $link['iconURL'] = FULL_URL_BASE . "modules/{$this->configModule}/images/{$link['icon']}.png";
            }

            if (isset($link['group']) && strlen($link['group'])) {
                $group = $this->getLinkGroup($link['group']);
                if (!isset($link['title']) && isset($group['title'])) {
                    $link['title'] = $group['title'];
                }
            }
        }

        return $links;
    }

    public function initializeForCommand() {

        switch ($this->command) {
            case 'group':
                $group = $this->getLinkGroup($this->getArg('group'));
                $response = array();
                $displayType = isset($group['display_type']) ? $group['display_type'] : $this->getModuleVar('display_type');
                $response['links'] = array_values($group['links']);
                $response['title'] = $group['title'];
                $response['displayType'] = $displayType;
                $response['description'] = $group['description'];
                $response['description_footer'] = $group['description_footer'];

                $this->setResponse($response);
                $this->setResponseVersion(2);
                break;

           case 'index':
                $links = $this->getLinkData();
                $response = array();
                $response['description'] = $this->getOptionalModuleVar('description','', 'strings');
                $response['description_footer'] = $this->getOptionalModuleVar('description_footer','','strings');
                $response['displayType'] =  $this->getModuleVar('display_type');
                $response['links'] =  array_values($links);
                
                $this->setResponse($response);
                $this->setResponseVersion(2);
                break;


            default:
                $this->invalidCommand();
                break;
        }
    }
}

