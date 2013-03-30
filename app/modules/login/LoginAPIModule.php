<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class LoginAPIModule extends APIModule
{
    protected $id = 'login';
    protected $vmin = 1;
    protected $vmax = 2;
    public function availableVersions() {
        return array(1,2);
    }

    protected function getAccessControlLists($type) {
        return array(AccessControlList::allAccess());
    }

    public function isEnabled() {
        return Kurogo::getSiteVar('AUTHENTICATION_ENABLED') && parent::isEnabled();
    }

    public function initializeForCommand() {  
        if (!Kurogo::getSiteVar('AUTHENTICATION_ENABLED')) {
            throw new KurogoConfigurationException("Authentication is not enabled on this site");
        }
        
        switch ($this->command) {
            case 'logout':
                if (!$this->isLoggedIn()) {
                    $this->redirectTo('session');
                } else {
                    $session = $this->getSession();
                    $user = $this->getUser();

                    $hard = $this->getArg('hard', false);
                    $authorityIndex = $this->getArg('authority', false);
                    if ($authorityIndex) {
                      $authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex);
                    } else {
                      $authority = $user->getAuthenticationAuthority();
                    }
                    
                    $session->logout($authority, $hard);
                    $this->redirectTo('session');
                }

                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;
                
           case 'getuserdata':
                $key = $this->getArg('key', null);
                $user = $this->getUser();
                $response = $user->getUserData($key);
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;
                
           case 'session':
                $session = $this->getSession();
				$response = array(
					'session_id'=>$session->getSessionID(),
					'token'=>$session->getLoginToken(),
				);
				
				// version 2 implements multiple identities into the response
                if ($this->requestedVersion == 2) {
                	$response['users'] = array();
                	$users = $session->getUsers();
                	foreach ($users as $user) {
                		$authority = $user->getAuthenticationAuthority();
                		$response['users'][] = array(
							'authority'=>$authority->getAuthorityIndex(),
							'authorityTitle'=>$authority->getAuthorityTitle(),
							'userID'=>$user->getUserID(),
							'name'=>$user->getFullName(),
							'sessiondata'=>$user->getSessionData()
                		);
                	}
					$this->setResponseVersion(2);
                } else {
					// version 1 assumes only 1 user
					$user = $this->getUser();
					$response['user'] = array(
						'authority'=>$user->getAuthenticationAuthorityIndex(),
						'userID'=>$user->getUserID(),
						'name'=>$user->getFullName(),
						'sessiondata'=>$user->getSessionData()
					);
					$this->setResponseVersion(1);
				}

                $this->setResponse($response);
                break;
                
            default:
                $this->invalidCommand();
                break;
        }
    }
   
}
