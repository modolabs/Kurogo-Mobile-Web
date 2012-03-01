##############
Data Response
##############

The data response object (and its subclasses) are the objects returned by :doc:`DataRetrievers <dataretriever>`. These
objects encapsulate the request and response so information such as status codes, headers and other data can
be organized. 

As a developer you would have 2 uses for this class:

#. Extracting the contents of the response if you are writing a parser or similar class that requires handling a reponse
#. Creating a response when writing a custom retriever. Typically this is not needed if you are simply subclassing one 
   of the included retriever classes.

==============
Public Methods
==============

* *getResponse()* - Returns the string response
* *getResponseError()* - Returns any error message 
* *getCode()* - Returns a status code (In HTTPDataReponse this will be the HTTP code)