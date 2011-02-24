<?php
/**
  * @package Authentication
  */

/**
  * @package Authentication
  */
class GoogleAppsAuthentication extends OAuthAuthentication
{
    protected $domain;
    protected $tokenSessionVar = 'googleapps_token';
    protected $tokenSecretSessionVar = 'googleapps_token_secret';
    protected $requestTokenURL = 'https://www.google.com/accounts/OAuthGetRequestToken';
    protected $authorizeTokenURL = 'https://www.google.com/accounts/OAuthAuthorizeToken';
    protected $accessTokenURL = 'https://www.google.com/accounts/OAuthGetAccessToken';
    protected $useCache = true;
    protected $cache;

    public function getDomain() {
        return $this->domain;
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
        //if the login is an email, trim off the domain part        
        if (preg_match('/^(.*?)@'. $this->domain . '$/', $login, $bits)) {        

            $user = new GoogleAppsUser($this);
            $user->setUserID($login);
            $user->setEmail($login);
            $user->setFullname($login);
            return $user;

            $login = $bits[1];
        }
        
        return false;
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
            'oauth_token'=>$this->token,
            'hd'=>$this->domain
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

        if (!isset($args['DOMAIN']) || strlen($args['DOMAIN'])==0) {
            throw new Exception("Google Apps Domain not set");
        }

        $this->domain = $args['DOMAIN'];
        
        if (isset($args['OAUTH']) && $args['OAUTH']) {
            if (!isset($args['CONSUMER_KEY'], $args['CONSUMER_SECRET'])) {
                throw new Exception("Consumer Key and secret must be set when OAuth is on");
            }
            
            $this->oauth = true;
            $this->consumer_key = $args['CONSUMER_KEY'];
            $this->consumer_secret = $args['CONSUMER_SECRET'];
        }
    }
}

/**
  * @package Authentication
  */
class GoogleAppsUser extends BasicUser
{
    protected $oauth_token;
    protected $oauth_token_secret;
    protected $admin = false;
    
    public function getDomain() {
        return $this->AuthenticationAuthority->getDomain();
    }
    
    public function __construct(GoogleAppsAuthentication $AuthenticationAuthority) {
        parent::__construct($AuthenticationAuthority);
        $this->oauth_token = $this->AuthenticationAuthority->getToken();
        $this->oauth_token_secret = $this->AuthenticationAuthority->getTokenSecret();
    }

    public function getAdmin() {
        return $this->admin;
    }
    
    private function setAdmin($admin) {
        $this->admin = $admin ? true : false;
    }
    
    public function setVars($data) {
        if (isset($data['entry'])) {
            if (isset($data['entry']['apps$name']['givenName'])) {
                $this->setFirstName($data['entry']['apps$name']['givenName']);
            } 

            if (isset($data['entry']['apps$name']['familyName'])) {
                $this->setLastName($data['entry']['apps$name']['familyName']);
            }

            if (isset($data['entry']['apps$login'])) {
                if (!isset($data['entry']['apps$login']['userName'])) {
                    error_log('$apps$login/userName not present');
                }
                $this->setUserID($data['entry']['apps$login']['userName']);                
                $this->setEmail($data['entry']['apps$login']['userName'] . '@' . $this->getDomain());
                $this->setAdmin($data['entry']['apps$login']['admin']);
            } else {
                error_log('$apps$login data not present');
            }

            return $this->getUserID();
        } else {
            error_log("Entry value not present");
        }
    }    
}
