<?php
/**
 * @package ExternalData
 */

/**
 * A generic class to handle the retrieval of external data
 * by URL
 * @package ExternalData
 */
 
class URLDataRetriever extends DataRetriever {

    protected $DEFAULT_RESPONSE_CLASS = 'HTTPDataResponse';
    protected $baseURL;
    protected $filters=array();
    protected $requestURL;
    protected $requestMethod='GET';
    protected $requestParameters=array();
    protected $requestHeaders=array();
    protected $requestData;
    protected $streamContext = null;
    
    /**
     * Sets the base url for the request. This value will be set automatically if the BASE_URL argument
     * is included in the factory method. Subclasses that have fixed URLs (i.e. web service data controllers)
     * can set this in the init() method.
     * @param string $baseURL the base url including protocol
     * @param bool clearFilters whether or not to clear the filters when setting (default is true)
     */
    public function setBaseURL($baseURL, $clearFilters=true) {
        $this->baseURL = $baseURL;
        if ($clearFilters) {
            $this->removeAllFilters();
        }
    }

    public function setURL($baseURL, $clearFilters=true) {
        $this->setBaseURL($baseURL, $clearFilters);
    }
    
    /**
     * Adds a parameter to the url request. In the subclass has not overwritten url() then it will be added to the
     * url as a query string. Note that you can only have 1 value per parameter at this time. 
     * @param string $var the parameter to add
     * @param mixed $value the value to assign. Must be a scalar value
     */
    public function addFilter($var, $value) {
        $this->filters[$var] = $value;
    }
    
    public function addParameter($var, $value) {
        $this->addFilter($var, $value);
    }
    
    public function setFilters($filters) {
        $this->filters = $filters;
    }
    
    public function setParameters($parameters) {
        $this->setFilters($parameters);
    }
    
    protected function parameters() {
        return $this->filters;
    }

    /**
     * Removes a parameter from the url request. 
     * @param string $var the parameter to remove
     */
    public function removeFilter($var) {
        if (isset($this->filters[$var])) {
            unset($this->filters[$var]);
        }
    }
    
    /**
     * Remove all parameters from the url request. 
     */
    public function removeAllFilters() {
        $this->filters = array();
    }
    
    protected function init($args) {
        parent::init($args);
        if (isset($args['BASE_URL'])) {
            $this->setBaseURL($args['BASE_URL']);
        }

        if (isset($args['METHOD'])) {
            $this->setMethod($args['METHOD']);
        }

        if (isset($args['HEADERS'])) {
            $this->setHeaders($args['HEADERS']);
        }

        if (isset($args['DATA'])) {
            $this->setData($args['DATA']);
        }
        
        $this->initStreamContext($args);
    }
    
    public function setHeaders($headers) {
        if (is_array($headers)) {
            $this->requestHeaders = $headers;
        }
    }

    public function addHeader($header, $value) {
        $this->requestHeaders[$header] = $value;
    }

    protected function headers() {
        return $this->requestHeaders;
    }
    
    protected function method() {
        return $this->requestMethod;
    }
    
    protected function setData($data) {
        $this->data = $data;
    }

    protected function data() {
        return $this->requestData;
    }
    
    
    public function setMethod($method) {
        if (!in_array($method, array('POST','GET','DELETE','PUT'))) {
            throw new KurogoConfigurationException("Invalid method $method");
        }
        
        $this->requestMethod = $method;
    }

    private function setContextMethod() {
        $method = $this->method();
        stream_context_set_option($this->streamContext, 'http', 'method', $method);
        return $method;
        
    }

    private function setContextHeaders() {
        $_headers = array();
        $headers = $this->headers();
        //@TODO: Might need to escape this
        foreach ($headers as $header=>$value) {
            $_headers[] = "$header: $value";
        }
            
        stream_context_set_option($this->streamContext, 'http', 'header', implode("\r\n", $_headers));
        return $headers;
    }
    
    private function setContextData() {
        if ($requestData = $this->data()) {
            stream_context_set_option($this->streamContext, 'http', 'content', $requestData);
        }
        return $requestData;
    }

