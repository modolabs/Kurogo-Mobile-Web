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
  
  protected function getAccessControlLists($type) {
    return array(AccessControlList::factory(AccessControlList::RULE_ACTION_ALLOW, 
                                            AccessControlList::RULE_TYPE_EVERYONE,
                                            AccessControlList::RULE_VALUE_ALL));
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
        case 'logoutConfirm':
            $authorityIndex = $this->getArg('authority');
            
            if (!$this->isLoggedIn($authorityIndex)) {
                $this->redirectTo('index', array());
            } elseif ($user = $this->getUser($authorityIndex)) {
                $authority = $user->getAuthenticationAuthority();
                $this->assign('message', sprintf("You are logged in as %s %s", $user->getFullName(), $multipleAuthorities ? '(' . $authority->getAuthorityTitle() . ')' : ''));
                $this->assign('url', $this->buildURL('logout', array('authority'=>$authorityIndex)));
                $this->assign('linkText', 'Logout');
                $this->setTemplatePage('message');
            } else {
                $this->redirectTo('index', array());
            }
            
            break;
        case 'logout':
            $this->setTemplatePage('message');
            $authorityIndex = $this->getArg('authority');
            $hard = $this->getArg('hard', false);

            if (!$this->isLoggedIn($authorityIndex)) {
                $this->redirectTo('index', array());
            } elseif ($authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex)) {
                $result = $session->logout($authority, $hard);
            } else {
                $this->redirectTo('index', array());
            }
                
            $this->assign('message', $result ? 'Logout Successful' : 'Logout failed');
        
            break;
            
        case 'login':
            $login          = $this->argVal($_POST, 'loginUser', '');
            $password       = $this->argVal($_POST, 'loginPassword', '');
            $options = array(
                'url'=>$url
            );
            
            $referrer = $this->argVal($_SERVER, 'HTTP_REFERER', '');
            $session  = $this->getSession();
            $session->setRemainLoggedIn($this->getArg('remainLoggedIn', 0));
            
            if ($this->argVal($_POST, 'login_link')) {
                $authorityIndex = key($this->argVal($_POST, 'login_link'));
            } else {
                $authorityIndex = $this->getArg('authority', AuthenticationAuthority::getDefaultAuthenticationAuthorityIndex());
            }
            $this->assign('authority', $authorityIndex);

            if ($this->isLoggedIn($authorityIndex)) {
                $this->redirectTo('index', $options);
            }                    
            
            if ($this->argVal($_POST, 'login_submit') && empty($login)) {
                $this->redirectTo('index', $options);
            }
            
            if ($authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex)) {
                $authority->setDebugMode($this->getSiteVar('DATA_DEBUG'));
                $result = $authority->login($login, $password, $session, $options);
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
                
                    $this->setTemplatePage('login');
                    $this->assign('message', 'Login Failed. Please check your login and password');
                    break;
                default:
                    $this->setTemplatePage('login');
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

                if (!$multipleAuthorities) {
                    $user = $this->getUser();
                    $this->redirectTo('logoutConfirm', array('authority'=>$user->getAuthenticationAuthorityIndex()));
                }

                $sessionUsers = $session->getUsers();
                $users = array();

                foreach ($sessionUsers as $authority=>$user) {
                    $users[] = array(
                        'title'=>sprintf("%s", $user->getFullName()),
                        'subtitle'=>$user->getAuthenticationAuthorityIndex(),
                        'url'  =>$this->buildBreadcrumbURL('logoutConfirm', array('authority'=>$user->getAuthenticationAuthorityIndex()), false)
                    );
                    if (isset($authenticationAuthorities[$authority])) {
                        unset($authenticationAuthorities[$authority]);
                    }

                    if (isset($authenticationAuthorityLinks[$authority])) {
                        unset($authenticationAuthorityLinks[$authority]);
                    }
                }

                $this->assign('users', $users);
                $this->assign('authenticationAuthorities', $authenticationAuthorities);
                $this->assign('authenticationAuthorityLinks', $authenticationAuthorityLinks);

                $this->setTemplatePage('loggedin');
            } else {
                $this->setTemplatePage('login');
            }
            break;
    }
  }

}

