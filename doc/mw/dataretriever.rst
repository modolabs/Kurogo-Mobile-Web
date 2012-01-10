##############
Data Retriever
##############

The Data Retriever class actually retrieves the data from the remote service. It accepts a
series of initialization arguments and contacts the service using the appropriate protocol.
Most custom DataRetrievers will be a subclass of one of the standard retrievers:

* *URLDataRetriever* - Retrieves data using URL or a local file. *OAuthDataRetriever* is a 
  standard subclass that using OAuth to sign the request.
* *SOAPDataRetriever* - Retrieves data using a SOAP request using a WSDL file, method and parameters.
* *DatabaseDataRetriever* - Retrieves data using a SQL query. 

The DataRetriever class also handles caching and sending the response data to the :doc:`parser <dataparser>` for 
processing. 

Developers would need to write a custom retriever if the service does not use a fixed URL, method (SOAP) or
SQL query (database). 

==============
Public Methods
==============

There are 2 critical methods that are used by any interface to the data retriever:

* *getData(&$response)* - This method returns the parsed data. The *response* should also
  be set in the response variable. This method will retrieve the data and response from 
  the cache if available.
* *getResponse()* - Retrieves the data and returns just the DataResponse object.
* *setOption($var, $value)* - Sets an option on the retriever. 
  
These methods generally don't need to be overridden if you are using one of the standard
subclasses.

================
Internal Methods
================

* *retrieveResponse()* - This method should return a DataResponse object. If you are not using one of
  the standard subclasses, then you must implement this method to actually retrieve the data.
* *setContext($var, $value)* - This value is passed to the DataParser. It can be used to set
  options that affect the parsing of the data, as well as pass important state information. 
* *initResponse* - Call this method if you are implementing your own *retrieveResponse()* method. 
  It will return a initialized response object ready for use.

================
Callback Methods
================


==================
Special Properties
==================

There are several properties that can affect the behavior of all retrievers:

* *DEFAULT_RESPONSE_CLASS* - The subclass of DataResponse that is used for the response.
* *DEFAULT_PARSER_CLASS* - The default parser that that will be used if the *PARSER_CLASS* initialization value is not set.
* *PARSER_INTERFACE* - The required interface for a DataParser. Some retrievers require their parsers to conform to a particular interface.
* *DEFAULT_CACHE_LIFETIME* - The default cache timeout if the *CACHE_LIFETIME* initialization value is not set.

=====================
Initialization Values
=====================

Retrievers typically are sent the values of a configuration file. There are several values that
used by all retrievers to configure the behavior of a particular instance:

* *PARSER_CLASS* - Sets the data parser class to be used.
* *AUTHORITY* - Sets the :doc:`authentication authority <authentication>` to be used. This is useful if your retriever requires authentication information 
* *OPTIONS* - Can set a series of options to the retriever. Options and their values are specific to the retriever. Since options are defined  
  in this fashion as arrays, they can only be used in PHP 5.3 (PHP 5.2 does not support array syntax in ini files)

Certain subclasses have additional configuration values.

===================
Writing A Retriever
===================

For most cases, you want to subclass an existing standard retriever type (URL, SOAP, Database). But
if you want to create your own, then you must implement *retrieveResponse* which should return 
a Data Response object. You should consult the documentation for the module to learn what
options are set and what additional interfaces need to be supported.

.. code-block:: php

    <?php

    class MyDataRetriever extends DataRetriever
    {
        protected function init($args) {
            parent::init($args); // you MUST call parent::init()
        
            /* handle a certain config value */    
            if (isset($args['SOME_CONFIG_VALUE'])) {
                $this->doSomething();
            }
            
        }
        
        protected function retrieveResponse() {
            $response = $this->initResponse();
            
            // do the work to actually get the data
            $data = doSomethingToGetData();
            
            $response->setResponse($data);
            
            return $response;
        }
    }

This is an example for a generic retriever. Subclasses of standard retrievers should be implemented
differently. Consult the documentation for each common subclass for more information.

=================
Common Subclasses
=================

.. toctree::
   :maxdepth: 1

   urldataretriever
   soapdataretriever
   databasedataretriever

