<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class CookieContextRule extends UserContextRule
{
	const COOKIE_PREFIX='kgoContext_';
	protected $cookie;
	protected $value;
    protected $resolution = UserContext::RESOLUTION_TYPE_MANUAL;

	public function getContextArgs() {
	    return array($this->cookie => $this->value);
	}

	protected function init($args) {
		parent::init($args);
		if (!$cookie = Kurogo::arrayVal($args, 'GROUP', SITE_NAME)) {
			throw new KurogoConfigurationException("GROUP not set");
		}
		
		$this->cookie = self::COOKIE_PREFIX . $cookie;
		$this->value = Kurogo::arrayVal($args, 'COOKIE_VALUE', $args['ID']);
	}
	
	public function evaluate() {
		$cookie = Kurogo::arrayVal($_COOKIE, $this->cookie);
        //see if the user chose this
	    if (isset($_GET[$this->cookie])) {
            if ($_GET[$this->cookie] == $this->value) {
                $this->setContext(true);
                $cookie = $this->value;
            } elseif ($cookie == $this->value) {
                $this->setContext(false);
                $cookie = null;
            }
        } elseif ($this->value == UserContext::CONTEXT_DEFAULT && !isset($cookie)) {
            return true;
        }
	
    	return $cookie == $this->value;
	}
	
	protected function setCookie() {
        $expires = Kurogo::getSiteVar('CONTEXT_COOKIE_LIFESPAN', 'cookies');
        setcookie($this->cookie, $this->value, time() + $expires, COOKIE_PATH);
        $_COOKIE[$this->cookie] = $this->value;
	}

	protected function clearCookie() {
        setcookie($this->cookie, false, 1, COOKIE_PATH);
        unset($_COOKIE[$this->cookie]);
	}

	public function setContext($bool) {
	    if ($this->value == UserContext::CONTEXT_DEFAULT) {
	        if ($bool && isset($_COOKIE[$this->cookie])) {
	            $this->clearCookie();
	        }
	        return true;
	    }
	    
	    if ($bool) {
	        Kurogo::log(LOG_DEBUG,"Setting CookieContext $this->cookie to $this->value", 'context');
	        $this->setCookie();
        } elseif (Kurogo::arrayVal($_COOKIE, $this->cookie) == $this->value) {
	        Kurogo::log(LOG_DEBUG,"Clearing CookieContext $this->cookie by $this->value", 'context');
	        $this->clearCookie();
        }
        return true;
	}

}