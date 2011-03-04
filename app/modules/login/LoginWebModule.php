<?php
/**
  * @package Module
  * @subpackage Login
  */

/**
  * @package Module
  * @subpackage Login
  */
class LoginWebModule extends WebModule {
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
            $authenticationAuthorityLinks[$authorityIndex] = $authorityData;
        }
    }
                    
    if (count($authenticationAuthorities)==0 && count($authenticationAuthorityLinks)==0) {
        throw new Exception("No authentication authorities have been defined");
    }
    
    $this->assign('authenticationAuthorities', $authenticationAuthorities);
    $this->assign('authenticationAuthorityLinks', $authenticationAuthorityLinks);
    $this->assign('allowRemainLoggedIn', $this->getSiteVar('AUTHENTICATION_REMAIN_LOGGED_IN_TIME'));
    if ($forgetPasswordURL = $this->getModuleVar('FORGET_PASSWORD_URL')) {
        $this->assign('FORGET_PASSWORD_URL', $this->buildBreadcrumbURL('forgotpassword', array()));
    }
    
    $multipleAuthorities = count($authenticationAuthorities) + count($authenticationAuthorityLinks) > 1;
    
    switch ($this->page)
    {
        case 'logout':
            $this->setTemplatePage('message');
            if (!$this->isLoggedIn()) {
                $this->redirectTo('login', array());
            } else {
                $user = $this->getUser();
                $authority = $user->getAuthenticationAuthority();
                $authority->logout($this);
                $this->assign('message', 'Logout Successful');
            }
        
            break;
            
        case 'login':
            $login          = $this->argVal($_POST, 'loginUser', '');
            $password       = $this->argVal($_POST, 'loginPassword', '');
            $options = array(
                'url'=>$url,
                'remainLoggedIn'=> $this->getArg('remainLoggedIn', false)
            );
            
            $referrer = $this->argVal($_SERVER, 'HTTP_REFERER', '');
            
            if ($this->argVal($_POST, 'login_link')) {
                $authorityIndex = key($this->argVal($_POST, 'login_link'));
            } else {
                $authorityIndex = $this->getArg('authority', AuthenticationAuthority::getDefaultAuthenticationAuthorityIndex());
            }
            $this->assign('authority', $authorityIndex);

            if ($this->isLoggedIn()) {
                $this->redirectTo('index', $options);
            }                    
            
            if ($this->argVal($_POST, 'login_submit') && empty($login)) {
                $this->redirectTo('index', $options);
            }
            
            if ($authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex)) {
                $result = $authority->login($login, $password, $this, $options);
            } else {
                error_log("Invalid authority $authorityIndex");
                $this->redirectTo('index', $options);
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
        case 'forgotpassword':
            if ($forgetPasswordURL = $this->getModuleVar('FORGET_PASSWORD_URL')) {
                header("Location: $forgetPasswordURL");
                exit();
            } else {
                $this->redirectTo('index', array());
            }
            break;
        case 'index':
            if ($this->isLoggedIn()) {
                if ($url) {
                    header("Location: $url");
                    exit();
                }
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

