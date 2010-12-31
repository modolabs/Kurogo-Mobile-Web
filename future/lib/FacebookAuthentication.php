<?php

class FacebookAuthentication extends AuthenticationAuthority
{
    protected $api_key;
    protected $api_secret;
    protected $redirect_uri;
    protected $access_token;
    protected $expires;
    
    public function auth($login, $password, &$user)
    {
        return AUTH_FAILED;
    }

    // facebook can only get the current user
    public function getUser($login)
    {
        if (empty($login)) {
            return new AnonymousUser();       
        }
        
        if ($user = $this->getCurrentUser()) {
            if ($user->getUserID() == $login) {
                return $user;
            }
        }

        return false;
    }
    
    public function getCurrentUser()
    {
        if ($this->access_token) {
            $url = "https://graph.facebook.com/me?" . http_build_query(array(
            'access_token'=>$this->access_token
            ));

            if ($result = @file_get_contents($url)) {
                if ($vars = json_decode($result, true)) {
                    $user = new FacebookUser($this);
                    $user->setUserID($vars['id']);
                    $user->setFirstName($vars['first_name']);
                    $user->setLastName($vars['last_name']);
                    if (isset($vars['email'])) {
                        $user->setEmail($vars['email']);
                    }
                    
                    return $user;
                }
            } else {
                throw new Exception("Error getting user: " . $result);
            }
        }
        return false;
    }
    
    public function login($login, $pass, Module $module)
    {
        if (isset($_GET['code'])) {
            $this->redirect_uri = $_SESSION['redirect_uri'];
            $code = $_GET['code'];
            
            $url = "https://graph.facebook.com/oauth/access_token?" . http_build_query(array(
            'client_id'=>$this->api_key,
            'redirect_uri'=>$this->redirect_uri,
            'client_secret'=>$this->api_secret,
            'code'=>$_GET['code']
            ));
                                    
            if ($result = @file_get_contents($url)) {
                $vars = explode("&", $result);
                foreach ($vars as $var) {
                    $var = explode("=", $var);
                    $arg = $var[0];
                    $value = $var[1];
                    switch ($arg) 
                    {
                        case 'access_token':
                        case 'expires':
                            $this->$arg = $_SESSION['fb_' . $arg] = $value;                        
                            break;
                    }
                }
                
                if ($user = $this->getCurrentUser()) {
                    $session = $module->getSession();
                    $session->login($user);
                    return AUTH_OK;
                }  else {
                    return AUTH_FAILED;
                }

            } else {
                return AUTH_FAILED;
            }
            
        } elseif (isset($_GET['error'])) {
            //most likely the user denied
            return AUTH_FAILED;
        } else {
            $this->redirect_uri = $_SESSION['redirect_uri'] = FULL_URL_BASE . 'login/login?' . http_build_query(array('authority'=>$this->getAuthorityIndex()));
            $url = "https://graph.facebook.com/oauth/authorize?" . http_build_query(array(
            'client_id'=>$this->api_key,
            'redirect_uri'=>$this->redirect_uri,
            'scope'=>'user_about_me,email'
            ));

            header("Location: $url");
            exit();
        }
    }
    
    public function logout(Module $module)
    {
        unset($_SESSION['fb_expires']);
        unset($_SESSION['fb_access_token']);
        parent::logout($module);
    }

    //does not support groups
    public function getGroup($group)
    {
        return false;
    }

    public function init($args)
    {
        $args = is_array($args) ? $args : array();
        if (!isset($args['API_KEY']) || !isset($args['API_SECRET'])) {
            throw new Exception("API key and secret not set");
        }
        
        $this->api_key = $args['API_KEY'];
        $this->api_secret = $args['API_SECRET'];
        if (isset($_SESSION['fb_access_token'])) {
            $this->access_token = $_SESSION['fb_access_token'];
        }
    }
}

class FacebookUser extends BasicUser
{
}