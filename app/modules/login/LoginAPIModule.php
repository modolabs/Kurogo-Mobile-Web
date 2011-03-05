<?php

class LoginAPIModule extends APIModule
{
    protected $id = 'login';
    protected $vmin = 1;
    protected $vmax = 1;
    public function availableVersions() {
        return array(1);
    }
    
    public function initializeForCommand() {  
        if (!$this->getSiteVar('AUTHENTICATION_ENABLED')) {
            throw new Exception("Authentication is not enabled on this site");
        }
        
        switch ($this->command) {
            case 'session':
                $session = $this->getSession();
                $user = $this->getUser();
                
                $response = array(
                    'session_id'=>$session->getSessionID(),
                    'token'=>$session->getLoginToken(),
                    'user'=>array(
                        'authority'=>$user->getAuthenticationAuthorityIndex(),
                        'userID'=>$user->getUserID(),
                        'name'=>$user->getFullName()
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