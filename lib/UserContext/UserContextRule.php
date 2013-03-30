<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

abstract class UserContextRule
{
    protected $resolution = UserContext::RESOLUTION_TYPE_AUTO;
    protected $active = false;
	abstract public function evaluate();
	
	public function setContext($bool) {
		throw new KurogoException("This context cannot be set manually");
	}

	public function getResolution() {
		return $this->resolution;
	}
	
	public function getContextArgs() {
	    return array();
	}
		
    public static function factory($args) {
    
    	if (!$class = Kurogo::arrayVal($args, 'RULE', 'CookieContextRule')) {
            throw new KurogoConfigurationException("Context class must be specified in RULE key");
    	}
        
        if (!class_exists($class)) {
            throw new KurogoConfigurationException("UserContextRule class $class not defined");
        }
        
        $rule = new $class;
        
        if (!$rule instanceOf UserContextRule) {
            throw new KurogoConfigurationException(get_class($rule) . " is not a subclass of UserContextRule");
        }
        
        $rule->init($args);
        return $rule;
    }
    
    protected function init($args) {
    }
}