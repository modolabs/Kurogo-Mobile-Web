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

Module -> Data Model -> Data Retriever -> Data Parser -> Returns Objects

Data retrieval is discussed in depth in :doc:`dataretrieval`.

===============
Data Validation
===============

There are several utility methods available to perform validation of input. They are implemented
as static methods of the *Validator* object. Each return a boolean of true or false depending on
whether the value is valid for the particular 

* *isValidEmail* - returns true if the value is a valid email address
* *isValidPhone* - returns true if the value is a valid phone number (currently only works for US/Canada numbers)
* *isValidURL* - returns true if the value is a valid url
