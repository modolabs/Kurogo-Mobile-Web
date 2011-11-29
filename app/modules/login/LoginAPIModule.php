<?php

class LoginAPIModule extends APIModule
{
    protected $id = 'login';
    protected $vmin = 1;
    protected $vmax = 1;
    public function availableVersions() {
        return array(1);
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
                $user = $this->getUser();
                
                $response = array(
                    'session_id'=>$session->getSessionID(),
                    'token'=>$session->getLoginToken(),
                    'user'=>array(
                        'authority'=>$user->getAuthenticationAuthorityIndex(),
                        'userID'=>$user->getUserID(),
                        'name'=>$user->getFullName(),
                        'sessiondata'=>$user->getSessionData()
                    )
                        
                );

                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;
                
            default:
                $this->invalidCommand();
                break;
        }
    }
   
}