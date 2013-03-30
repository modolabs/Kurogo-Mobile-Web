<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class TwitterOAuthProvider extends KurogoOAuthProvider
{
    protected $requestTokenURL = 'https://api.twitter.com/oauth/request_token';
    protected $accessTokenURL = 'https://api.twitter.com/oauth/access_token';

    protected function init($args) {
        parent::init($args);
        if (!isset($args['OAUTH_CONSUMER_KEY'], $args['OAUTH_CONSUMER_SECRET']) || 
            strlen($args['OAUTH_CONSUMER_KEY'])==0 || strlen($args['OAUTH_CONSUMER_SECRET'])==0) {
            throw new KurogoConfigurationException("Twitter Consumer key and secret not set");
        }
    }

    protected function getAuthURL(array $options) {

        $url = "https://api.twitter.com/oauth/authenticate?" . http_build_query(array(
            'oauth_token'=>$this->getToken(self::TOKEN_TYPE_REQUEST)
            )
        );
        return $url;
    }
    
}
