<?php

require_once realpath(LIB_DIR.'/Module.php');

class LoginModule extends Module {
  protected $id = 'login';
  
  protected function initialize() {
        
  }

  protected function initializeForPage() {
    if (!$this->getSiteVar('AUTHENTICATION_ENABLED')) {
        throw new Exception("Authentication is not enabled");
    }
    
    $url = $this->getArg('url', ''); //return url
    $this->assign('url', $url);
    $user = $this->getUser();

    $authenticationAuthorities = array();                
    $authenticationAuthorityLinks = array();                
    foreach (AuthenticationAuthority::getDefinedAuthenticationAuthorities() as $authority=>$authorityData) {
        if (isset($authorityData['URL'])) {
            $authorityData['LINK'] = $this->buildBreadcrumbURL('login', array('url'=>$url,'authority'=>$authority), false);
            $authenticationAuthorityLinks[$authority] = $authorityData;
        } else {
            $authenticationAuthorities[$authority] = $authorityData;
        }
    }
                    
    if (count($authenticationAuthorities)==0 && count($authenticationAuthoritysLinks)==0) {
        throw new Exception("No authentication authorities have been defined");
    }
    
    $this->assign('authenticationAuthorities', $authenticationAuthorities);
    $this->assign('authenticationAuthorityLinks', $authenticationAuthorityLinks);
    
    switch ($this->page)
    {
        case 'logout':
            $this->setTemplatePage('message');
            if (!$this->isLoggedIn()) {
                $this->redirectTo('login');
            } else {
                $result = $this->session->logout($user);
                $this->assign('message', 'Logout Successful');
                $this->assign('session_userID', $user->getUserID());
            }
        
            break;
            
        case 'login':
            $login = $this->argVal($_POST, 'loginUser', '');
            $password = $this->argVal($_POST, 'loginPassword', '');
            
            $authorityIndex = $this->getArg('authority', AuthenticationAuthority::getDefaultAuthenticationAuthority());
            $this->assign('authority', $authorityIndex);

            if ($this->isLoggedIn()) {
                $this->redirectTo('index');
            }                    
            
            if ($this->argVal($_POST, 'login_submit') && empty($login)) {
                $this->redirectTo('index');
            }
            
            if ($authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex)) {
                $result = $authority->login($login, $password, $this->session, $user);
            } else {
                $this->redirectTo('index');
            }

            switch ($result)
            {
                case AUTH_OK:
                    if ($url) {
                        header("Location: $url");
                        exit();
                    } 
                    $this->setTemplatePage('message');
                    $this->assign('message', 'Login Successful');
                    break;

                case AUTH_FAILED:
                case AUTH_USER_NOT_FOUND:
                
                    $this->setTemplatePage('index');
                    $this->assign('message', 'Login Failed. Please check your login and password');
                    break;
                default:
                    $this->setTemplatePage('index');
                    $this->assign('message', "Login Failed. An unknown error occurred $result");
                    

            }

            $this->assign('session_userID', $user->getUserID());
            break;
        case 'index':
            if ($this->isLoggedIn()) {
                $user = $this->getUser();
                $this->setTemplatePage('message');
                $this->assign('message', "You are logged in as " . $user->getUserID());
                $this->assign('url', $this->buildURL('logout'));
                $this->assign('linkText', 'Click here to logout.');
            }
            break;
    }
  }

}

