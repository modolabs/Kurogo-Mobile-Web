.. _apimodule:

####################
The APIModule Object
####################
  
APIModule provides the logic to initialize and render the JSON contents.

===========
Properties
===========

Values the module developer should set in the class declaration:

* *id* (string) - This property should be set to the same name and 
  capitalization as the module directory. This property **must** be set by all 
  modules.
* *responseVersion* - The version of the API output that will be returned.
  This **may** be set to a different value depending on the *command*.
* *vmin* - The minimum API version supported by the current implementation.
* *vmax* - The maximum API version supported by the current implementation.
* *response* - The payload to return to the client for their request. This will
  will be converted into JSON by the superclass.

Values set by the parent class:

* *command* (string) - This property is set when the module initializes and 
  represents the command :doc:`requested <requests>`. 
* *requestedVersion* - The API version requested by the client.
* *requestedVmin* - The minimum API version requested by the client, if the
  client is able to handle lower API versions.

===============
Methods to Use
===============

-------
Output
-------

* *setResponse($response)* - Sets the *response* property of this object.
* *setResponseVersion($version)* - Sets the *responseVersion* property of the
  object.
* *setResponseError(KurogoError $error)* - Sets the response's error field if 
  there an error or exceptional condition occurs anytime during the data
  handling and output process (for example, if an LDAP search runs over the 
  maximum number of results that can be returned) that the client should know 
  about.

-------
Errors
-------

* *throwError(KurogoError $error)* - If an error occurs such that no useful
  data can be returned to the client, calling this method will halt the usual
  process of setting the *response* property and immediately output the error
  message to the client.
* *invalidCommand()* - Call this method if the client requests a command that
  cannot be handled. This will result in a *throwError()* call with a standard
  message.

-------------
Configuration
-------------

* *getAPIConfigData($name)* - Returns an array from a config file named
  *page-{name}.ini* located in the *config/MODULEID/* folder.

===================
Methods to override
===================

* *initializeForCommand($command)* - This method represents the module's main
  logic when constructing the response. It must be overridden by each module.

=================
The CoreAPIModule
=================

The CoreAPIModule is a special subclass of APIModule that lives in *lib* rather
than the *app* directory, and returns metadata about each of the enabled 
APIModules.

