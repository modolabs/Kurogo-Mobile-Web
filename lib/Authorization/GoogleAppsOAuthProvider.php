<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class GoogleAppsOAuthProvider extends GoogleOAuthProvider
{
    protected $domain;
   /**
     * Discovers the OpenID Endpoint for a Google Apps domain
     * http://groups.google.com/group/google-federated-login-api/web/openid-discovery-for-hosted-domains?pli=1
	 * @return string, a url for the OpenID endpoint (i.e the login page)
     */
    protected function getOpenIDEndpoint() {
        $url = "https://www.google.com/accounts/o8/.well-known/host-meta?hd=" . $this->domain;
        if ($host_meta = file_get_contents($url)) {
            if (preg_match("/Link: <(.*?)>/", $host_meta, $matches)) {
                $url = $matches[1];
                if ($xrds = file_get_contents($url)) {
                    if (preg_match("#<URI>(.*?)</URI>#", $xrds, $matches)) {
                        return $matches[1];
                    }
                }
            }
        }
        
        return false;
    }

    public function init($args) {
        parent::init($args);
        $args = is_array($args) ? $args : array();

        if (!isset($args['GOOGLEAPPS_DOMAIN']) || strlen($args['GOOGLEAPPS_DOMAIN'])==0) {
            throw new KurogoConfigurationException("Google Apps Domain not set");
        }

        $this->domain = $args['GOOGLEAPPS_DOMAIN'];
    }
}
