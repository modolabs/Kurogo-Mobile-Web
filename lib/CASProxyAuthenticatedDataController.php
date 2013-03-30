<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

abstract class CASProxyAuthenticatedDataController extends DataController
{

    /**
     * Retrieves the data using the given url. The default implementation uses the file_get_content()
     * function to retrieve the request. Subclasses would need to implement this if a simple GET request
     * is not sufficient (i.e. you need POST or custom headers). 
     * @param string the url to retrieve
     * @return string the response from the server
     * @TODO support POST requests and custom headers and perhaps proxy requests
     */
    protected function retrieveData($url) {
        if ($this->debugMode) {
            error_log(sprintf(__CLASS__ . " Retrieving %s", $url));
        }
        
        try {
            if ($this->method == 'GET')
                $http = phpCAS::getProxiedService(PHPCAS_PROXIED_SERVICE_HTTP_GET);
            else if ($this->method = 'POST')
                $http = phpCAS::getProxiedService(PHPCAS_PROXIED_SERVICE_HTTP_POST);
            else
                throw new Exception('Unsupported HTTP method '.$this->method);
            
            $http->setUrl($url);
            // Not yet supported in phpCAS-1.2.2, will be added in a future version.
    //      foreach ($this->getHeaders() as $header) {
    //          $http->addRequestHeader($header);
    //      }
            
            $http->send();
    
            $this->response = DataResponse::factory('HTTPDataResponse', array());
            $this->response->setRequest($this->method, $url, $this->filters, $this->requestHeaders);
            $this->response->setResponse($http->getResponseBody(), $http->getResponseHeaders());
            
            if ($this->debugMode) {
                error_log(sprintf(__CLASS__ . " Returned status %d and %d bytes", $this->getResponseCode(), strlen($data)));
            }
            
            return $http->getResponseBody();
        }
        // The proxy ticket is no longer valid, the user will need to log out and log back in.
        catch (CAS_ProxyTicketException $e) {
            if ($this->debugMode) {
                error_log(__CLASS__ . " The user's proxy ticket expired, prompt for login.");
            }
            
            // For now we will just re-throw the exception and let the WebModule that
            // is calling us handle prompting for re-authentication.
            throw $e;
        }
    }
    
    /**
     * Returns a base filename for the cache file that will be used. The default implementation uses
     * a hash of the value returned from the url
     * @return string
     */
    protected function cacheFilename($url = null) {
        $url = $url ? $url : $this->url();
        
        // Add the user's id to the cache-key for a per-user cache.
        $session = Kurogo::getSession();
        $user = $session->getUser();
        
        return md5($url.$user->getUserID());
    }

}
