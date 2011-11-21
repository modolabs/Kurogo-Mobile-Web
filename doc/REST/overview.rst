#################
Overview
#################

Modules in the REST API generally parallel those in the mobile web.  A few 
exceptions include the Info module (desktop preview), which exists only in the 
mobile web, and the Core (hello) module which currently exists only in REST.

=========
Endpoints
=========

URLs in the REST API are generally similar to the mobile web, the major 
difference being that REST URLs include a path extension (typically *rest*)
that is not used by any of the mobile web modules.  For example, a mobile web 
installation may have a *calendar* event detail accessible at

:kbd:`http://localhost:8888/calendar/detail?id=12345&type=static&calendar=myCalendar&v=1`

The information for the event, in JSON, could be accessed at

:kbd:`http://localhost:8888/rest/calendar/detail?id=12345&type=static&calendar=myCalendar`

For all modules except the Core, the general syntax of a REST API endpoint
is

:kbd:`http[s]://<host>/<rest-extension>/<module-id>/<command>?<parameters>&v=1`

In the rest of this documentation, we will assume *rest* is the REST extension;
please modify your requests as necessary.

==========
Versions
==========

API versions are updated on a per-module basis as module funcitonality changes.
This means that, for example, the People module may be at version 1 at the same
time the Calendar module is at version 4.  API versions are always integers,
beginning at 1 and increasing in increments of 1.

All Kurogo modules other than the Core API must specify the range of API 
versions they currently support, by specifying a maximum and minimum supported
version.  This means a server may not skip versions.  The client must specify a
*v* parameter to request the API in a particular version.  The server will 
return output from the version specified by *v* if available.  If the *v* 
parameter is not specified, the server's response depends on implementation.  
The version that the server uses is given in the output; clients are 
responsible for checking that the returned version is supported by the client.

As of Kurogo Mobile Web 1.3, all REST APIs are at version 1.

==============
Output
==============

All output from the /rest extension is currently in JSON format, following 
this structure: ::

    {
        "id": <id>,
        "tag": <tag>,
        "command": <command>,
        "version": <version>,
        "error": <error>,
        "response": <response>,
        "context": <context>
    }

The values of the above are as follows:

* *id* - the filesystem module id.  The 
  `module id <http://modolabs.com/kurogo/guide/modules.html#properties>` is 
  the (case-sensitive) name of the filesystem directory where the module files 
  reside. This tells native apps which module to instantiate.
* *tag* - the user-facing id (via the URL) of the module.  Usually the same as 
  `configModule <http://modolabs.com/kurogo/guide/modules.html#properties>` in 
  the mobile web configuration.  Each $tag$ is unique and associated with a 
  single instantiation of a module, whereas there may be multiple modules 
  instantiated with the same $id$.
* *command* - this echoes back the command requested by the client.
* *version* - an integer specifying the version of the response associated 
  with the current module.
* *response* - the response body.
* *context* -

Each module independently determines the contents of *version*, *response*, 
and *context*.

=============
Errors
=============

In the JSON output returned, the "error" parameter is usually null.  However,
if the server receives an unusable request, there something wrong with the data
feed, or there is some other problem that makes the server unable to return the
response requested by the client, the "error" value contains an object as 
follows: ::

    {
        "code": <code>
        "title": <title>
        "message": <message>
    }
        
* *code* is an integer referring to a Kurogo-defined error code.  There is not
  currently a specification for Kurogo REST API error codes, so it is not safe
  to rely on this parameter.
* *title* is a short description of the error (e.g. "Data Server Unavailable").
* *message* is the full text of the error.

.. _rest-api-conventions:

=====================
REST API Conventions
=====================

When a list of items is returned, the field *title* is conventionally used as 
the name of the primary field that should be displayed to the user.  Some API
output will use a *displayField* parameter beside the list if a different field
name is used instead of *title*.

