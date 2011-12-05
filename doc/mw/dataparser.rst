##############
Data Parser
##############

The Data Parser class is responsible for parsing the server response into a common format.
This format is determined by the module and typically includes returning objects that conform
to a particular interface. 

There are several standard parsers included to handle common data types:

* *INIFileParser* - Parses .ini files into sections and key/value pairs
* *JSONDataParser* - Parses JSON data. Typically you would extend this to convert the data into objects.
* *PassthroughDataParser* - Does not parse the data. Useful in situations where the data needs to be returned as is.
* *RSSDataParser* - Parses RSS data into individual RSS items
* *XMLDataParser* - Parses XML data. This class needs to be extended to handle the elements appropriately.

====================
Methods to Implement
====================

* *parseData($data)* - This method is called to actually parse the data from the request. Note
  that for some retrievers (i.e. DatabaseRetrievers or LDAPRetrievers) the value will actually be a resource rather
  than a string. It should return the data parsed into it's appropriate value. The correct
  value type depends on the underlying data model. Typically this would be an object or an array
  of objects.

================
Internal Methods
================

* *setTotalItems($total)* - This should be called if the service provides a field to indicate the total 
  number of items in the request (and the number of items actually returned has been limited). This
  assists with paging.
* *getContext($val)* - Retrieves a context value that was set by the retriever to assist with
  parsing the data. Examples include resource variables and state information.

==================
Special Properties
==================

To customize the behavior of the parser, you can override certain properties:

* *parseMode* - should be one of the class constants:

  * DataParser::PARSE_MODE_RESPONSE (default) - this parser will parse a DataResponse object. The DataParser class implements
    the parseResponse method that will call the parseData method to parse the contents of the response
  * DataParser::PARSE_MODE_FILE -  this parser expects to receive a file name that points to a file to parse. This is useful
    for parsers that utilize functions that act on files rather than strings.
  

=====================
Initialization Values
=====================

Retrievers typically are sent the values of a configuration file. There are several values that
used by all retrievers to configure the behavior of a particular instance:

* *HALT_ON_PARSE_ERRORS* - If false then the parser should catch any exceptions while parsing and continue.

Certain subclasses have additional configuration values.

===================
Writing A Parser
===================


.. code-block:: php

    <?php

    class MyDataParser extends DataParser
    {
        
        protected function parseData($data) {
            
            // Take the data and parse it into objects or an array of objects
                    
            return $parsedData;
        }
    }


