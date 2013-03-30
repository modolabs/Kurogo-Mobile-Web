<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class GoogleOAuthProvider extends KurogoOAuthProvider
{
    protected $scope = array();
    protected $realm;
    protected $requireLogin=-1;
    protected $requestTokenURL='https://www.google.com/accounts/OAuthGetRequestToken';
    protected $accessTokenURL='https://www.google.com/accounts/OAuthGetAccessToken';

    protected function getOpenIDNameSpace($uri, $var) {
        if ($key = array_search($uri, $var)) {
            if (preg_match("/^openid_ns_(.*)$/", $key, $ns)) {
                return $ns[1];
            }
        }
        
        return false;
    }    
    
    protected function getOpenIDValue($value, $ns, $var) {
        return isset($var['openid_' . $ns . '_' . $value]) ? $var['openid_' . $ns . '_' . $value] : false;
    }    

    public function auth($options, &$userArray) {
        if (isset($options['startOver']) && $options['startOver']) {
            $this->reset();
        }
        
        if (isset($_REQUEST['openid_mode'])) {
            if (isset($_REQUEST['openid_identity'])) {
            
                if ($ns = $this->getOpenIDNamespace('http://specs.openid.net/extensions/oauth/1.0', $_REQUEST)) {
                    if ($request_token = $this->getOpenIDValue('request_token', $ns, $_REQUEST)) {
                        $this->setToken(KurogoOAuthProvider::TOKEN_TYPE_REQUEST, $request_token);
                        if (!$this->getAccessToken($options)) {
                            throw new KurogoDataServerException("Error getting OAuth Access token");
                        }
                    }
                }

                $userArray = $_REQUEST;
                return AUTH_OK;
            } else {
                Kurogo::log(LOG_WARNING,"openid_identity not found",'auth');
                return AUTH_FAILED;
            }
        } else {
        
            //redirect to auth page
            $url = $this->getAuthURL($options);
            Kurogo::redirectToURL($url);
        }
    }

    public function getAuthURL(array $params) {
    
        if (!$url = $this->getOpenIDEndpoint()) {
            throw new KurogoDataServerException("Unable to get Google OpenID endpoint.");
        }
        
        $url_parts = parse_url(FULL_URL_BASE);
        
        if (!isset($params['return_url']) || empty($params['return_url'])) {
            throw new KurogoConfigurationException("Return url not specified");
        }
        $return_url = $params['return_url'];
        
        if ($this->realm) {
            $realm = $this->realm;
            if (stripos($realm, $url_parts['host'])===false) {
                throw new KurogoConfigurationException("OpenID Realm $this->realm must match server name " . $url_parts['host']);
            }
            
        } else {
            $realm = sprintf("%s://%s", $url_parts['scheme'], $url_parts['host']);
            if (!in_array($_SERVER['SERVER_PORT'], array(80,443))) {
                $realm .= ":" . $_SERVER['SERVER_PORT'];
            }
        }
        
        $parameters = array(
            'openid.mode'=>'checkid_setup',
            'openid.ns'=>'http://specs.openid.net/auth/2.0',
            'openid.claimed_id'=>'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.identity'=>'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.return_to'=>$return_url,
            'openid.realm'=>$realm,
            'openid.ns.ax'=>'http://openid.net/srv/ax/1.0',
            'openid.ax.mode'=>'fetch_request',
            'openid.ax.required'=>'email,firstname,lastname',
            'openid.ax.type.email'=>'http://schema.openid.net/contact/email',
            'openid.ax.type.firstname'=>'http://axschema.org/namePerson/first',
            'openid.ax.type.lastname'=>'http://axschema.org/namePerson/last'
        );
        
        if ($this->requireLogin >= 0) {
            $parameters = array_merge($parameters, array(
                'openid.ns.pape'=>'http://specs.openid.net/extensions/pape/1.0',
                'openid.pape.max_auth_age'=>$this->requireLogin
            ));
        }
        
        if ($this->consumerKey) {
            if ($realm != ($url_parts['scheme'].'://'.$this->consumerKey)) {
                throw new KurogoConfigurationException("Google OpenID + OAuth will only work if the realm ($realm) and consumer key ($this->consumerKey) are the same");
            }
            
            $parameters = array_merge($parameters, array(
                'openid.ns.oauth'=>'http://specs.openid.net/extensions/oauth/1.0',
                'openid.oauth.consumer'=>$this->consumerKey,
                'openid.oauth.scope'=>implode(" ", $this->scope)
            ));
        }
                
	    $url .= stripos($url, "?") ? '&' : '?';
        $url .= http_build_query($parameters);

        return $url;
    }

    protected function init($args) {
        parent::init($args);

        if (isset($args['GOOGLE_REQUIRE_LOGIN'])) {
            $this->requireLogin = $args['GOOGLE_REQUIRE_LOGIN'];
        }

        if (isset($args['OPENID_REALM'])) {
            if (!preg_match("@^https?://@", $args['OPENID_REALM'])) {
                throw new KurogoConfigurationException("Invalid OpenID realm {$args['OPENID_REALM']}. Realm must be a full url");
            }

            $this->realm = $args['OPENID_REALM'];
        }

        if (isset($args['OAUTH_CONSUMER_KEY'], $args['OAUTH_CONSUMER_SECRET'])) {
            if (!isset($args['GOOGLE_SCOPE'])) {
                throw new KurogoConfigurationException("GOOGLE_SCOPE parameter must be specified");
            } elseif (!is_array($args['GOOGLE_SCOPE'])) {
                throw new KurogoConfigurationException("GOOGLE_SCOPE parameter is not an array");
            } 
            
            $this->scope = $args['GOOGLE_SCOPE'];
        }
    }

    protected function getOpenIDEndpoint() {

        $url = "https://www.google.com/accounts/o8/id";
        if ($xrds = file_get_contents($url)) {
            if (preg_match("#<URI>(.*?)</URI>#", $xrds, $matches)) {
                return $matches[1];
            }
        }

        return false;
    }
    


}