    public function setTimeout($timeout) {
        stream_context_set_option($this->streamContext, 'http', 'timeout', $timeout);
    }
    
    protected function streamContextOpts($args) {
        $streamContextOpts = array();
        
        if (isset($args['HTTP_PROXY_URL'])) {
            $streamContextOpts['http'] = array(
                'proxy'          => $args['HTTP_PROXY_URL'], 
                'request_fulluri'=> TRUE
            );
        }
        
        if (isset($args['HTTPS_PROXY_URL'])) {
            $streamContextOpts['https'] = array(
                'proxy'          => $proxyConfigs['HTTPS_PROXY_URL'], 
                'request_fulluri'=> TRUE
            );
        }

        return $streamContextOpts;        
    }
    
    protected function initStreamContext($args) {
        $this->streamContext = stream_context_create($this->streamContextOpts($args));
    }
    
    /**
     * Returns the url to use for the request. The default implementation will take the base url and
     * append any filters/parameters as query string parameters. Subclasses can override this method 
     * if a more dynamic method of URL generation is needed.
     * @return string
     */
    protected function url() {
        $url = $this->baseURL();
        $parameters = $this->parameters();
        if (count($parameters)>0) {
            $glue = strpos($this->baseURL, '?') !== false ? '&' : '?';
            $url .= $glue . http_build_query($parameters);
        }
        
        return $url;
    }
    
    protected function baseURL() {
        return $this->baseURL;
    }
         
    /**
     * Returns a base filename for the cache file that will be used. The default implementation uses
     * a hash of the value returned from the url
     * @return string
     */
    protected function cacheKey() {
        if ($this->requestMethod == 'GET') {
            if (!$url = $this->url()) {
                throw new KurogoDataException("URL could not be determined");
            }
            return 'url_' . md5($url);
        } 
        
        //only cache GET requests
        return null;
    }
    
    protected function initRequest() {
    }
    
    /**
     * Retrieves the data using the config url. The default implementation uses the file_get_content()
     * function to retrieve the request. Subclasses would need to implement this if a simple GET request
     * is not sufficient (i.e. you need POST or custom headers). 
     * @return HTTPDataResponse a DataResponse object
     */
    protected function retrieveResponse() {
    
        $this->initRequest();
        if (!$this->requestURL = $this->url()) {
            throw new KurogoDataException("URL could not be determined");
        }
                
        $this->requestParameters = $this->parameters();
        $this->requestMethod = $this->setContextMethod();
        $this->requestHeaders = $this->setContextHeaders();
        $this->requestData = $this->setContextData();
        
        Kurogo::log(LOG_INFO, "Retrieving $this->requestURL", 'url_retriever');
        $data = file_get_contents($this->requestURL, false, $this->streamContext);
        $http_response_header = isset($http_response_header) ? $http_response_header : array();

        $response = $this->initResponse();
        $response->setRequest($this->requestMethod, $this->requestURL, $this->requestParameters, $this->requestHeaders);

        $response->setResponse($data);
        $response->setResponseHeaders($http_response_header);
        
        Kurogo::log(LOG_DEBUG, sprintf("Returned status %d and %d bytes", $response->getCode(), strlen($data)), 'url_retriever');
        if ($response->getResponseError()) {
            Kurogo::log(LOG_WARNING, sprintf("%s for %s", $response->getResponseError(), $this->requestURL), 'url_retriever');
        }
        
        return $response;
    }

	protected function buildURL($parts) {
        $scheme = (isset($parts['scheme'])) ? $parts['scheme'] : 'http';
        $port = (isset($parts['port'])) ? $parts['port'] : (($scheme == 'https') ? '443' : '80');
        $host = (isset($parts['host'])) ? $parts['host'] : '';
        $path = (isset($parts['path'])) ? $parts['path'] : '';
    
        if (($scheme == 'https' && $port != '443')
            || ($scheme == 'http' && $port != '80')) {
          $host = "$host:$port";
        }
        return "$scheme://$host$path";
	}
	
	protected function canonicalURL($url) {
        $parts = parse_url($url);
        return $this->buildURL($parts);
	}
    
}
