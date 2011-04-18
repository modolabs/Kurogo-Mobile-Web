<?php
/**
  * @package Authentication
  */

/**
  * @package Authentication
  */
class GoogleAppsAuthentication extends GoogleAuthentication
{
    protected $userClass='GoogleAppsUser';
    protected $domain;

    public function getDomain() {
        return $this->domain;
    }
    
    protected function reset($hard=false)
    {
        parent::reset($hard);
        if ($hard) {
            // this where we would log out of google apps
        }
    }

    public function getUser($login) {

        if (empty($login)) {
            return new AnonymousUser();       
        }
        
        /* right now there is no way to validate a user. We'll be looking into this */
        //if the login is an email, trim off the domain part        
        if (preg_match('/^(.*?)@'. $this->domain . '$/', $login, $bits)) {        

            $user = new GoogleAppsUser($this);
            $user->setUserID($login);
            $user->setEmail($login);
            $user->setFullname($login);
            return $user;
        }
        
        return false;
    }
    
    protected function getAuthURL(array $params) {
        $url = $this->authorizeTokenURL;
        $parameters = array(
            'oauth_token'=>$this->token,
            'hd'=>$this->domain
        );

        if (!Kurogo::deviceClassifier()->isComputer()) {
            $parameters['btmpl'] ='mobile';
        }
        
	    $url .= stripos($url, "?") ? '&' : '?';
        $url .= http_build_query($parameters);
        return $url;
    }
    
    public function init($args) {
        parent::init($args);
        $args = is_array($args) ? $args : array();

        if (!isset($args['GOOGLEAPPS_DOMAIN']) || strlen($args['GOOGLEAPPS_DOMAIN'])==0) {
            throw new Exception("Google Apps Domain not set");
        }

        $this->domain = $args['GOOGLEAPPS_DOMAIN'];
    }
}

/**
  * @package Authentication
  */
class GoogleAppsUser extends GoogleUser
{
    public function getDomain() {
        return $this->AuthenticationAuthority->getDomain();
    }
}
