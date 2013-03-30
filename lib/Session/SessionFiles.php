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
  * @package Authentication
  */
class SessionFiles extends Session
{
    protected function init($args) {
        parent::init($args);
        
        ini_set('session.save_handler', 'files');
        
        // make sure session directory exists
        if (!is_dir(CACHE_DIR . "/session")) {
            mkdir(CACHE_DIR . "/session", 0700, true);
        }
        
        ini_set('session.save_path', CACHE_DIR . "/session");
    }    

    protected function saveLoginTokenData($new_login_token, $expires, $data) {
                    
        $params = array(
            'timestamp'=>time(),
            'expires'=>$expires,
            'data'=>$data
        );
                
        $file = $this->loginTokenFile($new_login_token);
        if ($this->login_token) {
            $oldfile = $this->loginTokenFile($this->login_token);
            unlink($oldfile);
        }
        
        file_put_contents($file, serialize($params));
        chmod($file, 0600);
    }
    
    protected function getLoginTokenData($token) {
        $file = $this->loginTokenFile($token);
        $data = false;
        if (file_exists($file)) {
            if ($data = file_get_contents($file)) {
                $data = unserialize($data);
                if ($data['expires']<time()) {
                    $data = false;
                }
            }
        }
        
        return $data;
    }
    
    protected function clearLoginTokenData($token) {
        $file = $this->loginTokenFile($token);
        @unlink($file);

        // clean up expired cookies
        $files = glob($this->loginTokenFolder() . "/login_*");
        foreach ($files as $file) {
            if ($data = file_get_contents($file)) {
                $data = unserialize($data);
                if ($data['expires']<time()) {
                    unlink($file);
                }
            }
        }
    }
    
    private function loginTokenFolder() {
        return ini_get('session.save_path');
    }
    
    private function loginTokenFile($token) {
        return $this->loginTokenFolder() . "/login_" . $token;
    }
}
