##################
Data Controller
##################

One of the most important classes to understand in Kurogo is the DataController class. This powerful
class is designed to abstract the process of gathering data from a web service. Controllers should
be designed to provide an interface to the data in a data centric way rather than a service centric 
way. 


==========
Usage
==========
   
Using the existing data controllers in modules is very straight forward. 
   
-------------
Instantiation
-------------

The controller is instantiated using the *DataController::factory($id, $args)* method. The $id parameter
is a string representing the subclass to instantiate, the $args parameter is an array of options::

    <?php
    
    $controller = DataController::factory('MyDataController', $args);

Typically the args will come from a :doc:`configuration <configuration>` file. 

After instantiation, the DataController class will call the *init($args)* method. This creates a 
DataParser and sets the properties of the controller based on the options. There are several 
common options you can include (some may be required by the class you are creating):

* *PARSER_CASS* - The class name of the parser to use. If not present it will use the value of the 
  Controller class' DEFAULT_PARSER_CLASS property.
* *BASE_URL* - Sets the base url of the request
* *TITLE* - Sets the title of the controller
* *CACHE_LIFETIME* - Sets the cache lifetime, in seconds
* *HTTP_PROXY_URL* - Use an HTTP proxy for the request. Should be in `URI <http://en.wikipedia.org/wiki/URI_scheme#Examples>`_ format. I.e: *tcp://user:password@proxy.example.com:port*

These options can also be defined in the *[data_controller]* section of *SITE_DIR/config/site.ini* any
options specified here will be used by all connections (unless they are overridden in their specific configuration)
This would be a good place to include a global HTTP proxy.

Each subclass can define its own set of options and handle those in its *init* method. Just make sure
to call *parent::init($args)* first. These arguments are also sent to the *init* method of the 
controllers's DataParser class. Subclasses might also set default filters that should be sent in all
requests.

----------------------
Setting the properties
----------------------
  
Set appropriate parameters for the request. This might include:

* Setting a range of dates to return for a calendar controller
* Setting a category of items to return

Each controller has a specific interface of methods to manage the filters for the request. There is
an internal method *addFilter($var, $value)* that will add parameters to the url that will be sent
to the service. Modules should generally not set these filters, but instead use logical methods
to abstract the options being set.

--------------------
Retrieving the Items
--------------------

Once the properties have been set, you can request the items that meet the request using the *items($start ,$limit)*
method. The *$start* parameter is a 0-indexed integer to represent which item in the sequence to start.
*$limit* represents how many entries to return. If those parameters are omitted, it will return all
items present in the request.


===========
Subclassing 
===========

You may wish to override the default value of several properties:

* $DEFAULT_PARSER_CLASS to the name of the default parser class you wish to use. Should be subclass of DataParser
* $cacheFolder - the name of the folder within the CACHE_DIR where downloaded files will be cached

There are several methods that you should be familiar with to use this class appropriately:

* *addFilter($filter,$value)* / *removeFilter($filter)* - Maintains a internal array of key/value filters that your controller can
  use to generate a filtered result set. The default implementation uses these filters as parameters 
  in your url request. 
* *setBaseURL($url)* - Sets the base url to use. You will have the opportunity to manipulate the url
  that gets used if you subclass the *url()* method.

---------------------
The Retrieval Process
---------------------

The data retrieval process has been abstracted in a way that allows subclasses to customize as few or 
many steps as the situation warrants.

The *items* method calls *getParsedData()*, which consists of retrieving the raw data with *getData* 
followed by calling *parseData* to generate an array of objects.

---------
Override:
---------

You can override any part of the process. Generally you want to override the most specific piece so 
you gain the benefits of caching and reduce the amount of code you need to implement.

.. image:: images/DataController.png
   :align: right

* *items($start, $limit)* if you want complete control of the data retrieval process. You will be expected to return
  an array of item objects. It is also important for you to call *setTotalItems($totalItems)* to return the number
  of items in the collection, and only return *$limit* items.
* *getParsedData()* if you need to control the number of items in a specific request. The *items*
  method will then return the correct filtered result based on the $start and $limit parameters. This
  method should return an array of objects. You will be responsible for caching.
* *getData()* if you only need to control the raw data from the service. This might be necessary
  if the nature of the data is not cacheable or if it does not use a url (i.e. it uses direct access
  PHP functions). This method should return a value (typically a string) that can be sent to the
  parseData() method of the DataParser object. You will be responsible for caching.
* *url()* if you only need to control the url that gets requested. This is necessary if the correct url
  must be determined at the point of retrieval. The default implementation combines the base url with
  the filters as query string parameters. This method should return a full URL as a string. By implementing
  this method you still benefit from caching.
* *retrieveData($url)* if the data cannot be retrieved using the PHP *file_get_contents()* function
  then you'll need to override this method. This would primarily be in cases where you cannot use a
  GET method or if specific HTTP headers must be set. You still will benefit from caching.
  
---------------------
Getting response data
---------------------

* *getResponse()* - Returns the (unparsed) response body from the request
* *getResponseHeaders()* - Returns an array of response headers after the request has been made
* *getResponseHeader($header)* - Returns a specific response header based on its name ('Content-Length', etc). This is currently case sensitive
* *getResponseStatus()* - Returns the HTTP response message ('OK', etc)
* *getResponseCode()* - Returns the most recent HTTP code from the response (200, 404, etc)

----------------
Internal methods
----------------

* *setBaseURL($url, $clearFilters=true)* - Sets the base url for the request. If $clearFilters is true
  (default) then the query string filters will be cleared. Set this parameter to false to maintain
  any filters.
* *addFilter($var, $value)* - Adds a parameter to the url query string. Currently only one value per parameter
  is supported. 
* *removeFilter($var)* - Removes a filter from the query string
* *removeAllFilters()* - Removes all filters from the query string
* *setTotalItems($total)* - This value is typically set by the parseData() method by querying the DataParser
  for the total number of items.
* *addHeader($header, $value)* - sets a header to be included in the request. 
* *setMethod($method)* - Sets the HTTP method to use in the request. Values include *GET*, *POST*, *PUT*, *DELETE*. 
  Support for other methods requires support from the remote server. The default method is *GET*
* *setTimeout($timeout)* - sets the timeout (in seconds) for the remote request. 

.. _kurogoobject_interface:

======================
KurogoObject Interface
======================

In order to present a common interface for retrieving data, the KurogoObject interface has been created.
Currently this interface contains no methods. It's presence exists to ensure that any module that
exposes a search mechanism and participates in :doc:`module interaction <moduleinteraction>` uses
objects. In the future, this interface may have required methods to promote certain object oriented
design principles. 