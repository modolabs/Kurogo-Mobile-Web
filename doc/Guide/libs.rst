##################
Standard Libraries
##################

There are a number of included libraries that can provide various services. 

=================================
Remote Data Gathering and Parsing
=================================

Retrieving and parsing remote data is an important task of web applications. In order to provide a 
consistent interface, a series of abstract classes and libraries have been provided.

The hierarchy looks like this:

url -> DataController (getData/items) -> DataParser (calls parseData) -> Returns Objects

--------------
DataController
--------------

A class that handles the retrieval of data from a data source. You set a URL,
and a parser (a subclass of DataParser). The class will retrieve the data, cache it based on a 
provided cache lifetime, and then run the data through the parser to generate a PHP data structure.
Typically the DataController is the public interface to a web service. There is one method 
that all subclasses *must* provide:
  
* *getItem($id)* - Should return a single object based on its id. 

You should also override the default value of several properties:

* $DEFAULT_PARSER_CLASS to the name of the default parser class you wish to use. Should be subclass of DataParser
* $cacheFolder - the name of the folder within the CACHE_DIR where downloaded files will be cached
* $cacheFileSuffix - a suffix to use for the cached files

There are several methods that you should be familiar with to use this class appropriately:

* *addFilter($filter,$value)*/*removeFilter($filter)* - Maintains a internal array of key/value filters that your controller can
  use to generate a filtered result set
* *setBaseURL($url)* - Sets the base url to use. You will have the opportunity to manipulate the url
  that gets used if you subclass the *url()* method.
* *factory($args)* - The factory method is the public way to instantiate your class. *args*
  is an associative array of parameters used to initialize it's settings. If you override this class
  you should add any default options necessary to the $args array. If any required options aren't present,
  you should throw an exception. You *should* call parent::factory($args) at the end of your 
  implementation and then return the result. 
  
There are several other methods that can be overridden

* *url()* - Should return the complet url to use for the request. You can provide an interface to set various
  parameters that will then affect the building of the query to the web service. By default, this method
  will simply return the base url.
* *items($start=0,$limit=null, &$totalItems=0)* - should return an array of items based on the current
  settings

Currently there are 2 included DataController implementations

* *CalendarDataController* - has parameters for start and ending time and content filtering to limit
  the returned items in the feed
* *RSSDataController* - adds content filtering

----------
DataParser
----------

This class that handles the parsing of the data retrieved from a DataController. It generally uses 
just one method *parseData* which is passed a string of the data. It is the responsibility of the 
parser to return an appropriate PHP structure that can be used in the application. In some cases, it
might benefit to have layer cache the results of the parsing if this is an expensive operation that 
might be repeated often. The responsibility of caching these results is up to subclasses since implementations
needs may vary.

Currently there are 4 included DataParser implementations

* *ICSDataParser* - Parses data within an iCalendar (ICS) file. Will return an iCalendar object. (see lib/iCalendar.php)
* *JSONDataParser* - Parses the string as JSON. Will return the string decoded into its PHP equivalent data type.
* *PassthroughDataParser* - Does no processing, simply returns the string as is. This is useful when you want
  to use the data controller, but no parsing is necessary.
* *RSSDataParser* - Parses data within a RSS/Atom/RDF feed using the XML parser. Will return an array of RSSItem objects (see lib/RSS.php)


===============
Data Validation
===============

There are several utility methods available to perform validation of input. They are implemented
as static methods of the *Validator* object. Each return a boolean of true or false depending on
whether the value is valid for the particular 

* *isValidEmail* - returns true if the value is a valid email address
* *isValidPhone* - returns true if the value is a valid phone number (currently only works for US/Canada numbers)
* *isValidURL* - returns true if the value is a valid url
