########
Core API
########

The Core API is the first API a native app should contact upon launch.  It 
provides the list of modules available on the server and their availability 
status.  It is the only module that is accessed via REST endpoint without a
module id:

:kbd:`http[s]://<host>/rest/hello?`

=======
hello
=======

:kbd:`/rest/hello?`

The *hello* command is used to establish the initial connection between the
server and client app.

The *response* parameter of the Core API module has the following form: ::

    {
        "version":"1.3",
        "modules":[
            {
                "id":"about",
                "tag":"about",
                "title":"About",
                "access":true,
                "vmin":1,
                "vmax":1,
                "home":false
            },
            {
                "id":"video",
                "tag":"video",
                "title":"Video",
                "access":true,
                "vmin":1,
                "vmax":1,
                "home":{"type":"primary","order":3}
            }
            // ...
        ]
    }

where

* *version* is the version of the current Kurogo Mobile Web installation
* *modules* is the list of modules available in this installation; each module has the parameters

  * *id*: the unique identifier that indicates what kind of module this is (not
    necessarily unique)
  * *tag*: the module id to put in the URL when requesting data from this module
  * *title*: the module's display title
  * *access*: whether the user is currently authorized to access this module.  If the module
    requires authentication, for example, this parameter may change from false to true after
    the user is authenticated via the login module (implying the Core module can be requested
    multiple times in the same session).
  * *vmin*: minimum version of the REST API supported by the server for this module
  * *vmax*: maximum version of the REST API supported by the server for this module
  * *home*: whether the module is located on the mobile web home screen. the *type* parameter
    is either primary or secondary, and the order is a 0 based number for its order on the
    home screen. If home is false then it does not appear on the home screen.





