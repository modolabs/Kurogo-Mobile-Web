<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class DownloadWebModule extends WebModule {
    protected $id = 'download';
    
    protected function initializeForPage() {
        $this->assign('deviceName',   Kurogo::getOptionalSiteVar($this->platform, null, 'deviceNames'));
        $this->assign('introduction', $this->getOptionalModuleVar('introduction', null, $this->platform, 'apps'));
        $this->assign('instructions', $this->getOptionalModuleVar('instructions', null, $this->platform, 'apps'));
        $this->assign('downloadUrl',  $this->getOptionalModuleVar('url', null, $this->platform, 'apps'));
    }
}
