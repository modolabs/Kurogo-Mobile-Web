<?php

require_once realpath(LIB_DIR.'/Module.php');

class LoginModule extends Module {
  protected $id = 'login';
  
  protected function initialize() {
        
  }

  protected function initializeForPage() {
    
    $url = $this->getArg('url', ''); //return url
    $this->assign('url', $url);
    $user = $this->getUser();

    switch ($this->page)
    {
        case 'logout':
            $this->page = 'message';
            if (!$this->session->isLoggedIn()) {
                $this->redirectTo('login');
            } else {
                $result = $this->session->logout($user);
                $this->assign('message', 'Logout Successful');
            }
        
            break;
            
        case 'login':
            $login = $this->argVal($_POST, 'loginUser', '');

            if ($this->session->isLoggedIn() || empty($login)) {
                $this->redirectTo('index');
            }
            
            $password = $this->argVal($_POST, 'loginPassword', '');
            $result = $this->session->login($login, $password, $user);

            switch ($result)
            {
                case AUTH_OK:
                    if ($url) {
                        header("Location: $url");
                        exit();
                    } 
                    $this->page = 'message';
                    $this->assign('message', 'Login Successful');
                    break;

                case AUTH_FAILED:
                case AUTH_USER_NOT_FOUND:
                    $this->page = 'index';
                    $this->assign('message', 'Login Failed. Please check your login and password');
                    break;

            }
            break;
        case 'index':
            if ($this->session->isLoggedIn()) {
                $user = $this->getUser();
                $this->page = 'message';
                $this->assign('message', "You are logged in as " . $user->getUserID());
                $this->assign('url', $this->buildURL('logout'));
                $this->assign('linkText', 'Click here to logout.');
            }
            break;
    }
  }

}

