<?php
/**
  * @package Module
  * @subpackage Login
  */

/**
  */
require_once realpath(LIB_DIR.'/Module.php');

/**
  * @package Module
  * @subpackage Login
  */
class LoginModule extends Module {
  protected $id = 'login';
  
  protected function initialize() {
        
  }

  protected function initializeForPage() {
    if (!$this->getSiteVar('AUTHENTICATION_ENABLED')) {
        throw new Exception("Authentication is not enabled on this site");
    }
    
    $url = $this->getArg('url', ''); //return url
    $this->assign('url', $url);
    $session = $this->getSession();

    $authenticationAuthorities = array();                
    $authenticationAuthorityLinks = array();                
    foreach (AuthenticationAuthority::getDefinedAuthenticationAuthorities() as $authorityIndex=>$authorityData) {
        $USER_LOGIN = $this->argVal($authorityData, 'USER_LOGIN', 'NONE');
        
        if ($USER_LOGIN=='FORM') {
            $authenticationAuthorities[$authorityIndex] = $authorityData;
        } elseif ($USER_LOGIN=='LINK') {
            $authorityData['LINK'] = $this->buildBreadcrumbURL('login', array(
                'url'=>$url,
                'authority'=>$authorityIndex, 
                'startOver'=>true), false);
            $authenticationAuthorityLinks[$authorityIndex] = $authorityData;
        }
    }
                    
    if (count($authenticationAuthorities)==0 && count($authenticationAuthorityLinks)==0) {
        throw new Exception("No authentication authorities have been defined");
    }
    
    $this->assign('authenticationAuthorities', $authenticationAuthorities);
    $this->assign('authenticationAuthorityLinks', $authenticationAuthorityLinks);
    
    $multipleAuthorities = count($authenticationAuthorities) + count($authenticationAuthorityLinks) > 1;
    
    switch ($this->page)
    {
        case 'logout':
            $this->setTemplatePage('message');
            if (!$this->isLoggedIn()) {
                $this->redirectTo('login');
            } else {
                $user = $this->getUser();
                $authority = $user->getAuthenticationAuthority();
                $authority->logout($this);
                $this->assign('message', 'Logout Successful');
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
                $result = $authority->login($login, $password, $this);
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

            break;
        case 'index':
            if ($this->isLoggedIn()) {
                $user = $this->getUser();
                $authority = $user->getAuthenticationAuthority();
                $this->setTemplatePage('message');
                
                $this->assign('message', sprintf("You are logged in as %s %s", $user->getFullName(), $multipleAuthorities ? '(' . $authority->getAuthorityTitle() . ')' : ''));
                
                $this->assign('url', $this->buildURL('logout'));
                $this->assign('linkText', 'Logout');
            }
            break;
    }
  }

}

