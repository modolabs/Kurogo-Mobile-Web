<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

//
// This class is instantiated for modules with WebBridge support
// but which do not have an API.
//
class KurogoWebBridgeAPIModule extends APIModule {
    protected $id = '';
    protected $vmin = 1;
    protected $vmax = 1;
    
    // web bridge modules do not know their ids
    public function setID($id) {
        $this->id = $id;
        if (!$this->configModule) {
            $this->configModule = $this->id;
        }
    }
    
    protected function initializeForCommand() {
        switch ($this->command) {
            default:
                $this->invalidCommand();
                break;
        }
    }
}
