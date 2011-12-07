#################
SOAPDataRetriever
#################

The SOAPDataRetriever allows you to easily retrieve data from a SOAP web service. This retriever
uses a subclass of the built-in PHP SOAPClient class. You can 
either configure it with static configuration data as well implementing methods to determine the values dynamically.

There are several values that ultimately make up the request:

* The base url. This typically comes from a WSDL file, but can be set manually
* The target URI. This value typically comes from a WSDL file but can be set manually.
* The SOAP Method
* The parameters for the request. The format of these parameters is dependent on the SOAP method being called

=====================
Static Configuration
=====================

When retrieving the data from a configuration file, the SOAPDataretriever will set the following values,
if present:

* *WSDL* - The location (URL or local location) of the WSDL file. This is typically the best way to initialize the SOAP service.

The following values are only valid if you are not using a WSDL file:

* *BASE_URL* - The HTTP endpoint of this service. This is only necessary when not providing a WSDL location.
* *URI* - The target URI. This is only necessary when not providing a WSDL location.

SOAP Actions

* *METHOD* - The SOAP method to call
* *PARAMETERS* - An array of parameters to send. Note that if the SOAP method requires a complex value
  for any of the paramaters, you cannot define this in configuration since it cannot be syntactically expressed in the INI file


==============
Dynamic Values
==============

In many cases the method cannot be determined until runtime. This is because it relies on data input
from the user or other information. Subclasses of SOAPDataRetriever have the opportunity to 
set these values at a variety of times depending on when it is appropriate.

----------------
Internal Methods
----------------

These methods could be called in the *init*, *setOption* or *initRequest* methods to set the values.

* *setMethod($method)* - the SOAP method to be called
* *setParameters($parameters)* - an array of parameters to be sent with the request. Note that some
  services use a single parameter that is a complex value (i.e. a single element array whose sole value is
  an array of values), and some services use an array of parameters (a multi-element array of single values). 

----------------
Callback methods
----------------

There are a variety of methods that are called when the request is prepared. You can subclass
these methods to return your own values. 

* *initRequest* - This method is called before the request is made. This is an opportunity to 
  set the various values using the above internal methods based on the current options and 
  settings. You should call parent::initRequest(). No return value is necessary


