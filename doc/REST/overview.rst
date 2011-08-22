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

:kbd:`http://localhost:8888/calendar/detail?id=12345&type=static&calendar=myCalendar`

The information for the event, in JSON, could be accessed at

:kbd:`http://localhost:8888/rest/calendar/detail?id=12345&type=static&calendar=myCalendar`

For all modules except the Core, the general syntax of a REST API endpoint
is

:kbd:`http[s]://<host>/<rest-extension>/<module-id>/<command>?<parameters>`

In the rest of this documentation, we will assume $rest$ as the REST extension

==============
Output
==============

All output from the /rest extension is currently in JSON format, following 
this structure: ::

    {
        "id":<id>,
        "tag":<tag>,
        "command":<command>,
        "version":<version>,
        "error":<error>,
        "response": <response>,
        "context":<context>
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






