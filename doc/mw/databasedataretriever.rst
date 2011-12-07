#####################
DatabaseDataRetriever
#####################

The DatabaseDataRetriever allows you to easily retrieve data from a relational database. This
will utilize the Kurogo :doc:`database <database>` classes and configuration styles to connect
and retrieve data using standard SQL queries. You can 
either configure it with static configuration data as well implementing methods to determine the values dynamically.

There are several values that ultimately make up the request:

* The connection configuration. This will either use the site database, or you can configure a different database server
* The SQL query to execute. This should be a SELECT statement
* An array of parameters. These parameters are sent to the underlying PDO database engine and used as bound parameters for the request.
  You should use bound parameters to assist against SQL injection attacks.

=====================
Static Configuration
=====================

In addition to the standard :ref:`database_config`, when retrieving the data from a configuration file, 
the DatabaseDataRetriever will set the following values, if present:

* *SQL* - The SQL query to execute. 
* *PARAMETERS* - An array of parameters to use as bound parameters. Since parameters are defined  
  in this fashion as arrays, they can only be used in PHP 5.3 (PHP 5.2 does not support array syntax in ini files)

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

* *setSQL($sql)* - Set the SQL to be called. Note that Kurogo does not provide any SQL validation, translation or
  abstraction for different database systems. This must be valid SQL for the target database engine.
* *setParameters($parameters)* - an array of parameters to be used as bound parameters (they typically replace ? in the query)
  The use of bound parameters is strongly recommended to protect against SQL injection attacks.

----------------
Callback methods
----------------

There are a variety of methods that are called when the request is prepared. You can subclass
these methods to return your own values. 

* *initRequest* - This method is called before the request is made. This is an opportunity to 
  set the various values using the above internal methods based on the current options and 
  settings. You should call parent::initRequest(). No return value is necessary


