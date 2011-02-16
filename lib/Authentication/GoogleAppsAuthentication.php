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

        //if the login is an email, trim off the domain part        
        if (preg_match('/^(.*?)@'. $this->domain . '$/', $login, $bits)) {        
            $login = $bits[1];
        }
        
        $url = 'https://apps-apis.google.com/a/feeds/'.$this->domain .'/user/2.0/' . $login;
        $parameters = array(
            'alt'=>'json'
        );
        
        $cacheFilename = "user_$login";
        if ($this->cache === NULL) {
              $this->cache = new DiskCache(CACHE_DIR . "/" . $this->domain, $this->cacheLifetime, TRUE);
              $this->cache->setSuffix('.json');
              $this->cache->preserveFormat();
        }

        if ($this->cache->isFresh($cacheFilename)) {
            $data = $this->cache->read($cacheFilename);
        } else {
            //cache isn't fresh, load the data
            if ($data = $this->oauthRequest('GET', $url, $parameters)) {
                $this->cache->write($data, $cacheFilename);
            }
        }
        
		// make the call
		if ($data) {
            $json = @json_decode($data, true);
            $user = new GoogleAppsUser($this);
            if ($user->setVars($json)) {
                return $user;
            }
        }

        return false;
    }
    
    protected function getRequestTokenParameters() {
        $parameters = array(
            'scope'=>implode(' ', array(
                'http://www.google.com/calendar/feeds',
                'http://apps-apis.google.com/a/feeds/',
                'https://www.googleapis.com/auth/userinfo#email'
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
                $this->setUserID($data['entry']['apps$login']['userName']);                
                $this->setEmail($data['entry']['apps$login']['userName'] . '@' . $this->getDomain());
                $this->setAdmin($data['entry']['apps$login']['admin']);
            }

            return $this->getUserID();
        }
    }    
}
