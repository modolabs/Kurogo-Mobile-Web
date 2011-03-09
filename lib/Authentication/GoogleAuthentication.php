<?php
/**
  * @package Authentication
  */

/**
  * @package Authentication
  */
class GoogleAuthentication extends OAuthAuthentication
{
    protected $userClass='GoogleUser';
    protected $requestTokenURL = 'https://www.google.com/accounts/OAuthGetRequestToken';
    protected $authorizeTokenURL = 'https://www.google.com/accounts/OAuthAuthorizeToken';
    protected $accessTokenURL = 'https://www.google.com/accounts/OAuthGetAccessToken';
    protected $useCache = true;
    protected $cache;

    protected function reset($hard=false)
    {
        parent::reset($hard);
        if ($hard) {
            // this where we would log out of google
        }
    }

    protected function getUserFromArray(array $array) {

        $url = 'https://www.googleapis.com/userinfo/email';
    
        $parameters = array(
            'alt'=>'json'
        );
        
        if (!$result = $this->oauthRequest('GET', $url, $parameters)) {
            error_log("Error getting email from $url");
            return false;
        }

        $data = json_decode($result, true);
        if (isset($data['data']['email'])) {
            return $this->getUser($data['data']['email']);
        }
        
        return false;
    }

    public function getUser($login) {

        if (empty($login)) {
            return new AnonymousUser();       
        }
        
        /* right now there is no way to validate a user. We'll be looking into this */
        $user = new $this->userClass($this);
        $user->setUserID($login);
        $user->setEmail($login);
        $user->setFullname($login);
        return $user;
    }
    
    protected function getRequestTokenParameters() {
        $parameters = array(
            'scope'=>implode(' ', array(
                'http://www.google.com/calendar/feeds',
                'http://apps-apis.google.com/a/feeds/',
                'https://www.googleapis.com/auth/userinfo#email',
                'http://www.google.com/m8/feeds/'
            ))
        );
        
        return $parameters;

    }

    protected function getAuthURL(array $params) {
        $url = $this->authorizeTokenURL;
        $parameters = array(
            'oauth_token'=>$this->token
        );

        if (!$GLOBALS['deviceClassifier']->isComputer()) {
            $parameters['btmpl'] ='mobile';
        }
        
	    $url .= stripos($url, "?") ? '&' : '?';
        $url .= http_build_query($parameters);
        return $url;
    }
    
    public function init($args) {
        parent::init($args);
        $args = is_array($args) ? $args : array();

        if (!isset($args['CONSUMER_KEY'], $args['CONSUMER_SECRET'])) {
            $args['CONSUMER_KEY']='anonymous';
            $args['CONSUMER_SECRET']='anonymous';
        }
        
        $this->consumer_key = $args['CONSUMER_KEY'];
        $this->consumer_secret = $args['CONSUMER_SECRET'];
    }
}

/**
  * @package Authentication
  */
class GoogleUser extends User
{
}
