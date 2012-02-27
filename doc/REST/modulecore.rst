########
Core API
########

The Core API is the first API a native app should contact upon launch.  It 
provides the list of modules available on the server and their availability 
status.  It is the only module that is accessed via REST endpoint without a
module id:

=======
hello
=======

:kbd:`http[s]://<host>/rest/hello?`

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
                "payload":null,
                "vmin":1,
                "vmax":1,
                "home":false
            },
            {
                "id":"video",
                "tag":"video",
                "title":"Video",
                "access":true,
                "payload":null,
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
  * *payload*: A module defined value send with the request. Not used by all modules.
  * *vmin*: minimum version of the REST API supported by the server for this module
  * *vmax*: maximum version of the REST API supported by the server for this module
  * *home*: whether the module is located on the mobile web home screen. the *type* parameter
    is either primary or secondary, and the order is a 0 based number for its order on the
    home screen. If home is false then it does not appear on the home screen.

========
classify
========

:kbd:`http[s]://<host>/rest/classify?useragent=<client user agent>`

The *classify* command is used by web servers to determine if they should forward a 
particular client browser to the Kurogo Mobile Web.  This command uses the 
Kurogo Framework to query the device detection service using the *user agent* 
of the mobile client's browser provided via the *useragent* parameter. 
The service will then return a series of properties based on the device:

Parameters

* *useragent* - the client browser's user agent.


Sample *response* ::

    {
        "mobile":true,
        "pagetype":"compliant",
        "platform":"iphone"
    }

where

* *mobile* - Boolean.  Whether or not the user agent corresponds to a mobile device supported 
  by the Kurogo Mobile Web installation.  Whether or not this returns true or false for tablet 
  devices is determined by the value of the *TABLET_ENABLED* site configuration option.  Web 
  servers should only forward clients to the Kurogo Mobile Web if this is true.
* *pagetype* - String. One of the device *buckets* that determines which major source of HTML the device
  will received. Values include *basic*, *touch*, *compliant* and *tablet*.  This is provided so 
  that web servers can provide custom forwarding behavior for a specific device bucket (e.g. displaying 
  a "Check out our mobile optimized website" link specifically for tablet devices).
* *platform* - String. The specific type of device. Values include *android*, *bbplus*, *blackberry*, 
  *computer*, *featurephone*, *iphone*, *ipad*, *palmos*, *spider*, *symbian*, *webos*, *winmo*, 
  *winphone7*.  This is provided so that web servers can provide custom forwarding behavior for a 
  specific platform.
