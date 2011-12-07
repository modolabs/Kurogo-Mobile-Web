#################
URLDataRetriever
#################

The URLDataRetriever is the most common retriever used. It retrieves data from a HTTP server
(or local file). You can either configure it with static configuration data as well implementing
methods to determine the values dynamically.

There are several values that ultimately make up the request:

* The base url - This could be an http url or a local file reference
* The query string parameters
* HTTP method - The default is GET
* Custom HTTP headers
* POST data - when using a POST method

=====================
Static Configuration
=====================

When retrieving the data from a configuration file, the URLDataretriever will set the following values,
if present:

* *BASE_URL* - The initial URL. It should include http/https as necessary or can reference a local
  file. You can use one of the directory constants such as *DATA_DIR*. It can include the query
  string
* *METHOD* - The HTTP method. By default, it will use GET
* *HEADERS* - An array of headers. Each header should be expressed as: HEADERS[header] = "value". Since headers are defined  
  in this fashion as arrays, they can only be used in PHP 5.3 (PHP 5.2 does not support array syntax in ini files)
* *DATA* - Data to be sent if using POST. 

==============
Dynamic Values
==============

In some cases the URL cannot be determined until runtime. This is because it relies on data input
from the user or other information. Subclasses of URLDataRetriever have the opportunity to 
set these values at a variety of times depending on when it is appropriate.

----------------
Internal Methods
----------------

These methods could be called in the *init*, *setOption* or *initRequest* methods to set the values.

* *setBaseURL($url, $resetParameters=true)* - Sets the url. If $resetParameteters is true (the default) then
  the array of parameters will be reset. This value can include the query string, but it is typically better
  to use addParameter to ensure values are escaped properly.
* *addParameter($var, $value)* - Adds a value to the query string.
* *setMethod($method)*  - Sets the HTTP method (GET, POST)
* *setHeader($header, $value)* - Sets an HTTP header
* *setData($data)* - Sets the data used in a POST request

----------------
Callback methods
----------------

There are a variety of methods that are called when the request is prepared. You can subclass
these methods to return your own values. 

* *initRequest* - This method is called before the request is made. This is an opportunity to 
  set the various values using the above internal methods based on the current options and 
  settings. You should call parent::initRequest(). No return value is necessary


