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
Typically the DataController is the public interface to a web service. 

This class is discussed in further depth in :doc:`datacontroller`

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

* *DOMDataParser* - Parses HTML content. Will return a DOMDocument object.
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
