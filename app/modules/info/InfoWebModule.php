<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class InfoWebModule extends WebModule {
  protected $id = 'info';
    protected $includeCommonCSS = false;

  protected function initializeForPage() {
  	//get links from module.ini [links]
	 $links = $this->getOptionalModuleSection('links');
	 $this->assign('links', $links);

  	//get app Data
	 $appData = Kurogo::getAppData();
	 $this->assign('appData',$appData);
	 
	 //get module data from modules.ini
	 $modulesData = $this->getOptionalModuleSections('modules');
	 foreach ($modulesData as $moduleID=>&$moduleData) {
	    $moduleData['icon'] = Kurogo::getOptionalModuleVar('icon', $moduleID, $moduleID, 'module', 'module');
	 }
	 $this->assign('modulesData', $modulesData);

	 $args = array();
	 if (Kurogo::getSiteVar('COMPUTER_TABLET_ENABLED', 'themes')) {
	    $args['setdevice'] = 'compliant';
	}
     $previewURL = $this->buildURLForModule($this->getHomeModuleID(), 'index', $args);
	 $this->assign('previewURL', $previewURL);
  }
}
