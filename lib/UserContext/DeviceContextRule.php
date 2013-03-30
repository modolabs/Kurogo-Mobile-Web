<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class DeviceContextRule extends UserContextRule
{
	protected $pagetype;
	protected $platform;
	protected $browser;

	protected function init($args) {
		parent::init($args);
		$this->pagetype = Kurogo::arrayVal($args, 'PAGETYPE', null);
		$this->platform = Kurogo::arrayVal($args, 'PLATFORM', null);
		$this->browser = Kurogo::arrayVal($args, 'BROWSER', null);
		
		if (strlen($this->pagetype)==0 && strlen($this->platform)==0 && strlen($this->browser)==0) {
		    throw new KurogoConfigurationException("PAGETYPE, PLATFORM or BROWSER must be specified in " . get_class($this));
		}
	}
	
	public function evaluate() {

        $eval = true;
	    if (isset($this->pagetype)) {
	        $eval = $eval && (Kurogo::getPagetype() == $this->pagetype);
	    }

	    if (isset($this->platform)) {
	        $eval = $eval && (Kurogo::getPlatform() == $this->platform);
	    }

	    if (isset($this->browser)) {
	        $eval = $eval && (Kurogo::getBrowser() == $this->browser);
	    }
	    
	    return $eval;
	}
	
}